<?php

declare(strict_types=1);

namespace App\Modules\Returns\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Customer\Services\AuthService;
use App\Modules\Returns\Services\ReturnRequestService;
use InvalidArgumentException;

final class ReturnRequestCustomerController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AuthService $auth,
        private readonly CmsPageService $pages,
        private readonly ReturnRequestService $returns
    ) {
    }

    public function index(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        return new Response($this->views->render('storefront.account.returns.index', [
            'returns' => $this->returns->listForUser((int) $customer['id']),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'statusLabels' => $this->returns->statusLabels(),
            'reasonLabels' => $this->returns->reasonLabels(),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function createForm(string $orderId): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        try {
            $context = $this->returns->getCreateContextForUser((int) $customer['id'], (int) $orderId);

            return new Response($this->views->render('storefront.account.returns.create', [
                'context' => $context,
                'error' => trim((string) ($_GET['error'] ?? '')),
                'reasonLabels' => $this->returns->reasonLabels(),
                'infoPages' => $this->pages->storefrontInfoPages(),
            ]));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/account/orders?error=' . urlencode($e->getMessage()));
        }
    }

    public function store(string $orderId): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        try {
            $returnId = $this->returns->createForUser((int) $customer['id'], (int) $orderId, $_POST);

            return $this->redirect('/account/returns/' . $returnId . '?message=' . urlencode('Returförfrågan skapad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/account/orders/' . (int) $orderId . '/returns/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function show(string $returnId): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        $detail = $this->returns->getDetailForUser((int) $returnId, (int) $customer['id']);
        if ($detail === null) {
            return $this->redirect('/account/returns?error=' . urlencode('Returärendet hittades inte för ditt konto.'));
        }

        return new Response($this->views->render('storefront.account.returns.show', [
            'detail' => $detail,
            'message' => trim((string) ($_GET['message'] ?? '')),
            'statusLabels' => $this->returns->statusLabels(),
            'reasonLabels' => $this->returns->reasonLabels(),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    /** @return array<string, mixed>|Response */
    private function requireCustomer(): array|Response
    {
        $customer = $this->auth->currentCustomer();
        if ($customer === null) {
            return $this->redirect('/login?error=' . urlencode('Du behöver logga in för att hantera returer.'));
        }

        return $customer;
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
