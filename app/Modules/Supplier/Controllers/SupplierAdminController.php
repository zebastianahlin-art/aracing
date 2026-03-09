<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Supplier\Services\SupplierService;

final class SupplierAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly SupplierService $suppliers)
    {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.suppliers.index', ['suppliers' => $this->suppliers->list()]));
    }

    public function createForm(): Response
    {
        return new Response($this->views->render('admin.suppliers.form', ['supplier' => null]));
    }

    public function store(): Response
    {
        $this->suppliers->create($_POST);

        return $this->redirect('/admin/suppliers');
    }

    public function editForm(string $id): Response
    {
        return new Response($this->views->render('admin.suppliers.form', ['supplier' => $this->suppliers->get((int) $id)]));
    }

    public function update(string $id): Response
    {
        $this->suppliers->update((int) $id, $_POST);

        return $this->redirect('/admin/suppliers');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
