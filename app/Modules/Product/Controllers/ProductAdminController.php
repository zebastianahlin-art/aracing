<?php

declare(strict_types=1);

namespace App\Modules\Product\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Brand\Services\BrandService;
use App\Modules\Category\Services\CategoryService;
use App\Modules\Product\Services\ProductService;

final class ProductAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly ProductService $products,
        private readonly BrandService $brands,
        private readonly CategoryService $categories
    ) {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.products.index', ['products' => $this->products->list()]));
    }

    public function createForm(): Response
    {
        return new Response($this->views->render('admin.products.form', [
            'product' => null,
            'brands' => $this->brands->list(),
            'categories' => $this->categories->listForSelect(),
        ]));
    }

    public function store(): Response
    {
        $this->products->create($_POST);

        return $this->redirect('/admin/products');
    }

    public function editForm(string $id): Response
    {
        return new Response($this->views->render('admin.products.form', [
            'product' => $this->products->get((int) $id),
            'brands' => $this->brands->list(),
            'categories' => $this->categories->listForSelect(),
        ]));
    }

    public function update(string $id): Response
    {
        $this->products->update((int) $id, $_POST);

        return $this->redirect('/admin/products');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
