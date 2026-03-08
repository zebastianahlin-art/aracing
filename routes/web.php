<?php

declare(strict_types=1);

use App\Core\Routing\Router;
use App\Modules\Admin\Controllers\AdminController;
use App\Modules\Storefront\Controllers\StorefrontController;

/** @var array{router: Router, view: \App\Core\View\ViewFactory} $app */
$storefront = new StorefrontController($app['view']);
$admin = new AdminController($app['view']);

$app['router']->get('/', [$storefront, 'home']);
$app['router']->get('/category/{slug}', [$storefront, 'category']);
$app['router']->get('/product/{slug}', [$storefront, 'product']);
$app['router']->get('/cart', [$storefront, 'cart']);
$app['router']->get('/checkout', [$storefront, 'checkout']);
$app['router']->get('/admin', [$admin, 'dashboard']);
