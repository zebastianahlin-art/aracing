<?php

declare(strict_types=1);

namespace App\Modules\Cart\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cart\Services\CartService;
use App\Modules\Cms\Services\CmsPageService;
use RuntimeException;

final class CartController
{
    public function __construct(private readonly ViewFactory $views, private readonly CartService $carts, private readonly CmsPageService $pages)
    {
    }

    public function show(): Response
    {
        $message = trim((string) ($_GET['message'] ?? ''));
        $error = trim((string) ($_GET['error'] ?? ''));

        return new Response($this->views->render('storefront.cart', [
            'cartData' => $this->carts->getCartBySession($this->sessionId()),
            'message' => $message,
            'error' => $error,
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function add(): Response
    {
        try {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $quantity = (int) ($_POST['quantity'] ?? 1);
            $this->carts->addProduct($this->sessionId(), $productId, $quantity);

            return $this->redirect('/cart?message=' . urlencode('Produkten lades i kundvagnen.'));
        } catch (RuntimeException $e) {
            return $this->redirect('/cart?error=' . urlencode($e->getMessage()));
        }
    }

    public function update(): Response
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);
        $this->carts->updateQuantity($this->sessionId(), $productId, $quantity);

        return $this->redirect('/cart?message=' . urlencode('Kundvagnen uppdaterades.'));
    }

    public function remove(): Response
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $this->carts->removeItem($this->sessionId(), $productId);

        return $this->redirect('/cart?message=' . urlencode('Raden togs bort.'));
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
