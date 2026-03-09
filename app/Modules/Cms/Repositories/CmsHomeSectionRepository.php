<?php

declare(strict_types=1);

namespace App\Modules\Cms\Repositories;

use PDO;

final class CmsHomeSectionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM cms_home_sections ORDER BY sort_order ASC, id ASC')->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function active(): array
    {
        return $this->pdo->query('SELECT * FROM cms_home_sections WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findBySectionKey(string $sectionKey): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cms_home_sections WHERE section_key = :section_key');
        $stmt->execute(['section_key' => $sectionKey]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function save(string $sectionKey, array $data): void
    {
        $existing = $this->findBySectionKey($sectionKey);

        if ($existing === null) {
            $stmt = $this->pdo->prepare('INSERT INTO cms_home_sections (section_key, title, subtitle, body_html, button_text, button_url, content_refs_json, is_active, sort_order, created_at, updated_at) VALUES (:section_key, :title, :subtitle, :body_html, :button_text, :button_url, :content_refs_json, :is_active, :sort_order, NOW(), NOW())');
            $stmt->execute([
                'section_key' => $sectionKey,
                'title' => $data['title'],
                'subtitle' => $data['subtitle'],
                'body_html' => $data['body_html'],
                'button_text' => $data['button_text'],
                'button_url' => $data['button_url'],
                'content_refs_json' => $data['content_refs_json'],
                'is_active' => $data['is_active'],
                'sort_order' => $data['sort_order'],
            ]);

            return;
        }

        $stmt = $this->pdo->prepare('UPDATE cms_home_sections SET title = :title, subtitle = :subtitle, body_html = :body_html, button_text = :button_text, button_url = :button_url, content_refs_json = :content_refs_json, is_active = :is_active, sort_order = :sort_order, updated_at = NOW() WHERE section_key = :section_key');
        $stmt->execute([
            'section_key' => $sectionKey,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'body_html' => $data['body_html'],
            'button_text' => $data['button_text'],
            'button_url' => $data['button_url'],
            'content_refs_json' => $data['content_refs_json'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ]);
    }
}
