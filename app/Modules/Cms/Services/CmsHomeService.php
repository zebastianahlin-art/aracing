<?php

declare(strict_types=1);

namespace App\Modules\Cms\Services;

use App\Modules\Category\Repositories\CategoryRepository;
use App\Modules\Cms\Repositories\CmsHomeSectionRepository;
use App\Modules\Product\Repositories\ProductRepository;

final class CmsHomeService
{
    private const DEFAULT_SECTIONS = [
        'hero' => 10,
        'intro' => 20,
        'featured_products' => 30,
        'featured_categories' => 40,
        'info' => 50,
    ];

    public function __construct(
        private readonly CmsHomeSectionRepository $sections,
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function adminSections(): array
    {
        $saved = $this->sections->all();
        $mapped = [];
        foreach ($saved as $row) {
            $mapped[(string) $row['section_key']] = $row;
        }

        $result = [];
        foreach (self::DEFAULT_SECTIONS as $key => $sortOrder) {
            $result[] = $mapped[$key] ?? [
                'section_key' => $key,
                'title' => '',
                'subtitle' => '',
                'body_html' => '',
                'button_text' => '',
                'button_url' => '',
                'content_refs_json' => '',
                'is_active' => 0,
                'sort_order' => $sortOrder,
            ];
        }

        return $result;
    }

    /** @param array<string, mixed> $input */
    public function saveAdminSections(array $input): void
    {
        foreach (self::DEFAULT_SECTIONS as $sectionKey => $defaultSort) {
            $this->sections->save($sectionKey, [
                'title' => $this->nullableString($input['title'][$sectionKey] ?? ''),
                'subtitle' => $this->nullableString($input['subtitle'][$sectionKey] ?? ''),
                'body_html' => $this->nullableString($input['body_html'][$sectionKey] ?? ''),
                'button_text' => $this->nullableString($input['button_text'][$sectionKey] ?? ''),
                'button_url' => $this->nullableString($input['button_url'][$sectionKey] ?? ''),
                'content_refs_json' => $this->normalizeRefs($input['content_refs'][$sectionKey] ?? ''),
                'is_active' => isset($input['is_active'][$sectionKey]) ? 1 : 0,
                'sort_order' => $this->toIntOrDefault($input['sort_order'][$sectionKey] ?? null, $defaultSort),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function storefrontHomeData(): array
    {
        $activeSections = $this->sections->active();
        $byKey = [];

        foreach ($activeSections as $section) {
            $key = (string) $section['section_key'];
            $section['ref_ids'] = $this->decodeRefIds($section['content_refs_json'] ?? null);
            $byKey[$key] = $section;
        }

        $featuredProducts = [];
        if (isset($byKey['featured_products'])) {
            $featuredProducts = $this->products->findActiveByIds($byKey['featured_products']['ref_ids']);
        }

        $featuredCategories = [];
        if (isset($byKey['featured_categories'])) {
            $featuredCategories = $this->categories->findByIds($byKey['featured_categories']['ref_ids']);
        }

        return [
            'sections' => $byKey,
            'featured_products' => $featuredProducts,
            'featured_categories' => $featuredCategories,
        ];
    }

    private function normalizeRefs(mixed $value): ?string
    {
        $ids = $this->decodeIdsFromCsv((string) $value);

        return $ids === [] ? null : json_encode(['ids' => $ids], JSON_UNESCAPED_UNICODE);
    }

    /** @return array<int, int> */
    private function decodeRefIds(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !isset($decoded['ids']) || !is_array($decoded['ids'])) {
            return [];
        }

        $ids = [];
        foreach ($decoded['ids'] as $id) {
            $idValue = (int) $id;
            if ($idValue > 0) {
                $ids[] = $idValue;
            }
        }

        return array_values(array_unique($ids));
    }

    /** @return array<int, int> */
    private function decodeIdsFromCsv(string $csv): array
    {
        $parts = array_map('trim', explode(',', $csv));
        $ids = [];

        foreach ($parts as $part) {
            if ($part !== '' && ctype_digit($part) && (int) $part > 0) {
                $ids[] = (int) $part;
            }
        }

        return array_values(array_unique($ids));
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function toIntOrDefault(mixed $value, int $default): int
    {
        $normalized = trim((string) $value);
        if ($normalized === '' || ctype_digit($normalized) === false) {
            return $default;
        }

        return (int) $normalized;
    }
}
