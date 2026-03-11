<?php

declare(strict_types=1);

namespace App\Modules\Returns\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Returns\Services\ReturnRequestService;
use InvalidArgumentException;

final class ReturnRequestAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly ReturnRequestService $returns
    ) {
    }

    public function index(): Response
    {
        $filters = [
            'status' => trim((string) ($_GET['status'] ?? '')),
        ];

        return new Response($this->views->render('admin.returns.index', [
            'returns' => $this->returns->listAdmin($filters),
            'filters' => $filters,
            'statuses' => $this->returns->statuses(),
            'statusLabels' => $this->returns->statusLabels(),
        ]));
    }

    public function show(string $id): Response
    {
        $detail = $this->returns->getDetailAdmin((int) $id);

        return new Response($this->views->render('admin.returns.show', [
            'detail' => $detail,
            'statuses' => $this->returns->statuses(),
            'statusLabels' => $this->returns->statusLabels(),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function updateStatus(string $id): Response
    {
        try {
            $this->returns->updateStatusAdmin((int) $id, trim((string) ($_POST['status'] ?? '')));

            return $this->redirect('/admin/returns/' . (int) $id . '?message=' . urlencode('Returstatus uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/returns/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function addNote(string $id): Response
    {
        try {
            $this->returns->updateAdminNote((int) $id, (string) ($_POST['admin_note'] ?? ''));

            return $this->redirect('/admin/returns/' . (int) $id . '?message=' . urlencode('Adminnotering sparad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/returns/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
