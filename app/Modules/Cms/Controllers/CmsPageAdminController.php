<?php

declare(strict_types=1);

namespace App\Modules\Cms\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsPageService;

final class CmsPageAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly CmsPageService $pages)
    {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.cms.pages_index', [
            'pages' => $this->pages->list(),
        ]));
    }

    public function createForm(): Response
    {
        return new Response($this->views->render('admin.cms.page_form', ['page' => null]));
    }

    public function store(): Response
    {
        $this->pages->create($_POST);

        return $this->redirect('/admin/cms/pages');
    }

    public function editForm(string $id): Response
    {
        return new Response($this->views->render('admin.cms.page_form', [
            'page' => $this->pages->get((int) $id),
        ]));
    }

    public function update(string $id): Response
    {
        $this->pages->update((int) $id, $_POST);

        return $this->redirect('/admin/cms/pages');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
