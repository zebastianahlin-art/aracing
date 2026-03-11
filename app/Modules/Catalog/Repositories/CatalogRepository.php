<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use PDO;

final class CatalogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function latestActiveProducts(int $limit = 12): array
    {
        $stmt = $this->pdo->prepare('SELECT p.id, p.name, p.slug, p.sku, p.description, p.seo_title, p.seo_description, p.canonical_url, p.meta_robots, p.is_indexable, p.sale_price, p.currency_code, p.stock_status, p.stock_quantity, p.backorder_allowed, p.is_featured, p.search_boost, p.sort_priority, b.name AS brand_name,
            (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS image_url
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            WHERE ' . $this->publicVisibilityWhereSql() . '
            ORDER BY p.sort_priority DESC, p.is_featured DESC, p.search_boost DESC, p.updated_at DESC, p.id DESC
            LIMIT :limit');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function categoryBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, slug, seo_title, seo_description, canonical_url, meta_robots, is_indexable FROM categories WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function activeProductsByCategory(int $categoryId): array
    {
        return $this->searchActiveProducts([
            'category_id' => $categoryId,
            'sort' => 'curated',
        ]);
    }

    /** @param array<string, mixed> $filters
     *  @return array<int, array<string, mixed>>
     */
    public function searchActiveProducts(array $filters): array
    {
        $params = [];
        $where = $this->buildListingWhere($filters, $params);
        $orderBy = $this->resolveOrderBy($filters, $params);

        $sql = 'SELECT p.id, p.name, p.slug, p.sku, p.sale_price, p.currency_code, p.stock_status, p.stock_quantity, p.backorder_allowed, p.is_featured, p.search_boost, p.sort_priority,
            b.name AS brand_name, c.name AS category_name,
            (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS image_url
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $orderBy;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $filters */
    public function countActiveProducts(array $filters): int
    {
        $params = [];
        $where = $this->buildListingWhere($filters, $params);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE ' . implode(' AND ', $where));
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array{id:int,name:string,slug:string}> */
    public function filterCategories(): array
    {
        return $this->pdo->query('SELECT c.id, c.name, c.slug
            FROM categories c
            INNER JOIN products p ON p.category_id = c.id
            WHERE ' . $this->publicVisibilityWhereSql('p') . '
            GROUP BY c.id, c.name, c.slug
            ORDER BY c.name ASC')->fetchAll();
    }

    /** @return array<int, array{id:int,name:string,slug:string}> */
    public function filterBrands(): array
    {
        return $this->pdo->query('SELECT b.id, b.name, b.slug
            FROM brands b
            INNER JOIN products p ON p.brand_id = b.id
            WHERE ' . $this->publicVisibilityWhereSql('p') . '
            GROUP BY b.id, b.name, b.slug
            ORDER BY b.name ASC')->fetchAll();
    }

    /** @param array<string,mixed> $filters
     *  @return array<int,array{id:int,name:string,product_count:int}>
     */
    public function filterBrandsByContext(array $filters): array
    {
        $params = [];
        $where = $this->buildListingWhere($filters, $params, ['brand_id']);

        $stmt = $this->pdo->prepare('SELECT b.id, b.name, COUNT(*) AS product_count
            FROM products p
            INNER JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY b.id, b.name
            ORDER BY b.name ASC');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<int, string> */
    public function filterStockStatuses(): array
    {
        $rows = $this->pdo->query("SELECT DISTINCT stock_status
            FROM products
            WHERE " . $this->publicVisibilityWhereSql('products') . "
            ORDER BY FIELD(stock_status, 'in_stock', 'out_of_stock', 'backorder')")->fetchAll();

        return array_map(static fn (array $row): string => (string) $row['stock_status'], $rows);
    }

    /** @param array<string,mixed> $filters
     *  @return array<int,array{stock_status:string,product_count:int}>
     */
    public function filterStockStatusesByContext(array $filters): array
    {
        $params = [];
        $where = $this->buildListingWhere($filters, $params, ['stock_status']);

        $stmt = $this->pdo->prepare("SELECT p.stock_status, COUNT(*) AS product_count
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY p.stock_status
            ORDER BY FIELD(p.stock_status, 'in_stock', 'out_of_stock', 'backorder')");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @param array<string,mixed> $filters
     *  @return array<int,array{id:int,name:string,product_count:int}>
     */
    public function filterCategoriesByContext(array $filters): array
    {
        $params = [];
        $where = $this->buildListingWhere($filters, $params, ['category_id']);

        $stmt = $this->pdo->prepare('SELECT c.id, c.name, COUNT(*) AS product_count
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            LEFT JOIN brands b ON b.id = p.brand_id
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY c.id, c.name
            ORDER BY c.name ASC');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function activeProductBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT p.id, p.brand_id, p.category_id, p.name, p.slug, p.sku, p.description, p.seo_title, p.seo_description, p.canonical_url, p.meta_robots, p.is_indexable, p.sale_price, p.currency_code, p.stock_status, p.stock_quantity, p.backorder_allowed, p.is_featured, p.search_boost, p.sort_priority, b.name AS brand_name,
            (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS image_url
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            WHERE p.slug = :slug AND ' . $this->publicVisibilityWhereSql());
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function productAttributes(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT attribute_key, attribute_value FROM product_attributes WHERE product_id = :product_id ORDER BY attribute_key ASC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    /** @param array<int,int> $ids
     * @return array<int,array<string,mixed>>
     */
    public function publicProductsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT p.id, p.name, p.slug, p.sku, p.description, p.review_count, p.average_rating,
                    p.sale_price, p.currency_code, p.stock_status, p.stock_quantity, p.backorder_allowed,
                    p.is_featured, p.search_boost, p.sort_priority,
                    b.name AS brand_name,
                    (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS image_url
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                WHERE ' . $this->publicVisibilityWhereSql() . ' AND p.id IN (' . $placeholders . ')';

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

    /** @param array<int,int> $excludeIds
     * @return array<int,array<string,mixed>>
     */
    public function fallbackRelatedProducts(int $currentProductId, ?int $categoryId, ?int $brandId, int $limit, array $excludeIds = []): array
    {
        if ($limit <= 0) {
            return [];
        }

        $excludeIds = array_values(array_unique(array_merge([$currentProductId], $excludeIds)));
        $excludePlaceholders = implode(',', array_fill(0, count($excludeIds), '?'));

        $sql = 'SELECT p.id, p.name, p.slug, p.sku, p.sale_price, p.currency_code, p.stock_status, p.stock_quantity, p.backorder_allowed,
                    p.is_featured, p.search_boost, p.sort_priority,
                    b.name AS brand_name,
                    (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS image_url
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                WHERE ' . $this->publicVisibilityWhereSql() . '
                  AND p.id NOT IN (' . $excludePlaceholders . ')';

        $params = $excludeIds;

        if ($categoryId !== null && $categoryId > 0) {
            $sql .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }

        if ($brandId !== null && $brandId > 0) {
            $sql .= ' AND p.brand_id = ?';
            $params[] = $brandId;
        }

        $sql .= " ORDER BY FIELD(p.stock_status, 'in_stock', 'backorder', 'out_of_stock') ASC,
                          p.sort_priority DESC,
                          p.is_featured DESC,
                          p.search_boost DESC,
                          p.updated_at DESC,
                          p.id DESC
                  LIMIT ?";
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function productImages(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT image_url, alt_text, sort_order, is_primary FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC, id ASC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $filters
     *  @param array<string, mixed> $params
     *  @return array<int, string>
     */
    private function buildListingWhere(array $filters, array &$params, array $ignoredFilters = []): array
    {
        $where = [$this->publicVisibilityWhereSql()];
        $ignored = array_fill_keys($ignoredFilters, true);

        if (!isset($ignored['category_id']) && isset($filters['category_id']) && (int) $filters['category_id'] > 0) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (!isset($ignored['brand_id']) && isset($filters['brand_id']) && (int) $filters['brand_id'] > 0) {
            $where[] = 'p.brand_id = :brand_id';
            $params['brand_id'] = (int) $filters['brand_id'];
        }

        if (!isset($ignored['min_price']) && !empty($filters['min_price'])) {
            $where[] = 'p.sale_price >= :min_price';
            $params['min_price'] = (float) $filters['min_price'];
        }

        if (!isset($ignored['max_price']) && !empty($filters['max_price'])) {
            $where[] = 'p.sale_price <= :max_price';
            $params['max_price'] = (float) $filters['max_price'];
        }

        if (!isset($ignored['stock_status']) && !empty($filters['stock_status'])) {
            $where[] = 'p.stock_status = :stock_status';
            $params['stock_status'] = (string) $filters['stock_status'];
        }

        if (!isset($ignored['q']) && !empty($filters['q'])) {
            $where[] = '(p.name LIKE :search_term OR p.sku LIKE :search_term OR b.name LIKE :search_term OR c.name LIKE :search_term)';
            $params['search_term'] = '%' . (string) $filters['q'] . '%';
        }

        if (!isset($ignored['fitment_only'])
            && (($filters['fitment_only'] ?? '0') === '1')
            && (int) ($filters['fitment_vehicle_id'] ?? 0) > 0) {
            $where[] = "EXISTS (
                SELECT 1
                FROM product_fitments pf
                WHERE pf.product_id = p.id
                  AND ((pf.vehicle_id = :fitment_vehicle_id AND pf.fitment_type = 'confirmed') OR pf.fitment_type = 'universal')
            )";
            $params['fitment_vehicle_id'] = (int) $filters['fitment_vehicle_id'];
        }

        return $where;
    }

    /** @param array<string, mixed> $filters
     * @param array<string, mixed> $params
     */
    private function resolveOrderBy(array $filters, array &$params): string
    {
        $sort = (string) ($filters['sort'] ?? 'curated');

        if ($sort === 'relevance' && !empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $params['q_exact'] = $q;
            $params['q_prefix'] = $q . '%';
            $params['q_contains'] = '%' . $q . '%';

            $score = "(CASE
                WHEN p.name = :q_exact THEN 120
                WHEN p.name LIKE :q_prefix THEN 80
                WHEN p.name LIKE :q_contains THEN 40
                ELSE 0
            END)
            + (CASE WHEN p.sku = :q_exact THEN 45 WHEN p.sku LIKE :q_prefix THEN 25 ELSE 0 END)
            + (CASE WHEN b.name = :q_exact THEN 20 WHEN b.name LIKE :q_contains THEN 10 ELSE 0 END)
            + (CASE WHEN c.name LIKE :q_contains THEN 8 ELSE 0 END)
            + (p.search_boost * 3)
            + (p.sort_priority * 2)
            + (CASE WHEN p.is_featured = 1 THEN 12 ELSE 0 END)
            + (CASE WHEN p.stock_status = 'in_stock' THEN 6 WHEN p.stock_status = 'backorder' THEN 2 ELSE 0 END)
            + (CASE WHEN p.sale_price IS NULL THEN -2 ELSE 2 END)";

            return $score . ' DESC, p.sort_priority DESC, p.is_featured DESC, p.updated_at DESC, p.id DESC';
        }

        return $this->mapSort($sort);
    }

    private function mapSort(string $sort): string
    {
        return match ($sort) {
            'name_asc' => 'p.name ASC',
            'name_desc' => 'p.name DESC',
            'price_asc' => 'p.sale_price ASC, p.name ASC',
            'price_desc' => 'p.sale_price DESC, p.name ASC',
            'latest' => 'p.updated_at DESC, p.id DESC',
            default => "p.sort_priority DESC, p.is_featured DESC, p.search_boost DESC, FIELD(p.stock_status, 'in_stock', 'backorder', 'out_of_stock') ASC, p.updated_at DESC, p.id DESC",
        };
    }

    private function publicVisibilityWhereSql(string $tableAlias = 'p'): string
    {
        return $tableAlias . '.is_active = 1 AND ' . $tableAlias . '.is_search_hidden = 0';
    }
}
