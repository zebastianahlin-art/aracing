<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Admin\Services\AiInventoryInsightService;
use App\Modules\Admin\Services\AiOperationalAlertService;
use App\Modules\Admin\Services\AiPricingInsightService;

final class AdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AiOperationalAlertService $alerts,
        private readonly AiInventoryInsightService $inventoryInsights,
        private readonly AiPricingInsightService $pricingInsights,
    ) {
    }

    public function dashboard(): Response
    {
        $inventoryPayload = $this->inventoryInsights->listInsights(['insight_type' => 'all']);
        $inventoryCounts = is_array($inventoryPayload['counts'] ?? null) ? $inventoryPayload['counts'] : [];
        $pricingPayload = $this->pricingInsights->listInsights(['insight_type' => 'all', 'linked_only' => '1']);
        $pricingCounts = is_array($pricingPayload['counts'] ?? null) ? $pricingPayload['counts'] : [];

        return new Response($this->views->render('admin.dashboard', [
            'alertsSummary' => $this->alerts->buildDashboardSummary(3),
            'inventoryInsightCounts' => $inventoryCounts,
            'pricingInsightCounts' => $pricingCounts,
        ]));
    }
}
