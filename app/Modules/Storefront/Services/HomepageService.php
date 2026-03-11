<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Services;

use App\Modules\Category\Repositories\CategoryRepository;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Storefront\Repositories\HomepageSectionItemRepository;
use App\Modules\Storefront\Repositories\HomepageSectionRepository;

final class HomepageService
{
    private const SECTION_TYPES = ['featured_products', 'featured_category', 'mixed_manual'];
    private const ITEM_TYPES = ['product', 'category'];

    public function __construct(
        private readonly HomepageSectionRepository $sections,
        private readonly HomepageSectionItemRepository $items,
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function adminSections(): array
    {
        $sections = $this->sections->all();
        foreach ($sections as &$section) {
            $section['items'] = $this->items->forSection((int) $section['id']);
        }

        return $sections;
    }

    /** @return array<string, mixed> */
    public function adminMeta(): array
    {
        return [
            'section_types' => self::SECTION_TYPES,
            'item_types' => self::ITEM_TYPES,
            'products' => $this->products->searchForSupplierMatch('', 200),
            'categories' => $this->categories->listForSelect(),
        ];
    }

    /** @param array<string, mixed> $input */
    public function saveSection(array $input, ?int $id = null): void
    {
        $payload = $this->normalizedSectionPayload($input, $id);
        if ($id === null) {
            $this->sections->create($payload);

            return;
        }

        if ($this->sections->findById($id) === null) {
            return;
        }

        $this->sections->update($id, $payload);
    }

    public function deleteSection(int $sectionId): void
    {
        $this->sections->delete($sectionId);
    }

    /** @param array<string, mixed> $input */
    public function addSectionItem(int $sectionId, array $input): void
    {
        if ($this->sections->findById($sectionId) === null) {
            return;
        }

        $this->items->create($this->normalizedItemPayload($input, $sectionId));
    }

    /** @param array<string, mixed> $input */
    public function updateSectionItem(int $sectionId, int $itemId, array $input): void
    {
        if ($this->sections->findById($sectionId) === null) {
            return;
        }

        $this->items->update($itemId, $sectionId, $this->normalizedItemPayload($input, $sectionId));
    }

    public function deleteSectionItem(int $sectionId, int $itemId): void
    {
        $this->items->delete($itemId, $sectionId);
    }

    /** @return array<string, mixed> */
    public function storefrontHomeData(): array
    {
        $payloadSections = [];
        foreach ($this->sections->active() as $section) {
            $resolved = $this->buildStorefrontSection($section);
            if ($resolved !== null) {
                $payloadSections[] = $resolved;
            }
        }

        return ['homepage_sections' => $payloadSections];
    }

    /** @param array<string, mixed> $section
     * @return array<string, mixed>|null
     */
    private function buildStorefrontSection(array $section): ?array
    {
        $sectionId = (int) ($section['id'] ?? 0);
        if ($sectionId <= 0) {
            return null;
        }

        $maxItems = $this->toPositiveInt($section['max_items'] ?? null, 8);
        $rows = $this->items->forSection($sectionId, true);

        $productIds = [];
        $categoryIds = [];
        foreach ($rows as $row) {
            $itemType = (string) ($row['item_type'] ?? '');
            $itemId = (int) ($row['item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            if ($itemType === 'product') {
                $productIds[] = $itemId;
            }

            if ($itemType === 'category') {
                $categoryIds[] = $itemId;
            }
        }

        $products = $this->products->publicProductsByIds(array_values(array_unique($productIds)));
        $categories = $this->publicCategoriesByIds(array_values(array_unique($categoryIds)));

        $productsById = [];
        foreach ($products as $product) {
            $productsById[(int) $product['id']] = $product;
        }

        $categoriesById = [];
        foreach ($categories as $category) {
            $categoriesById[(int) $category['id']] = $category;
        }

        $resolvedItems = [];
        $sectionType = (string) ($section['section_type'] ?? 'featured_products');
        foreach ($rows as $row) {
            $itemType = (string) ($row['item_type'] ?? '');
            $itemId = (int) ($row['item_id'] ?? 0);

            if ($sectionType === 'featured_products' && $itemType !== 'product') {
                continue;
            }

            if ($sectionType === 'featured_category' && $itemType !== 'category') {
                continue;
            }

            if ($itemType === 'product' && isset($productsById[$itemId])) {
                $resolvedItems[] = ['item_type' => 'product', 'item' => $productsById[$itemId]];
            }

            if ($itemType === 'category' && isset($categoriesById[$itemId])) {
                $resolvedItems[] = ['item_type' => 'category', 'item' => $categoriesById[$itemId]];
            }

            if (count($resolvedItems) >= $maxItems) {
                break;
            }
        }

        return [
            'id' => $sectionId,
            'key' => (string) ($section['section_key'] ?? ''),
            'title' => (string) ($section['title'] ?? ''),
            'subtitle' => (string) ($section['subtitle'] ?? ''),
            'section_type' => $sectionType,
            'cta_label' => $section['cta_label'],
            'cta_url' => $section['cta_url'],
            'items' => $resolvedItems,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function publicCategoriesByIds(array $ids): array
    {
        $rows = $this->categories->findByIds($ids);

        return array_values(array_filter($rows, static function (array $row): bool {
            return trim((string) ($row['slug'] ?? '')) !== '';
        }));
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizedSectionPayload(array $input, ?int $id): array
    {
        $type = trim((string) ($input['section_type'] ?? 'featured_products'));
        if (!in_array($type, self::SECTION_TYPES, true)) {
            $type = 'featured_products';
        }

        $keyCandidate = trim((string) ($input['section_key'] ?? ''));
        if ($keyCandidate === '') {
            $keyCandidate = 'homepage_section_' . ($id ?? time());
        }

        $key = strtolower((string) preg_replace('/[^a-z0-9_\-]+/', '_', $keyCandidate));

        return [
            'section_key' => mb_substr($key, 0, 80),
            'title' => mb_substr(trim((string) ($input['title'] ?? '')), 0, 190) ?: 'Sektion',
            'subtitle' => $this->nullableString(mb_substr(trim((string) ($input['subtitle'] ?? '')), 0, 255)),
            'section_type' => $type,
            'is_active' => isset($input['is_active']) ? 1 : 0,
            'sort_order' => $this->toInt($input['sort_order'] ?? null, 0),
            'max_items' => $this->toPositiveInt($input['max_items'] ?? null, 8),
            'cta_label' => $this->nullableString(mb_substr(trim((string) ($input['cta_label'] ?? '')), 0, 120)),
            'cta_url' => $this->normalizedCtaUrl($input['cta_url'] ?? null),
        ];
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizedItemPayload(array $input, int $sectionId): array
    {
        $itemType = trim((string) ($input['item_type'] ?? 'product'));
        if (!in_array($itemType, self::ITEM_TYPES, true)) {
            $itemType = 'product';
        }

        return [
            'homepage_section_id' => $sectionId,
            'item_type' => $itemType,
            'item_id' => $this->toPositiveInt($input['item_id'] ?? null, 0),
            'sort_order' => $this->toInt($input['sort_order'] ?? null, 0),
            'is_active' => isset($input['is_active']) ? 1 : 0,
        ];
    }

    private function normalizedCtaUrl(mixed $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return null;
        }

        if ($url[0] !== '/' && strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            return null;
        }

        return mb_substr($url, 0, 255);
    }

    private function nullableString(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function toInt(mixed $value, int $fallback): int
    {
        $normalized = trim((string) $value);
        if ($normalized === '' || !preg_match('/^-?\d+$/', $normalized)) {
            return $fallback;
        }

        return (int) $normalized;
    }

    private function toPositiveInt(mixed $value, int $fallback): int
    {
        $parsed = $this->toInt($value, $fallback);

        return $parsed > 0 ? $parsed : $fallback;
    }
}
