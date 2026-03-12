<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Supplier\Services\SupplierMonitoringService;
use App\Modules\Supplier\Services\SupplierService;

final class SupplierMonitoringAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly SupplierMonitoringService $monitoring,
        private readonly SupplierService $suppliers
    ) {
    }

    public function index(): Response
    {
        $payload = $this->monitoring->deviations($_GET);

        return new Response($this->views->render('admin.supplier_monitoring.index', [
            'rows' => $payload['rows'],
            'filters' => $payload['filters'],
            'counts' => $payload['counts'],
            'suppliers' => $this->suppliers->listActive(),
        ]));
    }
}
