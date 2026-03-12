<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Admin\Services\AiOperationalInsightsService;

final class AiOperationalReportController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AiOperationalInsightsService $insights
    ) {
    }

    public function index(): Response
    {
        $report = $this->insights->buildDailyOperationsReport();

        return new Response($this->views->render('admin.ai_ops_report', [
            'report' => $report,
        ]));
    }
}
