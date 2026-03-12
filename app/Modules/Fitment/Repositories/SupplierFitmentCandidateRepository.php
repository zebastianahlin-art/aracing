<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Repositories;

use PDO;

final class SupplierFitmentCandidateRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function adminQueue(array $filters): array
    {
        $sql = 'SELECT sfc.id,
                       sfc.supplier_item_id,
                       sfc.product_id,
                       sfc.raw_make,
                       sfc.raw_model,
                       sfc.raw_generation,
                       sfc.raw_engine,
                       sfc.raw_year_from,
                       sfc.raw_year_to,
                       sfc.raw_text,
                       sfc.normalized_make,
                       sfc.normalized_model,
                       sfc.normalized_generation,
                       sfc.normalized_engine,
                       sfc.matched_vehicle_id,
                       sfc.confidence_label,
                       sfc.mapping_source,
                       sfc.mapping_note,
                       sfc.status,
                       sfc.review_note,
                       sfc.created_at,
                       sfc.reviewed_at,
                       sfc.reviewed_by_user_id,
                       si.supplier_sku,
                       si.supplier_title,
                       s.id AS supplier_id,
                       s.name AS supplier_name,
                       p.name AS product_name,
                       p.sku AS product_sku,
                       v.make AS vehicle_make,
                       v.model AS vehicle_model,
                       v.generation AS vehicle_generation,
                       v.engine AS vehicle_engine,
                       v.year_from AS vehicle_year_from,
                       v.year_to AS vehicle_year_to
                FROM supplier_fitment_candidates sfc
                INNER JOIN supplier_items si ON si.id = sfc.supplier_item_id
                LEFT JOIN suppliers s ON s.id = si.supplier_id
                LEFT JOIN products p ON p.id = sfc.product_id
                LEFT JOIN vehicles v ON v.id = sfc.matched_vehicle_id';

        $where = [];
        $params = [];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'sfc.status = :status';
            $params['status'] = $filters['status'];
        }

        if (($filters['vehicle_match'] ?? '') === 'with_vehicle') {
            $where[] = 'sfc.matched_vehicle_id IS NOT NULL';
        }
        if (($filters['vehicle_match'] ?? '') === 'without_vehicle') {
            $where[] = 'sfc.matched_vehicle_id IS NULL';
        }

        if (($filters['product_link'] ?? '') === 'without_product') {
            $where[] = 'sfc.product_id IS NULL';
        }

        if (($filters['supplier_id'] ?? '') !== '' && ctype_digit((string) $filters['supplier_id'])) {
            $where[] = 'si.supplier_id = :supplier_id';
            $params['supplier_id'] = (int) $filters['supplier_id'];
        }

        $query = trim((string) ($filters['query'] ?? ''));
        if ($query !== '') {
            $where[] = '(si.supplier_sku LIKE :query OR si.supplier_title LIKE :query OR p.name LIKE :query OR p.sku LIKE :query OR sfc.raw_text LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY CASE WHEN sfc.status = "pending" THEN 0 ELSE 1 END ASC, sfc.id DESC LIMIT 300';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }


    /** @param array<int,int> $productIds
     * @return array<int,int>
     */
    public function pendingCountByProductIds(array $productIds): array
    {
        $productIds = array_values(array_filter(array_map(static fn (int $id): int => max(0, $id), $productIds), static fn (int $id): bool => $id > 0));
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->pdo->prepare('SELECT product_id, COUNT(*) AS pending_count
            FROM supplier_fitment_candidates
            WHERE status = "pending"
              AND product_id IN (' . $placeholders . ')
            GROUP BY product_id');
        foreach ($productIds as $index => $productId) {
            $stmt->bindValue($index + 1, $productId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) ($row['product_id'] ?? 0)] = (int) ($row['pending_count'] ?? 0);
        }

        return $result;
    }



    /** @return array<int,array<string,mixed>> */
    public function byProductId(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, supplier_item_id, product_id, raw_make, raw_model, raw_generation, raw_engine,
                raw_year_from, raw_year_to, raw_text, normalized_make, normalized_model, normalized_generation, normalized_engine,
                matched_vehicle_id, confidence_label, mapping_source, mapping_note, status, review_note,
                created_at, reviewed_at, reviewed_by_user_id
            FROM supplier_fitment_candidates
            WHERE product_id = :product_id
            ORDER BY id DESC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, supplier_item_id, product_id, raw_make, raw_model, raw_generation, raw_engine,
                raw_year_from, raw_year_to, raw_text, normalized_make, normalized_model, normalized_generation, normalized_engine,
                matched_vehicle_id, confidence_label, mapping_source, mapping_note, status, review_note,
                created_at, reviewed_at, reviewed_by_user_id
            FROM supplier_fitment_candidates WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $duplicateId = $this->findDuplicateId($data);
        if ($duplicateId !== null) {
            return $duplicateId;
        }

        $stmt = $this->pdo->prepare('INSERT INTO supplier_fitment_candidates (
                supplier_item_id, product_id, raw_make, raw_model, raw_generation, raw_engine,
                raw_year_from, raw_year_to, raw_text, normalized_make, normalized_model, normalized_generation, normalized_engine,
                matched_vehicle_id, confidence_label, mapping_source, mapping_note,
                status, review_note, created_at, updated_at
            ) VALUES (
                :supplier_item_id, :product_id, :raw_make, :raw_model, :raw_generation, :raw_engine,
                :raw_year_from, :raw_year_to, :raw_text, :normalized_make, :normalized_model, :normalized_generation, :normalized_engine,
                :matched_vehicle_id, :confidence_label, :mapping_source, :mapping_note,
                :status, NULL, NOW(), NOW()
            )');

        $stmt->execute([
            'supplier_item_id' => $data['supplier_item_id'],
            'product_id' => $data['product_id'],
            'raw_make' => $data['raw_make'],
            'raw_model' => $data['raw_model'],
            'raw_generation' => $data['raw_generation'],
            'raw_engine' => $data['raw_engine'],
            'raw_year_from' => $data['raw_year_from'],
            'raw_year_to' => $data['raw_year_to'],
            'raw_text' => $data['raw_text'],
            'normalized_make' => $data['normalized_make'],
            'normalized_model' => $data['normalized_model'],
            'normalized_generation' => $data['normalized_generation'],
            'normalized_engine' => $data['normalized_engine'],
            'matched_vehicle_id' => $data['matched_vehicle_id'],
            'confidence_label' => $data['confidence_label'],
            'mapping_source' => $data['mapping_source'],
            'mapping_note' => $data['mapping_note'],
            'status' => $data['status'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function markReviewed(int $id, string $status, ?string $reviewNote, ?int $vehicleId, ?int $reviewedByUserId): void
    {
        $stmt = $this->pdo->prepare('UPDATE supplier_fitment_candidates
            SET status = :status,
                review_note = :review_note,
                matched_vehicle_id = :matched_vehicle_id,
                reviewed_at = NOW(),
                reviewed_by_user_id = :reviewed_by_user_id,
                updated_at = NOW()
            WHERE id = :id');

        $stmt->bindValue('status', $status);
        $stmt->bindValue('review_note', $reviewNote);
        $stmt->bindValue('matched_vehicle_id', $vehicleId, $vehicleId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('reviewed_by_user_id', $reviewedByUserId, $reviewedByUserId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /** @param array<string,mixed> $data */
    private function findDuplicateId(array $data): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM supplier_fitment_candidates
            WHERE supplier_item_id = :supplier_item_id
              AND (product_id <=> :product_id)
              AND (raw_make <=> :raw_make)
              AND (raw_model <=> :raw_model)
              AND (raw_generation <=> :raw_generation)
              AND (raw_engine <=> :raw_engine)
              AND (raw_year_from <=> :raw_year_from)
              AND (raw_year_to <=> :raw_year_to)
              AND (raw_text <=> :raw_text)
            ORDER BY id DESC
            LIMIT 1');
        $stmt->execute([
            'supplier_item_id' => $data['supplier_item_id'],
            'product_id' => $data['product_id'],
            'raw_make' => $data['raw_make'],
            'raw_model' => $data['raw_model'],
            'raw_generation' => $data['raw_generation'],
            'raw_engine' => $data['raw_engine'],
            'raw_year_from' => $data['raw_year_from'],
            'raw_year_to' => $data['raw_year_to'],
            'raw_text' => $data['raw_text'],
        ]);

        $row = $stmt->fetch();

        return $row !== false ? (int) $row['id'] : null;
    }
}
