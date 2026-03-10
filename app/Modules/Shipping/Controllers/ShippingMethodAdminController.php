<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Shipping\Services\ShippingService;
use InvalidArgumentException;

final class ShippingMethodAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly ShippingService $shipping)
    {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.shipping_methods.index', [
            'methods' => $this->shipping->listForAdmin(),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function createForm(): Response
    {
        return new Response($this->views->render('admin.shipping_methods.form', [
            'method' => null,
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function store(): Response
    {
        try {
            $this->shipping->create($_POST);

            return $this->redirect('/admin/shipping-methods?message=' . urlencode('Fraktmetod skapad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/shipping-methods/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function editForm(string $id): Response
    {
        return new Response($this->views->render('admin.shipping_methods.form', [
            'method' => $this->shipping->getById((int) $id),
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function update(string $id): Response
    {
        try {
            $this->shipping->update((int) $id, $_POST);

            return $this->redirect('/admin/shipping-methods?message=' . urlencode('Fraktmetod uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/shipping-methods/' . (int) $id . '/edit?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
