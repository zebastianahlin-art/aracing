<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Repositories\CatalogRepository;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Product\Services\ProductRelationService;

final class ProductRecommendationService
{
    public function __construct(
        private readonly ProductRelationService $relations,
        private readonly CatalogRepository $catalog,
        private readonly InventoryService $inventory
    ) {
    }

    /** @param array<string,mixed> $product
     * @return array<int,array<string,mixed>>
     */
    public function relatedProducts(array $product, int $max = 6): array
    {
        return $this->resolveProducts($product, 'related', $max);
    }

    /** @param array<string,mixed> $product
     * @return array<int,array<string,mixed>>
     */
    public function crossSellProducts(array $product, int $max = 4): array
    {
        return $this->resolveProducts($product, 'cross_sell', $max);
    }

    /** @param array<string,mixed> $product
     * @return array<int,array<string,mixed>>
     */
    private function resolveProducts(array $product, string $relationType, int $max): array
    {
        $productId = (int) ($product['id'] ?? 0);
        if ($productId <= 0 || $max <= 0) {
            return [];
        }

        $manualIds = $this->relations->manualRelatedProductIds($productId, $relationType, $max);
        $manualProducts = $this->decorateProducts($this->catalog->publicProductsByIds($manualIds));

        if (count($manualProducts) >= $max) {
            return array_slice($manualProducts, 0, $max);
        }

        $existingIds = array_map(static fn (array $row): int => (int) $row['id'], $manualProducts);
        $need = $max - count($manualProducts);

        $fallback = $this->fallbackProducts($product, $need, $existingIds);

        return array_slice(array_merge($manualProducts, $fallback), 0, $max);
    }

    /** @param array<string,mixed> $product
     * @param array<int,int> $excludeIds
     * @return array<int,array<string,mixed>>
     */
    private function fallbackProducts(array $product, int $limit, array $excludeIds): array
    {
        if ($limit <= 0) {
            return [];
        }

        $productId = (int) ($product['id'] ?? 0);
        $categoryId = isset($product['category_id']) ? (int) $product['category_id'] : null;
        $brandId = isset($product['brand_id']) ? (int) $product['brand_id'] : null;

        $rows = $this->catalog->fallbackRelatedProducts($productId, $categoryId, $brandId, $limit, $excludeIds);
        if (count($rows) < $limit) {
            $missing = $limit - count($rows);
            $excludeFallbackIds = array_merge(
                $excludeIds,
                array_map(static fn (array $row): int => (int) $row['id'], $rows)
            );
            $rows = array_merge(
                $rows,
                $this->catalog->fallbackRelatedProducts($productId, $categoryId, null, $missing, $excludeFallbackIds)
            );
        }

        return $this->decorateProducts($rows);
    }

    /** @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function decorateProducts(array $rows): array
    {
        foreach ($rows as &$row) {
            $availability = $this->inventory->storefrontAvailability($row);
            $row['is_purchasable'] = $availability['is_purchasable'];
            $row['storefront_stock_label'] = $availability['label'];
        }
        unset($row);

        return $rows;
    }
}
