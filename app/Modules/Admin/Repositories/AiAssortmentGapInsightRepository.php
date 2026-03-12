<?php

declare(strict_types=1);

namespace App\Modules\Admin\Repositories;

use PDO;

final class AiAssortmentGapInsightRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int,array{id:int,name:string}> */
    public function listSupplierOptions(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM suppliers ORDER BY name ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int,array{id:int,name:string}> */
    public function listBrandOptions(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM brands ORDER BY name ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int,array{id:int,name:string}> */
    public function listCategoryOptions(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM categories ORDER BY name ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function listSupplierCoverageSignals(array $filters = []): array
    {
        $sql = 'SELECT si.supplier_id,
                       s.name AS supplier_name,
                       COUNT(*) AS supplier_item_count,
                       SUM(CASE WHEN psl.id IS NULL THEN 1 ELSE 0 END) AS unmapped_count,
                       SUM(CASE WHEN psl.id IS NOT NULL THEN 1 ELSE 0 END) AS mapped_count,
                       SUM(CASE WHEN COALESCE(si.review_status, "") = "unmatched" THEN 1 ELSE 0 END) AS review_unmatched_count,
                       MAX(ir.finished_at) AS last_import_finished_at
                FROM supplier_items si
                INNER JOIN suppliers s ON s.id = si.supplier_id
                LEFT JOIN product_supplier_links psl ON psl.supplier_item_id = si.id
                LEFT JOIN import_runs ir ON ir.id = si.import_run_id
                WHERE si.supplier_id IS NOT NULL';

        $params = [];
        if (!empty($filters['supplier_id']) && ctype_digit((string) $filters['supplier_id'])) {
            $sql .= ' AND si.supplier_id = :supplier_id';
            $params['supplier_id'] = (int) $filters['supplier_id'];
        }

        $sql .= ' GROUP BY si.supplier_id, s.name
                  HAVING supplier_item_count >= 3
                  ORDER BY unmapped_count DESC, supplier_item_count DESC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countCatalogMatchesForQuery(string $query): int
    {
        $query = trim($query);
        if ($query === '') {
            return 0;
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*)
            FROM products p
            WHERE p.is_active = 1
              AND p.is_search_hidden = 0
              AND (p.name LIKE :needle OR p.sku LIKE :needle)');
        $stmt->execute(['needle' => '%' . $query . '%']);

        return (int) $stmt->fetchColumn();
    }

    public function countActiveStockAlertsForQuery(string $query): int
    {
        $query = trim($query);
        if ($query === '') {
            return 0;
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*)
            FROM stock_alert_subscriptions sas
            INNER JOIN products p ON p.id = sas.product_id
            WHERE sas.status = "active"
              AND p.is_active = 1
              AND p.is_search_hidden = 0
              AND (p.name LIKE :needle OR p.sku LIKE :needle)');
        $stmt->execute(['needle' => '%' . $query . '%']);

        return (int) $stmt->fetchColumn();
    }
}
