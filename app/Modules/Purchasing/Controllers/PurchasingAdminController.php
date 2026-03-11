<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Purchasing\Services\PurchaseOrderDraftService;
use App\Modules\Purchasing\Services\PurchasingService;
use InvalidArgumentException;

final class PurchasingAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly PurchasingService $purchasing,
        private readonly PurchaseOrderDraftService $drafts,
    ) {
    }

    public function refillNeeds(): Response
    {
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'supplier_id' => (string) max(0, (int) ($_GET['supplier_id'] ?? 0)),
            'reason' => trim((string) ($_GET['reason'] ?? '')),
            'manual_status' => trim((string) ($_GET['manual_status'] ?? '')),
        ];

        return new Response($this->views->render('admin.purchasing.refill_needs', [
            'rows' => $this->purchasing->listRefillNeeds($filters),
            'filters' => $filters,
            'supplierOptions' => $this->purchasing->listSupplierOptions(),
            'reasonOptions' => $this->purchasing->restockReasonOptions(),
            'manualStatusOptions' => $this->purchasing->manualRestockStatusOptions(),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function updateRestockFlag(string $productId): Response
    {
        try {
            $this->purchasing->updateRestockFlag((int) $productId, (string) ($_POST['manual_status'] ?? 'new'), (string) ($_POST['manual_note'] ?? ''));

            return $this->redirect('/admin/purchasing?message=' . urlencode('Restockmarkering uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/purchasing?error=' . urlencode($e->getMessage()));
        }
    }

    public function createPurchaseOrderDrafts(): Response
    {
        try {
            $result = $this->drafts->createFromRestockSelection(
                (array) ($_POST['selected_product_ids'] ?? []),
                (array) ($_POST['selected_quantity'] ?? [])
            );

            $createdCount = count($result['created_draft_ids']);
            $message = 'Skapade ' . $createdCount . ' inköpsutkast.';
            if ((int) $result['skipped_without_supplier'] > 0) {
                $message .= ' ' . (int) $result['skipped_without_supplier'] . ' produkt(er) saknade leverantörskoppling och hoppades över.';
            }

            if ($createdCount === 1) {
                return $this->redirect('/admin/purchase-order-drafts/' . (int) $result['created_draft_ids'][0] . '?message=' . urlencode($message));
            }

            return $this->redirect('/admin/purchase-order-drafts?message=' . urlencode($message));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/purchasing?error=' . urlencode($e->getMessage()));
        }
    }

    public function draftIndex(): Response
    {
        $status = trim((string) ($_GET['status'] ?? ''));

        return new Response($this->views->render('admin.purchase_order_drafts.index', [
            'drafts' => $this->drafts->listDrafts($status),
            'statuses' => $this->drafts->statuses(),
            'filters' => ['status' => $status],
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function draftDetail(string $id): Response
    {
        return new Response($this->views->render('admin.purchase_order_drafts.show', [
            'detail' => $this->drafts->getDraftDetail((int) $id),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function draftPrint(string $id): Response
    {
        return new Response($this->views->render('admin.purchase_order_drafts.print', [
            'detail' => $this->drafts->getDraftDetail((int) $id),
        ]));
    }

    public function updateDraftNote(string $id): Response
    {
        try {
            $draftId = (int) $id;
            $this->drafts->updateInternalNote($draftId, (string) ($_POST['internal_note'] ?? ''));

            return $this->redirect('/admin/purchase-order-drafts/' . $draftId . '?message=' . urlencode('Intern notering uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/purchase-order-drafts/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function updateDraftItemQuantity(string $id, string $itemId): Response
    {
        try {
            $draftId = (int) $id;
            $this->drafts->updateItemQuantity($draftId, (int) $itemId, $_POST['quantity'] ?? null);

            return $this->redirect('/admin/purchase-order-drafts/' . $draftId . '?message=' . urlencode('Kvantitet uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/purchase-order-drafts/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function removeDraftItem(string $id, string $itemId): Response
    {
        try {
            $draftId = (int) $id;
            $this->drafts->removeItem($draftId, (int) $itemId);

            return $this->redirect('/admin/purchase-order-drafts/' . $draftId . '?message=' . urlencode('Rad borttagen.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/purchase-order-drafts/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function markDraftExported(string $id): Response
    {
        try {
            $draftId = (int) $id;
            $this->drafts->markExported($draftId);

            return $this->redirect('/admin/purchase-order-drafts/' . $draftId . '?message=' . urlencode('Utkast markerat som exporterat.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/purchase-order-drafts/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function cancelDraft(string $id): Response
    {
        try {
            $draftId = (int) $id;
            $this->drafts->markCancelled($draftId);

            return $this->redirect('/admin/purchase-order-drafts/' . $draftId . '?message=' . urlencode('Utkast avbrutet.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/purchase-order-drafts/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
