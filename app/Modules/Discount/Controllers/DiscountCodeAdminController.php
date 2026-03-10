<?php

declare(strict_types=1);

namespace App\Modules\Discount\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Discount\Services\DiscountService;
use InvalidArgumentException;

final class DiscountCodeAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly DiscountService $discounts)
    {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.discount_codes.index', [
            'codes' => $this->discounts->listForAdmin(),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function createForm(): Response
    {
        return new Response($this->views->render('admin.discount_codes.form', [
            'codeEntity' => null,
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function store(): Response
    {
        try {
            $this->discounts->create($_POST);

            return $this->redirect('/admin/discount-codes?message=' . urlencode('Kampanjkod skapad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/discount-codes/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function editForm(string $id): Response
    {
        return new Response($this->views->render('admin.discount_codes.form', [
            'codeEntity' => $this->discounts->getById((int) $id),
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function update(string $id): Response
    {
        try {
            $this->discounts->update((int) $id, $_POST);

            return $this->redirect('/admin/discount-codes?message=' . urlencode('Kampanjkod uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/discount-codes/' . (int) $id . '/edit?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
