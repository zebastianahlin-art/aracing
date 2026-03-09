<?php

declare(strict_types=1);

namespace App\Modules\Cart\Services;

use App\Modules\Cart\Repositories\CartProductRepository;
use App\Modules\Cart\Repositories\CartRepository;
use RuntimeException;

final class CartService
{
    public function __construct(
        private readonly CartRepository $carts,
        private readonly CartProductRepository $products
    ) {
    }

    /** @return array<string, mixed> */
    public function getCartBySession(string $sessionId): array
    {
        $cart = $this->carts->findBySessionId($sessionId);
        if ($cart === null) {
            $cartId = $this->carts->createForSession($sessionId, 'SEK');
            $cart = ['id' => $cartId, 'session_id' => $sessionId, 'currency_code' => 'SEK'];
        }

        $items = $this->carts->itemsForCart((int) $cart['id']);
        $subtotal = 0.0;

        foreach ($items as &$item) {
            $item['line_total'] = (float) $item['unit_price_snapshot'] * (int) $item['quantity'];
            $subtotal += $item['line_total'];
        }

        return [
            'cart' => $cart,
            'items' => $items,
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal,
        ];
    }

    public function addProduct(string $sessionId, int $productId, int $quantity): void
    {
        $quantity = max(1, $quantity);
        $product = $this->products->activeProductForCart($productId);

        if ($product === null) {
            throw new RuntimeException('Produkten är inte tillgänglig för köp.');
        }

        if (($product['stock_status'] ?? '') === 'out_of_stock') {
            throw new RuntimeException('Produkten är slut i lager och kan inte läggas i kundvagnen.');
        }

        if ($product['sale_price'] === null) {
            throw new RuntimeException('Produkten saknar försäljningspris och kan inte köpas.');
        }

        $cartData = $this->getCartBySession($sessionId);
        $cart = $cartData['cart'];
        $existing = $this->carts->findCartItem((int) $cart['id'], $productId);

        if ($existing !== null) {
            $this->carts->updateItemQuantity((int) $existing['id'], (int) $existing['quantity'] + $quantity);
        } else {
            $this->carts->addItem((int) $cart['id'], [
                'product_id' => $productId,
                'product_name_snapshot' => $product['name'],
                'sku_snapshot' => $product['sku'],
                'unit_price_snapshot' => (float) $product['sale_price'],
                'quantity' => $quantity,
            ]);
        }

        $this->carts->touchCart((int) $cart['id']);
    }

    public function updateQuantity(string $sessionId, int $productId, int $quantity): void
    {
        $cartData = $this->getCartBySession($sessionId);
        $cart = $cartData['cart'];
        $existing = $this->carts->findCartItem((int) $cart['id'], $productId);

        if ($existing === null) {
            return;
        }

        if ($quantity <= 0) {
            $this->carts->deleteItem((int) $cart['id'], $productId);
        } else {
            $this->carts->updateItemQuantity((int) $existing['id'], $quantity);
        }

        $this->carts->touchCart((int) $cart['id']);
    }

    public function removeItem(string $sessionId, int $productId): void
    {
        $cartData = $this->getCartBySession($sessionId);
        $cart = $cartData['cart'];
        $this->carts->deleteItem((int) $cart['id'], $productId);
        $this->carts->touchCart((int) $cart['id']);
    }

    public function clearBySession(string $sessionId): void
    {
        $cart = $this->carts->findBySessionId($sessionId);
        if ($cart === null) {
            return;
        }

        $this->carts->clearCart((int) $cart['id']);
        $this->carts->touchCart((int) $cart['id']);
    }
}
