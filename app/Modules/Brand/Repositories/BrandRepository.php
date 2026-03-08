<?php

declare(strict_types=1);

namespace App\Modules\Brand\Repositories;

use PDO;

final class BrandRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT id, name, slug, created_at, updated_at FROM brands ORDER BY name ASC')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, slug FROM brands WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(string $name, string $slug): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO brands (name, slug, created_at, updated_at) VALUES (:name, :slug, NOW(), NOW())');
        $stmt->execute(['name' => $name, 'slug' => $slug]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $slug): void
    {
        $stmt = $this->pdo->prepare('UPDATE brands SET name = :name, slug = :slug, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id, 'name' => $name, 'slug' => $slug]);
    }
}
