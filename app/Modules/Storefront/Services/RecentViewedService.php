<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Services;

use App\Modules\Catalog\Repositories\CatalogRepository;
use App\Modules\Inventory\Services\InventoryService;

final class RecentViewedService
{
    private const SESSION_KEY = 'recently_viewed_product_ids';
    private const MAX_STORED = 12;

    public function __construct(
        private readonly CatalogRepository $catalog,
        private readonly InventoryService $inventory
    ) {
    }

    public function trackProductView(int $productId): void
    {
        if ($productId <= 0) {
            return;
        }

        $ids = $this->recentViewedIds();
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id !== $productId));
        array_unshift($ids, $productId);

        if (count($ids) > self::MAX_STORED) {
            $ids = array_slice($ids, 0, self::MAX_STORED);
        }

        $_SESSION[self::SESSION_KEY] = $ids;
    }

    /** @return array<int,array<string,mixed>> */
    public function recentlyViewedProducts(int $limit = 6, ?int $excludeProductId = null): array
    {
        if ($limit <= 0) {
            return [];
        }

        $ids = $this->recentViewedIds();
        if ($excludeProductId !== null && $excludeProductId > 0) {
            $ids = array_values(array_filter($ids, static fn (int $id): bool => $id !== $excludeProductId));
        }

        if ($ids === []) {
            return [];
        }

        $products = $this->catalog->publicProductsByIds($ids);
        $products = array_slice($products, 0, $limit);

        foreach ($products as &$product) {
            $availability = $this->inventory->storefrontAvailability($product);
            $product['is_purchasable'] = $availability['is_purchasable'];
            $product['storefront_stock_label'] = $availability['label'];
        }
        unset($product);

        return $products;
    }

    /** @return array<int,int> */
    public function recentViewedIds(): array
    {
        $raw = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach ($raw as $value) {
            $id = (int) $value;
            if ($id > 0 && !in_array($id, $clean, true)) {
                $clean[] = $id;
            }
        }

        if (count($clean) > self::MAX_STORED) {
            $clean = array_slice($clean, 0, self::MAX_STORED);
        }

        return $clean;
    }
}

