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
    public function searchVehiclesForAssignment(string $query, int $limit = 80): array
    {
        $sql = 'SELECT id, make, model, generation, engine, year_from, year_to, is_active
'
             . 'FROM vehicles
'
             . 'WHERE is_active = 1';
        $params = [];

        $query = trim($query);
        if ($query !== '') {
            $sql .= ' AND (make LIKE :query OR model LIKE :query OR generation LIKE :query OR engine LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $sql .= ' ORDER BY make ASC, model ASC, generation ASC, engine ASC, year_from ASC, id ASC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        if (isset($params['query'])) {
            $stmt->bindValue('query', $params['query']);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
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

    /**
     * @param array<int,int> $productIds
     * @return array<int,array<int,string>>
     */
    public function fitmentTypesForProductsAndVehicle(array $productIds, int $vehicleId): array
    {
        if ($productIds === [] || $vehicleId <= 0) {
            return [];
        }

        $productIds = array_values(array_unique(array_map(static fn (int $id): int => max(0, $id), $productIds)));
        $productIds = array_values(array_filter($productIds, static fn (int $id): bool => $id > 0));

        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = 'SELECT product_id, fitment_type
            FROM product_fitments
            WHERE product_id IN (' . $placeholders . ')
              AND (vehicle_id = ? OR fitment_type = ?)';

        $stmt = $this->pdo->prepare($sql);
        $index = 1;
        foreach ($productIds as $productId) {
            $stmt->bindValue($index, $productId, PDO::PARAM_INT);
            $index++;
        }
        $stmt->bindValue($index, $vehicleId, PDO::PARAM_INT);
        $stmt->bindValue($index + 1, 'universal');
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $productId = (int) $row['product_id'];
            $type = (string) $row['fitment_type'];
            $result[$productId] ??= [];
            $result[$productId][] = $type;
        }

        return $result;
    }
}
