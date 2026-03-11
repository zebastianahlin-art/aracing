<?php

declare(strict_types=1);

namespace App\Modules\Support\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Customer\Services\AuthService;
use App\Modules\Support\Services\SupportCaseService;
use InvalidArgumentException;

final class SupportCaseStorefrontController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly SupportCaseService $support,
        private readonly AuthService $auth,
        private readonly CmsPageService $pages
    ) {
    }

    public function contactForm(): Response
    {
        $customer = $this->auth->currentCustomer();

        return new Response($this->views->render('storefront.support.contact', [
            'customer' => $customer,
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function createFromContactForm(): Response
    {
        try {
            $this->support->createFromContactForm($_POST, $this->auth->currentCustomer());

            return $this->redirect('/contact?message=' . urlencode('Tack! Ditt supportärende har skapats.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/contact?error=' . urlencode($e->getMessage()));
        }
    }

    public function accountIndex(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        return new Response($this->views->render('storefront.account.support.index', [
            'cases' => $this->support->listForUser((int) $customer['id']),
            'statusLabels' => $this->support->statusLabels(),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function accountShow(string $id): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        $detail = $this->support->getDetailForUser((int) $id, (int) $customer['id']);
        if ($detail === null) {
            return $this->redirect('/account/support-cases?error=' . urlencode('Supportärendet hittades inte för ditt konto.'));
        }

        return new Response($this->views->render('storefront.account.support.show', [
            'detail' => $detail,
            'statusLabels' => $this->support->statusLabels(),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function accountCreate(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        return new Response($this->views->render('storefront.account.support.create', [
            'customer' => $customer,
            'orderId' => null,
            'error' => trim((string) ($_GET['error'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function accountStore(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        try {
            $caseId = $this->support->createFromAccount((int) $customer['id'], $_POST);

            return $this->redirect('/account/support-cases/' . $caseId . '?message=' . urlencode('Supportärende skapat.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/account/support-cases/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function orderCreateForm(string $orderId): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        return new Response($this->views->render('storefront.account.support.create', [
            'customer' => $customer,
            'orderId' => (int) $orderId,
            'error' => trim((string) ($_GET['error'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function orderStore(string $orderId): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        try {
            $caseId = $this->support->createFromOrder((int) $customer['id'], (int) $orderId, $_POST);

            return $this->redirect('/account/support-cases/' . $caseId . '?message=' . urlencode('Supportärende kopplat till ordern skapades.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/account/orders/' . (int) $orderId . '/support/create?error=' . urlencode($e->getMessage()));
        }
    }

    /** @return array<string, mixed>|Response */
    private function requireCustomer(): array|Response
    {
        $customer = $this->auth->currentCustomer();
        if ($customer === null) {
            return $this->redirect('/login?error=' . urlencode('Du behöver logga in för att hantera supportärenden.'));
        }

        return $customer;
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
