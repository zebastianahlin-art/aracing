<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Catalog\Repositories\CatalogRepository;
use App\Modules\Fitment\Services\FitmentStorefrontService;
use App\Modules\Fitment\Services\FitmentService;
use App\Modules\Fitment\Services\FitmentCoverageService;
use App\Modules\Catalog\Repositories\SearchQueryAliasRepository;

final class CatalogService
{
    private const ALLOWED_SORTS = ['curated', 'relevance', 'latest', 'name_asc', 'name_desc', 'price_asc', 'price_desc'];
    private const ALLOWED_STOCK_STATUSES = ['in_stock', 'out_of_stock', 'backorder'];

    public function __construct(
        private readonly CatalogRepository $catalog,
        private readonly InventoryService $inventory,
        private readonly ProductRecommendationService $recommendations,
        private readonly FitmentService $fitment,
        private readonly FitmentStorefrontService $fitmentStorefront,
        private readonly FitmentCoverageService $fitmentCoverage,
        private readonly SearchQueryAliasRepository $queryAliases
    ) {
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
            $filters = $this->normalizeFilters($query, null);

            return [
                'category' => null,
                'products' => [],
                'filters' => $filters,
                'total' => 0,
                'filterOptions' => $this->filterOptions($filters),
                'activeFilters' => $this->activeFilters($filters, $this->filterOptions($filters), '/category/' . rawurlencode($slug)),
                'clearAllUrl' => '/category/' . rawurlencode($slug),
            ];
        }

        $filters = $this->normalizeFilters($query, (int) $category['id']);
        $total = $this->catalog->countActiveProducts($filters);
        $filterOptions = $this->filterOptions($filters);

        $activeVehicle = $this->fitment->selectedVehicle();

        return [
            'category' => $category,
            'products' => $this->fitmentStorefront->decorateProductCardsWithFitment($this->decorateProducts($this->catalog->searchActiveProducts($filters))),
            'total' => $total,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'activeFilters' => $this->activeFilters($filters, $filterOptions, '/category/' . rawurlencode((string) $category['slug'])),
            'clearAllUrl' => $this->buildUrl('/category/' . rawurlencode((string) $category['slug']), $this->baseQueryParams($filters)),
            'fitmentUi' => $this->fitmentStorefront->catalogFitmentUiPayload($filters, $activeVehicle),
            'coverageSignal' => $this->fitmentCoverage->categoryContextSignal($filters, $activeVehicle, $total),
        ];
    }

    /** @param array<string, mixed> $query
     *  @return array<string, mixed>
     */
    public function searchPage(array $query = []): array
    {
        $filters = $this->normalizeFilters($query, null);
        $total = $this->catalog->countActiveProducts($filters);
        $filterOptions = $this->filterOptions($filters);

        return [
            'products' => $this->fitmentStorefront->decorateProductCardsWithFitment($this->decorateProducts($this->catalog->searchActiveProducts($filters))),
            'total' => $total,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'activeFilters' => $this->activeFilters($filters, $filterOptions, '/search'),
            'clearAllUrl' => $this->buildUrl('/search', $this->baseQueryParams($filters)),
            'fitmentUi' => $this->fitmentStorefront->catalogFitmentUiPayload($filters, $this->fitment->selectedVehicle()),
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
        $product['related_products'] = $this->recommendations->relatedProducts($product, 6);
        $product['cross_sell_products'] = $this->recommendations->crossSellProducts($product, 4);

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
        $queryText = mb_substr($queryText, 0, 120);
        if ($queryText !== '') {
            $normalizedQuery = mb_strtolower(preg_replace('/\s+/', ' ', $queryText) ?? $queryText);
            $alias = $this->queryAliases->resolveActiveTarget($normalizedQuery);
            if (is_string($alias) && trim($alias) !== '') {
                $queryText = mb_substr(trim($alias), 0, 120);
            }
        }

        $sortInput = trim((string) ($query['sort'] ?? ''));
        $sort = $sortInput !== '' ? $sortInput : ($queryText !== '' ? 'relevance' : 'curated');

        $brandId = max(0, (int) ($query['brand_id'] ?? 0));
        $categoryId = $forcedCategoryId ?? max(0, (int) ($query['category_id'] ?? 0));
        $stockStatus = trim((string) ($query['stock_status'] ?? ''));

        $minPrice = $this->normalizePriceValue($query['min_price'] ?? '');
        $maxPrice = $this->normalizePriceValue($query['max_price'] ?? '');
        if ($minPrice !== '' && $maxPrice !== '' && (float) $minPrice > (float) $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        return [
            'q' => $queryText,
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'stock_status' => in_array($stockStatus, self::ALLOWED_STOCK_STATUSES, true) ? $stockStatus : '',
            'fitment_vehicle_id' => max(0, (int) ($query['fitment_vehicle_id'] ?? 0)),
            'fitment_only' => ((string) ($query['fitment_only'] ?? '0')) === '1' ? '1' : '0',
            'sort' => in_array($sort, self::ALLOWED_SORTS, true) ? $sort : ($queryText !== '' ? 'relevance' : 'curated'),
        ];
    }

    /** @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function filterOptions(array $filters): array
    {
        $contextFilters = [
            'q' => $filters['q'],
            'category_id' => $filters['category_id'],
            'brand_id' => $filters['brand_id'],
            'min_price' => $filters['min_price'],
            'max_price' => $filters['max_price'],
            'stock_status' => $filters['stock_status'],
            'fitment_vehicle_id' => $filters['fitment_vehicle_id'],
            'fitment_only' => $filters['fitment_only'],
        ];

        return [
            'categories' => $this->catalog->filterCategoriesByContext($contextFilters),
            'brands' => $this->catalog->filterBrandsByContext($contextFilters),
            'stock_statuses' => $this->catalog->filterStockStatusesByContext($contextFilters),
            'sorts' => [
                'curated' => 'Rekommenderad',
                'relevance' => 'Bästa träff',
                'latest' => 'Senaste',
                'name_asc' => 'Namn A-Ö',
                'name_desc' => 'Namn Ö-A',
                'price_asc' => 'Pris stigande',
                'price_desc' => 'Pris fallande',
            ],
            'stock_status_labels' => [
                'in_stock' => 'I lager',
                'backorder' => 'Beställningsvara',
                'out_of_stock' => 'Tillfälligt slut',
            ],
        ];
    }

    private function normalizePriceValue(mixed $value): string
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return '';
        }

        $float = (float) $normalized;
        if ($float < 0) {
            return '';
        }

        return number_format($float, 2, '.', '');
    }

    /** @param array<string,mixed> $filters
     *  @param array<string,mixed> $filterOptions
     *  @return array<int,array{key:string,label:string,remove_url:string}>
     */
    private function activeFilters(array $filters, array $filterOptions, string $basePath): array
    {
        $items = [];
        $baseParams = $this->baseQueryParams($filters);

        if ($filters['q'] !== '') {
            $items[] = [
                'key' => 'q',
                'label' => 'Sök: ' . $filters['q'],
                'remove_url' => $this->buildUrl($basePath, $this->withoutParam($baseParams, 'q')),
            ];
        }

        if ((int) $filters['brand_id'] > 0) {
            $brandName = null;
            foreach (($filterOptions['brands'] ?? []) as $brand) {
                if ((int) $brand['id'] === (int) $filters['brand_id']) {
                    $brandName = (string) $brand['name'];
                    break;
                }
            }

            $items[] = [
                'key' => 'brand_id',
                'label' => 'Varumärke: ' . ($brandName ?? '#' . (int) $filters['brand_id']),
                'remove_url' => $this->buildUrl($basePath, $this->withoutParam($baseParams, 'brand_id')),
            ];
        }

        if ((int) $filters['category_id'] > 0) {
            $categoryName = null;
            foreach (($filterOptions['categories'] ?? []) as $category) {
                if ((int) $category['id'] === (int) $filters['category_id']) {
                    $categoryName = (string) $category['name'];
                    break;
                }
            }

            $items[] = [
                'key' => 'category_id',
                'label' => 'Kategori: ' . ($categoryName ?? '#' . (int) $filters['category_id']),
                'remove_url' => $this->buildUrl($basePath, $this->withoutParam($baseParams, 'category_id')),
            ];
        }

        if ($filters['stock_status'] !== '') {
            $items[] = [
                'key' => 'stock_status',
                'label' => 'Lager: ' . (($filterOptions['stock_status_labels'][$filters['stock_status']] ?? $filters['stock_status'])),
                'remove_url' => $this->buildUrl($basePath, $this->withoutParam($baseParams, 'stock_status')),
            ];
        }

        if ($filters['fitment_only'] === '1' && (int) $filters['fitment_vehicle_id'] > 0) {
            $items[] = [
                'key' => 'fitment_only',
                'label' => 'YMM: Endast passande produkter',
                'remove_url' => $this->buildUrl($basePath, $this->withoutParam($baseParams, 'fitment_only')),
            ];
        }

        if ($filters['min_price'] !== '' || $filters['max_price'] !== '') {
            $priceLabel = 'Pris: ';
            if ($filters['min_price'] !== '' && $filters['max_price'] !== '') {
                $priceLabel .= $filters['min_price'] . ' - ' . $filters['max_price'] . ' SEK';
            } elseif ($filters['min_price'] !== '') {
                $priceLabel .= 'från ' . $filters['min_price'] . ' SEK';
            } else {
                $priceLabel .= 'upp till ' . $filters['max_price'] . ' SEK';
            }

            $params = $this->withoutParam($baseParams, 'min_price');
            $params = $this->withoutParam($params, 'max_price');
            $items[] = [
                'key' => 'price',
                'label' => $priceLabel,
                'remove_url' => $this->buildUrl($basePath, $params),
            ];
        }

        return $items;
    }

    /** @param array<string,mixed> $filters
     * @return array<string,string>
     */
    private function baseQueryParams(array $filters): array
    {
        $params = [
            'q' => (string) $filters['q'],
            'category_id' => (int) $filters['category_id'] > 0 ? (string) $filters['category_id'] : '',
            'brand_id' => (int) $filters['brand_id'] > 0 ? (string) $filters['brand_id'] : '',
            'stock_status' => (string) $filters['stock_status'],
            'min_price' => (string) $filters['min_price'],
            'max_price' => (string) $filters['max_price'],
            'fitment_only' => (string) $filters['fitment_only'],
            'fitment_vehicle_id' => (int) $filters['fitment_vehicle_id'] > 0 ? (string) $filters['fitment_vehicle_id'] : '',
            'sort' => (string) $filters['sort'],
        ];

        return array_filter($params, static fn (string $value): bool => $value !== '');
    }

    /** @param array<string,string> $params
     * @return array<string,string>
     */
    private function withoutParam(array $params, string $param): array
    {
        unset($params[$param]);

        return $params;
    }

    /** @param array<string,string> $params */
    private function buildUrl(string $path, array $params): string
    {
        if ($params === []) {
            return $path;
        }

        return $path . '?' . http_build_query($params);
    }
}
