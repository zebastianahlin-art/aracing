<?php

declare(strict_types=1);

namespace App\Modules\Import\Repositories;

use PDO;

final class SupplierItemReviewRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $filters
     *  @return array<int, array<string, mixed>>
     */
    public function search(array $filters): array
    {
        $sql = 'SELECT si.id, si.supplier_id, si.import_run_id, si.supplier_sku, si.supplier_title, si.price, si.stock_qty,
                       si.review_status, si.matched_at, si.last_reviewed_at, si.updated_at,
                       s.name AS supplier_name,
                       psl.product_id,
                       p.name AS product_name
                FROM supplier_items si
                LEFT JOIN suppliers s ON s.id = si.supplier_id
                LEFT JOIN product_supplier_links psl ON psl.supplier_item_id = si.id AND psl.is_primary = 1
                LEFT JOIN products p ON p.id = psl.product_id
                WHERE 1=1';

        $params = [];

        if (($filters['supplier_id'] ?? null) !== null) {
            $sql .= ' AND si.supplier_id = :supplier_id';
            $params['supplier_id'] = (int) $filters['supplier_id'];
        }

        if (($filters['import_run_id'] ?? null) !== null) {
            $sql .= ' AND si.import_run_id = :import_run_id';
            $params['import_run_id'] = (int) $filters['import_run_id'];
        }

        $skuQuery = trim((string) ($filters['supplier_sku'] ?? ''));
        if ($skuQuery !== '') {
            $sql .= ' AND si.supplier_sku LIKE :supplier_sku';
            $params['supplier_sku'] = '%' . $skuQuery . '%';
        }

        $titleQuery = trim((string) ($filters['supplier_title'] ?? ''));
        if ($titleQuery !== '') {
            $sql .= ' AND si.supplier_title LIKE :supplier_title';
            $params['supplier_title'] = '%' . $titleQuery . '%';
        }

        $status = (string) ($filters['match_status'] ?? '');
        if ($status === 'linked') {
            $sql .= ' AND psl.id IS NOT NULL';
        }

        if ($status === 'needs_review') {
            $sql .= " AND psl.id IS NULL AND si.review_status = 'needs_review'";
        }

        if ($status === 'unmatched') {
            $sql .= " AND psl.id IS NULL AND (si.review_status IS NULL OR si.review_status <> 'needs_review')";
        }

        $gap = (string) ($filters['data_gap'] ?? '');
        if ($gap === 'missing_title') {
            $sql .= ' AND (si.supplier_title IS NULL OR si.supplier_title = "")';
        }
        if ($gap === 'missing_sku') {
            $sql .= ' AND (si.supplier_sku IS NULL OR si.supplier_sku = "")';
        }
        if ($gap === 'missing_price') {
            $sql .= ' AND si.price IS NULL';
        }
        if ($gap === 'missing_stock') {
            $sql .= ' AND si.stock_qty IS NULL';
        }
        if ($gap === 'missing_product_link') {
            $sql .= ' AND psl.id IS NULL';
        }

        $sql .= ' ORDER BY si.updated_at DESC, si.id DESC LIMIT 500';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }
}
