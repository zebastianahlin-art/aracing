<?php

declare(strict_types=1);

namespace App\Modules\Checkout\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cart\Services\CartService;
use App\Modules\Checkout\Services\CheckoutService;
use App\Modules\Order\Services\OrderService;
use Throwable;

final class CheckoutController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly CartService $carts,
        private readonly CheckoutService $checkout,
        private readonly OrderService $orders
    ) {
    }

    public function form(): Response
    {
        return new Response($this->views->render('storefront.checkout', [
            'cartData' => $this->carts->getCartBySession($this->sessionId()),
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function placeOrder(): Response
    {
        try {
            $checkoutData = $this->checkout->normalize($_POST);
            $cartData = $this->carts->getCartBySession($this->sessionId());
            $orderNumber = $this->orders->createFromCart($checkoutData, $cartData);
            $this->carts->clearBySession($this->sessionId());
            $_SESSION['last_order_number'] = $orderNumber;

            return $this->redirect('/checkout/confirmation');
        } catch (Throwable $e) {
            return $this->redirect('/checkout?error=' . urlencode($e->getMessage()));
        }
    }

    public function confirmation(): Response
    {
        $orderNumber = $_SESSION['last_order_number'] ?? null;

        return new Response($this->views->render('storefront.order_confirmation', [
            'orderNumber' => is_string($orderNumber) ? $orderNumber : null,
        ]));
    }

    private function sessionId(): string
    {
        return session_id();
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
