<?php

declare(strict_types=1);

namespace App\Modules\Product\Repositories;

use PDO;

final class ProductSupplierItemLookupRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function search(?int $supplierId, string $query, int $limit = 150): array
    {
        $sql = 'SELECT si.id, si.supplier_id, si.supplier_sku, si.supplier_title, si.price, si.stock_qty, s.name AS supplier_name
                FROM supplier_items si
                LEFT JOIN suppliers s ON s.id = si.supplier_id
                WHERE si.supplier_id IS NOT NULL';

        $params = [];

        if ($supplierId !== null) {
            $sql .= ' AND si.supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }

        $query = trim($query);
        if ($query !== '') {
            $sql .= ' AND (si.supplier_sku LIKE :query OR si.supplier_title LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $sql .= ' ORDER BY si.updated_at DESC, si.id DESC LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        if ($supplierId !== null) {
            $stmt->bindValue('supplier_id', $supplierId, PDO::PARAM_INT);
        }

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT si.id, si.supplier_id, si.supplier_sku, si.supplier_title, si.price, si.stock_qty, s.name AS supplier_name
             FROM supplier_items si
             LEFT JOIN suppliers s ON s.id = si.supplier_id
             WHERE si.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
