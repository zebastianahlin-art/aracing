<?php

declare(strict_types=1);

namespace App\Modules\Returns\Services;

use App\Modules\Returns\Repositories\ReturnOrderRepository;
use App\Modules\Returns\Repositories\ReturnRequestHistoryRepository;
use App\Modules\Returns\Repositories\ReturnRequestItemRepository;
use App\Modules\Returns\Repositories\ReturnRequestRepository;
use InvalidArgumentException;

final class ReturnRequestService
{
    private const STATUSES = ['requested', 'under_review', 'approved', 'rejected', 'received', 'completed', 'cancelled'];
    private const REASON_CODES = ['wrong_item', 'damaged', 'not_as_expected', 'no_longer_needed', 'other'];

    private const STATUS_TRANSITIONS = [
        'requested' => ['under_review', 'cancelled'],
        'under_review' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['received', 'cancelled'],
        'rejected' => [],
        'received' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly ReturnRequestRepository $returns,
        private readonly ReturnRequestItemRepository $items,
        private readonly ReturnRequestHistoryRepository $history,
        private readonly ReturnOrderRepository $orders
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        return $this->returns->listForUser($userId);
    }

    /** @return array<int, array<string, mixed>> */
    public function listAdmin(array $filters): array
    {
        return $this->returns->listAdmin($filters);
    }


    /** @return array<string, string> */
    public function statusLabels(): array
    {
        return [
            'requested' => 'Begärd',
            'under_review' => 'Under granskning',
            'approved' => 'Godkänd',
            'rejected' => 'Avslagen',
            'received' => 'Mottagen',
            'completed' => 'Slutförd',
            'cancelled' => 'Annullerad',
        ];
    }

    /** @return array<string, string> */
    public function reasonLabels(): array
    {
        return [
            'wrong_item' => 'Fel artikel',
            'damaged' => 'Skadad',
            'not_as_expected' => 'Motsvarade inte förväntan',
            'no_longer_needed' => 'Behövs inte längre',
            'other' => 'Annat',
        ];
    }

    /** @return array<int, string> */
    public function statuses(): array
    {
        return self::STATUSES;
    }

    /** @return array<int, string> */
    public function reasonCodes(): array
    {
        return self::REASON_CODES;
    }

    /** @return array<int, array<string, mixed>> */
    public function listForOrder(int $orderId): array
    {
        return $this->returns->listForOrder($orderId);
    }

    /** @return array<string, mixed> */
    public function getCreateContextForUser(int $userId, int $orderId): array
    {
        $order = $this->orders->findUserOrderById($userId, $orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Ordern hittades inte för ditt konto.');
        }

        return [
            'order' => $order,
            'items' => $this->orders->orderItems($orderId),
            'reason_codes' => self::REASON_CODES,
        ];
    }

    public function createForUser(int $userId, int $orderId, array $input): int
    {
        $order = $this->orders->findUserOrderById($userId, $orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Ordern hittades inte för ditt konto.');
        }

        $orderItems = $this->orders->orderItems($orderId);
        $orderItemsById = [];
        foreach ($orderItems as $orderItem) {
            $orderItemsById[(int) $orderItem['id']] = $orderItem;
        }

        $selected = $input['items'] ?? [];
        if (!is_array($selected) || $selected === []) {
            throw new InvalidArgumentException('Välj minst en orderrad att returnera.');
        }

        $validatedItems = [];
        foreach ($selected as $orderItemId => $itemInput) {
            $orderItemId = (int) $orderItemId;
            if (!isset($orderItemsById[$orderItemId])) {
                throw new InvalidArgumentException('En vald orderrad är ogiltig.');
            }

            if (!is_array($itemInput) || !isset($itemInput['selected'])) {
                continue;
            }

            $quantity = (int) ($itemInput['quantity'] ?? 0);
            if ($quantity < 1) {
                throw new InvalidArgumentException('Antal måste vara minst 1 för valda rader.');
            }

            $orderedQty = (int) $orderItemsById[$orderItemId]['quantity'];
            if ($quantity > $orderedQty) {
                throw new InvalidArgumentException('Returantal får inte vara högre än beställt antal.');
            }

            $lineReasonCode = trim((string) ($itemInput['reason_code'] ?? ''));
            if ($lineReasonCode !== '' && !in_array($lineReasonCode, self::REASON_CODES, true)) {
                throw new InvalidArgumentException('Ogiltig orsakskod på en orderrad.');
            }

            $validatedItems[] = [
                'order_item_id' => $orderItemId,
                'product_id' => $orderItemsById[$orderItemId]['product_id'] !== null ? (int) $orderItemsById[$orderItemId]['product_id'] : null,
                'quantity' => $quantity,
                'reason_code' => $lineReasonCode !== '' ? $lineReasonCode : null,
                'comment' => $this->normalizeComment((string) ($itemInput['comment'] ?? ''), 1000),
            ];
        }

        if ($validatedItems === []) {
            throw new InvalidArgumentException('Välj minst en orderrad att returnera.');
        }

        $reasonCode = trim((string) ($input['reason_code'] ?? ''));
        if ($reasonCode !== '' && !in_array($reasonCode, self::REASON_CODES, true)) {
            throw new InvalidArgumentException('Ogiltig anledning vald.');
        }

        $requestedAt = date('Y-m-d H:i:s');
        $returnId = $this->returns->create([
            'order_id' => $orderId,
            'user_id' => $userId,
            'return_number' => $this->generateReturnNumber($orderId),
            'status' => 'requested',
            'reason_code' => $reasonCode !== '' ? $reasonCode : null,
            'customer_comment' => $this->normalizeComment((string) ($input['customer_comment'] ?? ''), 4000),
            'admin_note' => null,
            'requested_at' => $requestedAt,
            'approved_at' => null,
            'received_at' => null,
            'closed_at' => null,
        ]);

        foreach ($validatedItems as $item) {
            $this->items->create([
                'return_request_id' => $returnId,
                'order_item_id' => $item['order_item_id'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'reason_code' => $item['reason_code'],
                'comment' => $item['comment'],
            ]);

            $this->logHistory($returnId, 'item_added', null, (string) $item['order_item_id'], null, $userId);
        }

        $this->logHistory($returnId, 'created', null, 'requested', null, $userId);
        if (($input['customer_comment'] ?? '') !== '') {
            $this->logHistory($returnId, 'customer_note', null, null, (string) $input['customer_comment'], $userId);
        }

        return $returnId;
    }

    /** @return array<string, mixed>|null */
    public function getDetailForUser(int $returnRequestId, int $userId): ?array
    {
        $request = $this->returns->findByIdForUser($returnRequestId, $userId);
        if ($request === null) {
            return null;
        }

        return [
            'request' => $request,
            'items' => $this->items->listByReturnRequest($returnRequestId),
            'history' => $this->history->listByReturnRequest($returnRequestId),
        ];
    }

    /** @return array<string, mixed>|null */
    public function getDetailAdmin(int $returnRequestId): ?array
    {
        $request = $this->returns->findById($returnRequestId);
        if ($request === null) {
            return null;
        }

        $order = $this->orders->findOrderById((int) $request['order_id']);

        return [
            'request' => $request,
            'order' => $order,
            'items' => $this->items->listByReturnRequest($returnRequestId),
            'history' => $this->history->listByReturnRequest($returnRequestId),
            'allowed_statuses' => $this->allowedTransitions((string) $request['status']),
        ];
    }

    public function updateStatusAdmin(int $returnRequestId, string $newStatus, ?int $actorUserId = null): void
    {
        $request = $this->returns->findById($returnRequestId);
        if ($request === null) {
            throw new InvalidArgumentException('Returärendet hittades inte.');
        }

        $currentStatus = (string) $request['status'];
        if (!in_array($newStatus, self::STATUSES, true)) {
            throw new InvalidArgumentException('Ogiltig returstatus.');
        }

        if (!in_array($newStatus, $this->allowedTransitions($currentStatus), true)) {
            throw new InvalidArgumentException('Statusövergången är inte tillåten.');
        }

        $timestamps = [
            'approved_at' => $request['approved_at'],
            'received_at' => $request['received_at'],
            'closed_at' => $request['closed_at'],
        ];

        if ($newStatus === 'approved' && $timestamps['approved_at'] === null) {
            $timestamps['approved_at'] = date('Y-m-d H:i:s');
        }
        if ($newStatus === 'received' && $timestamps['received_at'] === null) {
            $timestamps['received_at'] = date('Y-m-d H:i:s');
        }
        if (in_array($newStatus, ['completed', 'rejected', 'cancelled'], true)) {
            $timestamps['closed_at'] = date('Y-m-d H:i:s');
        }

        $this->returns->updateStatus($returnRequestId, $newStatus, $timestamps);
        $this->logHistory($returnRequestId, 'status_changed', $currentStatus, $newStatus, null, $actorUserId);
    }

    public function updateAdminNote(int $returnRequestId, string $note, ?int $actorUserId = null): void
    {
        $request = $this->returns->findById($returnRequestId);
        if ($request === null) {
            throw new InvalidArgumentException('Returärendet hittades inte.');
        }

        $normalized = $this->normalizeComment($note, 4000);
        if ($normalized === null) {
            throw new InvalidArgumentException('Adminnotering får inte vara tom.');
        }

        $this->returns->updateAdminNote($returnRequestId, $normalized);
        $this->logHistory($returnRequestId, 'admin_note', null, null, $normalized, $actorUserId);
    }

    /** @return array<int, string> */
    private function allowedTransitions(string $status): array
    {
        return self::STATUS_TRANSITIONS[$status] ?? [];
    }

    private function logHistory(int $returnRequestId, string $eventType, ?string $fromValue, ?string $toValue, ?string $comment, ?int $actorUserId): void
    {
        $this->history->create([
            'return_request_id' => $returnRequestId,
            'event_type' => $eventType,
            'from_value' => $fromValue,
            'to_value' => $toValue,
            'comment' => $comment,
            'created_by_user_id' => $actorUserId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function generateReturnNumber(int $orderId): string
    {
        return sprintf('RMA-%d-%s', $orderId, strtoupper((string) bin2hex(random_bytes(3))));
    }

    private function normalizeComment(string $comment, int $maxLength): ?string
    {
        $comment = trim($comment);
        if ($comment === '') {
            return null;
        }

        if (mb_strlen($comment) > $maxLength) {
            throw new InvalidArgumentException('Kommentar är för lång.');
        }

        return $comment;
    }
}
