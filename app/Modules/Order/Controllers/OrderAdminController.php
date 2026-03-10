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
            'status' => trim((string) ($_GET['status'] ?? '')),
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

    public function update(string $id): Response
    {
        try {
            $this->orders->updateOrderAdminFields(
                (int) $id,
                (string) ($_POST['status'] ?? ''),
                (string) ($_POST['payment_status'] ?? ''),
                (string) ($_POST['fulfillment_status'] ?? ''),
                (string) ($_POST['internal_reference'] ?? '')
            );

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Order uppdaterad.'));
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
            $this->orders->addInternalNote((int) $id, (string) ($_POST['note_text'] ?? ''));

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Anteckning tillagd.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function markPacked(string $id): Response
    {
        try {
            $this->orders->markPacked((int) $id);

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Order markerad som packad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function markShipped(string $id): Response
    {
        try {
            $this->orders->markShipped((int) $id);

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Order markerad som skickad.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function markProcessing(string $id): Response
    {
        try {
            $this->orders->markProcessing((int) $id);

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Order markerad som processing.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/orders/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function updateShipment(string $id): Response
    {
        try {
            $this->orders->updateShipmentInfo(
                (int) $id,
                (string) ($_POST['tracking_number'] ?? ''),
                (string) ($_POST['shipping_method'] ?? ''),
                (string) ($_POST['shipped_by_name'] ?? ''),
                (string) ($_POST['shipment_note'] ?? '')
            );

            return $this->redirect('/admin/orders/' . (int) $id . '?message=' . urlencode('Försändelseinfo uppdaterad.'));
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
