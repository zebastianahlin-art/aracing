<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Repositories;

use PDO;

final class PurchaseListRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(string $name, string $status, ?string $notes): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO purchase_lists (name, status, notes, created_at, updated_at) VALUES (:name, :status, :notes, NOW(), NOW())');
        $stmt->execute([
            'name' => $name,
            'status' => $status,
            'notes' => $notes,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT pl.id,
                                          pl.name,
                                          pl.status,
                                          pl.notes,
                                          pl.created_at,
                                          pl.updated_at,
                                          COUNT(pli.id) AS item_count,
                                          SUM(COALESCE(pli.selected_quantity, 0)) AS total_selected_quantity
                                   FROM purchase_lists pl
                                   LEFT JOIN purchase_list_items pli ON pli.purchase_list_id = pl.id
                                   GROUP BY pl.id
                                   ORDER BY pl.updated_at DESC, pl.id DESC');

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, status, notes, created_at, updated_at FROM purchase_lists WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function updateMeta(int $id, string $status, ?string $notes): void
    {
        $stmt = $this->pdo->prepare('UPDATE purchase_lists
                                     SET status = :status,
                                         notes = :notes,
                                         updated_at = NOW()
                                     WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'notes' => $notes,
        ]);
    }
}
