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
                       p.backorder_allowed,
                       p.stock_updated_at,
                       p.is_active,
                       p.is_search_hidden,
                       p.is_featured,
                       p.search_boost,
                       p.sort_priority,
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

        $featured = (string) ($filters['featured'] ?? '');
        if ($featured === '1' || $featured === '0') {
            $where[] = 'p.is_featured = :is_featured';
            $params['is_featured'] = (int) $featured;
        }

        $hidden = (string) ($filters['hidden'] ?? '');
        if ($hidden === '1' || $hidden === '0') {
            $where[] = 'p.is_search_hidden = :is_search_hidden';
            $params['is_search_hidden'] = (int) $hidden;
        }

        $lowStock = (string) ($filters['low_stock'] ?? '');
        if ($lowStock === '1') {
            $where[] = 'p.stock_quantity <= 0';
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

    /** @param array<string,string> $filters
     * @return array<int,array<string,mixed>>
     */
    public function fitmentWorkflowOverview(array $filters): array
    {
        $sql = 'SELECT p.id,
                       p.name,
                       p.sku,
                       p.brand_id,
                       p.category_id,
                       b.name AS brand_name,
                       c.name AS category_name,
                       COUNT(pf.id) AS fitment_count,
                       SUM(CASE WHEN pf.fitment_type = "confirmed" THEN 1 ELSE 0 END) AS confirmed_fitment_count,
                       SUM(CASE WHEN pf.fitment_type = "universal" THEN 1 ELSE 0 END) AS universal_fitment_count
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN product_fitments pf ON pf.product_id = p.id';

        $where = [];
        $having = [];
        $params = [];

        $query = trim((string) ($filters['query'] ?? ''));
        if ($query !== '') {
            $where[] = '(p.name LIKE :query OR p.sku LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        if (($filters['brand_id'] ?? '') !== '' && ctype_digit((string) $filters['brand_id'])) {
            $where[] = 'p.brand_id = :brand_id';
            $params['brand_id'] = (int) $filters['brand_id'];
        }

        if (($filters['category_id'] ?? '') !== '' && ctype_digit((string) $filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY p.id';

        $queue = (string) ($filters['queue'] ?? 'all');
        if ($queue === 'without_fitment') {
            $having[] = 'COUNT(pf.id) = 0';
        }
        if ($queue === 'with_fitment') {
            $having[] = 'COUNT(pf.id) > 0';
        }
        if ($queue === 'universal') {
            $having[] = 'SUM(CASE WHEN pf.fitment_type = "universal" THEN 1 ELSE 0 END) > 0';
            $having[] = 'SUM(CASE WHEN pf.fitment_type = "confirmed" THEN 1 ELSE 0 END) = 0';
        }

        $fitmentCountBand = (string) ($filters['fitment_count_band'] ?? '');
        if ($fitmentCountBand === 'many') {
            $having[] = 'COUNT(pf.id) >= 10';
        }

        if ($having !== []) {
            $sql .= ' HAVING ' . implode(' AND ', $having);
        }

        $sql .= ' ORDER BY fitment_count ASC, p.updated_at DESC, p.id DESC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<string,int> */
    public function fitmentWorkflowTotals(): array
    {
        $row = $this->pdo->query('SELECT
                COUNT(*) AS total_products,
                SUM(CASE WHEN stats.fitment_count = 0 THEN 1 ELSE 0 END) AS without_fitment,
                SUM(CASE WHEN stats.fitment_count > 0 THEN 1 ELSE 0 END) AS with_fitment,
                SUM(CASE WHEN stats.universal_count > 0 THEN 1 ELSE 0 END) AS universal_products
            FROM (
                SELECT p.id,
                       COUNT(pf.id) AS fitment_count,
                       SUM(CASE WHEN pf.fitment_type = "universal" THEN 1 ELSE 0 END) AS universal_count
                FROM products p
                LEFT JOIN product_fitments pf ON pf.product_id = p.id
                GROUP BY p.id
            ) stats')->fetch();

        if ($row === false) {
            return ['total_products' => 0, 'without_fitment' => 0, 'with_fitment' => 0, 'universal_products' => 0];
        }

        return [
            'total_products' => (int) ($row['total_products'] ?? 0),
            'without_fitment' => (int) ($row['without_fitment'] ?? 0),
            'with_fitment' => (int) ($row['with_fitment'] ?? 0),
            'universal_products' => (int) ($row['universal_products'] ?? 0),
        ];
    }

    /** @param array<string,string> $filters
     * @return array<int,array<string,mixed>>
     */
    public function fitmentGapQueueOverview(array $filters): array
    {
        $sql = 'SELECT p.id,
                       p.name,
                       p.sku,
                       p.brand_id,
                       p.category_id,
                       b.name AS brand_name,
                       c.name AS category_name,
                       COUNT(pf.id) AS fitment_count,
                       SUM(CASE WHEN pf.fitment_type = "confirmed" THEN 1 ELSE 0 END) AS confirmed_fitment_count,
                       SUM(CASE WHEN pf.fitment_type = "universal" THEN 1 ELSE 0 END) AS universal_fitment_count,
                       SUM(CASE WHEN pf.fitment_type = "unknown" THEN 1 ELSE 0 END) AS unknown_fitment_count
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN product_fitments pf ON pf.product_id = p.id';

        $where = [];
        $params = [];

        $query = trim((string) ($filters['query'] ?? ''));
        if ($query !== '') {
            $where[] = '(p.name LIKE :query OR p.sku LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        if (($filters['brand_id'] ?? '') !== '' && ctype_digit((string) $filters['brand_id'])) {
            $where[] = 'p.brand_id = :brand_id';
            $params['brand_id'] = (int) $filters['brand_id'];
        }

        if (($filters['category_id'] ?? '') !== '' && ctype_digit((string) $filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY p.id ORDER BY p.updated_at DESC, p.id DESC LIMIT 500';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }



    /** @return array<int, array<string, mixed>> */
    public function searchForSupplierMatch(string $query, int $limit = 120): array
    {
        $sql = 'SELECT p.id, p.name, p.sku, b.name AS brand_name
'
             . 'FROM products p
'
             . 'LEFT JOIN brands b ON b.id = p.brand_id
'
             . 'WHERE 1=1';
        $params = [];

        $query = trim($query);
        if ($query !== '') {
            $sql .= ' AND (p.name LIKE :query OR p.sku LIKE :query OR p.slug LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $sql .= ' ORDER BY p.updated_at DESC, p.id DESC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);

        if (isset($params['query'])) {
            $stmt->bindValue('query', $params['query']);
        }

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @param array<string,string> $filters
     * @return array<int,array<string,mixed>>
     */
    public function articleCareQueue(array $filters): array
    {
        $sql = 'SELECT p.id,
                       p.name,
                       p.sku,
                       p.brand_id,
                       p.category_id,
                       p.description,
                       p.sale_price,
                       p.is_active,
                       p.is_search_hidden,
                       p.is_featured,
                       p.search_boost,
                       p.sort_priority,
                       CASE WHEN psl.id IS NULL THEN 0 ELSE 1 END AS has_supplier_link,
                       CASE WHEN pi.id IS NULL THEN 0 ELSE 1 END AS has_image
                FROM products p
                LEFT JOIN product_supplier_links psl ON psl.product_id = p.id AND psl.is_primary = 1
                LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1';

        $where = [];
        $params = [];

        $name = trim((string) ($filters['name'] ?? ''));
        if ($name !== '') {
            $where[] = 'p.name LIKE :name';
            $params['name'] = '%' . $name . '%';
        }

        $sku = trim((string) ($filters['sku'] ?? ''));
        if ($sku !== '') {
            $where[] = 'p.sku LIKE :sku';
            $params['sku'] = '%' . $sku . '%';
        }

        $active = $filters['active'] ?? '';
        if ($active === '0' || $active === '1') {
            $where[] = 'p.is_active = :is_active';
            $params['is_active'] = (int) $active;
        }

        $hasLink = $filters['has_supplier_link'] ?? '';
        if ($hasLink === '1') {
            $where[] = 'psl.id IS NOT NULL';
        }
        if ($hasLink === '0') {
            $where[] = 'psl.id IS NULL';
        }

        $gap = trim((string) ($filters['gap'] ?? ''));
        if ($gap !== '') {
            $where[] = match ($gap) {
                'missing_brand' => 'p.brand_id IS NULL',
                'missing_category' => 'p.category_id IS NULL',
                'missing_sale_price' => 'p.sale_price IS NULL',
                'missing_description' => '(p.description IS NULL OR TRIM(p.description) = \'\')',
                'missing_image' => 'pi.id IS NULL',
                'missing_supplier_link' => 'psl.id IS NULL',
                'inactive' => 'p.is_active = 0',
                default => '1=1',
            };
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY p.id ORDER BY p.updated_at DESC, p.id DESC';

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
        $stmt = $this->pdo->prepare('SELECT id, brand_id, category_id, name, slug, sku, description, seo_title, seo_description, canonical_url, meta_robots, is_indexable, sale_price, currency_code, stock_status, stock_quantity, backorder_allowed, stock_updated_at, is_active, is_search_hidden, is_featured, search_boost, sort_priority FROM products WHERE id = :id');
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
                    seo_title,
                    seo_description,
                    canonical_url,
                    meta_robots,
                    is_indexable,
                    sale_price,
                    currency_code,
                    stock_status,
                    stock_quantity,
                    backorder_allowed,
                    stock_updated_at,
                    is_active,
                    is_search_hidden,
                    is_featured,
                    search_boost,
                    sort_priority,
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
                    :seo_title,
                    :seo_description,
                    :canonical_url,
                    :meta_robots,
                    :is_indexable,
                    :sale_price,
                    :currency_code,
                    :stock_status,
                    :stock_quantity,
                    :backorder_allowed,
                    :stock_updated_at,
                    :is_active,
                    :is_search_hidden,
                    :is_featured,
                    :search_boost,
                    :sort_priority,
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
        $stmt->bindValue('seo_title', $data['seo_title']);
        $stmt->bindValue('seo_description', $data['seo_description']);
        $stmt->bindValue('canonical_url', $data['canonical_url']);
        $stmt->bindValue('meta_robots', $data['meta_robots']);
        $stmt->bindValue('is_indexable', $data['is_indexable'], PDO::PARAM_INT);
        $stmt->bindValue('sale_price', $data['sale_price']);
        $stmt->bindValue('currency_code', $data['currency_code']);
        $stmt->bindValue('stock_status', $data['stock_status']);
        $stmt->bindValue('stock_quantity', $data['stock_quantity'], $data['stock_quantity'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('backorder_allowed', $data['backorder_allowed'], PDO::PARAM_INT);
        $stmt->bindValue('stock_updated_at', $data['stock_updated_at']);
        $stmt->bindValue('is_active', $data['is_active'], PDO::PARAM_INT);
        $stmt->bindValue('is_search_hidden', $data['is_search_hidden'], PDO::PARAM_INT);
        $stmt->bindValue('is_featured', $data['is_featured'], PDO::PARAM_INT);
        $stmt->bindValue('search_boost', $data['search_boost'], PDO::PARAM_INT);
        $stmt->bindValue('sort_priority', $data['sort_priority'], PDO::PARAM_INT);
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
                    seo_title = :seo_title,
                    seo_description = :seo_description,
                    canonical_url = :canonical_url,
                    meta_robots = :meta_robots,
                    is_indexable = :is_indexable,
                    sale_price = :sale_price,
                    currency_code = :currency_code,
                    stock_status = :stock_status,
                    stock_quantity = :stock_quantity,
                    backorder_allowed = :backorder_allowed,
                    stock_updated_at = :stock_updated_at,
                    is_active = :is_active,
                    is_search_hidden = :is_search_hidden,
                    is_featured = :is_featured,
                    search_boost = :search_boost,
                    sort_priority = :sort_priority,
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
        $stmt->bindValue('seo_title', $data['seo_title']);
        $stmt->bindValue('seo_description', $data['seo_description']);
        $stmt->bindValue('canonical_url', $data['canonical_url']);
        $stmt->bindValue('meta_robots', $data['meta_robots']);
        $stmt->bindValue('is_indexable', $data['is_indexable'], PDO::PARAM_INT);
        $stmt->bindValue('sale_price', $data['sale_price']);
        $stmt->bindValue('currency_code', $data['currency_code']);
        $stmt->bindValue('stock_status', $data['stock_status']);
        $stmt->bindValue('stock_quantity', $data['stock_quantity'], $data['stock_quantity'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('backorder_allowed', $data['backorder_allowed'], PDO::PARAM_INT);
        $stmt->bindValue('stock_updated_at', $data['stock_updated_at']);
        $stmt->bindValue('is_active', $data['is_active'], PDO::PARAM_INT);
        $stmt->bindValue('is_search_hidden', $data['is_search_hidden'], PDO::PARAM_INT);
        $stmt->bindValue('is_featured', $data['is_featured'], PDO::PARAM_INT);
        $stmt->bindValue('search_boost', $data['search_boost'], PDO::PARAM_INT);
        $stmt->bindValue('sort_priority', $data['sort_priority'], PDO::PARAM_INT);
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
        $stmt = $this->pdo->prepare('UPDATE products SET stock_quantity = :stock_quantity, stock_updated_at = NOW(), updated_at = NOW() WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('stock_quantity', $stockQuantity, $stockQuantity !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
    }

    public function updateStockStatus(int $id, ?string $stockStatus): void
    {
        $stmt = $this->pdo->prepare('UPDATE products SET stock_status = :stock_status, stock_updated_at = NOW(), updated_at = NOW() WHERE id = :id');
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
    /** @param array<int, int> $ids
     *  @return array<int, array<string, mixed>>
     */
    public function findActiveByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT p.id, p.name, p.slug, p.sku, p.sale_price, p.currency_code, p.stock_status, p.stock_quantity, p.backorder_allowed, b.name AS brand_name,
            (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS image_url
'
             . 'FROM products p
'
             . 'LEFT JOIN brands b ON b.id = p.brand_id
'
             . 'WHERE p.is_active = 1 AND p.id IN (' . $placeholders . ')';

        $stmt = $this->pdo->prepare($sql);
        foreach (array_values($ids) as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    /** @return array<int, array{slug:string, updated_at:?string}> */
    public function sitemapIndexableProducts(): array
    {
        $sql = 'SELECT p.slug, p.updated_at
                FROM products p
                WHERE p.is_active = 1
                  AND p.is_search_hidden = 0
                  AND p.is_indexable = 1
                  AND TRIM(COALESCE(p.slug, "")) <> ""
                  AND (p.meta_robots IS NULL OR LOWER(p.meta_robots) NOT LIKE "%noindex%")
                ORDER BY p.updated_at DESC, p.id DESC';

        return $this->pdo->query($sql)->fetchAll();
    }

}
