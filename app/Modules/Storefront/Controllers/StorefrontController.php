<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Customer\Services\AuthService;
use App\Modules\Review\Services\ProductReviewService;
use App\Modules\Storefront\Services\RecentViewedService;
use App\Modules\Storefront\Services\SeoService;
use App\Modules\StockAlert\Services\StockAlertService;
use App\Modules\Wishlist\Services\WishlistService;
use App\Modules\Compare\Services\CompareService;
use App\Modules\Fitment\Services\FitmentService;
use App\Modules\Fitment\Services\SavedVehicleService;
use App\Modules\Fitment\Services\FitmentStorefrontService;
use App\Modules\Fitment\Services\VehicleNavigationService;

final class StorefrontController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly CatalogService $catalog,
        private readonly CmsPageService $pages,
        private readonly AuthService $auth,
        private readonly ProductReviewService $reviews,
        private readonly SeoService $seo,
        private readonly WishlistService $wishlists,
        private readonly StockAlertService $stockAlerts,
        private readonly RecentViewedService $recentViewed,
        private readonly CompareService $compare,
        private readonly FitmentService $fitment,
        private readonly SavedVehicleService $savedVehicles,
        private readonly FitmentStorefrontService $fitmentStorefront,
        private readonly VehicleNavigationService $vehicleNavigation
    ) {
    }

    public function home(): Response
    {
        $this->applyPrimaryVehicleForCustomer();

        return new Response($this->views->render('storefront.home', [
            'products' => $this->catalog->latestProducts(8),
            'recentlyViewedProducts' => $this->recentViewed->recentlyViewedProducts(6),
            'infoPages' => $this->pages->storefrontInfoPages(),
            'seo' => $this->seo->forStaticPage('Start', '/'),
            'fitment' => $this->fitment->selectorData(),
            'fitmentNotice' => trim((string) ($_GET['fitment_notice'] ?? '')),
            'fitmentStorefront' => $this->fitmentStorefront->activeVehiclePayload($this->customerId()),
            'vehicleNavigation' => $this->vehicleNavigation->storefrontPayload($this->customerId()),
        ]));
    }

    public function category(string $slug): Response
    {
        $this->applyPrimaryVehicleForCustomer();

        $payload = $this->catalog->categoryPage($slug, $this->fitment->catalogQueryWithFitment($_GET));
        $payload['infoPages'] = $this->pages->storefrontInfoPages();

        $secondaryFilters = $this->hasSecondaryCategoryFilters($_GET);
        $payload['seo'] = $this->seo->forCategory($payload['category'], '/category/' . rawurlencode($slug), $secondaryFilters);
        $payload['fitment'] = $this->fitment->selectorData();
        $payload['fitmentNotice'] = trim((string) ($_GET['fitment_notice'] ?? ''));
        $payload['fitmentStorefront'] = $this->fitmentStorefront->activeVehiclePayload($this->customerId());
        $payload['vehicleNavigation'] = $this->vehicleNavigation->storefrontPayload($this->customerId());

        return new Response($this->views->render('storefront.category', $payload));
    }

    public function search(): Response
    {
        $this->applyPrimaryVehicleForCustomer();

        $payload = $this->catalog->searchPage($this->fitment->catalogQueryWithFitment($_GET));
        $payload['infoPages'] = $this->pages->storefrontInfoPages();
        $payload['seo'] = $this->seo->forSearch('/search');
        $payload['fitment'] = $this->fitment->selectorData();
        $payload['fitmentNotice'] = trim((string) ($_GET['fitment_notice'] ?? ''));
        $payload['fitmentStorefront'] = $this->fitmentStorefront->activeVehiclePayload($this->customerId());
        $payload['vehicleNavigation'] = $this->vehicleNavigation->storefrontPayload($this->customerId());

        return new Response($this->views->render('storefront.search', $payload));
    }

    public function product(string $slug): Response
    {
        $this->applyPrimaryVehicleForCustomer();

        $product = $this->catalog->productPage($slug);

        $recentlyViewedProducts = [];
        if ($product !== null) {
            $this->recentViewed->trackProductView((int) $product['id']);
            $recentlyViewedProducts = $this->recentViewed->recentlyViewedProducts(6, (int) $product['id']);
        }

        $summary = ['review_count' => 0, 'average_rating' => 0.0];
        $publicReviews = [];
        if ($product !== null) {
            $summary = $this->reviews->publicSummaryForProduct((int) $product['id']);
            $publicReviews = $this->reviews->publicReviewsForProduct((int) $product['id']);
        }

        $customer = $this->auth->currentCustomer();
        $isWishlisted = false;
        if ($customer !== null && $product !== null) {
            $isWishlisted = $this->wishlists->isSaved((int) $customer['id'], (int) $product['id']);
        }

        $stockAlertPrefill = $customer !== null ? (string) ($customer['email'] ?? '') : '';
        $hasActiveStockAlert = false;
        if ($product !== null && (bool) ($product['is_purchasable'] ?? false) === false && $stockAlertPrefill !== '') {
            $hasActiveStockAlert = $this->stockAlerts->hasActiveSubscription((int) $product['id'], $stockAlertPrefill);
        }

        return new Response($this->views->render('storefront.product', [
            'product' => $product,
            'recentlyViewedProducts' => $recentlyViewedProducts,
            'compareCount' => $this->compare->count(),
            'maxCompareItems' => $this->compare->maxItems(),
            'isCompared' => $product !== null ? $this->compare->contains((int) $product['id']) : false,
            'compareMessage' => trim((string) ($_GET['compare_message'] ?? '')),
            'compareError' => trim((string) ($_GET['compare_error'] ?? '')),
            'reviewSummary' => $summary,
            'publicReviews' => $publicReviews,
            'customer' => $customer,
            'isWishlisted' => $isWishlisted,
            'wishlistMessage' => trim((string) ($_GET['message'] ?? '')),
            'wishlistError' => trim((string) ($_GET['error'] ?? '')),
            'reviewMessage' => trim((string) ($_GET['review_message'] ?? '')),
            'reviewError' => trim((string) ($_GET['review_error'] ?? '')),
            'stockAlertMessage' => trim((string) ($_GET['stock_alert_message'] ?? '')),
            'stockAlertNotice' => trim((string) ($_GET['stock_alert_notice'] ?? '')),
            'stockAlertError' => trim((string) ($_GET['stock_alert_error'] ?? '')),
            'stockAlertEmailPrefill' => $stockAlertPrefill,
            'hasActiveStockAlert' => $hasActiveStockAlert,
            'infoPages' => $this->pages->storefrontInfoPages(),
            'seo' => $this->seo->forProduct($product, '/product/' . rawurlencode($slug)),
            'fitment' => $this->fitment->selectorData(),
            'fitmentStatus' => $product !== null ? $this->fitmentStorefront->fitmentSignalForProduct((int) $product['id']) : null,
            'fitmentNotice' => trim((string) ($_GET['fitment_notice'] ?? '')),
            'fitmentStorefront' => $this->fitmentStorefront->activeVehiclePayload($this->customerId()),
            'vehicleNavigation' => $this->vehicleNavigation->storefrontPayload($this->customerId()),
        ]));
    }


    public function shopByVehicle(): Response
    {
        $this->applyPrimaryVehicleForCustomer();

        $customerId = $this->customerId();
        $vehicleNavigation = $this->vehicleNavigation->storefrontPayload($customerId, 18);

        return new Response($this->views->render('storefront.shop_by_vehicle', [
            'infoPages' => $this->pages->storefrontInfoPages(),
            'seo' => $this->seo->forStaticPage('Handla till vald bil', '/shop-by-vehicle'),
            'fitment' => $this->fitment->selectorData(),
            'fitmentNotice' => trim((string) ($_GET['fitment_notice'] ?? '')),
            'fitmentStorefront' => $this->fitmentStorefront->activeVehiclePayload($customerId),
            'vehicleNavigation' => $vehicleNavigation,
        ]));
    }

    public function cart(): Response
    {
        $this->applyPrimaryVehicleForCustomer();

        return new Response($this->views->render('storefront.cart', [
            'infoPages' => $this->pages->storefrontInfoPages(),
            'fitment' => $this->fitment->selectorData(),
            'fitmentNotice' => trim((string) ($_GET['fitment_notice'] ?? '')),
            'fitmentStorefront' => $this->fitmentStorefront->activeVehiclePayload($this->customerId()),
            'vehicleNavigation' => $this->vehicleNavigation->storefrontPayload($this->customerId()),
        ]));
    }

    public function checkout(): Response
    {
        $this->applyPrimaryVehicleForCustomer();

        return new Response($this->views->render('storefront.checkout', [
            'infoPages' => $this->pages->storefrontInfoPages(),
            'fitment' => $this->fitment->selectorData(),
            'fitmentNotice' => trim((string) ($_GET['fitment_notice'] ?? '')),
            'fitmentStorefront' => $this->fitmentStorefront->activeVehiclePayload($this->customerId()),
            'vehicleNavigation' => $this->vehicleNavigation->storefrontPayload($this->customerId()),
        ]));
    }


    private function applyPrimaryVehicleForCustomer(): void
    {
        $customer = $this->auth->currentCustomer();
        if ($customer === null) {
            return;
        }

        $this->savedVehicles->applyPrimaryVehicleIfNoActiveSelection((int) $customer['id']);
    }

    private function customerId(): ?int
    {
        $customer = $this->auth->currentCustomer();

        return $customer !== null ? (int) $customer['id'] : null;
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
