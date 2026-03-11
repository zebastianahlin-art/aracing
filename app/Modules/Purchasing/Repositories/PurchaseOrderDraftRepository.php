<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Repositories;

use PDO;

final class PurchaseOrderDraftRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO purchase_order_drafts (
                supplier_id,
                status,
                order_number,
                supplier_name_snapshot,
                supplier_reference,
                internal_note,
                created_by_user_id,
                created_at,
                updated_at,
                exported_at,
                receiving_status,
                received_at
            ) VALUES (
                :supplier_id,
                :status,
                :order_number,
                :supplier_name_snapshot,
                :supplier_reference,
                :internal_note,
                :created_by_user_id,
                NOW(),
                NOW(),
                NULL,
                :receiving_status,
                NULL
            )');

        $stmt->bindValue('supplier_id', $data['supplier_id'], $data['supplier_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('status', $data['status'], PDO::PARAM_STR);
        $stmt->bindValue('order_number', $data['order_number'], PDO::PARAM_STR);
        $stmt->bindValue('supplier_name_snapshot', $data['supplier_name_snapshot'], $data['supplier_name_snapshot'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue('supplier_reference', $data['supplier_reference'], $data['supplier_reference'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue('internal_note', $data['internal_note'], $data['internal_note'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue('created_by_user_id', $data['created_by_user_id'], $data['created_by_user_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('receiving_status', $data['receiving_status'] ?? 'not_received', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int,array<string,mixed>> */
    public function listAll(?string $status = null, ?string $receivingStatus = null): array
    {
        $sql = 'SELECT pod.*, COUNT(podi.id) AS item_count
                FROM purchase_order_drafts pod
                LEFT JOIN purchase_order_draft_items podi ON podi.purchase_order_draft_id = pod.id';

        $params = [];
        $conditions = [];

        if ($status !== null) {
            $conditions[] = 'pod.status = :status';
            $params['status'] = $status;
        }

        if ($receivingStatus !== null) {
            $conditions[] = 'pod.receiving_status = :receiving_status';
            $params['receiving_status'] = $receivingStatus;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY pod.id ORDER BY pod.created_at DESC, pod.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM purchase_order_drafts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function updateInternalNote(int $id, ?string $internalNote): void
    {
        $stmt = $this->pdo->prepare('UPDATE purchase_order_drafts
                SET internal_note = :internal_note,
                    updated_at = NOW()
                WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('internal_note', $internalNote, $internalNote !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
    }

    public function markExported(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE purchase_order_drafts
                SET status = :status,
                    exported_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => 'exported']);
    }

    public function markCancelled(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE purchase_order_drafts
                SET status = :status,
                    receiving_status = :receiving_status,
                    updated_at = NOW()
                WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => 'cancelled', 'receiving_status' => 'cancelled']);
    }

    public function updateReceivingState(int $id, string $receivingStatus, ?string $receivedAt): void
    {
        $stmt = $this->pdo->prepare('UPDATE purchase_order_drafts
                SET receiving_status = :receiving_status,
                    received_at = :received_at,
                    updated_at = NOW()
                WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('receiving_status', $receivingStatus, PDO::PARAM_STR);
        $stmt->bindValue('received_at', $receivedAt, $receivedAt !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
    }

    public function countCreatedOnDate(string $date): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM purchase_order_drafts WHERE DATE(created_at) = :date');
        $stmt->execute(['date' => $date]);

        return (int) $stmt->fetchColumn();
    }
}
