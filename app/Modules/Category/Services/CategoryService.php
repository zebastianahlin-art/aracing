<?php

declare(strict_types=1);

namespace App\Modules\Category\Services;

use App\Modules\Category\Repositories\CategoryRepository;
use App\Shared\Support\Slugger;

final class CategoryService
{
    private const ALLOWED_META_ROBOTS = [
        'index,follow',
        'noindex,follow',
    ];

    public function __construct(private readonly CategoryRepository $categories)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->categories->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function listForSelect(): array
    {
        return $this->categories->listForSelect();
    }

    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        return $this->categories->findById($id);
    }

    /** @param array<string, mixed> $input */
    public function create(array $input): int
    {
        return $this->categories->create($this->normalize($input));
    }

    /** @param array<string, mixed> $input */
    public function update(int $id, array $input): void
    {
        $data = $this->normalize($input);
        if (($data['parent_id'] ?? null) === $id) {
            $data['parent_id'] = null;
        }

        $this->categories->update($id, $data);
    }

    /** @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $slug = Slugger::slugify($input['slug'] ?? $name);

        return [
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $this->toNullableInt($input['parent_id'] ?? null),
            'seo_title' => $this->nullableString($input['seo_title'] ?? null, 255),
            'seo_description' => $this->nullableString($input['seo_description'] ?? null),
            'canonical_url' => $this->normalizeCanonicalUrl($input['canonical_url'] ?? null),
            'meta_robots' => $this->normalizeMetaRobots($input['meta_robots'] ?? null),
            'is_indexable' => isset($input['is_indexable']) ? 1 : 0,
        ];
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (int) $value;
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
