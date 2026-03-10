<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Repositories;

use PDO;

final class StockMovementRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO inventory_stock_movements (
            product_id,
            movement_type,
            quantity_delta,
            previous_quantity,
            new_quantity,
            reference_type,
            reference_id,
            comment,
            created_by_user_id,
            created_at
        ) VALUES (
            :product_id,
            :movement_type,
            :quantity_delta,
            :previous_quantity,
            :new_quantity,
            :reference_type,
            :reference_id,
            :comment,
            :created_by_user_id,
            NOW()
        )');

        $stmt->bindValue('product_id', (int) $data['product_id'], PDO::PARAM_INT);
        $stmt->bindValue('movement_type', (string) $data['movement_type']);
        $stmt->bindValue('quantity_delta', (int) $data['quantity_delta'], PDO::PARAM_INT);
        $stmt->bindValue('previous_quantity', (int) $data['previous_quantity'], PDO::PARAM_INT);
        $stmt->bindValue('new_quantity', (int) $data['new_quantity'], PDO::PARAM_INT);
        $stmt->bindValue('reference_type', $data['reference_type'] ?? null);
        $stmt->bindValue('reference_id', $data['reference_id'] ?? null, $data['reference_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('comment', $data['comment'] ?? null);
        $stmt->bindValue('created_by_user_id', $data['created_by_user_id'] ?? null, $data['created_by_user_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
    }

    /** @return array<int,array<string,mixed>> */
    public function listForProduct(int $productId, int $limit = 25): array
    {
        $stmt = $this->pdo->prepare('SELECT id, movement_type, quantity_delta, previous_quantity, new_quantity, reference_type, reference_id, comment, created_by_user_id, created_at
            FROM inventory_stock_movements
            WHERE product_id = :product_id
            ORDER BY id DESC
            LIMIT :limit');
        $stmt->bindValue('product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
