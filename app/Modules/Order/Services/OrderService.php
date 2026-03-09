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
    private const ALLOWED_FULFILLMENT_STATUS = ['unfulfilled', 'processing', 'shipped'];

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
                'fulfillment_status' => 'unfulfilled',
                'internal_reference' => null,
                'packed_at' => null,
                'shipped_at' => null,
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

    public function markPacked(int $orderId): void
    {
        $this->orders->beginTransaction();
        try {
            $this->orders->markPacked($orderId);
            $this->orders->createOrderEvent($orderId, 'order_packed', 'Order markerad som packad.');
            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function markShipped(int $orderId): void
    {
        $this->orders->beginTransaction();
        try {
            $this->orders->markShipped($orderId);
            $this->orders->createOrderEvent($orderId, 'order_shipped', 'Order markerad som skickad.');
            $this->orders->commit();
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    /** @return array<string, array<int, string>> */
    public function statusOptions(): array
    {
        return [
            'status' => self::ALLOWED_ORDER_STATUS,
            'payment_status' => self::ALLOWED_PAYMENT_STATUS,
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
