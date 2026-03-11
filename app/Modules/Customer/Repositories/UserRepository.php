<?php

declare(strict_types=1);

namespace App\Modules\Customer\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function createCustomer(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (email, password_hash, role, first_name, last_name, phone, created_at, updated_at)
            VALUES (:email, :password_hash, :role, :first_name, :last_name, :phone, NOW(), NOW())');

        $stmt->execute([
            'email' => mb_strtolower(trim((string) $data['email'])),
            'password_hash' => (string) $data['password_hash'],
            'role' => (string) ($data['role'] ?? 'customer'),
            'first_name' => $this->nullable($data['first_name'] ?? null),
            'last_name' => $this->nullable($data['last_name'] ?? null),
            'phone' => $this->nullable($data['phone'] ?? null),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateProfile(int $userId, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE users
            SET first_name = :first_name,
                last_name = :last_name,
                phone = :phone,
                updated_at = NOW()
            WHERE id = :id');

        $stmt->execute([
            'id' => $userId,
            'first_name' => $this->nullable($data['first_name'] ?? null),
            'last_name' => $this->nullable($data['last_name'] ?? null),
            'phone' => $this->nullable($data['phone'] ?? null),
        ]);
    }

    public function updateAddress(int $userId, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE users
            SET address_line_1 = :address_line_1,
                address_line_2 = :address_line_2,
                postal_code = :postal_code,
                city = :city,
                country_code = :country_code,
                updated_at = NOW()
            WHERE id = :id');

        $stmt->execute([
            'id' => $userId,
            'address_line_1' => $this->nullable($data['address_line_1'] ?? null),
            'address_line_2' => $this->nullable($data['address_line_2'] ?? null),
            'postal_code' => $this->nullable($data['postal_code'] ?? null),
            'city' => $this->nullable($data['city'] ?? null),
            'country_code' => $this->nullable($data['country_code'] ?? null),
        ]);
    }

    private function nullable(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
