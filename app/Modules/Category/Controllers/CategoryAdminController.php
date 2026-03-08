<?php

declare(strict_types=1);

namespace App\Modules\Category\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Category\Services\CategoryService;

final class CategoryAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly CategoryService $categories)
    {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.categories.index', ['categories' => $this->categories->list()]));
    }

    public function createForm(): Response
    {
        return new Response($this->views->render('admin.categories.form', [
            'category' => null,
            'parentOptions' => $this->categories->listForSelect(),
        ]));
    }

    public function store(): Response
    {
        $this->categories->create($_POST);

        return $this->redirect('/admin/categories');
    }

    public function editForm(string $id): Response
    {
        return new Response($this->views->render('admin.categories.form', [
            'category' => $this->categories->get((int) $id),
            'parentOptions' => $this->categories->listForSelect(),
        ]));
    }

    public function update(string $id): Response
    {
        $this->categories->update((int) $id, $_POST);

        return $this->redirect('/admin/categories');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
