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

    public function updateAddress(int $userId, array $input): void
    {
        $addressLine1 = trim((string) ($input['address_line_1'] ?? ''));
        $addressLine2 = trim((string) ($input['address_line_2'] ?? ''));
        $postalCode = trim((string) ($input['postal_code'] ?? ''));
        $city = trim((string) ($input['city'] ?? ''));
        $countryCode = strtoupper(trim((string) ($input['country_code'] ?? '')));

        if ($addressLine1 === '' && $postalCode === '' && $city === '' && $countryCode === '' && $addressLine2 === '') {
            $this->users->updateAddress($userId, [
                'address_line_1' => null,
                'address_line_2' => null,
                'postal_code' => null,
                'city' => null,
                'country_code' => null,
            ]);

            return;
        }

        if ($addressLine1 === '' || $postalCode === '' || $city === '' || $countryCode === '') {
            throw new InvalidArgumentException('Adressrad 1, postnummer, stad och land krävs för att spara adressen.');
        }

        $this->users->updateAddress($userId, [
            'address_line_1' => $this->limit($addressLine1, 190, 'Adressrad 1 är för lång.'),
            'address_line_2' => $this->limitNullable($addressLine2, 190, 'Adressrad 2 är för lång.'),
            'postal_code' => $this->limit($postalCode, 40, 'Postnummer är för långt.'),
            'city' => $this->limit($city, 120, 'Stad är för lång.'),
            'country_code' => $this->limit($countryCode, 10, 'Landkod är för lång.'),
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

    private function limit(string $value, int $maxLength, string $message): string
    {
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    private function limitNullable(string $value, int $maxLength, string $message): ?string
    {
        if ($value === '') {
            return null;
        }

        return $this->limit($value, $maxLength, $message);
    }
}
