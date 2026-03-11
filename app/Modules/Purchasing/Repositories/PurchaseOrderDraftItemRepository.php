<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Repositories;

use PDO;

final class PurchaseOrderDraftItemRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string,mixed> $data */
    public function create(int $draftId, array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO purchase_order_draft_items (
                purchase_order_draft_id,
                product_id,
                supplier_item_id,
                sku,
                supplier_sku,
                product_name_snapshot,
                quantity,
                unit_cost_snapshot,
                line_note,
                created_at,
                updated_at
            ) VALUES (
                :purchase_order_draft_id,
                :product_id,
                :supplier_item_id,
                :sku,
                :supplier_sku,
                :product_name_snapshot,
                :quantity,
                :unit_cost_snapshot,
                :line_note,
                NOW(),
                NOW()
            )');

        $stmt->bindValue('purchase_order_draft_id', $draftId, PDO::PARAM_INT);
        $stmt->bindValue('product_id', $data['product_id'], $data['product_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('supplier_item_id', $data['supplier_item_id'], $data['supplier_item_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('sku', $data['sku'], $data['sku'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue('supplier_sku', $data['supplier_sku'], $data['supplier_sku'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue('product_name_snapshot', $data['product_name_snapshot'], PDO::PARAM_STR);
        $stmt->bindValue('quantity', $data['quantity'], PDO::PARAM_INT);
        $stmt->bindValue('unit_cost_snapshot', $data['unit_cost_snapshot'], $data['unit_cost_snapshot'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue('line_note', $data['line_note'], $data['line_note'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
    }

    /** @return array<int,array<string,mixed>> */
    public function listByDraftId(int $draftId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM purchase_order_draft_items WHERE purchase_order_draft_id = :draft_id ORDER BY id ASC');
        $stmt->execute(['draft_id' => $draftId]);

        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $itemId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM purchase_order_draft_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $itemId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        $stmt = $this->pdo->prepare('UPDATE purchase_order_draft_items
                SET quantity = :quantity,
                    updated_at = NOW()
                WHERE id = :id');
        $stmt->execute(['id' => $itemId, 'quantity' => $quantity]);
    }

    public function deleteById(int $itemId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM purchase_order_draft_items WHERE id = :id');
        $stmt->execute(['id' => $itemId]);
    }
}
