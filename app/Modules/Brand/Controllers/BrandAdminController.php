<?php

declare(strict_types=1);

namespace App\Modules\Brand\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Brand\Services\BrandService;

final class BrandAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly BrandService $brands)
    {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.brands.index', ['brands' => $this->brands->list()]));
    }

    public function createForm(): Response
    {
        return new Response($this->views->render('admin.brands.form', ['brand' => null]));
    }

    public function store(): Response
    {
        $this->brands->create($_POST);

        return $this->redirect('/admin/brands');
    }

    public function editForm(string $id): Response
    {
        $brand = $this->brands->get((int) $id);

        return new Response($this->views->render('admin.brands.form', ['brand' => $brand]));
    }

    public function update(string $id): Response
    {
        $this->brands->update((int) $id, $_POST);

        return $this->redirect('/admin/brands');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
