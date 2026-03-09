<?php

declare(strict_types=1);

namespace App\Modules\Product\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Brand\Services\BrandService;
use App\Modules\Category\Services\CategoryService;
use App\Modules\Product\Services\ProductService;
use App\Modules\Product\Services\ProductSupplierLinkService;
use App\Modules\Supplier\Services\SupplierService;

final class ProductAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly ProductService $products,
        private readonly BrandService $brands,
        private readonly CategoryService $categories,
        private readonly SupplierService $suppliers,
        private readonly ProductSupplierLinkService $productSupplierLinks
    ) {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.products.index', ['products' => $this->products->list()]));
    }

    public function createForm(): Response
    {
        $selectedSupplierId = $this->toNullableInt($_GET['supplier_id'] ?? null);
        $supplierItemQuery = trim((string) ($_GET['supplier_item_query'] ?? ''));

        return new Response($this->views->render('admin.products.form', [
            'product' => null,
            'brands' => $this->brands->list(),
            'categories' => $this->categories->listForSelect(),
            'suppliers' => $this->suppliers->listActive(),
            'selected_supplier_id' => $selectedSupplierId,
            'supplier_item_query' => $supplierItemQuery,
            'supplier_items' => $this->productSupplierLinks->searchSupplierItems($selectedSupplierId, $supplierItemQuery),
        ]));
    }

    public function store(): Response
    {
        $this->products->create($_POST);

        return $this->redirect('/admin/products');
    }

    public function editForm(string $id): Response
    {
        $product = $this->products->get((int) $id);
        $selectedSupplierId = $this->toNullableInt($_GET['supplier_id'] ?? ($product['primary_supplier_link']['supplier_id'] ?? null));
        $supplierItemQuery = trim((string) ($_GET['supplier_item_query'] ?? ''));

        return new Response($this->views->render('admin.products.form', [
            'product' => $product,
            'brands' => $this->brands->list(),
            'categories' => $this->categories->listForSelect(),
            'suppliers' => $this->suppliers->listActive(),
            'selected_supplier_id' => $selectedSupplierId,
            'supplier_item_query' => $supplierItemQuery,
            'supplier_items' => $this->productSupplierLinks->searchSupplierItems($selectedSupplierId, $supplierItemQuery),
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

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '' || ctype_digit($normalized) === false) {
            return null;
        }

        return (int) $normalized;
    }
}
