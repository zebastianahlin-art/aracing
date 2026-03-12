<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Admin\Services\AiInventoryInsightService;
use App\Modules\Admin\Services\AiOperationalAlertService;

final class AdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AiOperationalAlertService $alerts,
        private readonly AiInventoryInsightService $inventoryInsights,
    ) {
    }

    public function dashboard(): Response
    {
        $inventoryPayload = $this->inventoryInsights->listInsights(['insight_type' => 'all']);
        $inventoryCounts = is_array($inventoryPayload['counts'] ?? null) ? $inventoryPayload['counts'] : [];

        return new Response($this->views->render('admin.dashboard', [
            'alertsSummary' => $this->alerts->buildDashboardSummary(3),
            'inventoryInsightCounts' => $inventoryCounts,
        ]));
    }
}
