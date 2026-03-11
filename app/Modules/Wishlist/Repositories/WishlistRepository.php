<?php

declare(strict_types=1);

namespace App\Modules\Wishlist\Repositories;

use PDO;

final class WishlistRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function addProduct(int $userId, int $productId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO wishlist_items (user_id, product_id, created_at, updated_at)
            VALUES (:user_id, :product_id, NOW(), NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()');
        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
    }

    public function removeProduct(int $userId, int $productId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM wishlist_items WHERE user_id = :user_id AND product_id = :product_id');
        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
    }

    public function hasProduct(int $userId, int $productId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM wishlist_items WHERE user_id = :user_id AND product_id = :product_id LIMIT 1');
        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /** @return array<int, array<string, mixed>> */
    public function publicProductsForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT p.id,
                       p.name,
                       p.slug,
                       p.sku,
                       p.sale_price,
                       p.currency_code,
                       p.stock_status,
                       p.stock_quantity,
                       b.name AS brand_name,
                       wi.created_at AS wishlisted_at,
                       (SELECT pi.image_url
                        FROM product_images pi
                        WHERE pi.product_id = p.id
                        ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC
                        LIMIT 1) AS image_url
                FROM wishlist_items wi
                INNER JOIN products p ON p.id = wi.product_id
                LEFT JOIN brands b ON b.id = p.brand_id
                WHERE wi.user_id = :user_id
                  AND p.is_active = 1
                  AND p.is_search_hidden = 0
                ORDER BY wi.created_at DESC, wi.id DESC");
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }
}
