<?php

declare(strict_types=1);

namespace App\Modules\Admin\Repositories;

use PDO;

final class AiMerchandisingInsightRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function listSectionSignals(): array
    {
        $sql = 'SELECT hs.id AS section_id,
                       hs.title AS section_title,
                       hs.section_key,
                       hs.is_active AS section_is_active,
                       COUNT(DISTINCT CASE WHEN hsi.item_type = "product" AND hsi.is_active = 1 AND p.id IS NOT NULL THEN p.id END) AS product_count,
                       SUM(CASE
                               WHEN hsi.item_type = "product"
                                    AND hsi.is_active = 1
                                    AND p.id IS NOT NULL
                                    AND (
                                        p.stock_status IN ("in_stock", "backorder")
                                        AND (COALESCE(p.stock_quantity, 0) > 0 OR p.stock_status = "backorder" OR COALESCE(p.backorder_allowed, 0) = 1)
                                    )
                               THEN 1 ELSE 0
                           END) AS buyable_count,
                       SUM(CASE
                               WHEN hsi.item_type = "product"
                                    AND hsi.is_active = 1
                                    AND p.id IS NOT NULL
                                    AND p.created_at >= DATE_SUB(NOW(), INTERVAL 45 DAY)
                               THEN 1 ELSE 0
                           END) AS fresh_product_count,
                       COALESCE(SUM(sales_30.sold_last_30_days), 0) AS sold_last_30_days,
                       COALESCE(SUM(sales_60.sold_last_60_days), 0) AS sold_last_60_days,
                       COALESCE(SUM(wishlist.wishlist_count), 0) AS wishlist_count,
                       COALESCE(SUM(alerts.active_stock_alerts), 0) AS active_stock_alerts,
                       MIN(CASE WHEN hsi.item_type = "product" AND hsi.is_active = 1 THEN p.id END) AS sample_product_id
                FROM homepage_sections hs
                LEFT JOIN homepage_section_items hsi
                    ON hsi.homepage_section_id = hs.id
                LEFT JOIN products p
                    ON p.id = hsi.item_id
                   AND hsi.item_type = "product"
                   AND p.is_active = 1
                LEFT JOIN (
                    SELECT oi.product_id,
                           SUM(oi.quantity) AS sold_last_30_days
                    FROM order_items oi
                    INNER JOIN orders o ON o.id = oi.order_id
                    WHERE oi.product_id IS NOT NULL
                      AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                      AND COALESCE(o.order_status, o.status) <> "cancelled"
                    GROUP BY oi.product_id
                ) sales_30 ON sales_30.product_id = p.id
                LEFT JOIN (
                    SELECT oi.product_id,
                           SUM(oi.quantity) AS sold_last_60_days
                    FROM order_items oi
                    INNER JOIN orders o ON o.id = oi.order_id
                    WHERE oi.product_id IS NOT NULL
                      AND o.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                      AND COALESCE(o.order_status, o.status) <> "cancelled"
                    GROUP BY oi.product_id
                ) sales_60 ON sales_60.product_id = p.id
                LEFT JOIN (
                    SELECT wi.product_id, COUNT(*) AS wishlist_count
                    FROM wishlist_items wi
                    GROUP BY wi.product_id
                ) wishlist ON wishlist.product_id = p.id
                LEFT JOIN (
                    SELECT sas.product_id, COUNT(*) AS active_stock_alerts
                    FROM stock_alert_subscriptions sas
                    WHERE sas.status = "active"
                    GROUP BY sas.product_id
                ) alerts ON alerts.product_id = p.id
                GROUP BY hs.id, hs.title, hs.section_key, hs.is_active
                ORDER BY hs.sort_order ASC, hs.id ASC';

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
