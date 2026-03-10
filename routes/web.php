<?php

declare(strict_types=1);

use App\Core\Routing\Router;
use App\Modules\Admin\Controllers\AdminController;
use App\Modules\Brand\Controllers\BrandAdminController;
use App\Modules\Brand\Repositories\BrandRepository;
use App\Modules\Brand\Services\BrandService;
use App\Modules\Cart\Controllers\CartController;
use App\Modules\Cart\Repositories\CartProductRepository;
use App\Modules\Cart\Repositories\CartRepository;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Repositories\CatalogRepository;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Category\Controllers\CategoryAdminController;
use App\Modules\Category\Repositories\CategoryRepository;
use App\Modules\Category\Services\CategoryService;
use App\Modules\Checkout\Controllers\CheckoutController;
use App\Modules\Cms\Controllers\CmsHomeAdminController;
use App\Modules\Cms\Controllers\CmsPageAdminController;
use App\Modules\Cms\Controllers\CmsStorefrontController;
use App\Modules\Cms\Repositories\CmsHomeSectionRepository;
use App\Modules\Cms\Repositories\CmsPageRepository;
use App\Modules\Cms\Services\CmsHomeService;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Checkout\Services\CheckoutService;
use App\Modules\Import\Controllers\ImportProfileAdminController;
use App\Modules\Import\Controllers\ImportRunAdminController;
use App\Modules\Import\Controllers\SupplierItemReviewAdminController;
use App\Modules\Import\Repositories\ImportProfileRepository;
use App\Modules\Import\Repositories\ImportRowRepository;
use App\Modules\Import\Repositories\ImportRunRepository;
use App\Modules\Import\Repositories\SupplierItemRepository;
use App\Modules\Import\Repositories\SupplierItemReviewRepository;
use App\Modules\Import\Services\CsvImportService;
use App\Modules\Import\Services\ImportProfileService;
use App\Modules\Import\Services\ImportRunService;
use App\Modules\Import\Services\SupplierItemReviewService;
use App\Modules\Order\Controllers\OrderAdminController;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Order\Services\OrderService;
use App\Modules\Product\Controllers\ProductAdminController;
use App\Modules\Product\Repositories\ProductAttributeRepository;
use App\Modules\Product\Repositories\ProductImageRepository;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Repositories\ProductSupplierItemLookupRepository;
use App\Modules\Product\Repositories\ProductSupplierLinkRepository;
use App\Modules\Product\Services\ProductService;
use App\Modules\Product\Services\ProductMediaService;
use App\Modules\Product\Services\ProductImageStorageService;
use App\Modules\Product\Services\ProductSupplierLinkService;
use App\Modules\Purchasing\Controllers\PurchasingAdminController;
use App\Modules\Purchasing\Repositories\PurchaseListItemRepository;
use App\Modules\Purchasing\Repositories\PurchaseListRepository;
use App\Modules\Purchasing\Repositories\RefillNeedRepository;
use App\Modules\Purchasing\Services\PurchasingService;
use App\Modules\Storefront\Controllers\StorefrontController;
use App\Modules\Supplier\Controllers\SupplierAdminController;
use App\Modules\Supplier\Repositories\SupplierRepository;
use App\Modules\Supplier\Services\SupplierService;

/** @var array{router: Router, view: \App\Core\View\ViewFactory, pdo: \PDO} $app */
$brandService = new BrandService(new BrandRepository($app['pdo']));
$categoryService = new CategoryService(new CategoryRepository($app['pdo']));
$supplierItemRepository = new SupplierItemRepository($app['pdo']);
$productSupplierLinkService = new ProductSupplierLinkService(
    new ProductSupplierLinkRepository($app['pdo']),
    new ProductSupplierItemLookupRepository($app['pdo']),
    $supplierItemRepository
);
$productService = new ProductService(
    new ProductRepository($app['pdo']),
    new ProductAttributeRepository($app['pdo']),
    new ProductImageRepository($app['pdo']),
    $productSupplierLinkService,
    new ProductSupplierItemLookupRepository($app['pdo'])
);
$productMediaService = new ProductMediaService(
    new ProductRepository($app['pdo']),
    new ProductImageRepository($app['pdo']),
    new ProductImageStorageService()
);
$catalogService = new CatalogService(new CatalogRepository($app['pdo']));
$supplierService = new SupplierService(new SupplierRepository($app['pdo']));
$importProfileService = new ImportProfileService(new ImportProfileRepository($app['pdo']));
$importRowRepository = new ImportRowRepository($app['pdo']);
$importRunRepository = new ImportRunRepository($app['pdo']);
$importRunService = new ImportRunService($importRunRepository, $importRowRepository);
$csvImportService = new CsvImportService(
    $importRunRepository,
    $importRowRepository,
    $supplierItemRepository,
    $importProfileService
);
$cartService = new CartService(new CartRepository($app['pdo']), new CartProductRepository($app['pdo']));
$orderService = new OrderService(new OrderRepository($app['pdo']));
$cmsPageService = new CmsPageService(new CmsPageRepository($app['pdo']));
$cmsHomeService = new CmsHomeService(
    new CmsHomeSectionRepository($app['pdo']),
    new ProductRepository($app['pdo']),
    new CategoryRepository($app['pdo'])
);

$purchasingService = new PurchasingService(
    new RefillNeedRepository($app['pdo']),
    new PurchaseListRepository($app['pdo']),
    new PurchaseListItemRepository($app['pdo'])
);

$storefront = new StorefrontController($app['view'], $catalogService, $cmsPageService);
$cmsStorefront = new CmsStorefrontController($app['view'], $cmsHomeService, $cmsPageService);
$cartController = new CartController($app['view'], $cartService, $cmsPageService);
$checkoutController = new CheckoutController($app['view'], $cartService, new CheckoutService(), $orderService, $cmsPageService);
$admin = new AdminController($app['view']);
$brandAdmin = new BrandAdminController($app['view'], $brandService);
$categoryAdmin = new CategoryAdminController($app['view'], $categoryService);
$productAdmin = new ProductAdminController($app['view'], $productService, $productMediaService, $brandService, $categoryService, $supplierService, $productSupplierLinkService);
$supplierAdmin = new SupplierAdminController($app['view'], $supplierService);
$importProfileAdmin = new ImportProfileAdminController($app['view'], $importProfileService, $supplierService);
$importRunAdmin = new ImportRunAdminController($app['view'], $importRunService, $importProfileService, $csvImportService);
$supplierItemReviewService = new SupplierItemReviewService(
    new SupplierItemReviewRepository($app['pdo']),
    $supplierItemRepository,
    new ProductSupplierLinkRepository($app['pdo']),
    new ProductSupplierItemLookupRepository($app['pdo'])
);
$supplierItemReviewAdmin = new SupplierItemReviewAdminController($app['view'], $supplierItemReviewService, $supplierService, $importRunService, $productService);
$orderAdmin = new OrderAdminController($app['view'], $orderService);
$purchasingAdmin = new PurchasingAdminController($app['view'], $purchasingService);
$cmsPageAdmin = new CmsPageAdminController($app['view'], $cmsPageService);
$cmsHomeAdmin = new CmsHomeAdminController($app['view'], $cmsHomeService);

$app['router']->get('/', [$cmsStorefront, 'home']);
$app['router']->get('/category/{slug}', [$storefront, 'category']);
$app['router']->get('/product/{slug}', [$storefront, 'product']);
$app['router']->get('/search', [$storefront, 'search']);

$app['router']->get('/pages/{slug}', [$cmsStorefront, 'page']);
$app['router']->get('/cart', [$cartController, 'show']);
$app['router']->post('/cart/items', [$cartController, 'add']);
$app['router']->post('/cart/items/update', [$cartController, 'update']);
$app['router']->post('/cart/items/remove', [$cartController, 'remove']);

$app['router']->get('/checkout', [$checkoutController, 'form']);
$app['router']->post('/checkout/place-order', [$checkoutController, 'placeOrder']);
$app['router']->get('/checkout/confirmation', [$checkoutController, 'confirmation']);
$app['router']->get('/order-status', [$checkoutController, 'orderStatus']);

$app['router']->get('/admin', [$admin, 'dashboard']);
$app['router']->get('/admin/brands', [$brandAdmin, 'index']);
$app['router']->get('/admin/brands/create', [$brandAdmin, 'createForm']);
$app['router']->post('/admin/brands', [$brandAdmin, 'store']);
$app['router']->get('/admin/brands/{id}/edit', [$brandAdmin, 'editForm']);
$app['router']->post('/admin/brands/{id}', [$brandAdmin, 'update']);

$app['router']->get('/admin/categories', [$categoryAdmin, 'index']);
$app['router']->get('/admin/categories/create', [$categoryAdmin, 'createForm']);
$app['router']->post('/admin/categories', [$categoryAdmin, 'store']);
$app['router']->get('/admin/categories/{id}/edit', [$categoryAdmin, 'editForm']);
$app['router']->post('/admin/categories/{id}', [$categoryAdmin, 'update']);

$app['router']->get('/admin/products', [$productAdmin, 'index']);
$app['router']->post('/admin/products/operations', [$productAdmin, 'runBulkAction']);
$app['router']->post('/admin/products/{id}/operations', [$productAdmin, 'runProductAction']);
$app['router']->get('/admin/products/create', [$productAdmin, 'createForm']);
$app['router']->post('/admin/products', [$productAdmin, 'store']);
$app['router']->get('/admin/products/article-care', [$productAdmin, 'articleCareQueue']);
$app['router']->get('/admin/products/{id}/edit', [$productAdmin, 'editForm']);
$app['router']->post('/admin/products/{id}', [$productAdmin, 'update']);
$app['router']->post('/admin/products/{id}/images/upload', [$productAdmin, 'uploadImages']);
$app['router']->post('/admin/products/{id}/images/{imageId}/update', [$productAdmin, 'updateImage']);
$app['router']->post('/admin/products/{id}/images/{imageId}/primary', [$productAdmin, 'setPrimaryImage']);
$app['router']->post('/admin/products/{id}/images/{imageId}/delete', [$productAdmin, 'deleteImage']);

$app['router']->get('/admin/orders', [$orderAdmin, 'index']);
$app['router']->get('/admin/orders/{id}', [$orderAdmin, 'show']);
$app['router']->post('/admin/orders/{id}/payment', [$orderAdmin, 'updatePayment']);
$app['router']->post('/admin/orders/{id}/notes', [$orderAdmin, 'addNote']);
$app['router']->post('/admin/orders/{id}/order-status', [$orderAdmin, 'transitionOrderStatus']);
$app['router']->post('/admin/orders/{id}/fulfillment-status', [$orderAdmin, 'transitionFulfillmentStatus']);
$app['router']->post('/admin/orders/{id}/shipment', [$orderAdmin, 'updateShipment']);
$app['router']->post('/admin/orders/{id}/internal-reference', [$orderAdmin, 'updateInternalReference']);
$app['router']->get('/admin/orders/{id}/print', [$orderAdmin, 'printView']);


$app['router']->get('/admin/purchasing', [$purchasingAdmin, 'refillNeeds']);
$app['router']->post('/admin/purchasing/purchase-lists', [$purchasingAdmin, 'createPurchaseList']);
$app['router']->get('/admin/purchase-lists', [$purchasingAdmin, 'purchaseLists']);
$app['router']->get('/admin/purchase-lists/{id}', [$purchasingAdmin, 'purchaseListDetail']);
$app['router']->post('/admin/purchase-lists/{id}/update', [$purchasingAdmin, 'updatePurchaseList']);
$app['router']->post('/admin/purchase-lists/{id}/items/{itemId}/quantity', [$purchasingAdmin, 'updatePurchaseListItem']);

$app['router']->get('/admin/suppliers', [$supplierAdmin, 'index']);
$app['router']->get('/admin/suppliers/create', [$supplierAdmin, 'createForm']);
$app['router']->post('/admin/suppliers', [$supplierAdmin, 'store']);
$app['router']->get('/admin/suppliers/{id}/edit', [$supplierAdmin, 'editForm']);
$app['router']->post('/admin/suppliers/{id}', [$supplierAdmin, 'update']);

$app['router']->get('/admin/import-profiles', [$importProfileAdmin, 'index']);
$app['router']->get('/admin/import-profiles/create', [$importProfileAdmin, 'createForm']);
$app['router']->post('/admin/import-profiles', [$importProfileAdmin, 'store']);
$app['router']->get('/admin/import-profiles/{id}/edit', [$importProfileAdmin, 'editForm']);
$app['router']->post('/admin/import-profiles/{id}', [$importProfileAdmin, 'update']);

$app['router']->get('/admin/import-runs', [$importRunAdmin, 'index']);
$app['router']->post('/admin/import-runs/upload', [$importRunAdmin, 'upload']);
$app['router']->get('/admin/import-runs/{id}', [$importRunAdmin, 'detail']);



$app['router']->get('/admin/cms/pages', [$cmsPageAdmin, 'index']);
$app['router']->get('/admin/cms/pages/create', [$cmsPageAdmin, 'createForm']);
$app['router']->post('/admin/cms/pages', [$cmsPageAdmin, 'store']);
$app['router']->get('/admin/cms/pages/{id}/edit', [$cmsPageAdmin, 'editForm']);
$app['router']->post('/admin/cms/pages/{id}', [$cmsPageAdmin, 'update']);
$app['router']->get('/admin/cms/home', [$cmsHomeAdmin, 'edit']);
$app['router']->post('/admin/cms/home', [$cmsHomeAdmin, 'update']);

$app['router']->get('/admin/supplier-item-review', [$supplierItemReviewAdmin, 'index']);
$app['router']->post('/admin/supplier-item-review/{id}/match', [$supplierItemReviewAdmin, 'match']);
$app['router']->post('/admin/supplier-item-review/{id}/clear', [$supplierItemReviewAdmin, 'clearMatch']);
$app['router']->post('/admin/supplier-item-review/{id}/reviewed', [$supplierItemReviewAdmin, 'markReviewed']);
