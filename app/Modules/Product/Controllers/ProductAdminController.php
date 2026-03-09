<?php

declare(strict_types=1);

namespace App\Modules\Product\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Brand\Services\BrandService;
use App\Modules\Category\Services\CategoryService;
use App\Modules\Product\Services\ProductService;
use App\Modules\Product\Services\ProductMediaService;
use App\Modules\Product\Services\ProductSupplierLinkService;
use App\Modules\Supplier\Services\SupplierService;

final class ProductAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly ProductService $products,
        private readonly ProductMediaService $media,
        private readonly BrandService $brands,
        private readonly CategoryService $categories,
        private readonly SupplierService $suppliers,
        private readonly ProductSupplierLinkService $productSupplierLinks
    ) {
    }

    public function index(): Response
    {
        $overview = $this->products->operationalOverview($_GET);

        return new Response($this->views->render('admin.products.index', [
            'products' => $overview['rows'],
            'filters' => $overview['filters'],
            'notice' => (string) ($_GET['notice'] ?? ''),
        ]));
    }

    public function runProductAction(string $id): Response
    {
        $productId = (int) $id;
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'sync_snapshot') {
            $this->products->syncPrimarySnapshot($productId);
        }

        if ($action === 'copy_price') {
            $this->products->copySupplierPriceToPublished($productId);
        }

        if ($action === 'copy_stock') {
            $this->products->copySupplierStockToPublished($productId);
        }

        if ($action === 'refresh_stock_status') {
            $this->products->refreshPublishedStockStatusFromQuantity($productId);
        }

        if ($action === 'set_active') {
            $this->products->setActiveStatus($productId, true);
        }

        if ($action === 'set_inactive') {
            $this->products->setActiveStatus($productId, false);
        }

        return $this->redirect('/admin/products?notice=' . urlencode('Produktåtgärd sparad'));
    }

    public function runBulkAction(): Response
    {
        $action = (string) ($_POST['bulk_action'] ?? '');
        $selected = $_POST['selected_product_ids'] ?? [];
        $ids = [];

        if (is_array($selected)) {
            foreach ($selected as $id) {
                $normalized = trim((string) $id);
                if ($normalized !== '' && ctype_digit($normalized)) {
                    $ids[] = (int) $normalized;
                }
            }
        }

        if ($ids !== [] && $action !== '') {
            $this->products->applyBulkOperation($ids, $action);
        }

        return $this->redirect('/admin/products?notice=' . urlencode('Bulkåtgärd körd'));
    }

    public function createForm(): Response
    {
        $selectedSupplierId = $this->toNullableInt($_GET['supplier_id'] ?? null);
        $supplierItemQuery = trim((string) ($_GET['supplier_item_query'] ?? ''));
        $draft = null;
        $supplierItemId = $this->toNullableInt($_GET['supplier_item_id'] ?? null);

        if ($supplierItemId !== null) {
            $draft = $this->products->prefillDraftFromSupplierItem($supplierItemId);
            if ($draft !== null && $selectedSupplierId === null) {
                $selectedSupplierId = (int) ($draft['supplier_item']['supplier_id'] ?? 0) ?: null;
            }
        }

        return new Response($this->views->render('admin.products.form', [
            'product' => $draft['product_defaults'] ?? null,
            'prefill_draft' => $draft,
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
        $productId = $this->products->create($_POST);

        $returnToReview = isset($_POST['return_to_review']) && (int) $_POST['return_to_review'] === 1;
        if ($returnToReview) {
            return $this->redirect('/admin/supplier-item-review?notice=' . urlencode('Produkt skapad och kopplad (#' . $productId . ')'));
        }

        return $this->redirect('/admin/products');
    }

    public function articleCareQueue(): Response
    {
        $queue = $this->products->articleCareQueue($_GET);

        return new Response($this->views->render('admin.products.article_care_queue', [
            'rows' => $queue['rows'],
            'filters' => $queue['filters'],
        ]));
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

    public function uploadImages(string $id): Response
    {
        $productId = (int) $id;

        try {
            $count = $this->media->uploadImages($productId, $_FILES, (string) ($_POST['default_alt_text'] ?? ''));

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode($count . ' bild(er) uppladdade') . '#media');
        } catch (\RuntimeException $exception) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($exception->getMessage()) . '#media');
        }
    }

    public function updateImage(string $id, string $imageId): Response
    {
        $productId = (int) $id;
        $isPrimary = ((string) ($_POST['is_primary'] ?? '0')) === '1';

        try {
            $this->media->updateImageMeta(
                $productId,
                (int) $imageId,
                (string) ($_POST['alt_text'] ?? ''),
                (int) ($_POST['sort_order'] ?? 0),
                $isPrimary
            );

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Bild metadata sparad') . '#media');
        } catch (\RuntimeException $exception) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($exception->getMessage()) . '#media');
        }
    }

    public function setPrimaryImage(string $id, string $imageId): Response
    {
        $productId = (int) $id;

        try {
            $this->media->setPrimaryImage($productId, (int) $imageId);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Primärbild uppdaterad') . '#media');
        } catch (\RuntimeException $exception) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($exception->getMessage()) . '#media');
        }
    }

    public function deleteImage(string $id, string $imageId): Response
    {
        $productId = (int) $id;

        $this->media->deleteImage($productId, (int) $imageId);

        return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Bild borttagen') . '#media');
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
