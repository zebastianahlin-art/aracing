<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Cms\Services\CmsPageService;

final class StorefrontController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly CatalogService $catalog,
        private readonly CmsPageService $pages
    ) {
    }

    public function home(): Response
    {
        return new Response($this->views->render('storefront.home', [
            'products' => $this->catalog->latestProducts(8),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function category(string $slug): Response
    {
        $payload = $this->catalog->categoryPage($slug, $_GET);
        $payload['infoPages'] = $this->pages->storefrontInfoPages();

        return new Response($this->views->render('storefront.category', $payload));
    }

    public function search(): Response
    {
        $payload = $this->catalog->searchPage($_GET);
        $payload['infoPages'] = $this->pages->storefrontInfoPages();

        return new Response($this->views->render('storefront.search', $payload));
    }

    public function product(string $slug): Response
    {
        return new Response($this->views->render('storefront.product', [
            'product' => $this->catalog->productPage($slug),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function cart(): Response
    {
        return new Response($this->views->render('storefront.cart', [
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function checkout(): Response
    {
        return new Response($this->views->render('storefront.checkout', [
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }
}
