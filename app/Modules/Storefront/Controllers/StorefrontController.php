<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Customer\Services\AuthService;
use App\Modules\Review\Services\ProductReviewService;
use App\Modules\Storefront\Services\SeoService;

final class StorefrontController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly CatalogService $catalog,
        private readonly CmsPageService $pages,
        private readonly AuthService $auth,
        private readonly ProductReviewService $reviews,
        private readonly SeoService $seo
    ) {
    }

    public function home(): Response
    {
        return new Response($this->views->render('storefront.home', [
            'products' => $this->catalog->latestProducts(8),
            'infoPages' => $this->pages->storefrontInfoPages(),
            'seo' => $this->seo->forStaticPage('Start', '/'),
        ]));
    }

    public function category(string $slug): Response
    {
        $payload = $this->catalog->categoryPage($slug, $_GET);
        $payload['infoPages'] = $this->pages->storefrontInfoPages();

        $secondaryFilters = $this->hasSecondaryCategoryFilters($_GET);
        $payload['seo'] = $this->seo->forCategory($payload['category'], '/category/' . rawurlencode($slug), $secondaryFilters);

        return new Response($this->views->render('storefront.category', $payload));
    }

    public function search(): Response
    {
        $payload = $this->catalog->searchPage($_GET);
        $payload['infoPages'] = $this->pages->storefrontInfoPages();
        $payload['seo'] = $this->seo->forSearch('/search');

        return new Response($this->views->render('storefront.search', $payload));
    }

    public function product(string $slug): Response
    {
        $product = $this->catalog->productPage($slug);

        $summary = ['review_count' => 0, 'average_rating' => 0.0];
        $publicReviews = [];
        if ($product !== null) {
            $summary = $this->reviews->publicSummaryForProduct((int) $product['id']);
            $publicReviews = $this->reviews->publicReviewsForProduct((int) $product['id']);
        }

        return new Response($this->views->render('storefront.product', [
            'product' => $product,
            'reviewSummary' => $summary,
            'publicReviews' => $publicReviews,
            'customer' => $this->auth->currentCustomer(),
            'reviewMessage' => trim((string) ($_GET['review_message'] ?? '')),
            'reviewError' => trim((string) ($_GET['review_error'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
            'seo' => $this->seo->forProduct($product, '/product/' . rawurlencode($slug)),
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

    /** @param array<string,mixed> $query */
    private function hasSecondaryCategoryFilters(array $query): bool
    {
        $keys = ['q', 'brand_id', 'min_price', 'max_price', 'stock_status', 'sort'];
        foreach ($keys as $key) {
            if (trim((string) ($query[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }
}
