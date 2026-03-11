<?php

declare(strict_types=1);

namespace App\Modules\Compare\Services;

use App\Modules\Catalog\Repositories\CatalogRepository;
use App\Modules\Inventory\Services\InventoryService;
use InvalidArgumentException;
use RuntimeException;

final class CompareService
{
    private const SESSION_KEY = 'compare_product_ids';
    private const MAX_ITEMS = 4;

    public function __construct(
        private readonly CatalogRepository $catalog,
        private readonly InventoryService $inventory
    ) {
    }

    public function addProduct(int $productId): void
    {
        if ($productId <= 0) {
            throw new InvalidArgumentException('Ogiltig produkt för jämförelse.');
        }

        $ids = $this->compareIds();
        if (in_array($productId, $ids, true)) {
            return;
        }

        if (count($ids) >= self::MAX_ITEMS) {
            throw new RuntimeException('Du kan jämföra max ' . self::MAX_ITEMS . ' produkter åt gången.');
        }

        $visibleProducts = $this->catalog->publicProductsByIds([$productId]);
        if ($visibleProducts === []) {
            throw new InvalidArgumentException('Produkten är inte tillgänglig för jämförelse.');
        }

        $ids[] = $productId;
        $_SESSION[self::SESSION_KEY] = $ids;
    }

    public function removeProduct(int $productId): void
    {
        if ($productId <= 0) {
            throw new InvalidArgumentException('Ogiltig produkt för jämförelse.');
        }

        $ids = array_values(array_filter($this->compareIds(), static fn (int $id): bool => $id !== $productId));
        $_SESSION[self::SESSION_KEY] = $ids;
    }

    public function contains(int $productId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        return in_array($productId, $this->compareIds(), true);
    }

    public function count(): int
    {
        return count($this->compareIds());
    }

    public function maxItems(): int
    {
        return self::MAX_ITEMS;
    }

    /** @return array<int,array<string,mixed>> */
    public function comparedProducts(): array
    {
        $ids = $this->compareIds();
        if ($ids === []) {
            return [];
        }

        $products = $this->catalog->publicProductsByIds($ids);
        $visibleIds = array_map(static fn (array $product): int => (int) $product['id'], $products);
        if ($visibleIds !== $ids) {
            $_SESSION[self::SESSION_KEY] = $visibleIds;
        }

        foreach ($products as &$product) {
            $availability = $this->inventory->storefrontAvailability($product);
            $product['is_purchasable'] = $availability['is_purchasable'];
            $product['storefront_stock_label'] = $availability['label'];
        }
        unset($product);

        return $products;
    }

    /** @return array<int,int> */
    public function compareIds(): array
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

            if (count($clean) >= self::MAX_ITEMS) {
                break;
            }
        }

        return $clean;
    }
}
