<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Order\Services\OrderService;
use InvalidArgumentException;

final class OrderAdminController
{
    public function __construct(private readonly ViewFactory $views, private readonly OrderService $orders)
    {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.orders.index', [
            'orders' => $this->orders->listOrders(),
        ]));
    }

    public function show(string $id): Response
    {
        $detail = $this->orders->getOrderDetail((int) $id);

        return new Response($this->views->render('admin.orders.show', [
            'detail' => $detail,
            'statusOptions' => $this->orders->statusOptions(),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function updateStatuses(string $id): Response
    {
        try {
            $this->orders->updateStatuses(
                (int) $id,
                (string) ($_POST['status'] ?? ''),
                (string) ($_POST['payment_status'] ?? ''),
                (string) ($_POST['fulfillment_status'] ?? '')
            );

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Status uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
