<?php

declare(strict_types=1);

namespace App\Modules\Admin\Repositories;

use PDO;

final class AiPricingInsightRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function listProductPricingSignals(array $filters = []): array
    {
        $sql = 'SELECT p.id,
                       p.name,
                       p.sku,
                       p.sale_price,
                       p.currency_code,
                       p.brand_id,
                       p.category_id,
                       b.name AS brand_name,
                       c.name AS category_name
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id';

        $where = ['p.is_active = 1'];
        $params = [];

        if (!empty($filters['brand_id']) && ctype_digit((string) $filters['brand_id'])) {
            $where[] = 'p.brand_id = :brand_id';
            $params['brand_id'] = (int) $filters['brand_id'];
        }

        if (!empty($filters['category_id']) && ctype_digit((string) $filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (($filters['linked_only'] ?? '') === '1') {
            $where[] = 'EXISTS (
                SELECT 1
                FROM product_supplier_links psl
                WHERE psl.product_id = p.id
            )';
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(p.name LIKE :search OR p.sku LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY p.updated_at DESC, p.id DESC LIMIT 2000';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<int,int> $productIds
     * @return array<int,array<string,mixed>>
     */
    public function listSupplierSignalsByProduct(array $productIds, ?int $supplierId = null): array
    {
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $sql = 'SELECT psl.product_id,
                       psl.supplier_item_id,
                       psl.is_primary,
                       psl.supplier_price_snapshot,
                       psl.supplier_sku_snapshot,
                       si.supplier_sku,
                       si.price AS supplier_item_price,
                       si.supplier_id,
                       s.name AS supplier_name,
                       l.supplier_price AS latest_snapshot_price,
                       l.captured_at AS latest_snapshot_captured_at,
                       prev.supplier_price AS previous_snapshot_price,
                       prev.captured_at AS previous_snapshot_captured_at
                FROM product_supplier_links psl
                INNER JOIN supplier_items si ON si.id = psl.supplier_item_id
                LEFT JOIN suppliers s ON s.id = psl.supplier_id
                LEFT JOIN (
                    SELECT sis.*
                    FROM supplier_item_snapshots sis
                    INNER JOIN (
                        SELECT supplier_item_id, MAX(id) AS max_id
                        FROM supplier_item_snapshots
                        GROUP BY supplier_item_id
                    ) mx ON mx.max_id = sis.id
                ) l ON l.supplier_item_id = psl.supplier_item_id
                LEFT JOIN supplier_item_snapshots prev ON prev.id = (
                    SELECT p2.id
                    FROM supplier_item_snapshots p2
                    WHERE p2.supplier_item_id = psl.supplier_item_id
                      AND l.id IS NOT NULL
                      AND p2.id < l.id
                    ORDER BY p2.id DESC
                    LIMIT 1
                )
                WHERE psl.product_id IN (' . $placeholders . ')';

        $bind = $productIds;

        if ($supplierId !== null) {
            $sql .= ' AND psl.supplier_id = ?';
            $bind[] = $supplierId;
        }

        $sql .= ' ORDER BY psl.product_id ASC, psl.is_primary DESC, psl.id ASC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($bind as $index => $value) {
            $stmt->bindValue($index + 1, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed>|null */
    public function activeTopPercentDiscount(): ?array
    {
        $sql = 'SELECT id, code, name, discount_value
                FROM discount_codes
                WHERE is_active = 1
                  AND discount_type = "percent"
                  AND (starts_at IS NULL OR starts_at <= NOW())
                  AND (ends_at IS NULL OR ends_at >= NOW())
                ORDER BY discount_value DESC, sort_order ASC, id ASC
                LIMIT 1';

        $row = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /** @return array<int,array{id:int,name:string}> */
    public function listSupplierOptions(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM suppliers ORDER BY name ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
