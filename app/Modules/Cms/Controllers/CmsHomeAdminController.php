<?php

declare(strict_types=1);

namespace App\Modules\Cms\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsHomeService;

final class CmsHomeAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly CmsHomeService $home)
    {
    }

    public function edit(): Response
    {
        return new Response($this->views->render('admin.cms.home_sections', [
            'sections' => $this->home->adminSections(),
        ]));
    }

    public function update(): Response
    {
        $this->home->saveAdminSections($_POST);

        return $this->redirect('/admin/cms/home');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
