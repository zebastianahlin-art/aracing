<?php

declare(strict_types=1);

namespace App\Modules\Product\Repositories;

use PDO;

final class ProductSupplierLinkRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function primaryForProduct(int $productId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT psl.id,
                    psl.product_id,
                    psl.supplier_item_id,
                    psl.supplier_id,
                    psl.is_primary,
                    psl.supplier_sku_snapshot,
                    psl.supplier_title_snapshot,
                    psl.supplier_price_snapshot,
                    psl.supplier_stock_snapshot,
                    s.name AS supplier_name
             FROM product_supplier_links psl
             LEFT JOIN suppliers s ON s.id = psl.supplier_id
             WHERE psl.product_id = :product_id AND psl.is_primary = 1
             ORDER BY psl.id DESC
             LIMIT 1'
        );

        $stmt->execute(['product_id' => $productId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @param array<string, mixed> $data */
    public function upsertPrimary(int $productId, array $data): void
    {
        $this->pdo->prepare('UPDATE product_supplier_links SET is_primary = 0, updated_at = NOW() WHERE product_id = :product_id')
            ->execute(['product_id' => $productId]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO product_supplier_links (
                product_id,
                supplier_item_id,
                supplier_id,
                is_primary,
                supplier_sku_snapshot,
                supplier_title_snapshot,
                supplier_price_snapshot,
                supplier_stock_snapshot,
                created_at,
                updated_at
            ) VALUES (
                :product_id,
                :supplier_item_id,
                :supplier_id,
                :is_primary,
                :supplier_sku_snapshot,
                :supplier_title_snapshot,
                :supplier_price_snapshot,
                :supplier_stock_snapshot,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                supplier_id = VALUES(supplier_id),
                is_primary = VALUES(is_primary),
                supplier_sku_snapshot = VALUES(supplier_sku_snapshot),
                supplier_title_snapshot = VALUES(supplier_title_snapshot),
                supplier_price_snapshot = VALUES(supplier_price_snapshot),
                supplier_stock_snapshot = VALUES(supplier_stock_snapshot),
                updated_at = NOW()'
        );

        $stmt->execute([
            'product_id' => $productId,
            'supplier_item_id' => $data['supplier_item_id'],
            'supplier_id' => $data['supplier_id'],
            'is_primary' => (int) $data['is_primary'],
            'supplier_sku_snapshot' => $data['supplier_sku_snapshot'],
            'supplier_title_snapshot' => $data['supplier_title_snapshot'],
            'supplier_price_snapshot' => $data['supplier_price_snapshot'],
            'supplier_stock_snapshot' => $data['supplier_stock_snapshot'],
        ]);
    }

    public function clearByProductId(int $productId): void
    {
        $this->pdo->prepare('DELETE FROM product_supplier_links WHERE product_id = :product_id')
            ->execute(['product_id' => $productId]);
    }
}
