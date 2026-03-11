<?php

declare(strict_types=1);

namespace App\Modules\Review\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Review\Services\ProductReviewService;
use InvalidArgumentException;

final class ProductReviewAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly ProductReviewService $reviews
    ) {
    }

    public function index(): Response
    {
        $filters = [
            'status' => trim((string) ($_GET['status'] ?? '')),
            'product_id' => trim((string) ($_GET['product_id'] ?? '')),
        ];

        return new Response($this->views->render('admin.reviews.index', [
            'reviews' => $this->reviews->listAdmin($filters),
            'filters' => $filters,
            'statuses' => $this->reviews->statuses(),
            'statusLabels' => $this->reviews->statusLabels(),
        ]));
    }

    public function show(string $id): Response
    {
        return new Response($this->views->render('admin.reviews.show', [
            'review' => $this->reviews->getAdminDetail((int) $id),
            'statuses' => $this->reviews->statuses(),
            'statusLabels' => $this->reviews->statusLabels(),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function updateStatus(string $id): Response
    {
        try {
            $this->reviews->moderate((int) $id, trim((string) ($_POST['status'] ?? '')));

            return $this->redirect('/admin/reviews/' . (int) $id . '?message=' . urlencode('Recensionsstatus uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/reviews/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
