<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Customer\Services\AuthService;
use App\Modules\Customer\Services\CustomerAccountService;
use InvalidArgumentException;

final class CustomerAccountController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AuthService $auth,
        private readonly CustomerAccountService $accounts,
        private readonly CmsPageService $pages
    ) {
    }

    public function dashboard(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        $orders = $this->accounts->listOrders((int) $customer['id']);

        return new Response($this->views->render('storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => array_slice($orders, 0, 5),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function orders(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        return new Response($this->views->render('storefront.account.orders', [
            'orders' => $this->accounts->listOrders((int) $customer['id']),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function showOrder(string $id): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        $detail = $this->accounts->getOrderDetail((int) $customer['id'], (int) $id);
        if ($detail === null) {
            return $this->redirect('/account/orders?error=' . urlencode('Ordern hittades inte för ditt konto.'));
        }

        return new Response($this->views->render('storefront.account.order_show', [
            'detail' => $detail,
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function profileForm(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        return new Response($this->views->render('storefront.account.profile', [
            'customer' => $customer,
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }


    public function addressForm(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        return new Response($this->views->render('storefront.account.address', [
            'customer' => $customer,
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function updateAddress(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        try {
            $this->accounts->updateAddress((int) $customer['id'], $_POST);

            return $this->redirect('/account/address?message=' . urlencode('Adress uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/account/address?error=' . urlencode($e->getMessage()));
        }
    }

    public function updateProfile(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        try {
            $this->accounts->updateProfile((int) $customer['id'], $_POST);

            return $this->redirect('/account/profile?message=' . urlencode('Profil uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/account/profile?error=' . urlencode($e->getMessage()));
        }
    }

    /** @return array<string, mixed>|Response */
    private function requireCustomer(): array|Response
    {
        $customer = $this->auth->currentCustomer();
        if ($customer === null) {
            return $this->redirect('/login?error=' . urlencode('Du behöver logga in för att se Mina sidor.'));
        }

        return $customer;
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
