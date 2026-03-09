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
            order_number, status, currency_code,
            customer_email, customer_first_name, customer_last_name, customer_phone,
            billing_address_line_1, billing_address_line_2, billing_postal_code, billing_city, billing_country,
            shipping_first_name, shipping_last_name, shipping_phone,
            shipping_address_line_1, shipping_address_line_2, shipping_postal_code, shipping_city, shipping_country,
            order_notes,
            subtotal_amount, shipping_amount, total_amount,
            payment_status, fulfillment_status,
            internal_reference, packed_at, shipped_at,
            tracking_number, shipping_method, shipped_by_name, shipment_note,
            created_at, updated_at
        ) VALUES (
            :order_number, :status, :currency_code,
            :customer_email, :customer_first_name, :customer_last_name, :customer_phone,
            :billing_address_line_1, :billing_address_line_2, :billing_postal_code, :billing_city, :billing_country,
            :shipping_first_name, :shipping_last_name, :shipping_phone,
            :shipping_address_line_1, :shipping_address_line_2, :shipping_postal_code, :shipping_city, :shipping_country,
            :order_notes,
            :subtotal_amount, :shipping_amount, :total_amount,
            :payment_status, :fulfillment_status,
            :internal_reference, :packed_at, :shipped_at,
            :tracking_number, :shipping_method, :shipped_by_name, :shipment_note,
            NOW(), NOW()
        )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function createOrderItem(int $orderId, array $item): void
    {
        $sql = 'INSERT INTO order_items (
            order_id, product_id, product_name_snapshot, sku_snapshot, unit_price_snapshot, quantity, line_total, created_at, updated_at
        ) VALUES (
            :order_id, :product_id, :product_name_snapshot, :sku_snapshot, :unit_price_snapshot, :quantity, :line_total, NOW(), NOW()
        )';

        $stmt = $this->pdo->prepare($sql);
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

    public function createOrderEvent(int $orderId, string $eventType, string $eventMessage): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO order_events (order_id, event_type, event_message, created_at)
            VALUES (:order_id, :event_type, :event_message, NOW())');
        $stmt->execute([
            'order_id' => $orderId,
            'event_type' => $eventType,
            'event_message' => $eventMessage,
        ]);
    }

    public function createOrderNote(int $orderId, string $noteType, string $noteText): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO order_notes (order_id, note_type, note_text, created_at, updated_at)
            VALUES (:order_id, :note_type, :note_text, NOW(), NOW())');
        $stmt->execute([
            'order_id' => $orderId,
            'note_type' => $noteType,
            'note_text' => $noteText,
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

        foreach (['status', 'payment_status', 'fulfillment_status'] as $field) {
            $value = trim((string) ($filters[$field] ?? ''));
            if ($value !== '') {
                $conditions[] = $field . ' = :' . $field;
                $params[$field] = $value;
            }
        }

        $sql = 'SELECT id, order_number, customer_first_name, customer_last_name, customer_email,
                status, payment_status, fulfillment_status, total_amount, created_at,
                tracking_number, shipping_method, packed_at, shipped_at
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
    public function orderNotes(int $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM order_notes WHERE order_id = :order_id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function orderEvents(int $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM order_events WHERE order_id = :order_id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }

    public function updateStatusesAndReference(int $orderId, string $status, string $paymentStatus, string $fulfillmentStatus, ?string $internalReference): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders
            SET status = :status,
                payment_status = :payment_status,
                fulfillment_status = :fulfillment_status,
                internal_reference = :internal_reference,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $orderId,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'fulfillment_status' => $fulfillmentStatus,
            'internal_reference' => $internalReference,
        ]);
    }

    public function updateShipmentInfo(int $orderId, ?string $trackingNumber, ?string $shippingMethod, ?string $shippedByName, ?string $shipmentNote): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders
            SET tracking_number = :tracking_number,
                shipping_method = :shipping_method,
                shipped_by_name = :shipped_by_name,
                shipment_note = :shipment_note,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $orderId,
            'tracking_number' => $trackingNumber,
            'shipping_method' => $shippingMethod,
            'shipped_by_name' => $shippedByName,
            'shipment_note' => $shipmentNote,
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

    public function markPacked(int $orderId): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders
            SET packed_at = NOW(),
                fulfillment_status = :fulfillment_status,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $orderId,
            'fulfillment_status' => 'packed',
        ]);
    }

    public function markShipped(int $orderId): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders
            SET shipped_at = NOW(),
                fulfillment_status = :fulfillment_status,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $orderId,
            'fulfillment_status' => 'shipped',
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
