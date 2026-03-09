<?php

declare(strict_types=1);

namespace App\Modules\Category\Repositories;

use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $sql = 'SELECT c.id, c.name, c.slug, c.parent_id, p.name AS parent_name
                FROM categories c
                LEFT JOIN categories p ON p.id = c.parent_id
                ORDER BY c.name ASC';

        return $this->pdo->query($sql)->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listForSelect(): array
    {
        return $this->pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, slug, parent_id FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(string $name, string $slug, ?int $parentId): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO categories (name, slug, parent_id, created_at, updated_at) VALUES (:name, :slug, :parent_id, NOW(), NOW())');
        $stmt->bindValue('name', $name);
        $stmt->bindValue('slug', $slug);
        $stmt->bindValue('parent_id', $parentId, $parentId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $slug, ?int $parentId): void
    {
        $stmt = $this->pdo->prepare('UPDATE categories SET name = :name, slug = :slug, parent_id = :parent_id, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('name', $name);
        $stmt->bindValue('slug', $slug);
        $stmt->bindValue('parent_id', $parentId, $parentId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
    }
    /** @param array<int, int> $ids
     *  @return array<int, array<string, mixed>>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT id, name, slug FROM categories WHERE id IN (' . $placeholders . ') ORDER BY name ASC';
        $stmt = $this->pdo->prepare($sql);

        foreach (array_values($ids) as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

}
