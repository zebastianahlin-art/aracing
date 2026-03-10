<?php

declare(strict_types=1);

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Repositories\CmsPageRepository;
use App\Shared\Support\Slugger;

final class CmsPageService
{

    private const STOREFRONT_INFO_SLUGS = [
        'kontakt' => 'Kontakt',
        'kopvillkor' => 'Köpvillkor',
        'retur-reklamation' => 'Retur / reklamation',
        'fraktinfo' => 'Fraktinfo',
        'om-oss' => 'Om oss',
    ];
    public function __construct(private readonly CmsPageRepository $pages)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->pages->all();
    }

    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        return $this->pages->findById($id);
    }

    /** @return array<string, mixed>|null */
    public function getActiveBySlug(string $slug): ?array
    {
        return $this->pages->findActiveBySlug($slug);
    }


    /** @return array<int, array<string, string>> */
    public function storefrontInfoPages(): array
    {
        $rows = $this->pages->findActiveBySlugs(array_keys(self::STOREFRONT_INFO_SLUGS));
        $mapped = [];
        foreach ($rows as $row) {
            $slug = (string) ($row['slug'] ?? '');
            if ($slug !== '') {
                $mapped[$slug] = $row;
            }
        }

        $result = [];
        foreach (self::STOREFRONT_INFO_SLUGS as $slug => $label) {
            $title = isset($mapped[$slug]['title']) ? (string) $mapped[$slug]['title'] : $label;
            $result[] = [
                'label' => $title,
                'slug' => $slug,
                'url' => '/pages/' . $slug,
            ];
        }

        return $result;
    }

    /** @param array<string, mixed> $input */
    public function create(array $input): int
    {
        return $this->pages->create($this->normalize($input));
    }

    /** @param array<string, mixed> $input */
    public function update(int $id, array $input): void
    {
        $this->pages->update($id, $this->normalize($input));
    }

    /** @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $slugSource = (string) ($input['slug'] ?? $title);
        $pageType = trim((string) ($input['page_type'] ?? 'page'));

        if (!in_array($pageType, ['page', 'legal', 'info'], true)) {
            $pageType = 'page';
        }

        return [
            'title' => $title,
            'slug' => Slugger::slugify($slugSource),
            'page_type' => $pageType,
            'is_active' => isset($input['is_active']) ? 1 : 0,
            'meta_title' => $this->nullableString($input['meta_title'] ?? null),
            'meta_description' => $this->nullableString($input['meta_description'] ?? null),
            'content_html' => $this->nullableString($input['content_html'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
