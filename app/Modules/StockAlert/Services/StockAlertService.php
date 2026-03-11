<?php

declare(strict_types=1);

namespace App\Modules\StockAlert\Services;

use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\StockAlert\Repositories\StockAlertRepository;
use InvalidArgumentException;

final class StockAlertService
{
    public function __construct(
        private readonly StockAlertRepository $subscriptions,
        private readonly ProductRepository $products,
        private readonly StockAlertNotificationService $notifications
    ) {
    }

    /** @return array{status:string,message:string} */
    public function subscribe(int $productId, string $email, ?int $userId, bool $isPurchasable): array
    {
        $email = mb_strtolower(trim($email));
        if ($productId <= 0) {
            throw new InvalidArgumentException('Ogiltig produkt.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Ange en giltig e-postadress.');
        }

        $product = $this->products->findById($productId);
        if ($product === null || (int) ($product['is_active'] ?? 0) !== 1 || (int) ($product['is_search_hidden'] ?? 0) === 1) {
            throw new InvalidArgumentException('Produkten kan inte bevakas.');
        }

        if ($isPurchasable) {
            throw new InvalidArgumentException('Produkten är redan köpbar just nu.');
        }

        $active = $this->subscriptions->findActiveByProductAndEmail($productId, $email);
        if ($active !== null) {
            return [
                'status' => 'already_active',
                'message' => 'Du bevakar redan denna produkt med angiven e-post.',
            ];
        }

        $this->subscriptions->create($productId, $userId, $email);

        return [
            'status' => 'created',
            'message' => 'Bevakning registrerad. Vi mejlar dig när produkten är köpbar igen.',
        ];
    }

    public function hasActiveSubscription(int $productId, string $email): bool
    {
        $email = mb_strtolower(trim($email));
        if ($productId <= 0 || $email === '') {
            return false;
        }

        return $this->subscriptions->findActiveByProductAndEmail($productId, $email) !== null;
    }

    public function triggerNotificationsForProduct(int $productId, bool $isPurchasable): void
    {
        if (!$isPurchasable || $productId <= 0) {
            return;
        }

        $product = $this->products->findById($productId);
        if ($product === null || (int) ($product['is_active'] ?? 0) !== 1 || (int) ($product['is_search_hidden'] ?? 0) === 1) {
            return;
        }

        $activeSubscriptions = $this->subscriptions->activeForProduct($productId);
        foreach ($activeSubscriptions as $subscription) {
            $sent = $this->notifications->sendBackInStockEmail($subscription, $product);
            if ($sent) {
                $this->subscriptions->markNotified((int) $subscription['id']);
            }
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function listForUser(int $userId): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Ogiltig användare.');
        }

        return $this->subscriptions->forUser($userId);
    }

    public function unsubscribeForUser(int $subscriptionId, int $userId): void
    {
        if ($subscriptionId <= 0 || $userId <= 0) {
            throw new InvalidArgumentException('Ogiltig bevakning.');
        }

        $this->subscriptions->markUnsubscribed($subscriptionId, $userId);
    }
}
