<?php

declare(strict_types=1);

namespace App\Modules\Import\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Import\Services\ImportRunService;
use App\Modules\Import\Services\SupplierItemReviewService;
use App\Modules\Product\Services\ProductService;
use App\Modules\Supplier\Services\SupplierService;

final class SupplierItemReviewAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly SupplierItemReviewService $reviews,
        private readonly SupplierService $suppliers,
        private readonly ImportRunService $runs,
        private readonly ProductService $products
    ) {
    }

    public function index(): Response
    {
        $queue = $this->reviews->reviewQueue($_GET);

        return new Response($this->views->render('admin.supplier_item_review.index', [
            'rows' => $queue['rows'],
            'filters' => $queue['filters'],
            'quality' => $queue['quality'],
            'suppliers' => $this->suppliers->listActive(),
            'runs' => $this->runs->list(),
            'products' => $this->products->searchForSupplierMatch((string) ($queue['filters']['product_query'] ?? '')),
            'notice' => (string) ($_GET['notice'] ?? ''),
            'error' => (string) ($_GET['error'] ?? ''),
        ]));
    }

    public function match(string $id): Response
    {
        try {
            $supplierItemId = (int) $id;
            $productId = (int) ($_POST['product_id'] ?? 0);
            if ($supplierItemId <= 0 || $productId <= 0) {
                throw new \RuntimeException('Välj giltig produkt för matchning.');
            }

            $this->reviews->matchToProduct($supplierItemId, $productId);

            return $this->redirectWithContext('Leverantörsartikel matchad.');
        } catch (\Throwable $exception) {
            return $this->redirectWithContext('', $exception->getMessage());
        }
    }

    public function clearMatch(string $id): Response
    {
        $this->reviews->clearMatch((int) $id);

        return $this->redirectWithContext('Matchning rensad.');
    }

    public function markReviewed(string $id): Response
    {
        $this->reviews->markReviewed((int) $id);

        return $this->redirectWithContext('Artikel markerad som granskad.');
    }

    private function redirectWithContext(string $notice = '', string $error = ''): Response
    {
        $query = trim((string) ($_POST['return_query'] ?? ''));
        $base = '/admin/supplier-item-review';

        $params = [];
        parse_str($query, $params);

        if ($notice !== '') {
            $params['notice'] = $notice;
        }

        if ($error !== '') {
            $params['error'] = $error;
        }

        $final = http_build_query($params);

        return new Response('', 302, [
            'Location' => $base . ($final !== '' ? '?' . $final : ''),
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
