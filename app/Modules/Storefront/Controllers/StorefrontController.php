<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Catalog\Services\CatalogService;

final class StorefrontController
{
    public function __construct(private readonly ViewFactory $views, private readonly CatalogService $catalog)
    {
    }

    public function home(): Response
    {
        return new Response($this->views->render('storefront.home', [
            'products' => $this->catalog->latestProducts(8),
        ]));
    }

    public function category(string $slug): Response
    {
        $payload = $this->catalog->categoryPage($slug);

        return new Response($this->views->render('storefront.category', $payload));
    }

    public function product(string $slug): Response
    {
        return new Response($this->views->render('storefront.product', [
            'product' => $this->catalog->productPage($slug),
        ]));
    }

    public function cart(): Response
    {
        return new Response($this->views->render('storefront.cart'));
    }

    public function checkout(): Response
    {
        return new Response($this->views->render('storefront.checkout'));
    }
}
