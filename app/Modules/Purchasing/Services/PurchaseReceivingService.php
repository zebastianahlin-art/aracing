<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Services;

use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use App\Modules\Purchasing\Repositories\PurchaseOrderDraftItemRepository;
use App\Modules\Purchasing\Repositories\PurchaseOrderDraftRepository;
use App\Modules\Purchasing\Repositories\PurchaseOrderReceiptRepository;
use InvalidArgumentException;
use PDO;

final class PurchaseReceivingService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly PurchaseOrderDraftRepository $drafts,
        private readonly PurchaseOrderDraftItemRepository $items,
        private readonly PurchaseOrderReceiptRepository $receipts,
        private readonly InventoryRepository $inventory,
        private readonly StockMovementRepository $movements,
    ) {
    }

    /** @param array<int|string,mixed> $receivedQuantities */
    public function receiveAgainstDraft(int $draftId, array $receivedQuantities, ?string $note = null, ?string $submissionToken = null, ?int $receivedByUserId = null): void
    {
        $draft = $this->requireDraft($draftId);
        $this->assertDraftCanReceive($draft);

        $token = $this->normalizeToken($submissionToken);
        if ($token !== null && $this->receipts->existsBySubmissionToken($draftId, $token)) {
            return;
        }

        $items = $this->items->listByDraftId($draftId);
        if ($items === []) {
            throw new InvalidArgumentException('Utkastet saknar rader att ta emot.');
        }

        $receiptRows = [];
        foreach ($items as $item) {
            $itemId = (int) $item['id'];
            $receivedNow = $this->normalizeReceiveQuantity($receivedQuantities[$itemId] ?? 0);
            if ($receivedNow <= 0) {
                continue;
            }

            $ordered = max(0, (int) $item['quantity']);
            $alreadyReceived = max(0, (int) ($item['received_quantity'] ?? 0));
            $remaining = max(0, $ordered - $alreadyReceived);

            if ($receivedNow > $remaining) {
                throw new InvalidArgumentException('Mottagen kvantitet kan inte överstiga kvarvarande antal för en rad.');
            }

            $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;
            if ($productId <= 0) {
                throw new InvalidArgumentException('Raden saknar produktkoppling och kan inte lagerföras.');
            }

            $receiptRows[] = [
                'item_id' => $itemId,
                'product_id' => $productId,
                'received_now' => $receivedNow,
                'new_received_total' => $alreadyReceived + $receivedNow,
            ];
        }

        if ($receiptRows === []) {
            throw new InvalidArgumentException('Ange minst en mottagen kvantitet större än 0.');
        }

        $this->pdo->beginTransaction();

        try {
            $receiptId = $this->receipts->create($draftId, $receivedByUserId, $this->normalizeNote($note), $token);

            foreach ($receiptRows as $row) {
                $inventoryRow = $this->inventory->findProductInventoryForUpdate((int) $row['product_id']);
                if ($inventoryRow === null) {
                    throw new InvalidArgumentException('Produkten för en mottagningsrad kunde inte hittas.');
                }

                $previousQuantity = max(0, (int) $inventoryRow['stock_quantity']);
                $newQuantity = $previousQuantity + (int) $row['received_now'];
                $stockStatus = $newQuantity > 0 ? 'in_stock' : ((string) $inventoryRow['stock_status'] !== '' ? (string) $inventoryRow['stock_status'] : 'out_of_stock');

                $this->inventory->updateInventory(
                    (int) $row['product_id'],
                    $newQuantity,
                    $stockStatus,
                    (int) ($inventoryRow['backorder_allowed'] ?? 0) === 1
                );

                $this->movements->create([
                    'product_id' => (int) $row['product_id'],
                    'movement_type' => 'purchase_receipt',
                    'quantity_delta' => (int) $row['received_now'],
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'reference_type' => 'purchase_order_receipt',
                    'reference_id' => $receiptId,
                    'comment' => 'Inleverans för ' . (string) $draft['order_number'],
                    'created_by_user_id' => $receivedByUserId,
                ]);

                $this->items->updateReceivedQuantity((int) $row['item_id'], (int) $row['new_received_total']);
                $this->receipts->createItem($receiptId, (int) $row['item_id'], (int) $row['received_now']);
            }

            $this->recalculateReceivingStatus($draftId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function recalculateReceivingStatus(int $draftId): void
    {
        $items = $this->items->listByDraftId($draftId);

        $hasOrdered = false;
        $hasReceived = false;
        $allReceived = true;

        foreach ($items as $item) {
            $ordered = max(0, (int) $item['quantity']);
            $received = max(0, (int) ($item['received_quantity'] ?? 0));

            if ($ordered > 0) {
                $hasOrdered = true;
                if ($received < $ordered) {
                    $allReceived = false;
                }
            }

            if ($received > 0) {
                $hasReceived = true;
            }
        }

        $status = 'not_received';
        if ($hasOrdered && $hasReceived) {
            $status = $allReceived ? 'received' : 'partially_received';
        }

        $this->drafts->updateReceivingState($draftId, $status, $status === 'received' ? date('Y-m-d H:i:s') : null);
    }

    /** @param array<string,mixed> $draft */
    private function assertDraftCanReceive(array $draft): void
    {
        $status = (string) ($draft['status'] ?? '');
        if ($status === 'cancelled') {
            throw new InvalidArgumentException('Avbrutna inköpsutkast kan inte mottas.');
        }

        if ($status !== 'exported') {
            throw new InvalidArgumentException('Inleverans kräver att inköpsutkastet är markerat som exporterat.');
        }
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

    private function normalizeReceiveQuantity(mixed $value): int
    {
        $raw = is_scalar($value) ? trim((string) $value) : '';
        if ($raw === '') {
            return 0;
        }

        if (!ctype_digit($raw)) {
            throw new InvalidArgumentException('Mottagen kvantitet måste vara ett heltal 0 eller högre.');
        }

        return (int) $raw;
    }

    private function normalizeNote(?string $note): ?string
    {
        $value = trim((string) $note);
        return $value === '' ? null : mb_substr($value, 0, 2000);
    }

    private function normalizeToken(?string $token): ?string
    {
        $value = trim((string) $token);
        return $value === '' ? null : mb_substr($value, 0, 120);
    }
}
