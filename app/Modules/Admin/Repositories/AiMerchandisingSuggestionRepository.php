<?php

declare(strict_types=1);

namespace App\Modules\Admin\Repositories;

use PDO;

final class AiMerchandisingSuggestionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO ai_merchandising_suggestions
            (suggestion_type, title, description, suggested_product_ids, context_snapshot, status, created_at, reviewed_by_user_id, reviewed_at)
            VALUES
            (:suggestion_type, :title, :description, :suggested_product_ids, :context_snapshot, :status, NOW(), :reviewed_by_user_id, :reviewed_at)');
        $stmt->execute([
            'suggestion_type' => $data['suggestion_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'suggested_product_ids' => $data['suggested_product_ids'],
            'context_snapshot' => $data['context_snapshot'],
            'status' => $data['status'] ?? 'pending',
            'reviewed_by_user_id' => $data['reviewed_by_user_id'] ?? null,
            'reviewed_at' => $data['reviewed_at'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int,array<string,mixed>> */
    public function listAll(?string $status = null): array
    {
        $sql = 'SELECT * FROM ai_merchandising_suggestions';
        $params = [];
        if ($status !== null && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $sql .= ' WHERE status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ai_merchandising_suggestions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function updateStatus(int $id, string $status, ?int $reviewedByUserId = null): void
    {
        $stmt = $this->pdo->prepare('UPDATE ai_merchandising_suggestions
            SET status = :status,
                reviewed_by_user_id = :reviewed_by_user_id,
                reviewed_at = CASE WHEN :status = "pending" THEN NULL ELSE NOW() END
            WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('status', $status);
        $stmt->bindValue('reviewed_by_user_id', $reviewedByUserId, $reviewedByUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
    }
}
