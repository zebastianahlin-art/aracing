<?php

declare(strict_types=1);

namespace App\Modules\Cart\Repositories;

use PDO;

final class CartProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function activeProductForCart(int $productId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, sku, sale_price, currency_code, stock_status, stock_quantity, backorder_allowed, is_active
            FROM products
            WHERE id = :id AND is_active = 1
            LIMIT 1');
        $stmt->execute(['id' => $productId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @param array<int,int> $productIds
     * @return array<int,array<string,mixed>>
     */
    public function activeProductsByIds(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->pdo->prepare('SELECT id, name, sku, sale_price, currency_code, stock_status, stock_quantity, backorder_allowed, is_active
            FROM products
            WHERE is_active = 1 AND id IN (' . $placeholders . ')');

        foreach (array_values($productIds) as $index => $productId) {
            $stmt->bindValue($index + 1, $productId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }
}
