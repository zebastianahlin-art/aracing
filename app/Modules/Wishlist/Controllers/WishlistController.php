<?php

declare(strict_types=1);

namespace App\Modules\Wishlist\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Customer\Services\AuthService;
use App\Modules\Wishlist\Services\WishlistService;
use InvalidArgumentException;

final class WishlistController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AuthService $auth,
        private readonly CmsPageService $pages,
        private readonly WishlistService $wishlists
    ) {
    }

    public function index(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        return new Response($this->views->render('storefront.account.wishlist', [
            'products' => $this->wishlists->listPublicSavedProducts((int) $customer['id']),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function add(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $backTo = $this->sanitizeBackTo((string) ($_POST['back_to'] ?? '/account/wishlist'));

        try {
            $this->wishlists->addProduct((int) $customer['id'], $productId);

            return $this->redirect($backTo . $this->separator($backTo) . 'message=' . urlencode('Produkten är sparad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect($backTo . $this->separator($backTo) . 'error=' . urlencode($e->getMessage()));
        }
    }

    public function remove(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $backTo = $this->sanitizeBackTo((string) ($_POST['back_to'] ?? '/account/wishlist'));

        try {
            $this->wishlists->removeProduct((int) $customer['id'], $productId);

            return $this->redirect($backTo . $this->separator($backTo) . 'message=' . urlencode('Produkten är borttagen från sparade.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect($backTo . $this->separator($backTo) . 'error=' . urlencode($e->getMessage()));
        }
    }

    /** @return array<string, mixed>|Response */
    private function requireCustomer(): array|Response
    {
        $customer = $this->auth->currentCustomer();
        if ($customer === null) {
            $returnTo = rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? '/account/wishlist'));

            return $this->redirect('/login?error=' . urlencode('Du behöver logga in för att använda sparade produkter.') . '&return_to=' . $returnTo);
        }

        return $customer;
    }

    private function sanitizeBackTo(string $backTo): string
    {
        $path = trim($backTo);
        if ($path === '' || !str_starts_with($path, '/')) {
            return '/account/wishlist';
        }

        if (str_starts_with($path, '//')) {
            return '/account/wishlist';
        }

        return $path;
    }

    private function separator(string $url): string
    {
        return str_contains($url, '?') ? '&' : '?';
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
