<?php

declare(strict_types=1);

namespace App\Modules\Import\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Import\Services\ImportProfileService;
use App\Modules\Supplier\Services\SupplierService;

final class ImportProfileAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly ImportProfileService $profiles,
        private readonly SupplierService $suppliers
    ) {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.import_profiles.index', ['profiles' => $this->profiles->list()]));
    }

    public function createForm(): Response
    {
        return new Response($this->views->render('admin.import_profiles.form', [
            'profile' => null,
            'suppliers' => $this->suppliers->listActive(),
        ]));
    }

    public function store(): Response
    {
        $this->profiles->create($_POST);

        return $this->redirect('/admin/import-profiles');
    }

    public function editForm(string $id): Response
    {
        return new Response($this->views->render('admin.import_profiles.form', [
            'profile' => $this->profiles->get((int) $id),
            'suppliers' => $this->suppliers->listActive(),
        ]));
    }

    public function update(string $id): Response
    {
        $this->profiles->update((int) $id, $_POST);

        return $this->redirect('/admin/import-profiles');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
