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
