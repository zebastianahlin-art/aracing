<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Repositories;

use PDO;

final class PurchaseListItemRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $data */
    public function create(int $purchaseListId, array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO purchase_list_items (
                purchase_list_id,
                product_id,
                supplier_id,
                supplier_item_id,
                product_name_snapshot,
                sku_snapshot,
                supplier_sku_snapshot,
                supplier_title_snapshot,
                supplier_price_snapshot,
                supplier_stock_snapshot,
                current_stock_quantity,
                suggested_quantity,
                selected_quantity,
                created_at,
                updated_at
            ) VALUES (
                :purchase_list_id,
                :product_id,
                :supplier_id,
                :supplier_item_id,
                :product_name_snapshot,
                :sku_snapshot,
                :supplier_sku_snapshot,
                :supplier_title_snapshot,
                :supplier_price_snapshot,
                :supplier_stock_snapshot,
                :current_stock_quantity,
                :suggested_quantity,
                :selected_quantity,
                NOW(),
                NOW()
            )');

        $stmt->bindValue('purchase_list_id', $purchaseListId, PDO::PARAM_INT);
        $stmt->bindValue('product_id', (int) $data['product_id'], PDO::PARAM_INT);
        $stmt->bindValue('supplier_id', $data['supplier_id'], $data['supplier_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('supplier_item_id', $data['supplier_item_id'], $data['supplier_item_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('product_name_snapshot', (string) $data['product_name_snapshot'], PDO::PARAM_STR);
        $stmt->bindValue('sku_snapshot', $data['sku_snapshot']);
        $stmt->bindValue('supplier_sku_snapshot', $data['supplier_sku_snapshot']);
        $stmt->bindValue('supplier_title_snapshot', $data['supplier_title_snapshot']);
        $stmt->bindValue('supplier_price_snapshot', $data['supplier_price_snapshot']);
        $stmt->bindValue('supplier_stock_snapshot', $data['supplier_stock_snapshot'], $data['supplier_stock_snapshot'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('current_stock_quantity', $data['current_stock_quantity'], $data['current_stock_quantity'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('suggested_quantity', $data['suggested_quantity'], $data['suggested_quantity'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('selected_quantity', $data['selected_quantity'], $data['selected_quantity'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
    }

    /** @return array<int, array<string, mixed>> */
    public function listByPurchaseListId(int $purchaseListId): array
    {
        $stmt = $this->pdo->prepare('SELECT pli.*,
                                            s.name AS supplier_name
                                     FROM purchase_list_items pli
                                     LEFT JOIN suppliers s ON s.id = pli.supplier_id
                                     WHERE pli.purchase_list_id = :purchase_list_id
                                     ORDER BY pli.id ASC');
        $stmt->execute(['purchase_list_id' => $purchaseListId]);

        return $stmt->fetchAll();
    }

    public function updateSelectedQuantity(int $itemId, int $selectedQuantity): void
    {
        $stmt = $this->pdo->prepare('UPDATE purchase_list_items
                                     SET selected_quantity = :selected_quantity,
                                         updated_at = NOW()
                                     WHERE id = :id');
        $stmt->execute([
            'id' => $itemId,
            'selected_quantity' => $selectedQuantity,
        ]);
    }
}
