<?php

declare(strict_types=1);

namespace App\Modules\Cart\Services;

use App\Modules\Discount\Services\DiscountService;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Cart\Repositories\CartProductRepository;
use App\Modules\Cart\Repositories\CartRepository;
use App\Modules\Shipping\Services\CheckoutTotalsService;
use RuntimeException;

final class CartService
{
    public function __construct(
        private readonly CartRepository $carts,
        private readonly CartProductRepository $products,
        private readonly InventoryService $inventory,
        private readonly DiscountService $discounts,
        private readonly CheckoutTotalsService $totals
    ) {
    }

    /** @return array<string, mixed> */
    public function getCartBySession(string $sessionId): array
    {
        $cart = $this->carts->findBySessionId($sessionId);
        if ($cart === null) {
            $cartId = $this->carts->createForSession($sessionId, 'SEK');
            $cart = ['id' => $cartId, 'session_id' => $sessionId, 'currency_code' => 'SEK', 'discount_code' => null];
        }

        $items = $this->carts->itemsForCart((int) $cart['id']);
        $subtotal = 0.0;

        foreach ($items as &$item) {
            $item['line_total'] = (float) $item['unit_price_snapshot'] * (int) $item['quantity'];
            $subtotal += $item['line_total'];
        }

        $activeDiscount = null;
        $discountAmount = 0.0;
        $discountError = null;
        $discountCode = trim((string) ($cart['discount_code'] ?? ''));
        if ($discountCode !== '') {
            try {
                $activeDiscount = $this->discounts->validateCodeForSubtotal($discountCode, $subtotal);
                $discountAmount = $this->discounts->calculateDiscountAmount($activeDiscount, $subtotal);
            } catch (\InvalidArgumentException $e) {
                $discountError = $e->getMessage();
            }
        }

        $calculatedTotals = $this->totals->calculate($subtotal, 0.0, $discountAmount);

        return [
            'cart' => $cart,
            'items' => $items,
            'active_discount' => $activeDiscount,
            'discount_error' => $discountError,
            'subtotal_amount' => $calculatedTotals['product_subtotal'],
            'discount_amount_inc_vat' => $calculatedTotals['discount_amount'],
            'total_amount' => $calculatedTotals['grand_total'],
        ];
    }

    public function addProduct(string $sessionId, int $productId, int $quantity): void
    {
        $quantity = max(1, $quantity);
        $product = $this->products->activeProductForCart($productId);

        if ($product === null) {
            throw new RuntimeException('Produkten är inte tillgänglig för köp.');
        }

        if ($this->inventory->isPurchasable($product) === false) {
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

    public function applyDiscountCode(string $sessionId, string $inputCode): void
    {
        $cartData = $this->getCartBySession($sessionId);
        $cart = $cartData['cart'];
        $subtotal = (float) ($cartData['subtotal_amount'] ?? 0);
        $discount = $this->discounts->validateCodeForSubtotal($inputCode, $subtotal);
        $this->discounts->calculateDiscountAmount($discount, $subtotal);

        $this->carts->updateDiscountCode((int) $cart['id'], (string) $discount['code']);
    }

    public function removeDiscountCode(string $sessionId): void
    {
        $cartData = $this->getCartBySession($sessionId);
        $cart = $cartData['cart'];
        $this->carts->updateDiscountCode((int) $cart['id'], null);
    }

    public function clearBySession(string $sessionId): void
    {
        $cart = $this->carts->findBySessionId($sessionId);
        if ($cart === null) {
            return;
        }

        $this->carts->clearCart((int) $cart['id']);
        $this->carts->updateDiscountCode((int) $cart['id'], null);
        $this->carts->touchCart((int) $cart['id']);
    }

    public function ensureCartItemsPurchasable(string $sessionId): void
    {
        $cartData = $this->getCartBySession($sessionId);
        $items = $cartData['items'] ?? [];
        if ($items === []) {
            return;
        }

        $productIds = array_map(static fn (array $item): int => (int) $item['product_id'], $items);
        $rows = $this->products->activeProductsByIds($productIds);
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $product = $byId[$productId] ?? null;

            if ($product === null || $this->inventory->isPurchasable($product) === false || $product['sale_price'] === null) {
                throw new RuntimeException('En eller flera produkter i kundvagnen är inte längre köpbara. Uppdatera kundvagnen innan checkout.');
            }
        }
    }
}
