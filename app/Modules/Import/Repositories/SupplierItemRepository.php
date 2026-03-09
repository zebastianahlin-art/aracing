<?php

declare(strict_types=1);

namespace App\Modules\Import\Repositories;

use PDO;

final class SupplierItemRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $mappedRow
     * @param array<string, mixed> $rawRow
     */
    public function upsertFromImport(int $supplierId, int $runId, array $mappedRow, array $rawRow): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO supplier_items (supplier_id, supplier_sku, supplier_title, supplier_name, raw_payload, import_run_id, price, stock_qty, created_at, updated_at)
             VALUES (:supplier_id, :supplier_sku, :supplier_title, :supplier_name, :raw_payload, :import_run_id, :price, :stock_qty, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 supplier_title = VALUES(supplier_title),
                 supplier_name = VALUES(supplier_name),
                 raw_payload = VALUES(raw_payload),
                 import_run_id = VALUES(import_run_id),
                 price = VALUES(price),
                 stock_qty = VALUES(stock_qty),
                 updated_at = NOW()'
        );

        $title = (string) ($mappedRow['supplier_title'] ?? $mappedRow['supplier_name'] ?? '');

        $stmt->execute([
            'supplier_id' => $supplierId,
            'supplier_sku' => (string) ($mappedRow['supplier_sku'] ?? ''),
            'supplier_title' => $title !== '' ? $title : null,
            'supplier_name' => $title !== '' ? $title : null,
            'raw_payload' => json_encode($rawRow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'import_run_id' => $runId,
            'price' => $this->toNullableDecimal($mappedRow['price'] ?? null),
            'stock_qty' => $this->toNullableInt($mappedRow['stock_qty'] ?? null),
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT si.id, si.supplier_id, si.import_run_id, si.supplier_sku, si.supplier_title, si.price, si.stock_qty,
                    si.review_status, si.matched_at, si.last_reviewed_at, s.name AS supplier_name
             FROM supplier_items si
             LEFT JOIN suppliers s ON s.id = si.supplier_id
             WHERE si.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function updateReviewStatus(int $id, ?string $status, bool $touchReviewedAt): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE supplier_items
             SET review_status = :review_status,
                 last_reviewed_at = CASE WHEN :touch_reviewed = 1 THEN NOW() ELSE last_reviewed_at END,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'review_status' => $status,
            'touch_reviewed' => $touchReviewedAt ? 1 : 0,
        ]);
    }

    public function setMatchedAt(int $id, bool $isMatched): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE supplier_items
             SET matched_at = CASE WHEN :is_matched = 1 THEN NOW() ELSE NULL END,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'is_matched' => $isMatched ? 1 : 0,
        ]);
    }

    private function toNullableDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (int) $normalized;
    }
}
