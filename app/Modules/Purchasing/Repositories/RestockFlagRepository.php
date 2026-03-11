<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Repositories;

use PDO;

final class RestockFlagRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<int,int> $productIds
     * @return array<int,array<string,mixed>>
     */
    public function listByProductIds(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->pdo->prepare('SELECT product_id, status, note, updated_at FROM restock_flags WHERE product_id IN (' . $placeholders . ')');

        foreach (array_values($productIds) as $index => $productId) {
            $stmt->bindValue($index + 1, $productId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function upsert(int $productId, string $status, ?string $note): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO restock_flags (product_id, status, note, created_at, updated_at)
                                     VALUES (:product_id, :status, :note, NOW(), NOW())
                                     ON DUPLICATE KEY UPDATE status = VALUES(status), note = VALUES(note), updated_at = NOW()');
        $stmt->bindValue('product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue('status', $status, PDO::PARAM_STR);
        $stmt->bindValue('note', $note, $note !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
    }

    public function delete(int $productId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM restock_flags WHERE product_id = :product_id');
        $stmt->execute(['product_id' => $productId]);
    }
}
