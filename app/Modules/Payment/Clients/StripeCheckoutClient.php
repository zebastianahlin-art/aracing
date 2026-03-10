<?php

declare(strict_types=1);

namespace App\Modules\Payment\Clients;

use RuntimeException;

final class StripeCheckoutClient
{
    private const API_BASE = 'https://api.stripe.com/v1';

    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret
    ) {
    }

    /** @param array<string, mixed> $order */
    public function createCheckoutSession(array $order, string $successUrl, string $cancelUrl): array
    {
        $amount = (int) round(((float) ($order['total_amount'] ?? 0)) * 100);
        if ($amount < 1) {
            throw new RuntimeException('Orderbelopp är ogiltigt för Stripe Checkout.');
        }

        return $this->post('/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) ($order['order_number'] ?? ''),
            'metadata[order_id]' => (string) ($order['id'] ?? ''),
            'metadata[order_number]' => (string) ($order['order_number'] ?? ''),
            'line_items[0][price_data][currency]' => strtolower((string) ($order['currency_code'] ?? 'SEK')),
            'line_items[0][price_data][unit_amount]' => (string) $amount,
            'line_items[0][price_data][product_data][name]' => 'A-Racing order ' . (string) ($order['order_number'] ?? ''),
            'line_items[0][quantity]' => '1',
        ]);
    }

    public function fetchCheckoutSession(string $sessionId): array
    {
        return $this->get('/checkout/sessions/' . rawurlencode($sessionId));
    }

    /** @return array{valid:bool, payload:array<string,mixed>|null} */
    public function verifyWebhook(string $payload, string $signatureHeader): array
    {
        if ($this->webhookSecret === '' || $signatureHeader === '') {
            return ['valid' => false, 'payload' => null];
        }

        $timestamp = '';
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($k === 't') {
                $timestamp = $v;
            }
            if ($k === 'v1') {
                $signatures[] = $v;
            }
        }

        if ($timestamp === '' || $signatures === []) {
            return ['valid' => false, 'payload' => null];
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
        $match = false;
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                $match = true;
                break;
            }
        }

        if (!$match) {
            return ['valid' => false, 'payload' => null];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return ['valid' => false, 'payload' => null];
        }

        return ['valid' => true, 'payload' => $decoded];
    }

    private function get(string $path): array
    {
        return $this->request('GET', $path, []);
    }

    private function post(string $path, array $params): array
    {
        return $this->request('POST', $path, $params);
    }

    private function request(string $method, string $path, array $params): array
    {
        if ($this->secretKey === '') {
            throw new RuntimeException('Stripe secret key saknas i konfigurationen.');
        }

        $ch = curl_init(self::API_BASE . $path);
        if ($ch === false) {
            throw new RuntimeException('Kunde inte initiera Stripe-anrop.');
        }

        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
        ];

        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!is_string($response)) {
            throw new RuntimeException('Tomt svar från Stripe: ' . $curlError);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Ogiltigt JSON-svar från Stripe.');
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = (string) ($decoded['error']['message'] ?? 'Stripe API-fel.');
            throw new RuntimeException($message);
        }

        return $decoded;
    }
}
