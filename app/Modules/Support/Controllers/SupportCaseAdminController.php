<?php

declare(strict_types=1);

namespace App\Modules\Support\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Support\Services\SupportCaseService;
use InvalidArgumentException;

final class SupportCaseAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly SupportCaseService $support
    ) {
    }

    public function index(): Response
    {
        $filters = [
            'status' => trim((string) ($_GET['status'] ?? '')),
            'source' => trim((string) ($_GET['source'] ?? '')),
        ];

        return new Response($this->views->render('admin.support-cases.index', [
            'cases' => $this->support->listAdmin($filters),
            'filters' => $filters,
            'statuses' => $this->support->statuses(),
            'sources' => $this->support->sources(),
            'statusLabels' => $this->support->statusLabels(),
            'priorityLabels' => $this->support->priorityLabels(),
            'sourceLabels' => $this->support->sourceLabels(),
        ]));
    }

    public function show(string $id): Response
    {
        return new Response($this->views->render('admin.support-cases.show', [
            'detail' => $this->support->getDetailAdmin((int) $id),
            'statuses' => $this->support->statuses(),
            'priorities' => $this->support->priorities(),
            'statusLabels' => $this->support->statusLabels(),
            'priorityLabels' => $this->support->priorityLabels(),
            'sourceLabels' => $this->support->sourceLabels(),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function updateStatus(string $id): Response
    {
        try {
            $this->support->updateStatusAdmin((int) $id, trim((string) ($_POST['status'] ?? '')));

            return $this->redirect('/admin/support-cases/' . (int) $id . '?message=' . urlencode('Status uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/support-cases/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function updatePriority(string $id): Response
    {
        try {
            $this->support->updatePriorityAdmin((int) $id, trim((string) ($_POST['priority'] ?? '')));

            return $this->redirect('/admin/support-cases/' . (int) $id . '?message=' . urlencode('Prioritet uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/support-cases/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function addAdminNote(string $id): Response
    {
        try {
            $this->support->updateAdminNote((int) $id, (string) ($_POST['admin_note'] ?? ''));

            return $this->redirect('/admin/support-cases/' . (int) $id . '?message=' . urlencode('Adminnotering sparad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/support-cases/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
