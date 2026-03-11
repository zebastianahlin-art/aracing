<?php

declare(strict_types=1);

namespace App\Modules\Support\Repositories;

use PDO;

final class SupportCaseRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO support_cases (
            case_number, user_id, order_id, email, name, phone, subject, message,
            status, priority, source, admin_note, closed_at, created_at, updated_at
        ) VALUES (
            :case_number, :user_id, :order_id, :email, :name, :phone, :subject, :message,
            :status, :priority, :source, :admin_note, :closed_at, NOW(), NOW()
        )');

        $stmt->execute([
            'case_number' => $data['case_number'],
            'user_id' => $data['user_id'],
            'order_id' => $data['order_id'],
            'email' => $data['email'],
            'name' => $data['name'],
            'phone' => $data['phone'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => $data['status'],
            'priority' => $data['priority'],
            'source' => $data['source'],
            'admin_note' => $data['admin_note'],
            'closed_at' => $data['closed_at'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function existsByCaseNumber(string $caseNumber): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM support_cases WHERE case_number = :case_number LIMIT 1');
        $stmt->execute(['case_number' => $caseNumber]);

        return $stmt->fetch() !== false;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT sc.*, o.order_number
            FROM support_cases sc
            LEFT JOIN orders o ON o.id = sc.order_id
            WHERE sc.id = :id
            LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT sc.id, sc.case_number, sc.subject, sc.status, sc.created_at, sc.order_id, o.order_number
            FROM support_cases sc
            LEFT JOIN orders o ON o.id = sc.order_id
            WHERE sc.user_id = :user_id
            ORDER BY sc.created_at DESC, sc.id DESC');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listAdmin(array $filters): array
    {
        $conditions = [];
        $params = [];

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $conditions[] = 'sc.status = :status';
            $params['status'] = $status;
        }

        $source = trim((string) ($filters['source'] ?? ''));
        if ($source !== '') {
            $conditions[] = 'sc.source = :source';
            $params['source'] = $source;
        }

        $sql = 'SELECT sc.id, sc.case_number, sc.subject, sc.email, sc.status, sc.priority, sc.source, sc.created_at, sc.order_id,
                o.order_number
            FROM support_cases sc
            LEFT JOIN orders o ON o.id = sc.order_id';

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY sc.created_at DESC, sc.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status, ?string $closedAt): void
    {
        $stmt = $this->pdo->prepare('UPDATE support_cases
            SET status = :status,
                closed_at = :closed_at,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'closed_at' => $closedAt,
        ]);
    }

    public function updatePriority(int $id, ?string $priority): void
    {
        $stmt = $this->pdo->prepare('UPDATE support_cases SET priority = :priority, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'priority' => $priority,
        ]);
    }

    public function updateAdminNote(int $id, ?string $adminNote): void
    {
        $stmt = $this->pdo->prepare('UPDATE support_cases SET admin_note = :admin_note, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'admin_note' => $adminNote,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listForOrder(int $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, case_number, subject, status, source, created_at
            FROM support_cases
            WHERE order_id = :order_id
            ORDER BY created_at DESC, id DESC');
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }
}
