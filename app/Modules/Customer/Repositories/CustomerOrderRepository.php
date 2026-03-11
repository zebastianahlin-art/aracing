<?php

declare(strict_types=1);

namespace App\Modules\Customer\Repositories;

use PDO;

final class CustomerOrderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, order_number, order_status, payment_status, fulfillment_status, total_amount, currency_code, created_at
            FROM orders
            WHERE user_id = :user_id
            ORDER BY created_at DESC, id DESC');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findForUserById(int $userId, int $orderId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            'id' => $orderId,
            'user_id' => $userId,
        ]);
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
}
