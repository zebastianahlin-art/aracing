<?php

declare(strict_types=1);

namespace App\Modules\Cart\Repositories;

use PDO;

final class CartRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function findBySessionId(string $sessionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, session_id, currency_code, discount_code FROM carts WHERE session_id = :session_id LIMIT 1');
        $stmt->execute(['session_id' => $sessionId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function createForSession(string $sessionId, string $currencyCode): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO carts (session_id, currency_code, discount_code, created_at, updated_at) VALUES (:session_id, :currency_code, NULL, NOW(), NOW())');
        $stmt->execute(['session_id' => $sessionId, 'currency_code' => $currencyCode]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function itemsForCart(int $cartId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, cart_id, product_id, product_name_snapshot, sku_snapshot, unit_price_snapshot, quantity
            FROM cart_items
            WHERE cart_id = :cart_id
            ORDER BY id ASC');
        $stmt->execute(['cart_id' => $cartId]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findCartItem(int $cartId, int $productId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id LIMIT 1');
        $stmt->execute(['cart_id' => $cartId, 'product_id' => $productId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function addItem(int $cartId, array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO cart_items (cart_id, product_id, product_name_snapshot, sku_snapshot, unit_price_snapshot, quantity, created_at, updated_at)
            VALUES (:cart_id, :product_id, :product_name_snapshot, :sku_snapshot, :unit_price_snapshot, :quantity, NOW(), NOW())');
        $stmt->execute([
            'cart_id' => $cartId,
            'product_id' => $data['product_id'],
            'product_name_snapshot' => $data['product_name_snapshot'],
            'sku_snapshot' => $data['sku_snapshot'],
            'unit_price_snapshot' => $data['unit_price_snapshot'],
            'quantity' => $data['quantity'],
        ]);
    }

    public function updateItemQuantity(int $itemId, int $quantity): void
    {
        $stmt = $this->pdo->prepare('UPDATE cart_items SET quantity = :quantity, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $itemId, 'quantity' => $quantity]);
    }

    public function deleteItem(int $cartId, int $productId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id');
        $stmt->execute(['cart_id' => $cartId, 'product_id' => $productId]);
    }

    public function clearCart(int $cartId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
        $stmt->execute(['cart_id' => $cartId]);
    }


    public function updateDiscountCode(int $cartId, ?string $discountCode): void
    {
        $stmt = $this->pdo->prepare('UPDATE carts SET discount_code = :discount_code, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $cartId, 'discount_code' => $discountCode]);
    }

    public function touchCart(int $cartId): void
    {
        $stmt = $this->pdo->prepare('UPDATE carts SET updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $cartId]);
    }
}
