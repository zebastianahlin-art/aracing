<?php

declare(strict_types=1);

namespace App\Modules\Payment\Repositories;

use PDO;

final class PaymentEventRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function hasProviderEvent(string $provider, string $providerEventId): bool
    {
        if ($providerEventId === '') {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM payment_events WHERE provider = :provider AND provider_event_id = :provider_event_id LIMIT 1');
        $stmt->execute([
            'provider' => $provider,
            'provider_event_id' => $providerEventId,
        ]);

        return $stmt->fetch() !== false;
    }

    public function create(array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO payment_events
            (order_id, provider, event_type, provider_event_id, payment_reference, payload_json, status_before, status_after, created_at)
            VALUES
            (:order_id, :provider, :event_type, :provider_event_id, :payment_reference, :payload_json, :status_before, :status_after, NOW())');
        $stmt->execute([
            'order_id' => $data['order_id'],
            'provider' => $data['provider'],
            'event_type' => $data['event_type'],
            'provider_event_id' => $data['provider_event_id'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'payload_json' => $data['payload_json'] ?? null,
            'status_before' => $data['status_before'] ?? null,
            'status_after' => $data['status_after'] ?? null,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function forOrder(int $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payment_events WHERE order_id = :order_id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }
}
