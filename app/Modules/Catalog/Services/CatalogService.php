<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Repositories\CatalogRepository;

final class CatalogService
{
    public function __construct(private readonly CatalogRepository $catalog)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function latestProducts(int $limit = 12): array
    {
        return $this->catalog->latestActiveProducts($limit);
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
            'products' => $this->catalog->searchActiveProducts($filters),
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
            'products' => $this->catalog->searchActiveProducts($filters),
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

        return $product;
    }

    /** @param array<string, mixed> $query
     *  @return array<string, mixed>
     */
    private function normalizeFilters(array $query, ?int $forcedCategoryId): array
    {
        $sort = (string) ($query['sort'] ?? 'latest');
        $allowedSorts = ['latest', 'name_asc', 'name_desc', 'price_asc', 'price_desc'];

        return [
            'q' => trim((string) ($query['q'] ?? '')),
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
                'latest' => 'Senaste',
                'name_asc' => 'Namn A-Ö',
                'name_desc' => 'Namn Ö-A',
                'price_asc' => 'Pris stigande',
                'price_desc' => 'Pris fallande',
            ],
        ];
    }
}
