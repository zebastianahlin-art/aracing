<?php

declare(strict_types=1);

namespace App\Modules\Product\Repositories;

use PDO;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $filters
     *  @return array<int, array<string, mixed>>
     */
    public function adminOverview(array $filters = []): array
    {
        $sql = 'SELECT p.id,
                       p.name,
                       p.slug,
                       p.sku,
                       p.sale_price,
                       p.currency_code,
                       p.stock_status,
                       p.stock_quantity,
                       p.is_active,
                       b.name AS brand_name,
                       c.name AS category_name,
                       psl.id AS supplier_link_id,
                       psl.supplier_item_id,
                       psl.supplier_sku_snapshot,
                       psl.supplier_title_snapshot,
                       psl.supplier_price_snapshot,
                       psl.supplier_stock_snapshot,
                       s.name AS supplier_name
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN product_supplier_links psl ON psl.product_id = p.id AND psl.is_primary = 1
                LEFT JOIN suppliers s ON s.id = psl.supplier_id';

        $where = [];
        $params = [];

        $nameQuery = trim((string) ($filters['name'] ?? ''));
        if ($nameQuery !== '') {
            $where[] = 'p.name LIKE :name_query';
            $params['name_query'] = '%' . $nameQuery . '%';
        }

        $skuQuery = trim((string) ($filters['sku'] ?? ''));
        if ($skuQuery !== '') {
            $where[] = 'p.sku LIKE :sku_query';
            $params['sku_query'] = '%' . $skuQuery . '%';
        }

        $active = $filters['active'] ?? '';
        if ($active === '1' || $active === '0') {
            $where[] = 'p.is_active = :is_active';
            $params['is_active'] = (int) $active;
        }

        $hasLink = $filters['has_link'] ?? '';
        if ($hasLink === '1') {
            $where[] = 'psl.id IS NOT NULL';
        } elseif ($hasLink === '0') {
            $where[] = 'psl.id IS NULL';
        }

        $stockStatus = trim((string) ($filters['stock_status'] ?? ''));
        if ($stockStatus !== '') {
            $where[] = 'p.stock_status = :stock_status';
            $params['stock_status'] = $stockStatus;
        }

        $deviation = $filters['deviation'] ?? '';
        if ($deviation === '1') {
            $where[] = '(
                psl.id IS NULL
                OR p.sale_price IS NULL
                OR p.is_active = 0
                OR (p.sale_price IS NOT NULL AND psl.supplier_price_snapshot IS NOT NULL AND p.sale_price <> psl.supplier_price_snapshot)
                OR (p.stock_quantity IS NOT NULL AND psl.supplier_stock_snapshot IS NOT NULL AND p.stock_quantity <> psl.supplier_stock_snapshot)
                OR ((p.stock_quantity IS NULL) <> (psl.supplier_stock_snapshot IS NULL))
            )';
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.updated_at DESC, p.id DESC';
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, brand_id, category_id, name, slug, sku, description, sale_price, currency_code, stock_status, stock_quantity, is_active FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO products (
                    brand_id,
                    category_id,
                    name,
                    slug,
                    sku,
                    description,
                    sale_price,
                    currency_code,
                    stock_status,
                    stock_quantity,
                    is_active,
                    created_at,
                    updated_at
                )
                VALUES (
                    :brand_id,
                    :category_id,
                    :name,
                    :slug,
                    :sku,
                    :description,
                    :sale_price,
                    :currency_code,
                    :stock_status,
                    :stock_quantity,
                    :is_active,
                    NOW(),
                    NOW()
                )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('brand_id', $data['brand_id'], $data['brand_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('category_id', $data['category_id'], $data['category_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('name', $data['name']);
        $stmt->bindValue('slug', $data['slug']);
        $stmt->bindValue('sku', $data['sku']);
        $stmt->bindValue('description', $data['description']);
        $stmt->bindValue('sale_price', $data['sale_price']);
        $stmt->bindValue('currency_code', $data['currency_code']);
        $stmt->bindValue('stock_status', $data['stock_status']);
        $stmt->bindValue('stock_quantity', $data['stock_quantity'], $data['stock_quantity'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('is_active', $data['is_active'], PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE products
                SET brand_id = :brand_id,
                    category_id = :category_id,
                    name = :name,
                    slug = :slug,
                    sku = :sku,
                    description = :description,
                    sale_price = :sale_price,
                    currency_code = :currency_code,
                    stock_status = :stock_status,
                    stock_quantity = :stock_quantity,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('brand_id', $data['brand_id'], $data['brand_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('category_id', $data['category_id'], $data['category_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('name', $data['name']);
        $stmt->bindValue('slug', $data['slug']);
        $stmt->bindValue('sku', $data['sku']);
        $stmt->bindValue('description', $data['description']);
        $stmt->bindValue('sale_price', $data['sale_price']);
        $stmt->bindValue('currency_code', $data['currency_code']);
        $stmt->bindValue('stock_status', $data['stock_status']);
        $stmt->bindValue('stock_quantity', $data['stock_quantity'], $data['stock_quantity'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('is_active', $data['is_active'], PDO::PARAM_INT);
        $stmt->execute();
    }

    public function updateSalePrice(int $id, ?string $salePrice): void
    {
        $stmt = $this->pdo->prepare('UPDATE products SET sale_price = :sale_price, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('sale_price', $salePrice);
        $stmt->execute();
    }

    public function updateStockQuantity(int $id, ?int $stockQuantity): void
    {
        $stmt = $this->pdo->prepare('UPDATE products SET stock_quantity = :stock_quantity, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('stock_quantity', $stockQuantity, $stockQuantity !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
    }

    public function updateStockStatus(int $id, ?string $stockStatus): void
    {
        $stmt = $this->pdo->prepare('UPDATE products SET stock_status = :stock_status, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('stock_status', $stockStatus);
        $stmt->execute();
    }

    public function updateActiveStatus(int $id, int $isActive): void
    {
        $stmt = $this->pdo->prepare('UPDATE products SET is_active = :is_active, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('is_active', $isActive, PDO::PARAM_INT);
        $stmt->execute();
    }
}
