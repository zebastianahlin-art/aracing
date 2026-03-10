<?php

declare(strict_types=1);

namespace App\Modules\Order\Repositories;

use PDO;

final class OrderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createOrder(array $data): int
    {
        $sql = 'INSERT INTO orders (
            order_number, status, order_status, currency_code,
            customer_email, customer_first_name, customer_last_name, customer_phone,
            billing_address_line_1, billing_address_line_2, billing_postal_code, billing_city, billing_country,
            shipping_first_name, shipping_last_name, shipping_phone,
            shipping_address_line_1, shipping_address_line_2, shipping_postal_code, shipping_city, shipping_country,
            shipping_method_code, shipping_method_name, shipping_method_description,
            discount_code, discount_name, discount_type, discount_value,
            order_notes,
            subtotal_amount, shipping_cost_ex_vat, shipping_cost_inc_vat, discount_amount_ex_vat, discount_amount_inc_vat, shipping_amount, total_amount,
            payment_status, payment_method, payment_reference, payment_note, fulfillment_status,
            carrier_code, carrier_name, tracking_number, tracking_url,
            shipped_at, delivered_at, cancelled_at,
            internal_reference,
            created_at, updated_at
        ) VALUES (
            :order_number, :status, :order_status, :currency_code,
            :customer_email, :customer_first_name, :customer_last_name, :customer_phone,
            :billing_address_line_1, :billing_address_line_2, :billing_postal_code, :billing_city, :billing_country,
            :shipping_first_name, :shipping_last_name, :shipping_phone,
            :shipping_address_line_1, :shipping_address_line_2, :shipping_postal_code, :shipping_city, :shipping_country,
            :shipping_method_code, :shipping_method_name, :shipping_method_description,
            :discount_code, :discount_name, :discount_type, :discount_value,
            :order_notes,
            :subtotal_amount, :shipping_cost_ex_vat, :shipping_cost_inc_vat, :discount_amount_ex_vat, :discount_amount_inc_vat, :shipping_amount, :total_amount,
            :payment_status, :payment_method, :payment_reference, :payment_note, :fulfillment_status,
            :carrier_code, :carrier_name, :tracking_number, :tracking_url,
            :shipped_at, :delivered_at, :cancelled_at,
            :internal_reference,
            NOW(), NOW()
        )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function createOrderItem(int $orderId, array $item): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO order_items (
            order_id, product_id, product_name_snapshot, sku_snapshot, unit_price_snapshot, quantity, line_total, created_at, updated_at
        ) VALUES (
            :order_id, :product_id, :product_name_snapshot, :sku_snapshot, :unit_price_snapshot, :quantity, :line_total, NOW(), NOW()
        )');
        $stmt->execute([
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'product_name_snapshot' => $item['product_name_snapshot'],
            'sku_snapshot' => $item['sku_snapshot'],
            'unit_price_snapshot' => $item['unit_price_snapshot'],
            'quantity' => $item['quantity'],
            'line_total' => $item['line_total'],
        ]);
    }

    public function createStatusHistory(
        int $orderId,
        string $type,
        ?string $fromValue,
        ?string $toValue,
        ?string $comment,
        ?int $createdByUserId = null
    ): void {
        $stmt = $this->pdo->prepare('INSERT INTO order_status_history
            (order_id, type, from_value, to_value, comment, created_by_user_id, created_at)
            VALUES (:order_id, :type, :from_value, :to_value, :comment, :created_by_user_id, NOW())');
        $stmt->execute([
            'order_id' => $orderId,
            'type' => $type,
            'from_value' => $fromValue,
            'to_value' => $toValue,
            'comment' => $comment,
            'created_by_user_id' => $createdByUserId,
        ]);
    }

    public function orderNumberExists(string $orderNumber): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM orders WHERE order_number = :order_number LIMIT 1');
        $stmt->execute(['order_number' => $orderNumber]);

        return $stmt->fetch() !== false;
    }

    /** @return array<int, array<string, mixed>> */
    public function listOrders(array $filters = []): array
    {
        $conditions = [];
        $params = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $conditions[] = '(order_number LIKE :search OR customer_email LIKE :search OR CONCAT(customer_first_name, " ", customer_last_name) LIKE :search OR tracking_number LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        foreach (['order_status', 'payment_status', 'payment_method', 'fulfillment_status'] as $field) {
            $value = trim((string) ($filters[$field] ?? ''));
            if ($value !== '') {
                $conditions[] = $field . ' = :' . $field;
                $params[$field] = $value;
            }
        }

        $sql = 'SELECT id, order_number, customer_first_name, customer_last_name, customer_email,
                order_status, payment_status, payment_method, fulfillment_status,
                shipping_method_name, shipping_cost_inc_vat, discount_code, total_amount, created_at,
                carrier_name, tracking_number, shipped_at, delivered_at, cancelled_at
            FROM orders';

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findOrderById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findOrderByNumber(string $orderNumber): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE order_number = :order_number LIMIT 1');
        $stmt->execute(['order_number' => $orderNumber]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function orderItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function orderHistory(int $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM order_status_history WHERE order_id = :order_id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }

    public function updateOrderStatus(int $orderId, string $orderStatus): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders
            SET order_status = :order_status,
                status = :legacy_status,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $orderId,
            'order_status' => $orderStatus,
            'legacy_status' => $orderStatus,
        ]);
    }

    public function updatePaymentStatus(int $orderId, string $paymentStatus, ?string $paymentReference, ?string $paymentNote): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders
            SET payment_status = :payment_status,
                payment_reference = :payment_reference,
                payment_note = :payment_note,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $orderId,
            'payment_status' => $paymentStatus,
            'payment_reference' => $paymentReference,
            'payment_note' => $paymentNote,
        ]);
    }

    public function updateFulfillmentStatus(int $orderId, string $fulfillmentStatus): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders
            SET fulfillment_status = :fulfillment_status,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $orderId,
            'fulfillment_status' => $fulfillmentStatus,
        ]);
    }

    public function updateShippingData(int $orderId, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders
            SET carrier_code = :carrier_code,
                carrier_name = :carrier_name,
                tracking_number = :tracking_number,
                tracking_url = :tracking_url,
                shipped_at = :shipped_at,
                delivered_at = :delivered_at,
                cancelled_at = :cancelled_at,
                updated_at = NOW()
            WHERE id = :id');

        $stmt->execute([
            'id' => $orderId,
            'carrier_code' => $data['carrier_code'] ?? null,
            'carrier_name' => $data['carrier_name'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'tracking_url' => $data['tracking_url'] ?? null,
            'shipped_at' => $data['shipped_at'] ?? null,
            'delivered_at' => $data['delivered_at'] ?? null,
            'cancelled_at' => $data['cancelled_at'] ?? null,
        ]);
    }

    public function updateInternalReference(int $orderId, ?string $internalReference): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders
            SET internal_reference = :internal_reference,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $orderId,
            'internal_reference' => $internalReference,
        ]);
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
