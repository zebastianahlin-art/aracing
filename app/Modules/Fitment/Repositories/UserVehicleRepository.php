<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Repositories;

use PDO;

final class UserVehicleRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function add(int $userId, int $vehicleId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO user_vehicles (user_id, vehicle_id, is_primary, created_at, updated_at)
            VALUES (:user_id, :vehicle_id, 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()');
        $stmt->execute([
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
        ]);
    }

    public function exists(int $userId, int $vehicleId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM user_vehicles WHERE user_id = :user_id AND vehicle_id = :vehicle_id LIMIT 1');
        $stmt->execute([
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /** @return array<int,array<string,mixed>> */
    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT uv.id,
                       uv.user_id,
                       uv.vehicle_id,
                       uv.is_primary,
                       uv.created_at,
                       uv.updated_at,
                       v.make,
                       v.model,
                       v.generation,
                       v.engine,
                       v.year_from,
                       v.year_to,
                       v.is_active AS vehicle_is_active
                FROM user_vehicles uv
                LEFT JOIN vehicles v ON v.id = uv.vehicle_id
                WHERE uv.user_id = :user_id
                ORDER BY uv.is_primary DESC, uv.created_at DESC, uv.id DESC');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function clearPrimary(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE user_vehicles SET is_primary = 0, updated_at = NOW() WHERE user_id = :user_id AND is_primary = 1');
        $stmt->execute(['user_id' => $userId]);
    }

    public function setPrimary(int $userId, int $vehicleId): void
    {
        $stmt = $this->pdo->prepare('UPDATE user_vehicles
            SET is_primary = 1, updated_at = NOW()
            WHERE user_id = :user_id AND vehicle_id = :vehicle_id');
        $stmt->execute([
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
        ]);
    }

    public function remove(int $userId, int $vehicleId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM user_vehicles WHERE user_id = :user_id AND vehicle_id = :vehicle_id');
        $stmt->execute([
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
        ]);
    }

    /** @return array<string,mixed>|null */
    public function primaryForUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT uv.id,
                       uv.user_id,
                       uv.vehicle_id,
                       uv.is_primary,
                       v.is_active AS vehicle_is_active
                FROM user_vehicles uv
                LEFT JOIN vehicles v ON v.id = uv.vehicle_id
                WHERE uv.user_id = :user_id
                  AND uv.is_primary = 1
                LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}

