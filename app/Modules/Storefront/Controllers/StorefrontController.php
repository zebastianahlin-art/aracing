<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;

final class StorefrontController
{
    public function __construct(private readonly ViewFactory $views)
    {
    }

    public function home(): Response
    {
        return new Response($this->views->render('storefront.home'));
    }

    public function category(string $slug): Response
    {
        return new Response($this->views->render('storefront.category', ['slug' => $slug]));
    }

    public function product(string $slug): Response
    {
        return new Response($this->views->render('storefront.product', ['slug' => $slug]));
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
