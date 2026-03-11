<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Catalog\Repositories\CatalogRepository;

final class CatalogService
{
    public function __construct(private readonly CatalogRepository $catalog, private readonly InventoryService $inventory)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function latestProducts(int $limit = 12): array
    {
        return $this->decorateProducts($this->catalog->latestActiveProducts($limit));
    }

    /** @param array<string, mixed> $query
     *  @return array<string, mixed>
     */
    public function categoryPage(string $slug, array $query = []): array
    {
        $category = $this->catalog->categoryBySlug($slug);

        if ($category === null) {
            return [
                'category' => null,
                'products' => [],
                'filters' => $this->normalizeFilters($query, null),
                'total' => 0,
                'filterOptions' => $this->filterOptions(),
            ];
        }

        $filters = $this->normalizeFilters($query, (int) $category['id']);

        return [
            'category' => $category,
            'products' => $this->decorateProducts($this->catalog->searchActiveProducts($filters)),
            'total' => $this->catalog->countActiveProducts($filters),
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
        ];
    }

    /** @param array<string, mixed> $query
     *  @return array<string, mixed>
     */
    public function searchPage(array $query = []): array
    {
        $filters = $this->normalizeFilters($query, null);

        return [
            'products' => $this->decorateProducts($this->catalog->searchActiveProducts($filters)),
            'total' => $this->catalog->countActiveProducts($filters),
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function productPage(string $slug): ?array
    {
        $product = $this->catalog->activeProductBySlug($slug);

        if ($product === null) {
            return null;
        }

        $product['attributes'] = $this->catalog->productAttributes((int) $product['id']);
        $product['images'] = $this->catalog->productImages((int) $product['id']);
        $product = $this->decorateProduct($product);

        return $product;
    }

    /** @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function decorateProducts(array $rows): array
    {
        foreach ($rows as &$row) {
            $row = $this->decorateProduct($row);
        }
        unset($row);

        return $rows;
    }

    /** @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function decorateProduct(array $row): array
    {
        $availability = $this->inventory->storefrontAvailability($row);
        $row['is_purchasable'] = $availability['is_purchasable'];
        $row['storefront_stock_label'] = $availability['label'];

        return $row;
    }

    /** @param array<string, mixed> $query
     *  @return array<string, mixed>
     */
    private function normalizeFilters(array $query, ?int $forcedCategoryId): array
    {
        $queryText = trim((string) ($query['q'] ?? ''));
        $sort = (string) ($query['sort'] ?? ($queryText !== '' ? 'relevance' : 'curated'));
        $allowedSorts = ['curated', 'relevance', 'latest', 'name_asc', 'name_desc', 'price_asc', 'price_desc'];

        return [
            'q' => $queryText,
            'category_id' => $forcedCategoryId ?? max(0, (int) ($query['category_id'] ?? 0)),
            'brand_id' => max(0, (int) ($query['brand_id'] ?? 0)),
            'min_price' => trim((string) ($query['min_price'] ?? '')),
            'max_price' => trim((string) ($query['max_price'] ?? '')),
            'stock_status' => trim((string) ($query['stock_status'] ?? '')),
            'sort' => in_array($sort, $allowedSorts, true) ? $sort : 'latest',
        ];
    }

    /** @return array<string, mixed> */
    private function filterOptions(): array
    {
        return [
            'categories' => $this->catalog->filterCategories(),
            'brands' => $this->catalog->filterBrands(),
            'stock_statuses' => $this->catalog->filterStockStatuses(),
            'sorts' => [
                'curated' => 'Rekommenderad',
                'relevance' => 'Bästa träff',
                'latest' => 'Senaste',
                'name_asc' => 'Namn A-Ö',
                'name_desc' => 'Namn Ö-A',
                'price_asc' => 'Pris stigande',
                'price_desc' => 'Pris fallande',
            ],
        ];
    }
}
