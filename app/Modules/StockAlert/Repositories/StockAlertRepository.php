<?php

declare(strict_types=1);

namespace App\Modules\StockAlert\Repositories;

use PDO;

final class StockAlertRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string,mixed>|null */
    public function findActiveByProductAndEmail(int $productId, string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stock_alert_subscriptions WHERE product_id = :product_id AND email = :email AND status = :status LIMIT 1');
        $stmt->execute([
            'product_id' => $productId,
            'email' => $email,
            'status' => 'active',
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /** @return array<int,array<string,mixed>> */
    public function activeForProduct(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stock_alert_subscriptions WHERE product_id = :product_id AND status = :status ORDER BY subscribed_at ASC, id ASC');
        $stmt->execute([
            'product_id' => $productId,
            'status' => 'active',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(int $productId, ?int $userId, string $email): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO stock_alert_subscriptions (product_id, user_id, email, status, subscribed_at, created_at, updated_at)
            VALUES (:product_id, :user_id, :email, :status, NOW(), NOW(), NOW())');
        $stmt->bindValue('product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue('user_id', $userId, $userId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('email', $email);
        $stmt->bindValue('status', 'active');
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function markNotified(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE stock_alert_subscriptions
            SET status = :status, notified_at = NOW(), updated_at = NOW()
            WHERE id = :id AND status = :active_status');
        $stmt->execute([
            'id' => $id,
            'status' => 'notified',
            'active_status' => 'active',
        ]);
    }

    public function markUnsubscribed(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE stock_alert_subscriptions
            SET status = :status, unsubscribed_at = NOW(), updated_at = NOW()
            WHERE id = :id AND user_id = :user_id AND status = :active_status');
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
            'status' => 'unsubscribed',
            'active_status' => 'active',
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT sas.id,
                       sas.product_id,
                       sas.user_id,
                       sas.email,
                       sas.status,
                       sas.subscribed_at,
                       sas.notified_at,
                       sas.unsubscribed_at,
                       p.name AS product_name,
                       p.slug AS product_slug,
                       p.is_active,
                       p.is_search_hidden
                FROM stock_alert_subscriptions sas
                INNER JOIN products p ON p.id = sas.product_id
                WHERE sas.user_id = :user_id
                ORDER BY sas.subscribed_at DESC, sas.id DESC');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public function countActiveSubscriptions(): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM stock_alert_subscriptions WHERE status = :status');
        $stmt->execute(['status' => 'active']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['total'] ?? 0);
    }

}
