<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Customer\Services\AuthService;
use App\Modules\Fitment\Services\SavedVehicleService;
use InvalidArgumentException;

final class SavedVehicleController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AuthService $auth,
        private readonly CmsPageService $pages,
        private readonly SavedVehicleService $savedVehicles
    ) {
    }

    public function index(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        return new Response($this->views->render('storefront.account.vehicles', [
            'vehicles' => $this->savedVehicles->listVehicles((int) $customer['id']),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function saveCurrent(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        $backTo = $this->sanitizeBackTo((string) ($_POST['back_to'] ?? '/account/vehicles'));

        try {
            $status = $this->savedVehicles->saveCurrentSelection((int) $customer['id']);
            $message = $status === 'already_saved' ? 'Bilen finns redan i Mina bilar.' : 'Bilen är sparad i Mina bilar.';

            return $this->redirect($backTo . $this->separator($backTo) . 'fitment_notice=' . urlencode($message));
        } catch (InvalidArgumentException $e) {
            return $this->redirect($backTo . $this->separator($backTo) . 'fitment_notice=' . urlencode($e->getMessage()));
        }
    }

    public function setPrimary(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
        try {
            $this->savedVehicles->setPrimary((int) $customer['id'], $vehicleId);

            return $this->redirect('/account/vehicles?message=' . urlencode('Primär bil uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/account/vehicles?error=' . urlencode($e->getMessage()));
        }
    }

    public function useVehicle(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
        $backTo = $this->sanitizeBackTo((string) ($_POST['back_to'] ?? '/account/vehicles'));
        try {
            $this->savedVehicles->useSavedVehicle((int) $customer['id'], $vehicleId);

            return $this->redirect($backTo . $this->separator($backTo) . 'fitment_notice=' . urlencode('Vald bil används nu i butiken.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect($backTo . $this->separator($backTo) . 'error=' . urlencode($e->getMessage()));
        }
    }

    public function remove(): Response
    {
        $customer = $this->requireCustomer();
        if ($customer instanceof Response) {
            return $customer;
        }

        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
        $this->savedVehicles->remove((int) $customer['id'], $vehicleId);

        return $this->redirect('/account/vehicles?message=' . urlencode('Bilen är borttagen från Mina bilar.'));
    }

    /** @return array<string,mixed>|Response */
    private function requireCustomer(): array|Response
    {
        $customer = $this->auth->currentCustomer();
        if ($customer === null) {
            $returnTo = rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? '/account/vehicles'));

            return $this->redirect('/login?error=' . urlencode('Du behöver logga in för att använda Mina bilar.') . '&return_to=' . $returnTo);
        }

        return $customer;
    }

    private function sanitizeBackTo(string $backTo): string
    {
        $path = trim($backTo);
        if ($path === '' || !str_starts_with($path, '/')) {
            return '/account/vehicles';
        }

        if (str_starts_with($path, '//')) {
            return '/account/vehicles';
        }

        return $path;
    }

    private function separator(string $url): string
    {
        return str_contains($url, '?') ? '&' : '?';
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
