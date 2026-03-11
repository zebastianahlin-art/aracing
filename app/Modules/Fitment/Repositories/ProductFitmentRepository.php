<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Repositories;

use PDO;

final class ProductFitmentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function byProductId(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT pf.id, pf.product_id, pf.vehicle_id, pf.fitment_type, pf.note,
                v.make, v.model, v.generation, v.engine, v.year_from, v.year_to, v.is_active AS vehicle_is_active
            FROM product_fitments pf
            INNER JOIN vehicles v ON v.id = pf.vehicle_id
            WHERE pf.product_id = :product_id
            ORDER BY pf.id DESC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    public function create(int $productId, int $vehicleId, string $fitmentType, ?string $note): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO product_fitments (product_id, vehicle_id, fitment_type, note, created_at, updated_at)
            VALUES (:product_id, :vehicle_id, :fitment_type, :note, NOW(), NOW())');
        $stmt->execute([
            'product_id' => $productId,
            'vehicle_id' => $vehicleId,
            'fitment_type' => $fitmentType,
            'note' => $note,
        ]);
    }

    public function deleteForProduct(int $productId, int $fitmentId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM product_fitments WHERE id = :id AND product_id = :product_id');
        $stmt->execute(['id' => $fitmentId, 'product_id' => $productId]);
    }

    /** @return array<int,array<string,mixed>> */
    public function productFitmentsForVehicle(int $productId, int $vehicleId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, fitment_type FROM product_fitments
            WHERE product_id = :product_id AND (vehicle_id = :vehicle_id OR fitment_type = :universal)');
        $stmt->execute([
            'product_id' => $productId,
            'vehicle_id' => $vehicleId,
            'universal' => 'universal',
        ]);

        return $stmt->fetchAll();
    }
}
