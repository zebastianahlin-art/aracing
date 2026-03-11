<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Services;

use App\Modules\Purchasing\Repositories\PurchaseOrderDraftItemRepository;
use App\Modules\Purchasing\Repositories\PurchaseOrderDraftRepository;
use App\Modules\Purchasing\Repositories\RefillNeedRepository;
use InvalidArgumentException;
use PDO;

final class PurchaseOrderDraftService
{
    private const ALLOWED_STATUSES = ['draft', 'exported', 'cancelled'];

    public function __construct(
        private readonly PDO $pdo,
        private readonly RefillNeedRepository $refillNeeds,
        private readonly PurchaseOrderDraftRepository $drafts,
        private readonly PurchaseOrderDraftItemRepository $items,
    ) {
    }

    /** @param array<int, int|string> $selectedProductIds
     *  @param array<int|string,mixed> $selectedQuantities
     *  @return array{created_draft_ids:array<int,int>, skipped_without_supplier:int}
     */
    public function createFromRestockSelection(array $selectedProductIds, array $selectedQuantities, ?int $createdByUserId = null): array
    {
        $productIds = array_values(array_unique(array_map('intval', $selectedProductIds)));
        $productIds = array_values(array_filter($productIds, static fn (int $id): bool => $id > 0));
        if ($productIds === []) {
            throw new InvalidArgumentException('Markera minst en restock-kandidat för att skapa inköpsutkast.');
        }

        $rows = $this->refillNeeds->listByProductIds($productIds);
        if ($rows === []) {
            throw new InvalidArgumentException('Kunde inte läsa restock-underlag för valda produkter.');
        }

        $grouped = [];
        $skippedWithoutSupplier = 0;

        foreach ($rows as $row) {
            $supplierId = $row['supplier_id'] !== null ? (int) $row['supplier_id'] : 0;
            if ($supplierId <= 0) {
                $skippedWithoutSupplier++;
                continue;
            }

            $productId = (int) $row['product_id'];
            $quantity = $this->normalizeQuantity($selectedQuantities[$productId] ?? 1);
            $grouped[$supplierId][] = [
                'product_id' => $productId,
                'supplier_item_id' => $row['supplier_item_id'] !== null ? (int) $row['supplier_item_id'] : null,
                'sku' => $this->normalizeNullableString($row['sku'] ?? null),
                'supplier_sku' => $this->normalizeNullableString($row['supplier_sku_snapshot'] ?? null),
                'product_name_snapshot' => (string) $row['product_name'],
                'quantity' => $quantity,
                'unit_cost_snapshot' => $row['supplier_price_snapshot'],
                'line_note' => null,
            ];

            if (!isset($grouped[$supplierId]['__supplier_name'])) {
                $grouped[$supplierId]['__supplier_name'] = $this->normalizeNullableString($row['supplier_name'] ?? null);
            }
        }

        if ($grouped === []) {
            throw new InvalidArgumentException('Inga utkast skapades eftersom valda produkter saknade leverantörskoppling.');
        }

        $createdIds = [];
        $this->pdo->beginTransaction();
        try {
            foreach ($grouped as $supplierId => $supplierRows) {
                $supplierName = $supplierRows['__supplier_name'] ?? null;
                unset($supplierRows['__supplier_name']);

                if ($supplierRows === []) {
                    continue;
                }

                $draftId = $this->drafts->create([
                    'supplier_id' => $supplierId,
                    'status' => 'draft',
                    'order_number' => $this->generateOrderNumber(),
                    'supplier_name_snapshot' => $supplierName,
                    'supplier_reference' => null,
                    'internal_note' => null,
                    'created_by_user_id' => $createdByUserId,
                ]);

                foreach ($supplierRows as $itemData) {
                    $this->items->create($draftId, $itemData);
                }

                $createdIds[] = $draftId;
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return ['created_draft_ids' => $createdIds, 'skipped_without_supplier' => $skippedWithoutSupplier];
    }

    /** @return array<int,array<string,mixed>> */
    public function listDrafts(?string $status): array
    {
        $normalizedStatus = $this->normalizeStatusFilter($status);
        return $this->drafts->listAll($normalizedStatus);
    }

    /** @return array<string,mixed>|null */
    public function getDraftDetail(int $draftId): ?array
    {
        $draft = $this->drafts->findById($draftId);
        if ($draft === null) {
            return null;
        }

        return ['draft' => $draft, 'items' => $this->items->listByDraftId($draftId)];
    }

    public function updateItemQuantity(int $draftId, int $itemId, mixed $quantity): void
    {
        $draft = $this->requireDraft($draftId);
        $this->assertDraftEditable($draft);
        $item = $this->requireItemForDraft($draftId, $itemId);
        $this->items->updateQuantity((int) $item['id'], $this->normalizeQuantity($quantity));
    }

    public function removeItem(int $draftId, int $itemId): void
    {
        $draft = $this->requireDraft($draftId);
        $this->assertDraftEditable($draft);
        $item = $this->requireItemForDraft($draftId, $itemId);
        $this->items->deleteById((int) $item['id']);
    }

    public function updateInternalNote(int $draftId, ?string $internalNote): void
    {
        $draft = $this->requireDraft($draftId);
        $this->assertDraftEditable($draft);
        $this->drafts->updateInternalNote($draftId, $this->normalizeNullableString($internalNote));
    }

    public function markExported(int $draftId): void
    {
        $draft = $this->requireDraft($draftId);
        if ((string) $draft['status'] !== 'draft') {
            throw new InvalidArgumentException('Endast utkast i status draft kan markeras som exporterade.');
        }
        $this->drafts->markExported($draftId);
    }

    public function markCancelled(int $draftId): void
    {
        $draft = $this->requireDraft($draftId);
        if ((string) $draft['status'] !== 'draft') {
            throw new InvalidArgumentException('Endast utkast i status draft kan avbrytas.');
        }
        $this->drafts->markCancelled($draftId);
    }

    /** @return array<int,string> */
    public function statuses(): array
    {
        return self::ALLOWED_STATUSES;
    }

    private function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $dailyCount = $this->drafts->countCreatedOnDate(date('Y-m-d')) + 1;

        return sprintf('POD-%s-%03d', $date, $dailyCount);
    }

    private function normalizeQuantity(mixed $quantity): int
    {
        $value = is_scalar($quantity) ? trim((string) $quantity) : '';
        if ($value === '' || ctype_digit($value) === false) {
            throw new InvalidArgumentException('Kvantitet måste vara ett positivt heltal.');
        }

        $normalized = (int) $value;
        if ($normalized <= 0) {
            throw new InvalidArgumentException('Kvantitet måste vara större än noll.');
        }

        return $normalized;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $normalized = trim((string) $value);
        return $normalized === '' ? null : mb_substr($normalized, 0, 2000);
    }

    private function normalizeStatusFilter(?string $status): ?string
    {
        $normalized = trim((string) $status);
        if ($normalized === '') {
            return null;
        }

        return in_array($normalized, self::ALLOWED_STATUSES, true) ? $normalized : null;
    }

    /** @return array<string,mixed> */
    private function requireDraft(int $draftId): array
    {
        if ($draftId <= 0) {
            throw new InvalidArgumentException('Ogiltigt utkast-id.');
        }

        $draft = $this->drafts->findById($draftId);
        if ($draft === null) {
            throw new InvalidArgumentException('Inköpsutkastet kunde inte hittas.');
        }

        return $draft;
    }

    /** @param array<string,mixed> $draft */
    private function assertDraftEditable(array $draft): void
    {
        if ((string) ($draft['status'] ?? '') !== 'draft') {
            throw new InvalidArgumentException('Endast utkast i status draft kan redigeras.');
        }
    }

    /** @return array<string,mixed> */
    private function requireItemForDraft(int $draftId, int $itemId): array
    {
        $item = $this->items->findById($itemId);
        if ($item === null || (int) $item['purchase_order_draft_id'] !== $draftId) {
            throw new InvalidArgumentException('Utkastsraden kunde inte hittas för valt inköpsutkast.');
        }

        return $item;
    }
}
