<?php

declare(strict_types=1);

namespace App\Modules\Import\Repositories;

use PDO;

final class SupplierItemSnapshotRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function capture(int $supplierItemId, int $importRunId, ?string $price, ?int $stockQuantity): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO supplier_item_snapshots (
                supplier_item_id, import_run_id, import_batch_key, supplier_price, currency, stock_quantity, stock_status, is_available, captured_at
             ) VALUES (
                :supplier_item_id, :import_run_id, :import_batch_key, :supplier_price, :currency, :stock_quantity, :stock_status, :is_available, NOW()
             )'
        );

        $stmt->execute([
            'supplier_item_id' => $supplierItemId,
            'import_run_id' => $importRunId,
            'import_batch_key' => $this->batchKey($importRunId, $supplierItemId),
            'supplier_price' => $price,
            'currency' => 'SEK',
            'stock_quantity' => $stockQuantity,
            'stock_status' => $this->resolveStockStatus($stockQuantity),
            'is_available' => $this->resolveAvailability($stockQuantity),
        ]);
    }

    private function resolveStockStatus(?int $stockQuantity): ?string
    {
        if ($stockQuantity === null) {
            return null;
        }

        return $stockQuantity > 0 ? 'in_stock' : 'out_of_stock';
    }

    private function resolveAvailability(?int $stockQuantity): ?int
    {
        if ($stockQuantity === null) {
            return null;
        }

        return $stockQuantity > 0 ? 1 : 0;
    }

    private function batchKey(int $importRunId, int $supplierItemId): string
    {
        return 'run:' . $importRunId . ':item:' . $supplierItemId;
    }
}
