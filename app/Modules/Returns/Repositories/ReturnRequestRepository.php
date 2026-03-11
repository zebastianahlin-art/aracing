<?php

declare(strict_types=1);

namespace App\Modules\Returns\Repositories;

use PDO;

final class ReturnRequestRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT rr.*, o.order_number
            FROM return_requests rr
            INNER JOIN orders o ON o.id = rr.order_id
            WHERE rr.user_id = :user_id
            ORDER BY rr.requested_at DESC, rr.id DESC');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listAdmin(array $filters): array
    {
        $sql = 'SELECT rr.*, o.order_number, o.customer_email,
                CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) AS customer_name
            FROM return_requests rr
            INNER JOIN orders o ON o.id = rr.order_id
            LEFT JOIN users u ON u.id = rr.user_id';

        $where = [];
        $params = [];
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'rr.status = :status';
            $params['status'] = $status;
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY rr.requested_at DESC, rr.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT rr.*, o.order_number, o.user_id AS order_user_id,
            o.customer_email, o.customer_first_name, o.customer_last_name
            FROM return_requests rr
            INNER JOIN orders o ON o.id = rr.order_id
            WHERE rr.id = :id
            LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findByIdForUser(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT rr.*, o.order_number
            FROM return_requests rr
            INNER JOIN orders o ON o.id = rr.order_id
            WHERE rr.id = :id AND rr.user_id = :user_id
            LIMIT 1');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listForOrder(int $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, return_number, status, requested_at
            FROM return_requests
            WHERE order_id = :order_id
            ORDER BY requested_at DESC, id DESC');
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO return_requests
            (order_id, user_id, return_number, status, reason_code, customer_comment, admin_note, requested_at, approved_at, received_at, closed_at, created_at, updated_at)
            VALUES
            (:order_id, :user_id, :return_number, :status, :reason_code, :customer_comment, :admin_note, :requested_at, :approved_at, :received_at, :closed_at, NOW(), NOW())');
        $stmt->execute([
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
            'return_number' => $data['return_number'],
            'status' => $data['status'],
            'reason_code' => $data['reason_code'],
            'customer_comment' => $data['customer_comment'],
            'admin_note' => $data['admin_note'],
            'requested_at' => $data['requested_at'],
            'approved_at' => $data['approved_at'],
            'received_at' => $data['received_at'],
            'closed_at' => $data['closed_at'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status, array $timestamps): void
    {
        $stmt = $this->pdo->prepare('UPDATE return_requests
            SET status = :status,
                approved_at = :approved_at,
                received_at = :received_at,
                closed_at = :closed_at,
                updated_at = NOW()
            WHERE id = :id');

        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'approved_at' => $timestamps['approved_at'] ?? null,
            'received_at' => $timestamps['received_at'] ?? null,
            'closed_at' => $timestamps['closed_at'] ?? null,
        ]);
    }

    public function updateAdminNote(int $id, ?string $note): void
    {
        $stmt = $this->pdo->prepare('UPDATE return_requests
            SET admin_note = :admin_note,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'admin_note' => $note,
        ]);
    }
}
