<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Order\Repositories\OrderRepository;
use InvalidArgumentException;
use RuntimeException;

final class OrderService
{
    private const ALLOWED_ORDER_STATUS = ['pending', 'confirmed', 'cancelled'];
    private const ALLOWED_PAYMENT_STATUS = ['unpaid', 'pending', 'paid'];
    private const ALLOWED_PAYMENT_METHODS = ['invoice_request', 'manual_card_phone', 'bank_transfer'];
    private const ALLOWED_FULFILLMENT_STATUS = ['unfulfilled', 'processing', 'packed', 'shipped'];

    public function __construct(private readonly OrderRepository $orders)
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
            'notes' => $this->orders->orderNotes($id),
            'events' => $this->orders->orderEvents($id),
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
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'payment_method' => $order['payment_method'] ?? null,
            'payment_reference' => $order['payment_reference'] ?? null,
            'payment_note' => $order['payment_note'] ?? null,
            'fulfillment_status' => $order['fulfillment_status'],
            'shipped_at' => $order['shipped_at'],
            'tracking_number' => $order['tracking_number'] ?? null,
            'shipping_method' => $order['shipping_method'] ?? null,
        ];
    }

    public function createFromCart(array $checkoutData, array $cartData): string
    {
        if (($cartData['items'] ?? []) === []) {
            throw new RuntimeException('Kundvagnen är tom.');
        }

        $orderNumber = $this->generateOrderNumber();
        $this->orders->beginTransaction();

        try {
            $orderId = $this->orders->createOrder([
                'order_number' => $orderNumber,
                'status' => 'pending',
                'currency_code' => $cartData['cart']['currency_code'] ?? 'SEK',
                'customer_email' => $checkoutData['customer_email'],
                'customer_first_name' => $checkoutData['customer_first_name'],
                'customer_last_name' => $checkoutData['customer_last_name'],
                'customer_phone' => $checkoutData['customer_phone'],
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
                'order_notes' => $checkoutData['order_notes'],
                'subtotal_amount' => $cartData['subtotal_amount'],
                'shipping_amount' => 0,
                'total_amount' => $cartData['total_amount'],
                'payment_status' => 'unpaid',
                'payment_method' => $checkoutData['payment_method'],
                'payment_reference' => null,
                'payment_note' => null,
                'fulfillment_status' => 'unfulfilled',
                'internal_reference' => null,
                'packed_at' => null,
                'shipped_at' => null,
                'tracking_number' => null,
                'shipping_method' => null,
                'shipped_by_name' => null,
                'shipment_note' => null,
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

            $this->orders->createOrderEvent($orderId, 'order_created', 'Order skapad via checkout.');
            $this->orders->createOrderEvent($orderId, 'payment_method_selected', sprintf('Betalmetod vald i checkout: %s.', $checkoutData['payment_method']));

            $this->orders->commit();

            return $orderNumber;
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function updateOrderAdminFields(int $orderId, string $status, string $paymentStatus, string $fulfillmentStatus, string $internalReference): void
    {
        $status = trim($status);
        $paymentStatus = trim($paymentStatus);
        $fulfillmentStatus = trim($fulfillmentStatus);
        $internalReference = trim($internalReference);

        if (!in_array($status, self::ALLOWED_ORDER_STATUS, true)) {
            throw new InvalidArgumentException('Ogiltig orderstatus.');
        }

        if (!in_array($paymentStatus, self::ALLOWED_PAYMENT_STATUS, true)) {
            throw new InvalidArgumentException('Ogiltig betalstatus.');
        }

        if (!in_array($fulfillmentStatus, self::ALLOWED_FULFILLMENT_STATUS, true)) {
            throw new InvalidArgumentException('Ogiltig leveransstatus.');
        }

        $order = $this->orders->findOrderById($orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Order hittades inte.');
        }

        $this->orders->beginTransaction();

        try {
            $this->orders->updateStatusesAndReference(
                $orderId,
                $status,
                $paymentStatus,
                $fulfillmentStatus,
                $internalReference !== '' ? $internalReference : null
            );

            if ($order['status'] !== $status) {
                $this->orders->createOrderEvent($orderId, 'status_changed', sprintf('Orderstatus ändrad: %s → %s.', $order['status'], $status));
            }

            if ($order['payment_status'] !== $paymentStatus) {
                $this->orders->createOrderEvent($orderId, 'payment_status_changed', sprintf('Betalstatus ändrad: %s → %s.', $order['payment_status'], $paymentStatus));
            }

            if ($order['fulfillment_status'] !== $fulfillmentStatus) {
                $this->orders->createOrderEvent($orderId, 'fulfillment_status_changed', sprintf('Leveransstatus ändrad: %s → %s.', $order['fulfillment_status'], $fulfillmentStatus));
            }

            $previousReference = trim((string) ($order['internal_reference'] ?? ''));
            if ($previousReference !== $internalReference) {
                $eventMessage = $internalReference === ''
                    ? 'Intern referens rensad.'
                    : sprintf('Intern referens satt: %s.', $internalReference);
                $this->orders->createOrderEvent($orderId, 'internal_reference_updated', $eventMessage);
            }

            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function updatePaymentAdminFields(int $orderId, string $paymentStatus, string $paymentReference, string $paymentNote): void
    {
        $paymentStatus = trim($paymentStatus);
        $paymentReference = trim($paymentReference);
        $paymentNote = trim($paymentNote);

        if (!in_array($paymentStatus, self::ALLOWED_PAYMENT_STATUS, true)) {
            throw new InvalidArgumentException('Ogiltig betalstatus.');
        }

        $order = $this->orders->findOrderById($orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Order hittades inte.');
        }

        $this->orders->beginTransaction();
        try {
            $this->orders->updatePaymentAdminFields(
                $orderId,
                $paymentStatus,
                $paymentReference !== '' ? $paymentReference : null,
                $paymentNote !== '' ? $paymentNote : null
            );

            if ($order['payment_status'] !== $paymentStatus) {
                $this->orders->createOrderEvent($orderId, 'payment_status_changed', sprintf('Betalstatus ändrad: %s → %s.', $order['payment_status'], $paymentStatus));
            }

            $previousReference = trim((string) ($order['payment_reference'] ?? ''));
            if ($previousReference !== $paymentReference) {
                $message = $paymentReference === ''
                    ? 'Betalreferens rensad.'
                    : sprintf('Betalreferens uppdaterad: %s.', $paymentReference);
                $this->orders->createOrderEvent($orderId, 'payment_reference_updated', $message);
            }

            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function addInternalNote(int $orderId, string $noteText): void
    {
        $noteText = trim($noteText);
        if ($noteText === '') {
            throw new InvalidArgumentException('Anteckning får inte vara tom.');
        }

        $this->orders->beginTransaction();
        try {
            $this->orders->createOrderNote($orderId, 'internal', $noteText);
            $this->orders->createOrderEvent($orderId, 'internal_note_added', 'Intern anteckning tillagd.');
            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function markProcessing(int $orderId): void
    {
        $order = $this->orders->findOrderById($orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Order hittades inte.');
        }

        $this->orders->beginTransaction();
        try {
            $this->orders->updateFulfillmentStatus($orderId, 'processing');
            $this->orders->createOrderEvent($orderId, 'order_processing', 'Order markerad som processing.');
            if (($order['fulfillment_status'] ?? '') !== 'processing') {
                $this->orders->createOrderEvent($orderId, 'fulfillment_status_changed', sprintf('Leveransstatus ändrad: %s → processing.', (string) $order['fulfillment_status']));
            }
            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function markPacked(int $orderId): void
    {
        $order = $this->orders->findOrderById($orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Order hittades inte.');
        }

        $this->orders->beginTransaction();
        try {
            $this->orders->markPacked($orderId);
            $this->orders->createOrderEvent($orderId, 'order_packed', 'Order markerad som packad.');
            if (($order['fulfillment_status'] ?? '') !== 'packed') {
                $this->orders->createOrderEvent($orderId, 'fulfillment_status_changed', sprintf('Leveransstatus ändrad: %s → packed.', (string) $order['fulfillment_status']));
            }
            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function markShipped(int $orderId): void
    {
        $order = $this->orders->findOrderById($orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Order hittades inte.');
        }

        $this->orders->beginTransaction();
        try {
            $this->orders->markShipped($orderId);
            $this->orders->createOrderEvent($orderId, 'order_shipped', 'Order markerad som skickad.');
            if (($order['fulfillment_status'] ?? '') !== 'shipped') {
                $this->orders->createOrderEvent($orderId, 'fulfillment_status_changed', sprintf('Leveransstatus ändrad: %s → shipped.', (string) $order['fulfillment_status']));
            }
            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function updateShipmentInfo(int $orderId, string $trackingNumber, string $shippingMethod, string $shippedByName, string $shipmentNote): void
    {
        $order = $this->orders->findOrderById($orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Order hittades inte.');
        }

        $trackingNumber = trim($trackingNumber);
        $shippingMethod = trim($shippingMethod);
        $shippedByName = trim($shippedByName);
        $shipmentNote = trim($shipmentNote);

        $this->orders->beginTransaction();
        try {
            $this->orders->updateShipmentInfo(
                $orderId,
                $trackingNumber !== '' ? $trackingNumber : null,
                $shippingMethod !== '' ? $shippingMethod : null,
                $shippedByName !== '' ? $shippedByName : null,
                $shipmentNote !== '' ? $shipmentNote : null
            );

            if (trim((string) ($order['tracking_number'] ?? '')) !== $trackingNumber) {
                $message = $trackingNumber === ''
                    ? 'Trackingnummer rensat.'
                    : sprintf('Trackingnummer uppdaterat: %s.', $trackingNumber);
                $this->orders->createOrderEvent($orderId, 'tracking_number_updated', $message);
            }

            if (trim((string) ($order['shipping_method'] ?? '')) !== $shippingMethod) {
                $message = $shippingMethod === ''
                    ? 'Fraktmetod rensad.'
                    : sprintf('Fraktmetod uppdaterad: %s.', $shippingMethod);
                $this->orders->createOrderEvent($orderId, 'shipping_method_updated', $message);
            }

            if (trim((string) ($order['shipment_note'] ?? '')) !== $shipmentNote) {
                $message = $shipmentNote === ''
                    ? 'Försändelsenotering rensad.'
                    : 'Försändelsenotering uppdaterad.';
                $this->orders->createOrderEvent($orderId, 'shipment_note_updated', $message);
            }

            if (trim((string) ($order['shipped_by_name'] ?? '')) !== $shippedByName) {
                $message = $shippedByName === ''
                    ? 'Skickad av rensad.'
                    : sprintf('Skickad av uppdaterad: %s.', $shippedByName);
                $this->orders->createOrderEvent($orderId, 'shipped_by_name_updated', $message);
            }

            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function paymentMethodLabel(?string $paymentMethod): string
    {
        return match ((string) $paymentMethod) {
            'invoice_request' => 'Fakturaförfrågan',
            'manual_card_phone' => 'Kortbetalning via telefon',
            'bank_transfer' => 'Banköverföring',
            default => 'Ej vald',
        };
    }

    public function paymentNextStepText(?string $paymentMethod): string
    {
        return match ((string) $paymentMethod) {
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
            'status' => self::ALLOWED_ORDER_STATUS,
            'payment_status' => self::ALLOWED_PAYMENT_STATUS,
            'payment_method' => self::ALLOWED_PAYMENT_METHODS,
            'fulfillment_status' => self::ALLOWED_FULFILLMENT_STATUS,
        ];
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
