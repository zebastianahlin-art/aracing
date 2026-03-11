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
        $sql = 'SELECT c.id, c.name, c.slug, c.parent_id, c.seo_title, c.meta_robots, c.is_indexable, p.name AS parent_name
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
        $stmt = $this->pdo->prepare('SELECT id, name, slug, parent_id, seo_title, seo_description, canonical_url, meta_robots, is_indexable FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO categories (name, slug, parent_id, seo_title, seo_description, canonical_url, meta_robots, is_indexable, created_at, updated_at) VALUES (:name, :slug, :parent_id, :seo_title, :seo_description, :canonical_url, :meta_robots, :is_indexable, NOW(), NOW())');
        $stmt->bindValue('name', $data['name']);
        $stmt->bindValue('slug', $data['slug']);
        $stmt->bindValue('parent_id', $data['parent_id'], $data['parent_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('seo_title', $data['seo_title']);
        $stmt->bindValue('seo_description', $data['seo_description']);
        $stmt->bindValue('canonical_url', $data['canonical_url']);
        $stmt->bindValue('meta_robots', $data['meta_robots']);
        $stmt->bindValue('is_indexable', $data['is_indexable'], PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE categories SET name = :name, slug = :slug, parent_id = :parent_id, seo_title = :seo_title, seo_description = :seo_description, canonical_url = :canonical_url, meta_robots = :meta_robots, is_indexable = :is_indexable, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('name', $data['name']);
        $stmt->bindValue('slug', $data['slug']);
        $stmt->bindValue('parent_id', $data['parent_id'], $data['parent_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('seo_title', $data['seo_title']);
        $stmt->bindValue('seo_description', $data['seo_description']);
        $stmt->bindValue('canonical_url', $data['canonical_url']);
        $stmt->bindValue('meta_robots', $data['meta_robots']);
        $stmt->bindValue('is_indexable', $data['is_indexable'], PDO::PARAM_INT);
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

    /** @return array<int, array{slug:string, updated_at:?string}> */
    public function sitemapIndexableCategories(): array
    {
        $sql = 'SELECT c.slug, c.updated_at
                FROM categories c
                WHERE c.is_indexable = 1
                  AND TRIM(COALESCE(c.slug, "")) <> ""
                  AND (c.meta_robots IS NULL OR LOWER(c.meta_robots) NOT LIKE "%noindex%")
                ORDER BY c.updated_at DESC, c.id DESC';

        return $this->pdo->query($sql)->fetchAll();
    }

}
