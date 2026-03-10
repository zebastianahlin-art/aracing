<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Repositories;

use PDO;

final class InventoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string,mixed>|null */
    public function findProductInventory(int $productId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, stock_quantity, stock_status, backorder_allowed, stock_updated_at FROM products WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $productId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function updateInventory(int $productId, int $quantity, string $stockStatus, bool $backorderAllowed): void
    {
        $stmt = $this->pdo->prepare('UPDATE products
            SET stock_quantity = :stock_quantity,
                stock_status = :stock_status,
                backorder_allowed = :backorder_allowed,
                stock_updated_at = NOW(),
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $productId,
            'stock_quantity' => $quantity,
            'stock_status' => $stockStatus,
            'backorder_allowed' => $backorderAllowed ? 1 : 0,
        ]);
    }
}
