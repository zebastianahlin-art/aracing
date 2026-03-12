<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Admin\Services\AiInventoryInsightService;
use App\Modules\Brand\Services\BrandService;
use App\Modules\Category\Services\CategoryService;

final class AiInventoryInsightAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AiInventoryInsightService $insights,
        private readonly BrandService $brands,
        private readonly CategoryService $categories,
    ) {
    }

    public function index(): Response
    {
        $payload = $this->insights->listInsights($_GET);

        return new Response($this->views->render('admin.ai_inventory_insights', [
            'payload' => $payload,
            'brandOptions' => $this->brands->list(),
            'categoryOptions' => $this->categories->listForSelect(),
        ]));
    }
}
