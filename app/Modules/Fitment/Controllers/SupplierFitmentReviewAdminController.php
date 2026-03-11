<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Fitment\Services\SupplierFitmentReviewService;
use App\Modules\Supplier\Services\SupplierService;

final class SupplierFitmentReviewAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly SupplierFitmentReviewService $review,
        private readonly SupplierService $suppliers
    ) {
    }

    public function index(): Response
    {
        $payload = $this->review->adminQueue($_GET);

        return new Response($this->views->render('admin.fitment.supplier_review', [
            'rows' => $payload['rows'],
            'filters' => $payload['filters'],
            'suppliers' => $this->suppliers->list(),
            'notice' => (string) ($_GET['notice'] ?? ''),
            'error' => (string) ($_GET['error'] ?? ''),
        ]));
    }

    public function intake(): Response
    {
        try {
            $id = $this->review->intakeCandidate($_POST);

            return $this->redirect('/admin/supplier-fitment-review?notice=' . urlencode('Kandidat skapad (#' . $id . ').'));
        } catch (\Throwable $exception) {
            return $this->redirect('/admin/supplier-fitment-review?error=' . urlencode($exception->getMessage()));
        }
    }

    public function approve(string $id): Response
    {
        try {
            $this->review->approve((int) $id, $_POST);

            return $this->redirect('/admin/supplier-fitment-review?notice=' . urlencode('Kandidat godkänd och fitment skapad.'));
        } catch (\Throwable $exception) {
            return $this->redirect('/admin/supplier-fitment-review?error=' . urlencode($exception->getMessage()));
        }
    }

    public function reject(string $id): Response
    {
        try {
            $this->review->reject((int) $id, $_POST);

            return $this->redirect('/admin/supplier-fitment-review?notice=' . urlencode('Kandidat avvisad.'));
        } catch (\Throwable $exception) {
            return $this->redirect('/admin/supplier-fitment-review?error=' . urlencode($exception->getMessage()));
        }
    }

    public function skip(string $id): Response
    {
        try {
            $this->review->skip((int) $id, $_POST);

            return $this->redirect('/admin/supplier-fitment-review?notice=' . urlencode('Kandidat markerad som skippad.'));
        } catch (\Throwable $exception) {
            return $this->redirect('/admin/supplier-fitment-review?error=' . urlencode($exception->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
