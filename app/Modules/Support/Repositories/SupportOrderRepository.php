<?php

declare(strict_types=1);

namespace App\Modules\Support\Repositories;

use PDO;

final class SupportOrderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function findUserOrderById(int $userId, int $orderId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, order_number, user_id FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            'id' => $orderId,
            'user_id' => $userId,
        ]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, order_number, user_id FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $orderId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
