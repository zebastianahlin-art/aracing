<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Brand\Services\BrandService;
use App\Modules\Category\Services\CategoryService;
use App\Modules\Fitment\Services\FitmentWorkflowService;

final class FitmentWorkflowAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly FitmentWorkflowService $workflow,
        private readonly BrandService $brands,
        private readonly CategoryService $categories
    ) {
    }

    public function index(): Response
    {
        $payload = $this->workflow->adminQueue($_GET);

        return new Response($this->views->render('admin.fitment.workflow', [
            'rows' => $payload['rows'],
            'filters' => $payload['filters'],
            'totals' => $payload['totals'],
            'statuses' => $this->workflow->allowedStatuses(),
            'brands' => $this->brands->list(),
            'categories' => $this->categories->listForSelect(),
            'notice' => (string) ($_GET['notice'] ?? ''),
        ]));
    }

    public function updateFlag(string $productId): Response
    {
        $this->workflow->updateFlag((int) $productId, $_POST);

        return $this->redirect('/admin/fitment-workflow?notice=' . urlencode('Fitmentstatus uppdaterad.'));
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
