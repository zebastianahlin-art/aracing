<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Brand\Services\BrandService;
use App\Modules\Supplier\Services\SupplierService;
use App\Modules\Supplier\Services\SupplierWatchlistService;
use InvalidArgumentException;

final class SupplierWatchlistAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly SupplierWatchlistService $watchlist,
        private readonly SupplierService $suppliers,
        private readonly BrandService $brands,
    ) {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.supplier_watchlist.index', [
            'entries' => $this->watchlist->listForAdmin(),
            'suppliers' => $this->suppliers->listActive(),
            'brands' => $this->brands->list(),
            'counts' => $this->watchlist->activeCounts(),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function store(): Response
    {
        try {
            $payload = $_POST;
            $entityType = trim(strtolower((string) ($_POST['entity_type'] ?? '')));
            if ($entityType === 'supplier') {
                $payload['entity_id'] = $_POST['supplier_id'] ?? null;
            } elseif ($entityType === 'brand') {
                $payload['entity_id'] = $_POST['brand_id'] ?? null;
            }

            $this->watchlist->createOrUpdate($payload, null);
            return $this->redirect('/admin/supplier-watchlist?message=' . urlencode('Bevakning sparad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/supplier-watchlist?error=' . urlencode($e->getMessage()));
        }
    }

    public function update(string $id): Response
    {
        try {
            $this->watchlist->updateEntry((int) $id, $_POST);
            return $this->redirect('/admin/supplier-watchlist?message=' . urlencode('Bevakning uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/supplier-watchlist?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
