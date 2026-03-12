<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use PDO;

final class SearchQuerySuggestionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO search_query_suggestions
            (source_query, suggestion_type, suggested_value, explanation, status, created_at, reviewed_by_user_id, reviewed_at)
            VALUES (:source_query, :suggestion_type, :suggested_value, :explanation, :status, NOW(), :reviewed_by_user_id, :reviewed_at)');
        $stmt->execute([
            'source_query' => mb_substr((string) ($data['source_query'] ?? ''), 0, 255),
            'suggestion_type' => mb_substr((string) ($data['suggestion_type'] ?? ''), 0, 60),
            'suggested_value' => mb_substr((string) ($data['suggested_value'] ?? ''), 0, 255),
            'explanation' => $data['explanation'] ?? null,
            'status' => (string) ($data['status'] ?? 'pending'),
            'reviewed_by_user_id' => $data['reviewed_by_user_id'] ?? null,
            'reviewed_at' => $data['reviewed_at'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int,array<string,mixed>> */
    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM search_query_suggestions ORDER BY FIELD(status, "pending", "approved", "rejected"), created_at DESC, id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int,array<string,mixed>> */
    public function listPendingForQuery(string $sourceQuery): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM search_query_suggestions WHERE source_query = :source_query AND status = "pending" ORDER BY created_at DESC');
        $stmt->execute(['source_query' => mb_substr($sourceQuery, 0, 255)]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM search_query_suggestions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function markReviewed(int $id, string $status, ?int $reviewedByUserId): void
    {
        $stmt = $this->pdo->prepare('UPDATE search_query_suggestions
            SET status = :status, reviewed_by_user_id = :reviewed_by_user_id, reviewed_at = NOW()
            WHERE id = :id');
        $stmt->bindValue('status', $status);
        $stmt->bindValue('reviewed_by_user_id', $reviewedByUserId, $reviewedByUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function existsPending(string $sourceQuery, string $type, string $value): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM search_query_suggestions
            WHERE source_query = :source_query AND suggestion_type = :suggestion_type AND suggested_value = :suggested_value AND status = "pending"
            LIMIT 1');
        $stmt->execute([
            'source_query' => mb_substr($sourceQuery, 0, 255),
            'suggestion_type' => mb_substr($type, 0, 60),
            'suggested_value' => mb_substr($value, 0, 255),
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}
