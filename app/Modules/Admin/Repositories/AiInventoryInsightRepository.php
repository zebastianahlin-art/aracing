<?php

declare(strict_types=1);

namespace App\Modules\Admin\Repositories;

use PDO;

final class AiInventoryInsightRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function listInventorySignals(array $filters = []): array
    {
        $sql = 'SELECT p.id,
                       p.name,
                       p.sku,
                       p.stock_quantity,
                       p.stock_status,
                       p.backorder_allowed,
                       p.is_active,
                       p.brand_id,
                       p.category_id,
                       b.name AS brand_name,
                       c.name AS category_name,
                       psl.supplier_id,
                       s.name AS supplier_name,
                       COALESCE(sales_30.qty, 0) AS sold_last_30_days,
                       COALESCE(sales_60.qty, 0) AS sold_last_60_days,
                       sales_60.last_sale_at,
                       COALESCE(alerts.active_count, 0) AS active_stock_alerts,
                       COALESCE(restock.pending_qty, 0) AS pending_restock_qty,
                       rf.status AS restock_status
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN product_supplier_links psl ON psl.product_id = p.id AND psl.is_primary = 1
                LEFT JOIN suppliers s ON s.id = psl.supplier_id
                LEFT JOIN (
                    SELECT oi.product_id, SUM(oi.quantity) AS qty
                    FROM order_items oi
                    INNER JOIN orders o ON o.id = oi.order_id
                    WHERE oi.product_id IS NOT NULL
                      AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                      AND COALESCE(o.order_status, o.status) <> "cancelled"
                    GROUP BY oi.product_id
                ) sales_30 ON sales_30.product_id = p.id
                LEFT JOIN (
                    SELECT oi.product_id, SUM(oi.quantity) AS qty, MAX(o.created_at) AS last_sale_at
                    FROM order_items oi
                    INNER JOIN orders o ON o.id = oi.order_id
                    WHERE oi.product_id IS NOT NULL
                      AND o.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                      AND COALESCE(o.order_status, o.status) <> "cancelled"
                    GROUP BY oi.product_id
                ) sales_60 ON sales_60.product_id = p.id
                LEFT JOIN (
                    SELECT product_id, COUNT(*) AS active_count
                    FROM stock_alert_subscriptions
                    WHERE status = "active"
                    GROUP BY product_id
                ) alerts ON alerts.product_id = p.id
                LEFT JOIN (
                    SELECT podi.product_id,
                           SUM(GREATEST(podi.quantity - COALESCE(podi.received_quantity, 0), 0)) AS pending_qty
                    FROM purchase_order_draft_items podi
                    INNER JOIN purchase_order_drafts pod ON pod.id = podi.purchase_order_draft_id
                    WHERE podi.product_id IS NOT NULL
                      AND pod.status IN ("draft", "exported")
                      AND pod.receiving_status IN ("not_received", "partially_received")
                    GROUP BY podi.product_id
                ) restock ON restock.product_id = p.id
                LEFT JOIN restock_flags rf ON rf.product_id = p.id';

        $where = ['p.is_active = 1'];
        $params = [];

        if (!empty($filters['supplier_id']) && ctype_digit((string) $filters['supplier_id'])) {
            $where[] = 'psl.supplier_id = :supplier_id';
            $params['supplier_id'] = (int) $filters['supplier_id'];
        }

        if (!empty($filters['brand_id']) && ctype_digit((string) $filters['brand_id'])) {
            $where[] = 'p.brand_id = :brand_id';
            $params['brand_id'] = (int) $filters['brand_id'];
        }

        if (!empty($filters['category_id']) && ctype_digit((string) $filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(p.name LIKE :search OR p.sku LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY p.id DESC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int,array{id:int,name:string}> */
    public function listSupplierOptions(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM suppliers ORDER BY name ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
