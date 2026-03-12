<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Admin\Services\AiMerchandisingInsightService;

final class AiMerchandisingInsightAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AiMerchandisingInsightService $insights,
    ) {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.ai_merch_insights', [
            'payload' => $this->insights->listInsights($_GET),
        ]));
    }
}
