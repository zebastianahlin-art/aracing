<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Repositories;

use PDO;

final class ShippingMethodRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function allForAdmin(): array
    {
        return $this->pdo->query('SELECT * FROM shipping_methods ORDER BY sort_order ASC, name ASC, id ASC')->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function activeMethods(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order ASC, name ASC, id ASC');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM shipping_methods WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findActiveByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM shipping_methods WHERE code = :code AND is_active = 1 LIMIT 1');
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO shipping_methods (
            code, name, description, price_ex_vat, price_inc_vat, is_active, sort_order, created_at, updated_at
        ) VALUES (
            :code, :name, :description, :price_ex_vat, :price_inc_vat, :is_active, :sort_order, NOW(), NOW()
        )');
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->pdo->prepare('UPDATE shipping_methods
            SET code = :code,
                name = :name,
                description = :description,
                price_ex_vat = :price_ex_vat,
                price_inc_vat = :price_inc_vat,
                is_active = :is_active,
                sort_order = :sort_order,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute($data);
    }
}

