<?php

declare(strict_types=1);

namespace App\Modules\Import\Repositories;

use PDO;

final class AiProductImportDraftRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO ai_product_import_drafts (
            source_url, source_domain, source_type, status, parser_key, parser_version, extraction_strategy,
            quality_label, confidence_summary, missing_fields, quality_flags,
            import_title, import_brand, import_sku, import_short_description, import_description,
            import_price, import_currency, import_stock_text,
            import_image_urls, import_attributes, import_raw_text,
            ai_summary, ai_structured_payload, review_note,
            created_by_user_id, reviewed_by_user_id, reviewed_at
        ) VALUES (
            :source_url, :source_domain, :source_type, :status, :parser_key, :parser_version, :extraction_strategy,
            :quality_label, :confidence_summary, :missing_fields, :quality_flags,
            :import_title, :import_brand, :import_sku, :import_short_description, :import_description,
            :import_price, :import_currency, :import_stock_text,
            :import_image_urls, :import_attributes, :import_raw_text,
            :ai_summary, :ai_structured_payload, :review_note,
            :created_by_user_id, :reviewed_by_user_id, :reviewed_at
        )');

        $stmt->execute([
            'source_url' => $data['source_url'],
            'source_domain' => $data['source_domain'],
            'source_type' => $data['source_type'],
            'status' => $data['status'],
            'parser_key' => $data['parser_key'] ?? null,
            'parser_version' => $data['parser_version'] ?? null,
            'extraction_strategy' => $data['extraction_strategy'] ?? null,
            'quality_label' => $data['quality_label'] ?? null,
            'confidence_summary' => $data['confidence_summary'] ?? null,
            'missing_fields' => $data['missing_fields'] ?? null,
            'quality_flags' => $data['quality_flags'] ?? null,
            'import_title' => $data['import_title'],
            'import_brand' => $data['import_brand'],
            'import_sku' => $data['import_sku'],
            'import_short_description' => $data['import_short_description'],
            'import_description' => $data['import_description'],
            'import_price' => $data['import_price'],
            'import_currency' => $data['import_currency'],
            'import_stock_text' => $data['import_stock_text'],
            'import_image_urls' => $data['import_image_urls'],
            'import_attributes' => $data['import_attributes'],
            'import_raw_text' => $data['import_raw_text'],
            'ai_summary' => $data['ai_summary'],
            'ai_structured_payload' => $data['ai_structured_payload'],
            'review_note' => $data['review_note'],
            'created_by_user_id' => $data['created_by_user_id'],
            'reviewed_by_user_id' => $data['reviewed_by_user_id'],
            'reviewed_at' => $data['reviewed_at'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int,array<string,mixed>> */
    public function list(array $filters = []): array
    {
        $sql = 'SELECT id, source_url, source_domain, source_type, status, parser_key, extraction_strategy,
                       quality_label, missing_fields, quality_flags,
                       import_title, import_brand, import_sku, created_at, reviewed_at
                FROM ai_product_import_drafts
                WHERE 1=1';

        $params = [];
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }

        $qualityLabel = trim((string) ($filters['quality_label'] ?? ''));
        if ($qualityLabel !== '') {
            $sql .= ' AND quality_label = :quality_label';
            $params['quality_label'] = $qualityLabel;
        }

        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT 300';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ai_product_import_drafts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function updateStatus(int $id, string $status, ?int $reviewedByUserId = null, ?string $reviewNote = null): void
    {
        $reviewedAt = null;
        if (in_array($status, ['reviewed', 'rejected', 'imported'], true)) {
            $reviewedAt = date('Y-m-d H:i:s');
        }

        $stmt = $this->pdo->prepare('UPDATE ai_product_import_drafts
                                     SET status = :status,
                                         reviewed_by_user_id = :reviewed_by_user_id,
                                         review_note = :review_note,
                                         reviewed_at = :reviewed_at,
                                         updated_at = NOW()
                                     WHERE id = :id');

        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'reviewed_by_user_id' => $reviewedByUserId,
            'review_note' => $reviewNote,
            'reviewed_at' => $reviewedAt,
        ]);
    }

    public function markHandedOff(int $id, string $targetType, int $targetId, ?int $handedOffByUserId = null): bool
    {
        $stmt = $this->pdo->prepare('UPDATE ai_product_import_drafts
                                     SET handed_off_at = NOW(),
                                         handed_off_by_user_id = :handed_off_by_user_id,
                                         handoff_target_type = :handoff_target_type,
                                         handoff_target_id = :handoff_target_id,
                                         updated_at = NOW()
                                     WHERE id = :id
                                       AND handed_off_at IS NULL');

        $stmt->execute([
            'id' => $id,
            'handed_off_by_user_id' => $handedOffByUserId,
            'handoff_target_type' => $targetType,
            'handoff_target_id' => $targetId,
        ]);

        return $stmt->rowCount() > 0;
    }

    /** @param array<string,mixed> $quality */
    public function updateQualityMetadata(int $id, array $quality): void
    {
        $stmt = $this->pdo->prepare('UPDATE ai_product_import_drafts
                                     SET quality_label = :quality_label,
                                         confidence_summary = :confidence_summary,
                                         missing_fields = :missing_fields,
                                         quality_flags = :quality_flags,
                                         updated_at = NOW()
                                     WHERE id = :id');

        $stmt->execute([
            'id' => $id,
            'quality_label' => $quality['quality_label'] ?? null,
            'confidence_summary' => $quality['confidence_summary'] ?? null,
            'missing_fields' => $quality['missing_fields'] ?? null,
            'quality_flags' => $quality['quality_flags'] ?? null,
        ]);
    }
}
