<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Repositories;

use PDO;

final class PurchaseOrderReceiptRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $draftId, ?int $receivedByUserId, ?string $note, ?string $submissionToken): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO purchase_order_receipts (
                purchase_order_draft_id,
                received_by_user_id,
                note,
                submission_token,
                created_at
            ) VALUES (
                :purchase_order_draft_id,
                :received_by_user_id,
                :note,
                :submission_token,
                NOW()
            )');

        $stmt->bindValue('purchase_order_draft_id', $draftId, PDO::PARAM_INT);
        $stmt->bindValue('received_by_user_id', $receivedByUserId, $receivedByUserId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('note', $note, $note !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue('submission_token', $submissionToken, $submissionToken !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function createItem(int $receiptId, int $draftItemId, int $quantityReceived): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO purchase_order_receipt_items (
                purchase_order_receipt_id,
                purchase_order_draft_item_id,
                quantity_received,
                created_at
            ) VALUES (
                :purchase_order_receipt_id,
                :purchase_order_draft_item_id,
                :quantity_received,
                NOW()
            )');
        $stmt->execute([
            'purchase_order_receipt_id' => $receiptId,
            'purchase_order_draft_item_id' => $draftItemId,
            'quantity_received' => $quantityReceived,
        ]);
    }

    public function existsBySubmissionToken(int $draftId, string $submissionToken): bool
    {
        $stmt = $this->pdo->prepare('SELECT id
            FROM purchase_order_receipts
            WHERE purchase_order_draft_id = :purchase_order_draft_id
              AND submission_token = :submission_token
            LIMIT 1');
        $stmt->execute([
            'purchase_order_draft_id' => $draftId,
            'submission_token' => $submissionToken,
        ]);

        return $stmt->fetch() !== false;
    }

    /** @return array<int,array<string,mixed>> */
    public function listByDraftId(int $draftId): array
    {
        $stmt = $this->pdo->prepare('SELECT por.id, por.note, por.received_by_user_id, por.created_at,
                SUM(pori.quantity_received) AS total_quantity_received
            FROM purchase_order_receipts por
            LEFT JOIN purchase_order_receipt_items pori ON pori.purchase_order_receipt_id = por.id
            WHERE por.purchase_order_draft_id = :purchase_order_draft_id
            GROUP BY por.id, por.note, por.received_by_user_id, por.created_at
            ORDER BY por.id DESC');
        $stmt->execute(['purchase_order_draft_id' => $draftId]);

        return $stmt->fetchAll();
    }
}
