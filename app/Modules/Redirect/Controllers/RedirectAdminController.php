<?php

declare(strict_types=1);

namespace App\Modules\Redirect\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Redirect\Services\RedirectService;
use InvalidArgumentException;

final class RedirectAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly RedirectService $redirects)
    {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.redirects.index', [
            'redirects' => $this->redirects->listForAdmin($_GET),
            'filters' => [
                'is_active' => trim((string) ($_GET['is_active'] ?? '')),
            ],
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function createForm(): Response
    {
        return new Response($this->views->render('admin.redirects.form', [
            'redirect' => null,
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function store(): Response
    {
        try {
            $this->redirects->create($_POST);

            return $this->redirect('/admin/redirects?message=' . urlencode('Redirect skapad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/redirects/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function editForm(string $id): Response
    {
        return new Response($this->views->render('admin.redirects.form', [
            'redirect' => $this->redirects->getById((int) $id),
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function update(string $id): Response
    {
        try {
            $this->redirects->update((int) $id, $_POST);

            return $this->redirect('/admin/redirects?message=' . urlencode('Redirect uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/redirects/' . (int) $id . '/edit?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
