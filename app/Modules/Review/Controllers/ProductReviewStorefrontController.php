<?php

declare(strict_types=1);

namespace App\Modules\Review\Controllers;

use App\Core\Http\Response;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Customer\Services\AuthService;
use App\Modules\Review\Services\ProductReviewService;
use InvalidArgumentException;

final class ProductReviewStorefrontController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly CatalogService $catalog,
        private readonly ProductReviewService $reviews
    ) {
    }

    public function store(string $slug): Response
    {
        $customer = $this->auth->currentCustomer();
        if ($customer === null) {
            return $this->redirect('/login?error=' . urlencode('Logga in för att lämna en recension.'));
        }

        $product = $this->catalog->productPage($slug);
        if ($product === null) {
            return $this->redirect('/search?error=' . urlencode('Produkten hittades inte.'));
        }

        try {
            $this->reviews->createFromCustomer((int) $customer['id'], (int) $product['id'], $_POST, $customer);

            return $this->redirect('/product/' . rawurlencode($slug) . '?review_message=' . urlencode('Tack! Din recension är mottagen och inväntar granskning.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/product/' . rawurlencode($slug) . '?review_error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
