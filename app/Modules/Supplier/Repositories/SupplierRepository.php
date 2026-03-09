<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Repositories;

use PDO;

final class SupplierRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo
            ->query('SELECT id, name, slug, is_active, contact_name, contact_email, notes, created_at, updated_at FROM suppliers ORDER BY name ASC')
            ->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function allActive(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name ASC');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, slug, is_active, contact_name, contact_email, notes FROM suppliers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO suppliers (name, slug, is_active, contact_name, contact_email, notes, created_at, updated_at)
             VALUES (:name, :slug, :is_active, :contact_name, :contact_email, :notes, NOW(), NOW())'
        );

        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $data['id'] = $id;

        $stmt = $this->pdo->prepare(
            'UPDATE suppliers
             SET name = :name,
                 slug = :slug,
                 is_active = :is_active,
                 contact_name = :contact_name,
                 contact_email = :contact_email,
                 notes = :notes,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute($data);
    }
}
