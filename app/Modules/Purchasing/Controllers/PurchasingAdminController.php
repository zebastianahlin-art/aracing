<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Purchasing\Services\PurchasingService;
use InvalidArgumentException;

final class PurchasingAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly PurchasingService $purchasing)
    {
    }

    public function refillNeeds(): Response
    {
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'supplier_id' => trim((string) ($_GET['supplier_id'] ?? '')),
        ];

        return new Response($this->views->render('admin.purchasing.refill_needs', [
            'rows' => $this->purchasing->listRefillNeeds($filters),
            'filters' => $filters,
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function createPurchaseList(): Response
    {
        try {
            $purchaseListId = $this->purchasing->createManualPurchaseList(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['notes'] ?? ''),
                (array) ($_POST['selected_product_ids'] ?? []),
                (array) ($_POST['selected_quantity'] ?? [])
            );

            return $this->redirect('/admin/purchase-lists/' . $purchaseListId . '?message=' . urlencode('Inköpsunderlag skapat.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/purchasing?error=' . urlencode($e->getMessage()));
        }
    }

    public function purchaseLists(): Response
    {
        return new Response($this->views->render('admin.purchase_lists.index', [
            'lists' => $this->purchasing->listPurchaseLists(),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function purchaseListDetail(string $id): Response
    {
        $detail = $this->purchasing->getPurchaseListDetail((int) $id);

        return new Response($this->views->render('admin.purchase_lists.show', [
            'detail' => $detail,
            'statusOptions' => $this->purchasing->statusOptions(),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function updatePurchaseList(string $id): Response
    {
        try {
            $this->purchasing->updatePurchaseListMeta((int) $id, (string) ($_POST['status'] ?? ''), (string) ($_POST['notes'] ?? ''));

            return $this->redirect('/admin/purchase-lists/' . (int) $id . '?message=' . urlencode('Inköpsunderlag uppdaterat.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/purchase-lists/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function updatePurchaseListItem(string $id, string $itemId): Response
    {
        try {
            $this->purchasing->updateSelectedQuantity((int) $itemId, $_POST['selected_quantity'] ?? null);

            return $this->redirect('/admin/purchase-lists/' . (int) $id . '?message=' . urlencode('Rad uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/purchase-lists/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
