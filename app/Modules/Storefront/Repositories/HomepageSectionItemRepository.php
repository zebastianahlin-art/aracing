<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Repositories;

use PDO;

final class HomepageSectionItemRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function forSection(int $sectionId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM homepage_section_items WHERE homepage_section_id = :section_id';
        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['section_id' => $sectionId]);

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO homepage_section_items (homepage_section_id, item_type, item_id, sort_order, is_active, created_at, updated_at) VALUES (:homepage_section_id, :item_type, :item_id, :sort_order, :is_active, NOW(), NOW())');
        $stmt->execute([
            'homepage_section_id' => $data['homepage_section_id'],
            'item_type' => $data['item_type'],
            'item_id' => $data['item_id'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $sectionId, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE homepage_section_items SET item_type = :item_type, item_id = :item_id, sort_order = :sort_order, is_active = :is_active, updated_at = NOW() WHERE id = :id AND homepage_section_id = :homepage_section_id');
        $stmt->execute([
            'id' => $id,
            'homepage_section_id' => $sectionId,
            'item_type' => $data['item_type'],
            'item_id' => $data['item_id'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
        ]);
    }

    public function delete(int $id, int $sectionId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM homepage_section_items WHERE id = :id AND homepage_section_id = :homepage_section_id');
        $stmt->execute([
            'id' => $id,
            'homepage_section_id' => $sectionId,
        ]);
    }
}
