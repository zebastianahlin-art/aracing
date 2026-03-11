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
                exported_at
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
                NULL
            )');

        $stmt->bindValue('supplier_id', $data['supplier_id'], $data['supplier_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('status', $data['status'], PDO::PARAM_STR);
        $stmt->bindValue('order_number', $data['order_number'], PDO::PARAM_STR);
        $stmt->bindValue('supplier_name_snapshot', $data['supplier_name_snapshot'], $data['supplier_name_snapshot'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue('supplier_reference', $data['supplier_reference'], $data['supplier_reference'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue('internal_note', $data['internal_note'], $data['internal_note'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue('created_by_user_id', $data['created_by_user_id'], $data['created_by_user_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int,array<string,mixed>> */
    public function listAll(?string $status = null): array
    {
        $sql = 'SELECT pod.*, COUNT(podi.id) AS item_count
                FROM purchase_order_drafts pod
                LEFT JOIN purchase_order_draft_items podi ON podi.purchase_order_draft_id = pod.id';

        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE pod.status = :status';
            $params['status'] = $status;
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
                    updated_at = NOW()
                WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => 'cancelled']);
    }

    public function countCreatedOnDate(string $date): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM purchase_order_drafts WHERE DATE(created_at) = :date');
        $stmt->execute(['date' => $date]);

        return (int) $stmt->fetchColumn();
    }
}
