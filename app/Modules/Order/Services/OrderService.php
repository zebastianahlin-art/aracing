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
    public function listOrders(): array
    {
        return $this->orders->listOrders();
    }

    /** @return array<string, mixed>|null */
    public function getOrderDetail(int $id): ?array
    {
        $order = $this->orders->findOrderById($id);
        if ($order === null) {
            return null;
        }

        $items = $this->orders->orderItems($id);

        return ['order' => $order, 'items' => $items];
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

            $this->orders->commit();

            return $orderNumber;
        } catch (\Throwable $e) {
            $this->orders->rollBack();
            throw $e;
        }
    }

    public function updateStatuses(int $orderId, string $status, string $paymentStatus, string $fulfillmentStatus): void
    {
        $status = trim($status);
        $paymentStatus = trim($paymentStatus);
        $fulfillmentStatus = trim($fulfillmentStatus);

        if (!in_array($status, self::ALLOWED_ORDER_STATUS, true)) {
            throw new InvalidArgumentException('Ogiltig orderstatus.');
        }

        if (!in_array($paymentStatus, self::ALLOWED_PAYMENT_STATUS, true)) {
            throw new InvalidArgumentException('Ogiltig betalstatus.');
        }

        if (!in_array($fulfillmentStatus, self::ALLOWED_FULFILLMENT_STATUS, true)) {
            throw new InvalidArgumentException('Ogiltig leveransstatus.');
        }

        $this->orders->updateStatuses($orderId, $status, $paymentStatus, $fulfillmentStatus);
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
