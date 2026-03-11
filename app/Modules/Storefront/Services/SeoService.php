<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Services;

final class SeoService
{
    public function __construct(private readonly string $siteName = 'A-Racing')
    {
    }

    /** @param array<string,mixed>|null $product
     *  @return array{title:string,description:?string,canonical:string,robots:string}
     */
    public function forProduct(?array $product, string $path): array
    {
        $name = trim((string) ($product['name'] ?? 'Produkt'));

        return $this->build([
            'seo_title' => $product['seo_title'] ?? null,
            'title' => $name,
            'seo_description' => $product['seo_description'] ?? null,
            'description_source' => $product['description'] ?? null,
            'canonical_url' => $product['canonical_url'] ?? null,
            'default_canonical_path' => $path,
            'meta_robots' => $product['meta_robots'] ?? null,
            'is_indexable' => $product['is_indexable'] ?? 1,
        ]);
    }

    /** @param array<string,mixed>|null $category */
    public function forCategory(?array $category, string $path, bool $hasSecondaryFilters): array
    {
        $meta = $this->build([
            'seo_title' => $category['seo_title'] ?? null,
            'title' => trim((string) ($category['name'] ?? 'Kategori')),
            'seo_description' => $category['seo_description'] ?? null,
            'description_source' => null,
            'canonical_url' => $category['canonical_url'] ?? null,
            'default_canonical_path' => $path,
            'meta_robots' => $category['meta_robots'] ?? null,
            'is_indexable' => $category['is_indexable'] ?? 1,
        ]);

        if ($hasSecondaryFilters) {
            $meta['robots'] = 'noindex,follow';
        }

        return $meta;
    }

    /** @param array<string,mixed>|null $page */
    public function forCmsPage(?array $page, string $path): array
    {
        return $this->build([
            'seo_title' => $page['meta_title'] ?? null,
            'title' => trim((string) ($page['title'] ?? 'Informationssida')),
            'seo_description' => $page['meta_description'] ?? null,
            'description_source' => $page['content_html'] ?? null,
            'canonical_url' => $page['canonical_url'] ?? null,
            'default_canonical_path' => $path,
            'meta_robots' => $page['meta_robots'] ?? null,
            'is_indexable' => $page['is_indexable'] ?? 1,
        ]);
    }

    public function forSearch(string $path): array
    {
        return [
            'title' => 'Sök | ' . $this->siteName,
            'description' => null,
            'canonical' => $this->toAbsoluteUrl($path),
            'robots' => 'noindex,follow',
        ];
    }

    public function forStaticPage(string $title, string $path, bool $indexable = true): array
    {
        return [
            'title' => $title . ' | ' . $this->siteName,
            'description' => null,
            'canonical' => $this->toAbsoluteUrl($path),
            'robots' => $indexable ? 'index,follow' : 'noindex,follow',
        ];
    }

    /** @param array<string,mixed> $input
     * @return array{title:string,description:?string,canonical:string,robots:string}
     */
    private function build(array $input): array
    {
        $titleBase = $this->cleanText((string) ($input['seo_title'] ?? '')) ?: $this->cleanText((string) ($input['title'] ?? ''));
        if ($titleBase === '') {
            $titleBase = $this->siteName;
        }

        $title = $titleBase;
        if (!str_contains(mb_strtolower($titleBase), mb_strtolower($this->siteName))) {
            $title .= ' | ' . $this->siteName;
        }

        $description = $this->cleanText((string) ($input['seo_description'] ?? ''));
        if ($description === '') {
            $description = $this->fallbackDescription((string) ($input['description_source'] ?? ''));
        }

        $canonical = $this->resolveCanonical((string) ($input['canonical_url'] ?? ''), (string) ($input['default_canonical_path'] ?? '/'));

        $robots = $this->resolveRobots((string) ($input['meta_robots'] ?? ''), (int) ($input['is_indexable'] ?? 1));

        return [
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'canonical' => $canonical,
            'robots' => $robots,
        ];
    }

    private function resolveCanonical(string $explicitCanonical, string $defaultPath): string
    {
        $canonical = trim($explicitCanonical);
        if ($canonical === '') {
            return $this->toAbsoluteUrl($defaultPath);
        }

        if (preg_match('#^https?://#i', $canonical) === 1) {
            return $canonical;
        }

        if (str_starts_with($canonical, '/')) {
            return $this->toAbsoluteUrl($canonical);
        }

        return $this->toAbsoluteUrl($defaultPath);
    }

    private function resolveRobots(string $metaRobots, int $isIndexable): string
    {
        $robots = mb_strtolower(trim($metaRobots));
        if ($robots !== '') {
            return $robots;
        }

        return $isIndexable === 0 ? 'noindex,follow' : 'index,follow';
    }

    private function fallbackDescription(string $content): string
    {
        $text = $this->cleanText($content);
        if ($text === '') {
            return '';
        }

        return mb_substr($text, 0, 160);
    }

    private function cleanText(string $value): string
    {
        $stripped = strip_tags($value);
        $stripped = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $stripped = preg_replace('/\s+/u', ' ', $stripped) ?? '';

        return trim($stripped);
    }

    private function toAbsoluteUrl(string $path): string
    {
        $normalizedPath = str_starts_with($path, '/') ? $path : '/' . ltrim($path, '/');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000'));

        return $scheme . '://' . $host . $normalizedPath;
    }
}
