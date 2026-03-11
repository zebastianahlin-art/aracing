<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Storefront\Services\HomepageService;

final class HomepageAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly HomepageService $home)
    {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.storefront.homepage_sections', [
            'sections' => $this->home->adminSections(),
            'meta' => $this->home->adminMeta(),
        ]));
    }

    public function handle(): Response
    {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'create_section') {
            $this->home->saveSection($_POST, null);
        }

        if ($action === 'update_section') {
            $this->home->saveSection($_POST, (int) ($_POST['section_id'] ?? 0));
        }

        if ($action === 'delete_section') {
            $this->home->deleteSection((int) ($_POST['section_id'] ?? 0));
        }

        if ($action === 'add_item') {
            $this->home->addSectionItem((int) ($_POST['section_id'] ?? 0), $_POST);
        }

        if ($action === 'update_item') {
            $this->home->updateSectionItem((int) ($_POST['section_id'] ?? 0), (int) ($_POST['item_id_row'] ?? 0), $_POST);
        }

        if ($action === 'delete_item') {
            $this->home->deleteSectionItem((int) ($_POST['section_id'] ?? 0), (int) ($_POST['item_id_row'] ?? 0));
        }

        return $this->redirect('/admin/homepage-sections');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
