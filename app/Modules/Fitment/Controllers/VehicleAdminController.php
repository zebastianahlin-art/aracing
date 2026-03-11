<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Fitment\Services\VehicleService;

final class VehicleAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly VehicleService $vehicles)
    {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.vehicles.index', [
            'vehicles' => $this->vehicles->adminList(),
        ]));
    }

    public function createForm(): Response
    {
        return new Response($this->views->render('admin.vehicles.form', ['vehicle' => null]));
    }

    public function store(): Response
    {
        $this->vehicles->create($_POST);

        return $this->redirect('/admin/vehicles');
    }

    public function editForm(string $id): Response
    {
        return new Response($this->views->render('admin.vehicles.form', [
            'vehicle' => $this->vehicles->get((int) $id),
        ]));
    }

    public function update(string $id): Response
    {
        $this->vehicles->update((int) $id, $_POST);

        return $this->redirect('/admin/vehicles');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
