<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Repositories;

use PDO;

final class AiFitmentSuggestionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function listByProductId(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT afs.id,
                afs.product_id,
                afs.suggested_vehicle_id,
                afs.source_type,
                afs.source_reference_id,
                afs.confidence_label,
                afs.suggestion_reason,
                afs.input_snapshot,
                afs.status,
                afs.created_by_user_id,
                afs.reviewed_by_user_id,
                afs.created_at,
                afs.updated_at,
                afs.reviewed_at,
                v.make,
                v.model,
                v.generation,
                v.engine,
                v.year_from,
                v.year_to,
                v.is_active AS vehicle_is_active
            FROM ai_fitment_suggestions afs
            INNER JOIN vehicles v ON v.id = afs.suggested_vehicle_id
            WHERE afs.product_id = :product_id
            ORDER BY CASE WHEN afs.status = "pending" THEN 0 ELSE 1 END ASC, afs.id DESC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, product_id, suggested_vehicle_id, source_type, source_reference_id,
                confidence_label, suggestion_reason, input_snapshot, status,
                created_by_user_id, reviewed_by_user_id, created_at, updated_at, reviewed_at
            FROM ai_fitment_suggestions
            WHERE id = :id
            LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function findPendingDuplicate(int $productId, int $vehicleId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM ai_fitment_suggestions
            WHERE product_id = :product_id
              AND suggested_vehicle_id = :suggested_vehicle_id
              AND status = "pending"
            ORDER BY id DESC
            LIMIT 1');
        $stmt->execute([
            'product_id' => $productId,
            'suggested_vehicle_id' => $vehicleId,
        ]);
        $row = $stmt->fetch();

        return $row !== false ? (int) $row['id'] : null;
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO ai_fitment_suggestions (
                product_id,
                suggested_vehicle_id,
                source_type,
                source_reference_id,
                confidence_label,
                suggestion_reason,
                input_snapshot,
                status,
                created_by_user_id,
                created_at,
                updated_at
            ) VALUES (
                :product_id,
                :suggested_vehicle_id,
                :source_type,
                :source_reference_id,
                :confidence_label,
                :suggestion_reason,
                :input_snapshot,
                :status,
                :created_by_user_id,
                NOW(),
                NOW()
            )');

        $stmt->bindValue('product_id', $data['product_id'], PDO::PARAM_INT);
        $stmt->bindValue('suggested_vehicle_id', $data['suggested_vehicle_id'], PDO::PARAM_INT);
        $stmt->bindValue('source_type', $data['source_type']);
        $stmt->bindValue('source_reference_id', $data['source_reference_id'], $data['source_reference_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('confidence_label', $data['confidence_label']);
        $stmt->bindValue('suggestion_reason', $data['suggestion_reason']);
        $stmt->bindValue('input_snapshot', $data['input_snapshot']);
        $stmt->bindValue('status', $data['status']);
        $stmt->bindValue('created_by_user_id', $data['created_by_user_id'], $data['created_by_user_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function markReviewed(int $id, string $status, ?int $reviewedByUserId): void
    {
        $stmt = $this->pdo->prepare('UPDATE ai_fitment_suggestions
            SET status = :status,
                reviewed_by_user_id = :reviewed_by_user_id,
                reviewed_at = NOW(),
                updated_at = NOW()
            WHERE id = :id');
        $stmt->bindValue('status', $status);
        $stmt->bindValue('reviewed_by_user_id', $reviewedByUserId, $reviewedByUserId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
