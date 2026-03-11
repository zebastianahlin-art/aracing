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

    private const ALLOWED_META_ROBOTS = [
        'index,follow',
        'noindex,follow',
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
            'meta_title' => $this->nullableString($input['meta_title'] ?? null, 255),
            'meta_description' => $this->nullableString($input['meta_description'] ?? null),
            'canonical_url' => $this->normalizeCanonicalUrl($input['canonical_url'] ?? null),
            'meta_robots' => $this->normalizeMetaRobots($input['meta_robots'] ?? null),
            'is_indexable' => isset($input['is_indexable']) ? 1 : 0,
            'content_html' => $this->nullableString($input['content_html'] ?? null),
        ];
    }

    private function nullableString(mixed $value, ?int $maxLength = null): ?string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        if ($maxLength !== null && mb_strlen($normalized) > $maxLength) {
            return mb_substr($normalized, 0, $maxLength);
        }

        return $normalized;
    }

    private function normalizeCanonicalUrl(mixed $value): ?string
    {
        $url = $this->nullableString($value, 255);
        if ($url === null) {
            return null;
        }

        if (preg_match('#^(https?://|/)#i', $url) !== 1) {
            return null;
        }

        return $url;
    }

    private function normalizeMetaRobots(mixed $value): ?string
    {
        $robots = mb_strtolower(trim((string) $value));
        if ($robots === '') {
            return null;
        }

        return in_array($robots, self::ALLOWED_META_ROBOTS, true) ? $robots : null;
    }
}
