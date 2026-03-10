<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Core\View\ViewFactory;
use App\Modules\Order\Repositories\EmailMessageRepository;
use App\Modules\Order\Repositories\OrderRepository;

final class OrderEmailService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly EmailMessageRepository $emailMessages,
        private readonly TransactionalEmailSender $sender,
        private readonly ViewFactory $views
    ) {
    }

    public function sendOrderConfirmation(int $orderId): void
    {
        $this->sendForOrder($orderId, 'order_confirmation', 'orderbekräftelse', true);
    }

    public function sendOrderShipped(int $orderId): void
    {
        $this->sendForOrder($orderId, 'order_shipped', 'order skickad', true);
    }

    public function sendOrderCancelled(int $orderId): void
    {
        $this->sendForOrder($orderId, 'order_cancelled', 'order annullerad', true);
    }

    private function sendForOrder(int $orderId, string $emailType, string $subjectLabel, bool $avoidDuplicate): void
    {
        if ($avoidDuplicate && $this->emailMessages->hasSentMessage('order', $orderId, $emailType)) {
            return;
        }

        $order = $this->orders->findOrderById($orderId);
        if ($order === null) {
            return;
        }

        $items = $this->orders->orderItems($orderId);
        $recipient = (string) ($order['customer_email'] ?? '');
        $subject = sprintf('A-Racing %s %s', $subjectLabel, (string) $order['order_number']);

        $messageId = $this->emailMessages->createPending('order', $orderId, $emailType, $recipient, $subject);

        try {
            $html = $this->renderBody($emailType, $order, $items);
            $meta = $this->sender->send($recipient, $subject, $html);
            $this->emailMessages->markSent($messageId, $meta['provider'], $meta['provider_message_id']);
        } catch (\Throwable $e) {
            $this->emailMessages->markFailed($messageId, 'native_php_mail', $e->getMessage());
        }
    }

    /** @param array<int, array<string, mixed>> $items */
    private function renderBody(string $emailType, array $order, array $items): string
    {
        return match ($emailType) {
            'order_confirmation' => $this->views->render('emails.orders.order_confirmation', [
                'order' => $order,
                'items' => $items,
                'paymentMethodLabel' => $this->paymentMethodLabel((string) ($order['payment_method'] ?? '')),
                'paymentNextStepText' => $this->paymentNextStepText((string) ($order['payment_method'] ?? '')),
                'orderStatusUrl' => $this->orderStatusUrl((string) ($order['order_number'] ?? '')),
            ]),
            'order_shipped' => $this->views->render('emails.orders.order_shipped', [
                'order' => $order,
                'orderStatusUrl' => $this->orderStatusUrl((string) ($order['order_number'] ?? '')),
            ]),
            'order_cancelled' => $this->views->render('emails.orders.order_cancelled', [
                'order' => $order,
            ]),
            default => throw new \InvalidArgumentException('Okänd email_type.'),
        };
    }

    private function paymentMethodLabel(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'stripe_checkout' => 'Kort / direktbetalning (Stripe)',
            'invoice_request' => 'Fakturaförfrågan',
            'manual_card_phone' => 'Kortbetalning via telefon',
            'bank_transfer' => 'Banköverföring',
            default => 'Ej vald',
        };
    }

    private function paymentNextStepText(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'stripe_checkout' => 'Betalningen hanteras i Stripe Checkout och verifieras automatiskt.',
            'invoice_request' => 'Vi återkommer med orderbekräftelse och betalningsinstruktion.',
            'manual_card_phone' => 'Vi kontaktar dig för att slutföra betalningen.',
            'bank_transfer' => 'Betalningsinstruktion skickas manuellt efter granskning.',
            default => 'Vi kontaktar dig vid behov med betalningsinformation.',
        };
    }

    private function orderStatusUrl(string $orderNumber): string
    {
        $baseUrl = rtrim((string) (getenv('APP_URL') ?: 'http://127.0.0.1:8000'), '/');

        return $baseUrl . '/order-status?order_number=' . urlencode($orderNumber);
    }
}
