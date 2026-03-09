<?php

declare(strict_types=1);

namespace App\Modules\Import\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Import\Services\CsvImportService;
use App\Modules\Import\Services\ImportProfileService;
use App\Modules\Import\Services\ImportRunService;

final class ImportRunAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly ImportRunService $runs,
        private readonly ImportProfileService $profiles,
        private readonly CsvImportService $csvImports
    ) {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.import_runs.index', [
            'runs' => $this->runs->list(),
            'profiles' => $this->profiles->list(),
            'error' => isset($_GET['error']) ? (string) $_GET['error'] : null,
        ]));
    }

    public function detail(string $id): Response
    {
        $detail = $this->runs->detail((int) $id);

        return new Response($this->views->render('admin.import_runs.show', $detail));
    }

    public function upload(): Response
    {
        try {
            $profileId = (int) ($_POST['import_profile_id'] ?? 0);
            $file = $_FILES['csv_file'] ?? null;

            if ($profileId <= 0 || !is_array($file)) {
                throw new \RuntimeException('Välj importprofil och CSV-fil.');
            }

            $runId = $this->csvImports->import($profileId, $file);

            return $this->redirect('/admin/import-runs/' . $runId);
        } catch (\Throwable $exception) {
            return $this->redirect('/admin/import-runs?error=' . rawurlencode($exception->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
