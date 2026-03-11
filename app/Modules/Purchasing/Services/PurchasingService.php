<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Services;

use App\Modules\Purchasing\Repositories\PurchaseListItemRepository;
use App\Modules\Purchasing\Repositories\PurchaseListRepository;
use App\Modules\Purchasing\Repositories\RefillNeedRepository;
use App\Modules\Purchasing\Repositories\RestockFlagRepository;
use InvalidArgumentException;

final class PurchasingService
{
    private const ALLOWED_STATUSES = ['draft', 'reviewed', 'exported'];
    private const MANUAL_RESTOCK_STATUSES = ['new', 'reviewed', 'handling'];
    private const LOW_STOCK_THRESHOLD = 2;

    public function __construct(
        private readonly RefillNeedRepository $refillNeeds,
        private readonly PurchaseListRepository $purchaseLists,
        private readonly PurchaseListItemRepository $purchaseListItems,
        private readonly RestockFlagRepository $restockFlags,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function listRefillNeeds(array $filters = []): array
    {
        $normalizedFilters = $this->normalizeRefillFilters($filters);
        $rows = $this->refillNeeds->listRefillNeeds(['search' => $normalizedFilters['search'], 'supplier_id' => $normalizedFilters['supplier_id'], 'low_stock_threshold' => self::LOW_STOCK_THRESHOLD]);

        $productIds = array_map(static fn (array $row): int => (int) $row['product_id'], $rows);
        $flags = $this->indexFlagsByProductId($this->restockFlags->listByProductIds($productIds));
        $filteredRows = [];

        foreach ($rows as $row) {
            $manual = $flags[(int) $row['product_id']] ?? null;
            $reasons = $this->buildReasons($row);

            $manualStatus = (string) ($manual['status'] ?? 'new');
            if (!in_array($manualStatus, self::MANUAL_RESTOCK_STATUSES, true)) {
                $manualStatus = 'new';
            }

            if ($normalizedFilters['reason'] !== '' && !in_array($normalizedFilters['reason'], $reasons, true)) {
                continue;
            }

            if ($normalizedFilters['manual_status'] !== '' && $manualStatus !== $normalizedFilters['manual_status']) {
                continue;
            }

            $row['suggested_quantity'] = $this->suggestedQuantity($row['stock_quantity']);
            $row['reasons'] = $reasons;
            $row['reason_labels'] = array_map(fn (string $reason): string => $this->reasonLabel($reason), $reasons);
            $row['manual_status'] = $manualStatus;
            $row['manual_note'] = $this->normalizeNotes($manual['note'] ?? null);
            $row['manual_updated_at'] = $manual['updated_at'] ?? null;
            $row['has_supplier_link'] = $row['supplier_item_id'] !== null || $row['supplier_id'] !== null;

            $filteredRows[] = $row;
        }

        return $filteredRows;
    }

    /** @return array<int,array{id:int,name:string}> */
    public function listSupplierOptions(): array
    {
        return $this->refillNeeds->listSupplierOptions();
    }

    /** @return array<int,string> */
    public function restockReasonOptions(): array
    {
        return [
            'out_of_stock' => 'Slut i lager',
            'low_stock' => 'Låg nivå',
            'backorder_enabled' => 'Backorder aktiv',
            'missing_supplier_link' => 'Utan leverantörskoppling',
        ];
    }

    /** @return array<int,string> */
    public function manualRestockStatusOptions(): array
    {
        return [
            'new' => 'Ny',
            'reviewed' => 'Granskad',
            'handling' => 'Hanteras',
        ];
    }

    public function updateRestockFlag(int $productId, string $status, ?string $note): void
    {
        if ($productId <= 0) {
            throw new InvalidArgumentException('Ogiltigt produkt-id för restockmarkering.');
        }

        $normalizedStatus = trim($status);
        if ($normalizedStatus === 'new') {
            $this->restockFlags->delete($productId);
            return;
        }

        if (!in_array($normalizedStatus, self::MANUAL_RESTOCK_STATUSES, true)) {
            throw new InvalidArgumentException('Ogiltig restockstatus.');
        }

        $this->restockFlags->upsert($productId, $normalizedStatus, $this->normalizeNotes($note));
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

    /** @param array<string,mixed> $row
     * @return array<int,string>
     */
    private function buildReasons(array $row): array
    {
        $reasons = [];

        if ((string) ($row['stock_status'] ?? '') === 'out_of_stock') {
            $reasons[] = 'out_of_stock';
        }

        if ($row['stock_quantity'] === null || (int) $row['stock_quantity'] <= self::LOW_STOCK_THRESHOLD) {
            $reasons[] = 'low_stock';
        }

        if ((int) ($row['backorder_allowed'] ?? 0) === 1 || (string) ($row['stock_status'] ?? '') === 'backorder') {
            $reasons[] = 'backorder_enabled';
        }

        if ($row['supplier_item_id'] === null && $row['supplier_id'] === null) {
            $reasons[] = 'missing_supplier_link';
        }

        return $reasons;
    }

    private function reasonLabel(string $reason): string
    {
        return $this->restockReasonOptions()[$reason] ?? $reason;
    }

    private function normalizeNotes(?string $notes): ?string
    {
        $normalized = trim((string) $notes);

        return $normalized === '' ? null : mb_substr($normalized, 0, 2000);
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

    /** @param array<int,array<string,mixed>> $flags
     * @return array<int,array<string,mixed>>
     */
    private function indexFlagsByProductId(array $flags): array
    {
        $indexed = [];

        foreach ($flags as $flag) {
            $indexed[(int) $flag['product_id']] = $flag;
        }

        return $indexed;
    }

    /** @param array<string,mixed> $filters
     * @return array{search:string,supplier_id:int,reason:string,manual_status:string}
     */
    private function normalizeRefillFilters(array $filters): array
    {
        $reason = trim((string) ($filters['reason'] ?? ''));
        if (!array_key_exists($reason, $this->restockReasonOptions())) {
            $reason = '';
        }

        $manualStatus = trim((string) ($filters['manual_status'] ?? ''));
        if ($manualStatus !== '' && !in_array($manualStatus, self::MANUAL_RESTOCK_STATUSES, true)) {
            $manualStatus = '';
        }

        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'supplier_id' => max(0, (int) ($filters['supplier_id'] ?? 0)),
            'reason' => $reason,
            'manual_status' => $manualStatus,
        ];
    }
}
