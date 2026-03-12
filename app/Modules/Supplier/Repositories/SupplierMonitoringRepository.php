<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Repositories;

use PDO;

final class SupplierMonitoringRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function latestStates(?int $supplierId, bool $linkedOnly): array
    {
        $sql = 'SELECT si.id AS supplier_item_id,
                       si.supplier_id,
                       si.supplier_sku,
                       si.supplier_title,
                       s.name AS supplier_name,
                       psl.product_id,
                       p.name AS product_name,
                       l.id AS latest_snapshot_id,
                       l.import_run_id AS latest_import_run_id,
                       l.supplier_price AS latest_price,
                       l.currency AS latest_currency,
                       l.stock_quantity AS latest_stock_quantity,
                       l.stock_status AS latest_stock_status,
                       l.is_available AS latest_is_available,
                       l.captured_at AS latest_captured_at,
                       prev.id AS previous_snapshot_id,
                       prev.import_run_id AS previous_import_run_id,
                       prev.supplier_price AS previous_price,
                       prev.currency AS previous_currency,
                       prev.stock_quantity AS previous_stock_quantity,
                       prev.stock_status AS previous_stock_status,
                       prev.is_available AS previous_is_available,
                       prev.captured_at AS previous_captured_at
                FROM supplier_items si
                INNER JOIN (
                    SELECT sis.*
                    FROM supplier_item_snapshots sis
                    INNER JOIN (
                        SELECT supplier_item_id, MAX(id) AS max_id
                        FROM supplier_item_snapshots
                        GROUP BY supplier_item_id
                    ) mx ON mx.max_id = sis.id
                ) l ON l.supplier_item_id = si.id
                LEFT JOIN supplier_item_snapshots prev ON prev.id = (
                    SELECT p2.id
                    FROM supplier_item_snapshots p2
                    WHERE p2.supplier_item_id = si.id
                      AND p2.id < l.id
                    ORDER BY p2.id DESC
                    LIMIT 1
                )
                LEFT JOIN suppliers s ON s.id = si.supplier_id
                LEFT JOIN product_supplier_links psl ON psl.supplier_item_id = si.id AND psl.is_primary = 1
                LEFT JOIN products p ON p.id = psl.product_id
                WHERE 1=1';

        $params = [];
        if ($supplierId !== null) {
            $sql .= ' AND si.supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }

        if ($linkedOnly) {
            $sql .= ' AND psl.product_id IS NOT NULL';
        }

        $sql .= ' ORDER BY l.id DESC LIMIT 2000';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function latestCompletedRuns(?int $supplierId): array
    {
        $sql = 'SELECT r.supplier_id, MAX(r.id) AS latest_run_id
                FROM import_runs r
                WHERE r.status = "completed"';
        $params = [];

        if ($supplierId !== null) {
            $sql .= ' AND r.supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }

        $sql .= ' GROUP BY r.supplier_id';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function newlySeenForRun(int $supplierId, int $runId, bool $linkedOnly): array
    {
        $sql = 'SELECT si.id AS supplier_item_id,
                       si.supplier_id,
                       si.supplier_sku,
                       si.supplier_title,
                       s.name AS supplier_name,
                       psl.product_id,
                       p.name AS product_name,
                       l.supplier_price AS latest_price,
                       l.currency AS latest_currency,
                       l.stock_quantity AS latest_stock_quantity,
                       l.stock_status AS latest_stock_status,
                       l.is_available AS latest_is_available,
                       l.captured_at AS latest_captured_at,
                       l.import_run_id AS latest_import_run_id
                FROM supplier_item_snapshots l
                INNER JOIN supplier_items si ON si.id = l.supplier_item_id
                LEFT JOIN suppliers s ON s.id = si.supplier_id
                LEFT JOIN product_supplier_links psl ON psl.supplier_item_id = si.id AND psl.is_primary = 1
                LEFT JOIN products p ON p.id = psl.product_id
                WHERE si.supplier_id = :supplier_id
                  AND l.import_run_id = :run_id
                  AND NOT EXISTS (
                      SELECT 1
                      FROM supplier_item_snapshots prev
                      WHERE prev.supplier_item_id = l.supplier_item_id
                        AND prev.import_run_id < :run_id
                  )';

        if ($linkedOnly) {
            $sql .= ' AND psl.product_id IS NOT NULL';
        }

        $sql .= ' ORDER BY l.id DESC LIMIT 1000';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('supplier_id', $supplierId, PDO::PARAM_INT);
        $stmt->bindValue('run_id', $runId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function missingInRecentImportForRun(int $supplierId, int $runId, bool $linkedOnly): array
    {
        $sql = 'SELECT si.id AS supplier_item_id,
                       si.supplier_id,
                       si.supplier_sku,
                       si.supplier_title,
                       s.name AS supplier_name,
                       psl.product_id,
                       p.name AS product_name,
                       prev.supplier_price AS previous_price,
                       prev.currency AS previous_currency,
                       prev.stock_quantity AS previous_stock_quantity,
                       prev.stock_status AS previous_stock_status,
                       prev.is_available AS previous_is_available,
                       prev.captured_at AS previous_captured_at,
                       :run_id AS latest_import_run_id
                FROM supplier_items si
                INNER JOIN supplier_item_snapshots prev ON prev.id = (
                    SELECT p2.id
                    FROM supplier_item_snapshots p2
                    WHERE p2.supplier_item_id = si.id
                      AND p2.import_run_id < :run_id
                    ORDER BY p2.id DESC
                    LIMIT 1
                )
                LEFT JOIN suppliers s ON s.id = si.supplier_id
                LEFT JOIN product_supplier_links psl ON psl.supplier_item_id = si.id AND psl.is_primary = 1
                LEFT JOIN products p ON p.id = psl.product_id
                WHERE si.supplier_id = :supplier_id
                  AND NOT EXISTS (
                      SELECT 1
                      FROM supplier_item_snapshots current_in_run
                      WHERE current_in_run.supplier_item_id = si.id
                        AND current_in_run.import_run_id = :run_id
                  )';

        if ($linkedOnly) {
            $sql .= ' AND psl.product_id IS NOT NULL';
        }

        $sql .= ' ORDER BY prev.id DESC LIMIT 1000';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('supplier_id', $supplierId, PDO::PARAM_INT);
        $stmt->bindValue('run_id', $runId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
