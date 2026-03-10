<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Core\Config\Config;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Payment\Clients\StripeCheckoutClient;
use App\Modules\Payment\Repositories\PaymentEventRepository;
use RuntimeException;

final class PaymentService
{
    public const STRIPE_PROVIDER = 'stripe';

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly PaymentEventRepository $events,
        private readonly StripeCheckoutClient $stripe,
        private readonly Config $config
    ) {
    }

    public function isProviderPaymentMethod(string $paymentMethod): bool
    {
        return $paymentMethod === 'stripe_checkout';
    }

    /** @return array{redirect_url:string} */
    public function initiateProviderPayment(int $orderId): array
    {
        $order = $this->orders->findOrderById($orderId);
        if ($order === null) {
            throw new RuntimeException('Ordern kunde inte hittas för betalinitiering.');
        }

        if ((string) ($order['payment_method'] ?? '') !== 'stripe_checkout') {
            throw new RuntimeException('Ordern använder inte Stripe Checkout.');
        }

        $baseUrl = rtrim((string) $this->config->get('url', ''), '/');
        $successUrl = $baseUrl . '/checkout/payment/return?provider=stripe&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $baseUrl . '/checkout/payment/return?provider=stripe&status=cancelled&order_number=' . urlencode((string) $order['order_number']);

        $session = $this->stripe->createCheckoutSession($order, $successUrl, $cancelUrl);
        $sessionId = (string) ($session['id'] ?? '');
        $sessionStatus = (string) ($session['status'] ?? 'open');
        $reference = (string) ($session['payment_intent'] ?? '');
        $redirectUrl = (string) ($session['url'] ?? '');

        if ($sessionId === '' || $redirectUrl === '') {
            throw new RuntimeException('Stripe svarade utan session-id eller redirect-url.');
        }

        $before = (string) $order['payment_status'];
        $after = $this->mapStripeStatusToInternal((string) $sessionStatus, false);

        $this->orders->updatePaymentProviderData($orderId, [
            'payment_status' => $after,
            'payment_reference' => $reference !== '' ? $reference : null,
            'payment_provider' => self::STRIPE_PROVIDER,
            'payment_provider_reference' => $reference !== '' ? $reference : null,
            'payment_provider_session_id' => $sessionId,
            'payment_provider_status' => $sessionStatus,
            'payment_note' => 'Stripe Checkout-session skapad.',
            'payment_authorized_at' => null,
            'payment_paid_at' => null,
            'payment_failed_at' => null,
        ]);

        $this->events->create([
            'order_id' => $orderId,
            'provider' => self::STRIPE_PROVIDER,
            'event_type' => 'checkout_session_created',
            'provider_event_id' => null,
            'payment_reference' => $reference !== '' ? $reference : $sessionId,
            'payload_json' => json_encode($session, JSON_UNESCAPED_UNICODE),
            'status_before' => $before,
            'status_after' => $after,
        ]);

        if ($before !== $after) {
            $this->orders->createStatusHistory($orderId, 'payment_status', $before, $after, 'Stripe Checkout initierad.');
        }

        return ['redirect_url' => $redirectUrl];
    }

    /** @return array{order_number:string,status:string,message:string} */
    public function handleReturn(string $provider, ?string $sessionId, ?string $status, ?string $orderNumber): array
    {
        if ($provider !== self::STRIPE_PROVIDER) {
            return ['order_number' => $orderNumber ?? '', 'status' => 'failed', 'message' => 'Okänd betalprovider.'];
        }

        if (($status ?? '') === 'cancelled') {
            $order = $orderNumber !== null ? $this->orders->findOrderByNumber($orderNumber) : null;
            if ($order !== null) {
                $this->applyMappedStatus($order, 'expired', null, 'return_received', 'Stripe return: payment cancelled by customer.');
                return ['order_number' => (string) $order['order_number'], 'status' => 'cancelled', 'message' => 'Betalningen avbröts.'];
            }

            return ['order_number' => $orderNumber ?? '', 'status' => 'cancelled', 'message' => 'Betalningen avbröts.'];
        }

        if (($sessionId ?? '') === '') {
            return ['order_number' => $orderNumber ?? '', 'status' => 'failed', 'message' => 'Saknar sessionsreferens från provider.'];
        }

        $session = $this->stripe->fetchCheckoutSession((string) $sessionId);
        $orderId = (int) ($session['metadata']['order_id'] ?? 0);
        $order = $orderId > 0 ? $this->orders->findOrderById($orderId) : null;

        if ($order === null) {
            return ['order_number' => $orderNumber ?? '', 'status' => 'failed', 'message' => 'Order kunde inte verifieras.'];
        }

        $paymentIntent = (string) ($session['payment_intent'] ?? '');
        $this->applyMappedStatus(
            $order,
            (string) ($session['payment_status'] ?? 'unpaid'),
            $paymentIntent !== '' ? $paymentIntent : null,
            'return_received',
            'Stripe return processed.'
        );

        $internal = $this->mapStripeStatusToInternal((string) ($session['payment_status'] ?? 'unpaid'), false);

        return [
            'order_number' => (string) $order['order_number'],
            'status' => $internal,
            'message' => $internal === 'paid' ? 'Betalningen verifierad.' : 'Betalningen är inte slutförd ännu.',
        ];
    }

    /** @return array{ok:bool,message:string} */
    public function handleWebhook(string $payload, string $signature): array
    {
        $verification = $this->stripe->verifyWebhook($payload, $signature);
        if ($verification['valid'] !== true || !is_array($verification['payload'])) {
            return ['ok' => false, 'message' => 'Ogiltig Stripe-signatur.'];
        }

        $event = $verification['payload'];
        $eventId = (string) ($event['id'] ?? '');
        $eventType = (string) ($event['type'] ?? 'unknown');

        if ($eventId !== '' && $this->events->hasProviderEvent(self::STRIPE_PROVIDER, $eventId)) {
            return ['ok' => true, 'message' => 'Event redan behandlat.'];
        }

        $object = $event['data']['object'] ?? null;
        if (!is_array($object)) {
            return ['ok' => false, 'message' => 'Stripe webhook saknar objektdata.'];
        }

        $orderId = (int) ($object['metadata']['order_id'] ?? 0);
        if ($orderId < 1) {
            return ['ok' => false, 'message' => 'Stripe webhook saknar metadata.order_id.'];
        }

        $order = $this->orders->findOrderById($orderId);
        if ($order === null) {
            return ['ok' => false, 'message' => 'Order för webhook hittades inte.'];
        }

        $providerStatus = $this->extractProviderStatusFromEvent($eventType, $object);
        $reference = (string) ($object['payment_intent'] ?? $object['id'] ?? '');

        $this->applyMappedStatus($order, $providerStatus, $reference !== '' ? $reference : null, 'webhook_received', 'Stripe webhook: ' . $eventType, $eventId, $event);

        return ['ok' => true, 'message' => 'Webhook behandlad.'];
    }

    private function extractProviderStatusFromEvent(string $eventType, array $payload): string
    {
        return match ($eventType) {
            'checkout.session.completed' => (string) ($payload['payment_status'] ?? 'paid'),
            'checkout.session.expired' => 'expired',
            'payment_intent.payment_failed' => 'failed',
            default => (string) ($payload['status'] ?? 'unknown'),
        };
    }

    /** @param array<string,mixed> $order */
    private function applyMappedStatus(
        array $order,
        string $providerStatus,
        ?string $reference,
        string $eventType,
        string $note,
        ?string $providerEventId = null,
        ?array $payload = null
    ): void {
        $orderId = (int) $order['id'];
        $before = (string) $order['payment_status'];
        $after = $this->mapStripeStatusToInternal($providerStatus, (string) ($order['payment_provider_status'] ?? '') === 'paid');

        $timestamps = [
            'payment_authorized_at' => null,
            'payment_paid_at' => null,
            'payment_failed_at' => null,
        ];

        if ($after === 'authorized') {
            $timestamps['payment_authorized_at'] = date('Y-m-d H:i:s');
        }
        if ($after === 'paid') {
            $timestamps['payment_paid_at'] = date('Y-m-d H:i:s');
        }
        if ($after === 'failed' || $after === 'cancelled') {
            $timestamps['payment_failed_at'] = date('Y-m-d H:i:s');
        }

        $this->orders->updatePaymentProviderData($orderId, [
            'payment_status' => $after,
            'payment_reference' => $reference,
            'payment_provider' => self::STRIPE_PROVIDER,
            'payment_provider_reference' => $reference,
            'payment_provider_session_id' => (string) ($order['payment_provider_session_id'] ?? null),
            'payment_provider_status' => $providerStatus,
            'payment_note' => $note,
            'payment_authorized_at' => $timestamps['payment_authorized_at'],
            'payment_paid_at' => $timestamps['payment_paid_at'],
            'payment_failed_at' => $timestamps['payment_failed_at'],
        ]);

        $this->events->create([
            'order_id' => $orderId,
            'provider' => self::STRIPE_PROVIDER,
            'event_type' => $this->toEventType($eventType, $after),
            'provider_event_id' => $providerEventId,
            'payment_reference' => $reference,
            'payload_json' => $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'status_before' => $before,
            'status_after' => $after,
        ]);

        if ($before !== $after) {
            $this->orders->createStatusHistory($orderId, 'payment_status', $before, $after, 'Payment event: ' . $eventType);
        }
    }

    private function mapStripeStatusToInternal(string $providerStatus, bool $alreadyPaid): string
    {
        return match ($providerStatus) {
            'paid' => 'paid',
            'unpaid', 'open' => $alreadyPaid ? 'paid' : 'pending',
            'no_payment_required' => 'authorized',
            'failed' => 'failed',
            'expired', 'canceled', 'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    private function toEventType(string $eventType, string $mappedStatus): string
    {
        if ($eventType === 'return_received' || $eventType === 'webhook_received') {
            return $eventType;
        }

        return match ($mappedStatus) {
            'authorized' => 'payment_authorized',
            'paid' => 'payment_paid',
            'failed' => 'payment_failed',
            'cancelled' => 'payment_cancelled',
            default => 'webhook_received',
        };
    }
}
