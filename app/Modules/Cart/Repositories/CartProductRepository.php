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
        $stmt = $this->pdo->prepare('SELECT id, name, sku, sale_price, currency_code, stock_status, is_active
            FROM products
            WHERE id = :id AND is_active = 1
            LIMIT 1');
        $stmt->execute(['id' => $productId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
