<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Repositories;

use PDO;

final class RefillNeedRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listRefillNeeds(array $filters = []): array
    {
        $sql = 'SELECT p.id AS product_id,
                       p.name AS product_name,
                       p.sku,
                       p.stock_status,
                       p.stock_quantity,
                       p.is_active,
                       psl.supplier_id,
                       psl.supplier_item_id,
                       s.name AS supplier_name,
                       psl.supplier_sku_snapshot,
                       psl.supplier_title_snapshot,
                       psl.supplier_price_snapshot,
                       psl.supplier_stock_snapshot
                FROM products p
                INNER JOIN product_supplier_links psl ON psl.product_id = p.id AND psl.is_primary = 1
                INNER JOIN suppliers s ON s.id = psl.supplier_id
                WHERE p.is_active = 1
                  AND (p.stock_quantity IS NULL OR p.stock_quantity <= 2)
                  AND psl.supplier_stock_snapshot > 0';

        $params = [];
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $sql .= ' AND (p.name LIKE :search OR p.sku LIKE :search OR psl.supplier_sku_snapshot LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $supplierId = (int) ($filters['supplier_id'] ?? 0);
        if ($supplierId > 0) {
            $sql .= ' AND psl.supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }

        $sql .= ' ORDER BY psl.supplier_stock_snapshot DESC, p.updated_at DESC, p.id DESC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @param array<int, int> $productIds
     *  @return array<int, array<string, mixed>>
     */
    public function listByProductIds(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->pdo->prepare(
            'SELECT p.id AS product_id,
                    p.name AS product_name,
                    p.sku,
                    p.stock_quantity,
                    psl.supplier_id,
                    psl.supplier_item_id,
                    s.name AS supplier_name,
                    psl.supplier_sku_snapshot,
                    psl.supplier_title_snapshot,
                    psl.supplier_price_snapshot,
                    psl.supplier_stock_snapshot
             FROM products p
             INNER JOIN product_supplier_links psl ON psl.product_id = p.id AND psl.is_primary = 1
             LEFT JOIN suppliers s ON s.id = psl.supplier_id
             WHERE p.id IN (' . $placeholders . ')'
        );

        foreach (array_values($productIds) as $index => $productId) {
            $stmt->bindValue($index + 1, $productId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }
}
