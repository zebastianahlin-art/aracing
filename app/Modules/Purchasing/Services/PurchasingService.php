<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Services;

use App\Modules\Purchasing\Repositories\PurchaseListItemRepository;
use App\Modules\Purchasing\Repositories\PurchaseListRepository;
use App\Modules\Purchasing\Repositories\RefillNeedRepository;
use InvalidArgumentException;

final class PurchasingService
{
    private const ALLOWED_STATUSES = ['draft', 'reviewed', 'exported'];

    public function __construct(
        private readonly RefillNeedRepository $refillNeeds,
        private readonly PurchaseListRepository $purchaseLists,
        private readonly PurchaseListItemRepository $purchaseListItems,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function listRefillNeeds(array $filters = []): array
    {
        $rows = $this->refillNeeds->listRefillNeeds($filters);

        foreach ($rows as &$row) {
            $row['suggested_quantity'] = $this->suggestedQuantity($row['stock_quantity']);
            $row['refill_indicator'] = $this->refillIndicator($row['stock_quantity']);
        }

        return $rows;
    }

    /** @param array<int, int|string> $selectedProductIds
     *  @param array<int|string, mixed> $selectedQuantities
     */
    public function createManualPurchaseList(string $name, ?string $notes, array $selectedProductIds, array $selectedQuantities): int
    {
        $normalizedName = trim($name);
        if ($normalizedName === '') {
            throw new InvalidArgumentException('Namn på inköpsunderlag krävs.');
        }

        $productIds = array_values(array_unique(array_map('intval', $selectedProductIds)));
        $productIds = array_filter($productIds, static fn (int $id): bool => $id > 0);
        if ($productIds === []) {
            throw new InvalidArgumentException('Markera minst en produkt för inköpsunderlaget.');
        }

        $rows = $this->refillNeeds->listByProductIds($productIds);
        if ($rows === []) {
            throw new InvalidArgumentException('Kunde inte hämta underlag för valda produkter.');
        }

        $purchaseListId = $this->purchaseLists->create($normalizedName, 'draft', $this->normalizeNotes($notes));
        foreach ($rows as $row) {
            $productId = (int) $row['product_id'];
            $suggestedQuantity = $this->suggestedQuantity($row['stock_quantity']);
            $selectedQuantity = $this->normalizeQuantity($selectedQuantities[$productId] ?? $suggestedQuantity, $suggestedQuantity);

            $this->purchaseListItems->create($purchaseListId, [
                'product_id' => $productId,
                'supplier_id' => $row['supplier_id'] !== null ? (int) $row['supplier_id'] : null,
                'supplier_item_id' => $row['supplier_item_id'] !== null ? (int) $row['supplier_item_id'] : null,
                'product_name_snapshot' => (string) $row['product_name'],
                'sku_snapshot' => $this->normalizeNullableString($row['sku'] ?? null),
                'supplier_sku_snapshot' => $this->normalizeNullableString($row['supplier_sku_snapshot'] ?? null),
                'supplier_title_snapshot' => $this->normalizeNullableString($row['supplier_title_snapshot'] ?? null),
                'supplier_price_snapshot' => $row['supplier_price_snapshot'],
                'supplier_stock_snapshot' => $row['supplier_stock_snapshot'] !== null ? (int) $row['supplier_stock_snapshot'] : null,
                'current_stock_quantity' => $row['stock_quantity'] !== null ? (int) $row['stock_quantity'] : null,
                'suggested_quantity' => $suggestedQuantity,
                'selected_quantity' => $selectedQuantity,
            ]);
        }

        return $purchaseListId;
    }

    /** @return array<int, array<string, mixed>> */
    public function listPurchaseLists(): array
    {
        return $this->purchaseLists->listAll();
    }

    /** @return array<string, mixed>|null */
    public function getPurchaseListDetail(int $id): ?array
    {
        $list = $this->purchaseLists->findById($id);
        if ($list === null) {
            return null;
        }

        $items = $this->purchaseListItems->listByPurchaseListId($id);

        return [
            'list' => $list,
            'items' => $items,
        ];
    }

    public function updatePurchaseListMeta(int $id, string $status, ?string $notes): void
    {
        $normalizedStatus = trim($status);
        if (!in_array($normalizedStatus, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Ogiltig status för inköpsunderlag.');
        }

        $this->purchaseLists->updateMeta($id, $normalizedStatus, $this->normalizeNotes($notes));
    }

    public function updateSelectedQuantity(int $itemId, mixed $selectedQuantity): void
    {
        $quantity = $this->normalizeQuantity($selectedQuantity, 1);
        $this->purchaseListItems->updateSelectedQuantity($itemId, $quantity);
    }

    /** @return array<int, string> */
    public function statusOptions(): array
    {
        return self::ALLOWED_STATUSES;
    }

    private function suggestedQuantity(mixed $stockQuantity): int
    {
        if ($stockQuantity === null) {
            return 5;
        }

        return max(1, 5 - (int) $stockQuantity);
    }

    private function refillIndicator(mixed $stockQuantity): string
    {
        if ($stockQuantity === null) {
            return 'Saknar publicerat saldo';
        }

        return (int) $stockQuantity <= 0 ? 'Kritiskt låg' : 'Låg nivå';
    }

    private function normalizeNotes(?string $notes): ?string
    {
        $normalized = trim((string) $notes);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeQuantity(mixed $value, int $fallback): int
    {
        if ($value === null || trim((string) $value) === '' || is_numeric($value) === false) {
            return $fallback;
        }

        return max(1, (int) $value);
    }
}
