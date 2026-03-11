<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Repositories;

use PDO;

final class HomepageSectionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM homepage_sections ORDER BY sort_order ASC, id ASC')->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function active(): array
    {
        return $this->pdo->query('SELECT * FROM homepage_sections WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM homepage_sections WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO homepage_sections (section_key, title, subtitle, section_type, is_active, sort_order, max_items, cta_label, cta_url, created_at, updated_at) VALUES (:section_key, :title, :subtitle, :section_type, :is_active, :sort_order, :max_items, :cta_label, :cta_url, NOW(), NOW())');
        $stmt->execute([
            'section_key' => $data['section_key'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'section_type' => $data['section_type'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'max_items' => $data['max_items'],
            'cta_label' => $data['cta_label'],
            'cta_url' => $data['cta_url'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE homepage_sections SET section_key = :section_key, title = :title, subtitle = :subtitle, section_type = :section_type, is_active = :is_active, sort_order = :sort_order, max_items = :max_items, cta_label = :cta_label, cta_url = :cta_url, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'section_key' => $data['section_key'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'section_type' => $data['section_type'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'max_items' => $data['max_items'],
            'cta_label' => $data['cta_label'],
            'cta_url' => $data['cta_url'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM homepage_sections WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
