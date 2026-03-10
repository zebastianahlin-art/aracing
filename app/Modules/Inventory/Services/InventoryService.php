<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use InvalidArgumentException;
use RuntimeException;

final class InventoryService
{
    private const STOCK_STATUSES = ['in_stock', 'out_of_stock', 'backorder'];

    public function __construct(
        private readonly InventoryRepository $inventory,
        private readonly StockMovementRepository $movements
    ) {
    }

    /** @param array<string,mixed> $product */
    public function isPurchasable(array $product): bool
    {
        $status = $this->normalizeStockStatus((string) ($product['stock_status'] ?? 'out_of_stock'));
        $quantity = max(0, (int) ($product['stock_quantity'] ?? 0));
        $backorderAllowed = (int) ($product['backorder_allowed'] ?? 0) === 1;

        if ($status === 'backorder') {
            return true;
        }

        if ($status === 'in_stock' && $quantity > 0) {
            return true;
        }

        return $backorderAllowed;
    }

    /** @param array<string,mixed> $product
     * @return array{code:string,label:string,is_purchasable:bool}
     */
    public function storefrontAvailability(array $product): array
    {
        $status = $this->normalizeStockStatus((string) ($product['stock_status'] ?? 'out_of_stock'));

        return [
            'code' => $status,
            'label' => $this->storefrontLabel($status),
            'is_purchasable' => $this->isPurchasable($product),
        ];
    }

    public function storefrontLabel(string $status): string
    {
        return match ($this->normalizeStockStatus($status)) {
            'in_stock' => 'I lager',
            'backorder' => 'Beställningsvara',
            default => 'Tillfälligt slut',
        };
    }

    public function normalizeStockStatus(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, self::STOCK_STATUSES, true)) {
            return $normalized;
        }

        return 'out_of_stock';
    }

    /** @return array<int,string> */
    public function allowedStockStatuses(): array
    {
        return self::STOCK_STATUSES;
    }

    public function manualSetStock(
        int $productId,
        int $newQuantity,
        string $stockStatus,
        bool $backorderAllowed,
        ?string $comment = null
    ): void {
        if ($newQuantity < 0) {
            throw new InvalidArgumentException('Lagervärde kan inte vara negativt.');
        }

        $current = $this->inventory->findProductInventory($productId);
        if ($current === null) {
            throw new RuntimeException('Produkten hittades inte.');
        }

        $normalizedStatus = $this->normalizeStockStatus($stockStatus);
        $previousQuantity = max(0, (int) $current['stock_quantity']);
        $delta = $newQuantity - $previousQuantity;

        $this->inventory->beginTransaction();

        try {
            $this->inventory->updateInventory($productId, $newQuantity, $normalizedStatus, $backorderAllowed);
            $this->movements->create([
                'product_id' => $productId,
                'movement_type' => 'manual_adjustment',
                'quantity_delta' => $delta,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'reference_type' => 'admin_product',
                'reference_id' => $productId,
                'comment' => $comment,
                'created_by_user_id' => null,
            ]);
            $this->inventory->commit();
        } catch (\Throwable $exception) {
            $this->inventory->rollBack();
            throw $exception;
        }
    }

    public function manualAdjustStock(
        int $productId,
        int $delta,
        ?string $comment = null
    ): void {
        $current = $this->inventory->findProductInventory($productId);
        if ($current === null) {
            throw new RuntimeException('Produkten hittades inte.');
        }

        $previousQuantity = max(0, (int) $current['stock_quantity']);
        $newQuantity = $previousQuantity + $delta;

        if ($newQuantity < 0) {
            throw new InvalidArgumentException('Justering ger negativt lagersaldo.');
        }

        $this->inventory->beginTransaction();

        try {
            $this->inventory->updateInventory(
                $productId,
                $newQuantity,
                $this->normalizeStockStatus((string) $current['stock_status']),
                (int) ($current['backorder_allowed'] ?? 0) === 1
            );
            $this->movements->create([
                'product_id' => $productId,
                'movement_type' => 'manual_adjustment',
                'quantity_delta' => $delta,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'reference_type' => 'admin_product',
                'reference_id' => $productId,
                'comment' => $comment,
                'created_by_user_id' => null,
            ]);
            $this->inventory->commit();
        } catch (\Throwable $exception) {
            $this->inventory->rollBack();
            throw $exception;
        }
    }

    public function logStockSync(int $productId, int $previousQuantity, int $newQuantity, ?string $comment = null): void
    {
        $this->movements->create([
            'product_id' => $productId,
            'movement_type' => 'import_sync',
            'quantity_delta' => $newQuantity - $previousQuantity,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'reference_type' => 'supplier_snapshot',
            'reference_id' => $productId,
            'comment' => $comment,
            'created_by_user_id' => null,
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public function stockMovementsForProduct(int $productId, int $limit = 25): array
    {
        return $this->movements->listForProduct($productId, $limit);
    }
}
