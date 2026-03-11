<?php

declare(strict_types=1);

namespace App\Modules\Cms\Repositories;

use PDO;

final class CmsPageRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT id, title, slug, page_type, is_active, meta_title, meta_robots, is_indexable, updated_at FROM cms_pages ORDER BY updated_at DESC, id DESC')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cms_pages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findActiveBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cms_pages WHERE slug = :slug AND is_active = 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @param array<int, string> $slugs
     *  @return array<int, array<string, mixed>>
     */
    public function findActiveBySlugs(array $slugs): array
    {
        $normalizedSlugs = [];
        foreach ($slugs as $slug) {
            $value = trim((string) $slug);
            if ($value !== '') {
                $normalizedSlugs[] = $value;
            }
        }

        if ($normalizedSlugs === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($normalizedSlugs), '?'));
        $sql = sprintf(
            'SELECT id, title, slug, page_type FROM cms_pages WHERE is_active = 1 AND slug IN (%s)',
            $placeholders
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($normalizedSlugs);

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO cms_pages (title, slug, page_type, is_active, meta_title, meta_description, canonical_url, meta_robots, is_indexable, content_html, created_at, updated_at) VALUES (:title, :slug, :page_type, :is_active, :meta_title, :meta_description, :canonical_url, :meta_robots, :is_indexable, :content_html, NOW(), NOW())');
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->pdo->prepare('UPDATE cms_pages SET title = :title, slug = :slug, page_type = :page_type, is_active = :is_active, meta_title = :meta_title, meta_description = :meta_description, canonical_url = :canonical_url, meta_robots = :meta_robots, is_indexable = :is_indexable, content_html = :content_html, updated_at = NOW() WHERE id = :id');
        $stmt->execute($data);
    }
}
