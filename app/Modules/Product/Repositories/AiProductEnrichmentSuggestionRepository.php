<?php

declare(strict_types=1);

namespace App\Modules\Product\Repositories;

use PDO;

final class AiProductEnrichmentSuggestionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO ai_product_enrichment_suggestions (
            product_id, suggestion_type, source_context, input_snapshot,
            suggested_category_id,
            suggested_title, suggested_short_description, suggested_description,
            suggested_attributes, suggested_seo_title, suggested_meta_description,
            ai_summary, status, created_by_user_id, reviewed_by_user_id, reviewed_at
        ) VALUES (
            :product_id, :suggestion_type, :source_context, :input_snapshot,
            :suggested_category_id,
            :suggested_title, :suggested_short_description, :suggested_description,
            :suggested_attributes, :suggested_seo_title, :suggested_meta_description,
            :ai_summary, :status, :created_by_user_id, :reviewed_by_user_id, :reviewed_at
        )');

        $stmt->execute([
            'product_id' => $data['product_id'],
            'suggestion_type' => $data['suggestion_type'],
            'source_context' => $data['source_context'] ?? null,
            'input_snapshot' => $data['input_snapshot'] ?? null,
            'suggested_category_id' => $data['suggested_category_id'] ?? null,
            'suggested_title' => $data['suggested_title'] ?? null,
            'suggested_short_description' => $data['suggested_short_description'] ?? null,
            'suggested_description' => $data['suggested_description'] ?? null,
            'suggested_attributes' => $data['suggested_attributes'] ?? null,
            'suggested_seo_title' => $data['suggested_seo_title'] ?? null,
            'suggested_meta_description' => $data['suggested_meta_description'] ?? null,
            'ai_summary' => $data['ai_summary'] ?? null,
            'status' => $data['status'],
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'reviewed_by_user_id' => $data['reviewed_by_user_id'] ?? null,
            'reviewed_at' => $data['reviewed_at'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function findPendingByProductAndType(int $productId, string $suggestionType): ?array
    {
        $stmt = $this->pdo->prepare('SELECT *
            FROM ai_product_enrichment_suggestions
            WHERE product_id = :product_id
              AND suggestion_type = :suggestion_type
              AND status = "pending"
            ORDER BY created_at DESC, id DESC
            LIMIT 1');
        $stmt->execute([
            'product_id' => $productId,
            'suggestion_type' => $suggestionType,
        ]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int,array<string,mixed>> */
    public function listForProduct(int $productId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare('SELECT *
            FROM ai_product_enrichment_suggestions
            WHERE product_id = :product_id
            ORDER BY created_at DESC, id DESC
            LIMIT :limit');
        $stmt->bindValue('product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue('limit', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int,array<string,mixed>> */
    public function listForProductByType(int $productId, string $suggestionType, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare('SELECT *
            FROM ai_product_enrichment_suggestions
            WHERE product_id = :product_id AND suggestion_type = :suggestion_type
            ORDER BY created_at DESC, id DESC
            LIMIT :limit');
        $stmt->bindValue('product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue('suggestion_type', $suggestionType, PDO::PARAM_STR);
        $stmt->bindValue('limit', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ai_product_enrichment_suggestions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function markApplied(int $id, ?int $reviewedByUserId = null): bool
    {
        $stmt = $this->pdo->prepare('UPDATE ai_product_enrichment_suggestions
            SET status = "applied", reviewed_by_user_id = :reviewed_by_user_id, reviewed_at = NOW(), updated_at = NOW()
            WHERE id = :id AND status = "pending"');
        $stmt->execute([
            'id' => $id,
            'reviewed_by_user_id' => $reviewedByUserId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function markRejected(int $id, ?int $reviewedByUserId = null): bool
    {
        $stmt = $this->pdo->prepare('UPDATE ai_product_enrichment_suggestions
            SET status = "rejected", reviewed_by_user_id = :reviewed_by_user_id, reviewed_at = NOW(), updated_at = NOW()
            WHERE id = :id AND status = "pending"');
        $stmt->execute([
            'id' => $id,
            'reviewed_by_user_id' => $reviewedByUserId,
        ]);

        return $stmt->rowCount() > 0;
    }
}
