<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Order\Repositories\EmailMessageRepository;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Discount\Services\DiscountService;
use App\Modules\Shipping\Services\CheckoutTotalsService;
use App\Modules\Shipping\Services\ShippingService;
use InvalidArgumentException;
use RuntimeException;

final class OrderService
{
    private const ALLOWED_ORDER_STATUS = ['placed', 'confirmed', 'processing', 'completed', 'cancelled'];
    private const ALLOWED_PAYMENT_STATUS = ['unpaid', 'pending', 'authorized', 'paid', 'failed', 'cancelled'];
    private const ALLOWED_PAYMENT_METHODS = ['stripe_checkout', 'invoice_request', 'manual_card_phone', 'bank_transfer'];
    private const ALLOWED_FULFILLMENT_STATUS = ['unfulfilled', 'picking', 'packed', 'shipped', 'delivered', 'cancelled'];

    private const ORDER_TRANSITIONS = [
        'placed' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    private const FULFILLMENT_TRANSITIONS = [
        'unfulfilled' => ['picking', 'cancelled'],
        'picking' => ['packed', 'cancelled'],
        'packed' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly EmailMessageRepository $emailMessages,
        private readonly OrderEmailService $orderEmails,
        private readonly ShippingService $shipping,
        private readonly CheckoutTotalsService $totals,
        private readonly DiscountService $discounts
    )
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listOrders(array $filters = []): array
    {
        return $this->orders->listOrders($filters);
    }

    /** @return array<string, mixed>|null */
    public function getOrderDetail(int $id): ?array
    {
        $order = $this->orders->findOrderById($id);
        if ($order === null) {
            return null;
        }

        return [
            'order' => $order,
            'items' => $this->orders->orderItems($id),
            'history' => $this->orders->orderHistory($id),
            'emails' => $this->emailMessages->forRelated('order', $id),
        ];
    }

    /** @return array<string, mixed>|null */
    public function getPublicOrderSummaryByNumber(string $orderNumber): ?array
    {
        $cleanOrderNumber = trim($orderNumber);
        if ($cleanOrderNumber === '') {
            return null;
        }

        $order = $this->orders->findOrderByNumber($cleanOrderNumber);
        if ($order === null) {
            return null;
        }

        return [
            'order_number' => $order['order_number'],
            'order_status' => $order['order_status'],
            'payment_status' => $order['payment_status'],
            'payment_method' => $order['payment_method'] ?? null,
            'payment_reference' => $order['payment_reference'] ?? null,
            'payment_note' => $order['payment_note'] ?? null,
            'payment_provider' => $order['payment_provider'] ?? null,
            'payment_provider_reference' => $order['payment_provider_reference'] ?? null,
            'payment_provider_session_id' => $order['payment_provider_session_id'] ?? null,
            'payment_provider_status' => $order['payment_provider_status'] ?? null,
            'payment_authorized_at' => $order['payment_authorized_at'] ?? null,
            'payment_paid_at' => $order['payment_paid_at'] ?? null,
            'payment_failed_at' => $order['payment_failed_at'] ?? null,
            'fulfillment_status' => $order['fulfillment_status'],
            'shipping_method_code' => $order['shipping_method_code'] ?? null,
            'shipping_method_name' => $order['shipping_method_name'] ?? null,
            'shipping_method_description' => $order['shipping_method_description'] ?? null,
            'shipping_cost_inc_vat' => (float) ($order['shipping_cost_inc_vat'] ?? 0),
            'discount_code' => $order['discount_code'] ?? null,
            'discount_name' => $order['discount_name'] ?? null,
            'discount_amount_inc_vat' => (float) ($order['discount_amount_inc_vat'] ?? 0),
            'product_subtotal' => (float) ($order['subtotal_amount'] ?? 0),
            'grand_total' => (float) ($order['total_amount'] ?? 0),
            'currency_code' => $order['currency_code'] ?? 'SEK',
            'company_name' => $order['company_name'] ?? null,
            'company_registration_number' => $order['company_registration_number'] ?? null,
            'vat_number' => $order['vat_number'] ?? null,
            'carrier_name' => $order['carrier_name'] ?? null,
            'tracking_number' => $order['tracking_number'] ?? null,
            'tracking_url' => $order['tracking_url'] ?? null,
            'shipped_at' => $order['shipped_at'],
            'delivered_at' => $order['delivered_at'],
        ];
    }


    public function findOrderIdByNumber(string $orderNumber): ?int
    {
        $order = $this->orders->findOrderByNumber(trim($orderNumber));
        if ($order === null) {
            return null;
        }

        return (int) $order['id'];
    }

    public function createFromCart(array $checkoutData, array $cartData): string
    {
        if (($cartData['items'] ?? []) === []) {
            throw new RuntimeException('Kundvagnen är tom.');
        }

        $selectedMethod = $this->shipping->validateSelectedMethod((string) ($checkoutData['shipping_method_code'] ?? ''));
        $shippingSnapshot = $this->shipping->buildOrderSnapshot($selectedMethod);
        $productSubtotal = (float) ($cartData['subtotal_amount'] ?? 0);

        $discount = null;
        $discountAmount = 0.0;
        $cartDiscountCode = trim((string) ($cartData['cart']['discount_code'] ?? ''));
        if ($cartDiscountCode !== '') {
            $discount = $this->discounts->validateCodeForSubtotal($cartDiscountCode, $productSubtotal);
            $discountAmount = $this->discounts->calculateDiscountAmount($discount, $productSubtotal);
        }

        $totals = $this->totals->calculate($productSubtotal, (float) $shippingSnapshot['shipping_cost_inc_vat'], $discountAmount);
        $discountSnapshot = $this->discounts->buildOrderSnapshot($discount, $discountAmount);

        $orderNumber = $this->generateOrderNumber();
        $this->orders->beginTransaction();

        try {
            $orderId = $this->orders->createOrder([
                'order_number' => $orderNumber,
                'status' => 'placed',
                'order_status' => 'placed',
                'currency_code' => $cartData['cart']['currency_code'] ?? 'SEK',
                'user_id' => $checkoutData['customer_user_id'] ?? null,
                'customer_email' => $checkoutData['customer_email'],
                'customer_first_name' => $checkoutData['customer_first_name'],
                'customer_last_name' => $checkoutData['customer_last_name'],
                'customer_phone' => $checkoutData['customer_phone'],
                'company_name' => $checkoutData['company_name'] ?? null,
                'company_registration_number' => $checkoutData['company_registration_number'] ?? null,
                'vat_number' => $checkoutData['vat_number'] ?? null,
                'billing_address_line_1' => $checkoutData['billing_address_line_1'],
                'billing_address_line_2' => $checkoutData['billing_address_line_2'],
                'billing_postal_code' => $checkoutData['billing_postal_code'],
                'billing_city' => $checkoutData['billing_city'],
                'billing_country' => $checkoutData['billing_country'],
                'shipping_first_name' => $checkoutData['shipping_first_name'],
                'shipping_last_name' => $checkoutData['shipping_last_name'],
                'shipping_phone' => $checkoutData['shipping_phone'],
                'shipping_address_line_1' => $checkoutData['shipping_address_line_1'],
                'shipping_address_line_2' => $checkoutData['shipping_address_line_2'],
                'shipping_postal_code' => $checkoutData['shipping_postal_code'],
                'shipping_city' => $checkoutData['shipping_city'],
                'shipping_country' => $checkoutData['shipping_country'],
                'shipping_method_code' => $shippingSnapshot['shipping_method_code'],
                'shipping_method_name' => $shippingSnapshot['shipping_method_name'],
                'shipping_method_description' => $shippingSnapshot['shipping_method_description'],
                'discount_code' => $discountSnapshot['discount_code'] ?? null,
                'discount_name' => $discountSnapshot['discount_name'] ?? null,
                'discount_type' => $discountSnapshot['discount_type'] ?? null,
                'discount_value' => $discountSnapshot['discount_value'] ?? null,
                'order_notes' => $checkoutData['order_notes'],
                'subtotal_amount' => $totals['product_subtotal'],
                'shipping_cost_ex_vat' => $shippingSnapshot['shipping_cost_ex_vat'],
                'shipping_cost_inc_vat' => $shippingSnapshot['shipping_cost_inc_vat'],
                'discount_amount_ex_vat' => $discountSnapshot['discount_amount_ex_vat'] ?? 0,
                'discount_amount_inc_vat' => $discountSnapshot['discount_amount_inc_vat'] ?? 0,
                'shipping_amount' => $shippingSnapshot['shipping_cost_inc_vat'],
                'total_amount' => $totals['grand_total'],
                'payment_status' => $checkoutData['payment_method'] === 'stripe_checkout' ? 'pending' : 'unpaid',
                'payment_method' => $checkoutData['payment_method'],
                'payment_provider' => $checkoutData['payment_method'] === 'stripe_checkout' ? 'stripe' : null,
                'payment_reference' => null,
                'payment_provider_reference' => null,
                'payment_provider_session_id' => null,
                'payment_provider_status' => null,
                'payment_note' => null,
                'payment_authorized_at' => null,
                'payment_paid_at' => null,
                'payment_failed_at' => null,
                'fulfillment_status' => 'unfulfilled',
                'carrier_code' => null,
                'carrier_name' => null,
                'tracking_number' => null,
                'tracking_url' => null,
                'shipped_at' => null,
                'delivered_at' => null,
                'cancelled_at' => null,
                'internal_reference' => null,
            ]);

            foreach ($cartData['items'] as $item) {
                $lineTotal = (float) $item['unit_price_snapshot'] * (int) $item['quantity'];
                $this->orders->createOrderItem($orderId, [
                    'product_id' => $item['product_id'],
                    'product_name_snapshot' => $item['product_name_snapshot'],
                    'sku_snapshot' => $item['sku_snapshot'],
                    'unit_price_snapshot' => $item['unit_price_snapshot'],
                    'quantity' => $item['quantity'],
                    'line_total' => $lineTotal,
                ]);
            }

            $this->orders->createStatusHistory($orderId, 'order_status', null, 'placed', 'Order skapad via checkout.');
            $this->orders->createStatusHistory($orderId, 'payment_status', null, (string) ($checkoutData['payment_method'] === 'stripe_checkout' ? 'pending' : 'unpaid'), 'Betalning initierad.');
            $this->orders->createStatusHistory($orderId, 'fulfillment_status', null, 'unfulfilled', 'Order väntar på plock.');

            if ($discountSnapshot !== null) {
                $this->discounts->incrementUsageCount((int) $discountSnapshot['discount_id']);
            }

            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }

        $this->orderEmails->sendOrderConfirmation($orderId);

        return $orderNumber;
    }

    public function transitionOrderStatus(int $orderId, string $targetStatus): void
    {
        $order = $this->mustFindOrder($orderId);
        $fromStatus = (string) $order['order_status'];
        $targetStatus = trim($targetStatus);

        if (!in_array($targetStatus, self::ALLOWED_ORDER_STATUS, true)) {
            throw new InvalidArgumentException('Ogiltig orderstatus.');
        }

        if ($fromStatus === $targetStatus) {
            return;
        }

        if (!in_array($targetStatus, self::ORDER_TRANSITIONS[$fromStatus] ?? [], true)) {
            throw new InvalidArgumentException('Otillåten orderstatus-övergång.');
        }

        $this->orders->beginTransaction();
        try {
            $this->orders->updateOrderStatus($orderId, $targetStatus);
            $this->orders->createStatusHistory($orderId, 'order_status', $fromStatus, $targetStatus, 'Orderstatus uppdaterad av admin.');
            if ($targetStatus === 'cancelled') {
                $this->orders->updateShippingData($orderId, ['cancelled_at' => date('Y-m-d H:i:s')]);
            }
            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }

        if ($targetStatus === 'cancelled') {
            $this->orderEmails->sendOrderCancelled($orderId);
        }
    }

    public function transitionFulfillmentStatus(int $orderId, string $targetStatus): void
    {
        $order = $this->mustFindOrder($orderId);
        $fromStatus = (string) $order['fulfillment_status'];
        $targetStatus = trim($targetStatus);

        if (!in_array($targetStatus, self::ALLOWED_FULFILLMENT_STATUS, true)) {
            throw new InvalidArgumentException('Ogiltig fulfillment-status.');
        }

        if ($fromStatus === $targetStatus) {
            return;
        }

        if (!in_array($targetStatus, self::FULFILLMENT_TRANSITIONS[$fromStatus] ?? [], true)) {
            throw new InvalidArgumentException('Otillåten fulfillment-övergång.');
        }

        $shippingPatch = [];
        if ($targetStatus === 'shipped' && empty($order['shipped_at'])) {
            $shippingPatch['shipped_at'] = date('Y-m-d H:i:s');
        }
        if ($targetStatus === 'delivered' && empty($order['delivered_at'])) {
            $shippingPatch['delivered_at'] = date('Y-m-d H:i:s');
        }
        if ($targetStatus === 'cancelled' && empty($order['cancelled_at'])) {
            $shippingPatch['cancelled_at'] = date('Y-m-d H:i:s');
        }

        $this->orders->beginTransaction();
        try {
            $this->orders->updateFulfillmentStatus($orderId, $targetStatus);
            $this->orders->createStatusHistory($orderId, 'fulfillment_status', $fromStatus, $targetStatus, 'Fulfillment-status uppdaterad av admin.');
            if ($shippingPatch !== []) {
                $this->orders->updateShippingData($orderId, $shippingPatch);
            }
            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }

        if ($targetStatus === 'shipped') {
            $this->orderEmails->sendOrderShipped($orderId);
        }
        if ($targetStatus === 'cancelled') {
            $this->orderEmails->sendOrderCancelled($orderId);
        }
    }

    public function updatePaymentAdminFields(int $orderId, string $paymentStatus, string $paymentReference, string $paymentNote): void
    {
        $order = $this->mustFindOrder($orderId);
        $paymentStatus = trim($paymentStatus);

        if (!in_array($paymentStatus, self::ALLOWED_PAYMENT_STATUS, true)) {
            throw new InvalidArgumentException('Ogiltig betalstatus.');
        }

        $paymentReference = trim($paymentReference);
        $paymentNote = trim($paymentNote);

        $this->orders->beginTransaction();
        try {
            $this->orders->updatePaymentStatus(
                $orderId,
                $paymentStatus,
                $paymentReference !== '' ? $paymentReference : null,
                $paymentNote !== '' ? $paymentNote : null
            );

            if ((string) $order['payment_status'] !== $paymentStatus) {
                $this->orders->createStatusHistory($orderId, 'payment_status', (string) $order['payment_status'], $paymentStatus, 'Betalstatus uppdaterad av admin.');
            }

            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function addAdminNote(int $orderId, string $noteText): void
    {
        $this->mustFindOrder($orderId);
        $noteText = trim($noteText);
        if ($noteText === '') {
            throw new InvalidArgumentException('Anteckning får inte vara tom.');
        }

        $this->orders->createStatusHistory($orderId, 'note', null, null, $noteText);
    }

    public function updateShipmentInfo(int $orderId, array $input): void
    {
        $order = $this->mustFindOrder($orderId);

        $payload = [
            'carrier_code' => $this->nullable($input['carrier_code'] ?? null),
            'carrier_name' => $this->nullable($input['carrier_name'] ?? null),
            'tracking_number' => $this->nullable($input['tracking_number'] ?? null),
            'tracking_url' => $this->nullable($input['tracking_url'] ?? null),
            'shipped_at' => $this->nullable($input['shipped_at'] ?? null),
            'delivered_at' => null,
            'cancelled_at' => null,
        ];

        $this->orders->beginTransaction();
        try {
            $this->orders->updateShippingData($orderId, $payload);

            foreach (['carrier_code', 'carrier_name', 'tracking_number', 'tracking_url', 'shipped_at'] as $field) {
                $from = $this->nullable($order[$field] ?? null);
                $to = $payload[$field];
                if ($from !== $to) {
                    $this->orders->createStatusHistory($orderId, 'shipping', $from, $to, sprintf('%s uppdaterad.', $field));
                }
            }

            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function updateInternalReference(int $orderId, string $internalReference): void
    {
        $order = $this->mustFindOrder($orderId);
        $next = $this->nullable($internalReference);
        $previous = $this->nullable($order['internal_reference'] ?? null);
        if ($next === $previous) {
            return;
        }

        $this->orders->updateInternalReference($orderId, $next);
        $this->orders->createStatusHistory($orderId, 'note', $previous, $next, 'Intern referens uppdaterad.');
    }

    public function paymentMethodLabel(?string $paymentMethod): string
    {
        return match ((string) $paymentMethod) {
            'stripe_checkout' => 'Kort / direktbetalning (Stripe)',
            'invoice_request' => 'Fakturaförfrågan',
            'manual_card_phone' => 'Kortbetalning via telefon',
            'bank_transfer' => 'Banköverföring',
            default => 'Ej vald',
        };
    }

    public function paymentNextStepText(?string $paymentMethod): string
    {
        return match ((string) $paymentMethod) {
            'stripe_checkout' => 'Betalningen initieras i Stripe Checkout. Vid avbrott kan du försöka igen från orderstatus.',
            'invoice_request' => 'Vi återkommer med orderbekräftelse och betalningsinstruktion.',
            'manual_card_phone' => 'Vi kontaktar dig för att slutföra betalningen.',
            'bank_transfer' => 'Betalningsinstruktion skickas manuellt efter granskning.',
            default => 'Vi kontaktar dig vid behov med betalningsinformation.',
        };
    }

    /** @return array<string, array<int, string>> */
    public function statusOptions(): array
    {
        return [
            'order_status' => self::ALLOWED_ORDER_STATUS,
            'payment_status' => self::ALLOWED_PAYMENT_STATUS,
            'payment_method' => self::ALLOWED_PAYMENT_METHODS,
            'fulfillment_status' => self::ALLOWED_FULFILLMENT_STATUS,
        ];
    }

    /** @return array<string, mixed> */
    private function mustFindOrder(int $orderId): array
    {
        $order = $this->orders->findOrderById($orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Order hittades inte.');
        }

        return $order;
    }

    private function nullable(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function generateOrderNumber(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $orderNumber = sprintf('AR-%s-%04d', date('Ymd'), random_int(0, 9999));
            if ($this->orders->orderNumberExists($orderNumber) === false) {
                return $orderNumber;
            }
        }

        throw new RuntimeException('Kunde inte skapa unikt ordernummer.');
    }
}
