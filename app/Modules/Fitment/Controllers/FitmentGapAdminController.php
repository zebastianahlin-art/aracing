<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Brand\Services\BrandService;
use App\Modules\Category\Services\CategoryService;
use App\Modules\Fitment\Services\FitmentGapService;

final class FitmentGapAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly FitmentGapService $gaps,
        private readonly BrandService $brands,
        private readonly CategoryService $categories
    ) {
    }

    public function index(): Response
    {
        $payload = $this->gaps->adminQueue($_GET);

        return new Response($this->views->render('admin.fitment.gaps', [
            'rows' => $payload['rows'],
            'filters' => $payload['filters'],
            'totals' => $payload['totals'],
            'brands' => $this->brands->list(),
            'categories' => $this->categories->listForSelect(),
        ]));
    }
}
