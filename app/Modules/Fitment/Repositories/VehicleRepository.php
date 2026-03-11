<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Repositories;

use PDO;

final class VehicleRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function allForAdmin(): array
    {
        return $this->pdo->query('SELECT id, make, model, generation, engine, fuel_type, year_from, year_to, body_type, is_active, sort_order
            FROM vehicles
            ORDER BY sort_order DESC, make ASC, model ASC, generation ASC, engine ASC, id DESC')->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, make, model, generation, engine, fuel_type, year_from, year_to, body_type, is_active, sort_order FROM vehicles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string,mixed>|null */
    public function findActiveById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, make, model, generation, engine, fuel_type, year_from, year_to, body_type, is_active, sort_order
            FROM vehicles
            WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO vehicles (make, model, generation, engine, fuel_type, year_from, year_to, body_type, is_active, sort_order, created_at, updated_at)
            VALUES (:make, :model, :generation, :engine, :fuel_type, :year_from, :year_to, :body_type, :is_active, :sort_order, NOW(), NOW())');
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->pdo->prepare('UPDATE vehicles
            SET make = :make,
                model = :model,
                generation = :generation,
                engine = :engine,
                fuel_type = :fuel_type,
                year_from = :year_from,
                year_to = :year_to,
                body_type = :body_type,
                is_active = :is_active,
                sort_order = :sort_order,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute($data);
    }

    /** @return array<int,array<string,mixed>> */
    public function activeForSelector(): array
    {
        return $this->pdo->query('SELECT id, make, model, generation, engine, year_from, year_to
            FROM vehicles
            WHERE is_active = 1
            ORDER BY make ASC, model ASC, generation ASC, engine ASC, year_from ASC, id ASC')->fetchAll();
    }
}
