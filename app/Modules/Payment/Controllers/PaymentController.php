<?php

declare(strict_types=1);

namespace App\Modules\Payment\Controllers;

use App\Core\Http\Response;
use App\Modules\Payment\Services\PaymentService;

final class PaymentController
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function stripeReturn(): Response
    {
        $result = $this->payments->handleReturn(
            trim((string) ($_GET['provider'] ?? '')),
            isset($_GET['session_id']) ? (string) $_GET['session_id'] : null,
            isset($_GET['status']) ? (string) $_GET['status'] : null,
            isset($_GET['order_number']) ? (string) $_GET['order_number'] : null
        );

        $query = http_build_query([
            'order_number' => $result['order_number'],
            'payment_result' => $result['status'],
            'payment_message' => $result['message'],
        ]);

        return new Response('', 302, ['Location' => '/order-status?' . $query, 'Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function stripeWebhook(): Response
    {
        $payload = file_get_contents('php://input');
        $signature = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

        $result = $this->payments->handleWebhook(is_string($payload) ? $payload : '', $signature);
        $status = $result['ok'] ? 200 : 400;

        return new Response($result['message'], $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
