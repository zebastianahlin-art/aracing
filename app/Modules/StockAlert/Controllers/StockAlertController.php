<?php

declare(strict_types=1);

namespace App\Modules\StockAlert\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Customer\Services\AuthService;
use App\Modules\StockAlert\Services\StockAlertService;
use InvalidArgumentException;

final class StockAlertController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AuthService $auth,
        private readonly CatalogService $catalog,
        private readonly CmsPageService $pages,
        private readonly StockAlertService $alerts
    ) {
    }

    public function subscribe(string $slug): Response
    {
        $product = $this->catalog->productPage($slug);
        if ($product === null) {
            return $this->redirect('/product/' . rawurlencode($slug) . '?stock_alert_error=' . urlencode('Produkten hittades inte.'));
        }

        $customer = $this->auth->currentCustomer();
        $emailInput = trim((string) ($_POST['email'] ?? ($customer['email'] ?? '')));

        try {
            $result = $this->alerts->subscribe(
                (int) $product['id'],
                $emailInput,
                $customer !== null ? (int) $customer['id'] : null,
                (bool) ($product['is_purchasable'] ?? false)
            );

            $param = $result['status'] === 'already_active' ? 'stock_alert_notice' : 'stock_alert_message';

            return $this->redirect('/product/' . rawurlencode($slug) . '?' . $param . '=' . urlencode($result['message']));
        } catch (InvalidArgumentException $exception) {
            return $this->redirect('/product/' . rawurlencode($slug) . '?stock_alert_error=' . urlencode($exception->getMessage()));
        }
    }

    public function accountIndex(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        return new Response($this->views->render('storefront.account.stock_alerts', [
            'alerts' => $this->alerts->listForUser((int) $customer['id']),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function unsubscribe(string $id): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        try {
            $this->alerts->unsubscribeForUser((int) $id, (int) $customer['id']);

            return $this->redirect('/account/stock-alerts?message=' . urlencode('Bevakning avslutad.'));
        } catch (InvalidArgumentException $exception) {
            return $this->redirect('/account/stock-alerts?error=' . urlencode($exception->getMessage()));
        }
    }

    /** @return array<string,mixed>|Response */
    private function requireCustomer(): array|Response
    {
        $customer = $this->auth->currentCustomer();
        if ($customer === null) {
            $returnTo = rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? '/account/stock-alerts'));

            return $this->redirect('/login?error=' . urlencode('Du behöver logga in för att se bevakningar.') . '&return_to=' . $returnTo);
        }

        return $customer;
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
