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
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'order_status' => trim((string) ($_GET['order_status'] ?? '')),
            'payment_status' => trim((string) ($_GET['payment_status'] ?? '')),
            'payment_method' => trim((string) ($_GET['payment_method'] ?? '')),
            'fulfillment_status' => trim((string) ($_GET['fulfillment_status'] ?? '')),
        ];

        return new Response($this->views->render('admin.orders.index', [
            'orders' => $this->orders->listOrders($filters),
            'filters' => $filters,
            'statusOptions' => $this->orders->statusOptions(),
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

    public function transitionOrderStatus(string $id): Response
    {
        try {
            $this->orders->transitionOrderStatus((int) $id, (string) ($_POST['order_status'] ?? ''));

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Orderstatus uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function transitionFulfillmentStatus(string $id): Response
    {
        try {
            $this->orders->transitionFulfillmentStatus((int) $id, (string) ($_POST['fulfillment_status'] ?? ''));

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Fulfillment-status uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function updatePayment(string $id): Response
    {
        try {
            $this->orders->updatePaymentAdminFields(
                (int) $id,
                (string) ($_POST['payment_status'] ?? ''),
                (string) ($_POST['payment_reference'] ?? ''),
                (string) ($_POST['payment_note'] ?? '')
            );

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Betalningsinfo uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function addNote(string $id): Response
    {
        try {
            $this->orders->addAdminNote((int) $id, (string) ($_POST['note_text'] ?? ''));

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Anteckning tillagd.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function updateShipment(string $id): Response
    {
        try {
            $this->orders->updateShipmentInfo((int) $id, $_POST);

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Frakt-/trackinginfo uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function updateInternalReference(string $id): Response
    {
        try {
            $this->orders->updateInternalReference((int) $id, (string) ($_POST['internal_reference'] ?? ''));

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Intern referens uppdaterad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function printView(string $id): Response
    {
        $detail = $this->orders->getOrderDetail((int) $id);

        return new Response($this->views->render('admin.orders.print', [
            'detail' => $detail,
        ]));
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
