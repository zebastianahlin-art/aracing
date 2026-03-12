<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Fitment\Services\FitmentCoverageService;

final class FitmentCoverageAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly FitmentCoverageService $coverage
    ) {
    }

    public function index(): Response
    {
        $payload = $this->coverage->adminCategoryCoverage($_GET);

        return new Response($this->views->render('admin.fitment.coverage', [
            'rows' => $payload['rows'],
            'filters' => $payload['filters'],
            'totals' => $payload['totals'],
        ]));
    }
}
