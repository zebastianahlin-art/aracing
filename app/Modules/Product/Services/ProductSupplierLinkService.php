<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Modules\Product\Repositories\ProductSupplierItemLookupRepository;
use App\Modules\Product\Repositories\ProductSupplierLinkRepository;

final class ProductSupplierLinkService
{
    public function __construct(
        private readonly ProductSupplierLinkRepository $links,
        private readonly ProductSupplierItemLookupRepository $items
    ) {
    }

    /** @return array<string, mixed>|null */
    public function primaryLinkForProduct(int $productId): ?array
    {
        return $this->links->primaryForProduct($productId);
    }

    /** @return array<int, array<string, mixed>> */
    public function searchSupplierItems(?int $supplierId, string $query): array
    {
        return $this->items->search($supplierId, $query);
    }

    /** @param array<string, string> $input */
    public function syncPrimaryFromInput(int $productId, array $input): void
    {
        $supplierItemId = $this->toNullableInt($input['supplier_item_id'] ?? null);
        $isPrimary = isset($input['link_is_primary']) ? 1 : 0;

        if ($supplierItemId === null || $isPrimary === 0) {
            $this->links->clearByProductId($productId);

            return;
        }

        $supplierItem = $this->items->findById($supplierItemId);
        if ($supplierItem === null || $supplierItem['supplier_id'] === null) {
            $this->links->clearByProductId($productId);

            return;
        }

        $this->syncPrimaryFromSupplierItem($productId, (int) $supplierItem['id']);
    }

    public function syncPrimarySnapshot(int $productId): bool
    {
        $primaryLink = $this->links->primaryForProduct($productId);
        if ($primaryLink === null) {
            return false;
        }

        return $this->syncPrimaryFromSupplierItem($productId, (int) $primaryLink['supplier_item_id']);
    }

    private function syncPrimaryFromSupplierItem(int $productId, int $supplierItemId): bool
    {
        $supplierItem = $this->items->findById($supplierItemId);
        if ($supplierItem === null || $supplierItem['supplier_id'] === null) {
            return false;
        }

        $this->links->upsertPrimary($productId, [
            'supplier_item_id' => $supplierItemId,
            'supplier_id' => (int) $supplierItem['supplier_id'],
            'is_primary' => 1,
            'supplier_sku_snapshot' => $supplierItem['supplier_sku'] ?: null,
            'supplier_title_snapshot' => $supplierItem['supplier_title'] ?: null,
            'supplier_price_snapshot' => $supplierItem['price'],
            'supplier_stock_snapshot' => $supplierItem['stock_qty'],
        ]);

        return true;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '' || ctype_digit($normalized) === false) {
            return null;
        }

        return (int) $normalized;
    }
}
