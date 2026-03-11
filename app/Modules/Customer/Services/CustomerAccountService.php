<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Repositories\CustomerOrderRepository;
use App\Modules\Customer\Repositories\UserRepository;
use InvalidArgumentException;

final class CustomerAccountService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly CustomerOrderRepository $orders
    ) {
    }

    public function updateProfile(int $userId, array $input): void
    {
        $firstName = trim((string) ($input['first_name'] ?? ''));
        $lastName = trim((string) ($input['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            throw new InvalidArgumentException('Förnamn och efternamn är obligatoriskt.');
        }

        $this->users->updateProfile($userId, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim((string) ($input['phone'] ?? '')),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listOrders(int $userId): array
    {
        return $this->orders->listForUser($userId);
    }

    /** @return array<string, mixed>|null */
    public function getOrderDetail(int $userId, int $orderId): ?array
    {
        $order = $this->orders->findForUserById($userId, $orderId);
        if ($order === null) {
            return null;
        }

        return [
            'order' => $order,
            'items' => $this->orders->orderItems($orderId),
        ];
    }
}
