<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Services;

use App\Modules\Category\Repositories\CategoryRepository;
use App\Modules\Cms\Repositories\CmsPageRepository;
use App\Modules\Product\Repositories\ProductRepository;

final class SitemapService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
        private readonly CmsPageRepository $pages
    ) {
    }

    public function sitemapIndexXml(): string
    {
        $entries = [
            ['loc' => $this->absoluteUrl('/sitemaps/products.xml'), 'lastmod' => $this->latestLastmod($this->products->sitemapIndexableProducts())],
            ['loc' => $this->absoluteUrl('/sitemaps/categories.xml'), 'lastmod' => $this->latestLastmod($this->categories->sitemapIndexableCategories())],
            ['loc' => $this->absoluteUrl('/sitemaps/pages.xml'), 'lastmod' => $this->latestLastmod($this->pages->sitemapIndexablePages())],
        ];

        return $this->renderSitemapIndex($entries);
    }

    public function productSitemapXml(): string
    {
        return $this->renderUrlSet($this->toUrlRows($this->products->sitemapIndexableProducts(), '/product/'));
    }

    public function categorySitemapXml(): string
    {
        return $this->renderUrlSet($this->toUrlRows($this->categories->sitemapIndexableCategories(), '/category/'));
    }

    public function pageSitemapXml(): string
    {
        $rows = $this->toUrlRows($this->pages->sitemapIndexablePages(), '/pages/');
        array_unshift($rows, ['loc' => $this->absoluteUrl('/'), 'lastmod' => null]);

        return $this->renderUrlSet($rows);
    }

    /** @param array<int, array{slug:string, updated_at:?string}> $rows
     * @return array<int, array{loc:string, lastmod:?string}>
     */
    private function toUrlRows(array $rows, string $prefix): array
    {
        $urls = [];

        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $path = $prefix . rawurlencode($slug);
            $url = $this->absoluteUrl($path);
            $urls[$url] = [
                'loc' => $url,
                'lastmod' => $this->toW3cDate($row['updated_at'] ?? null),
            ];
        }

        return array_values($urls);
    }

    /** @param array<int, array{loc:string,lastmod:?string}> $entries */
    private function renderSitemapIndex(array $entries): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($entries as $entry) {
            $xml[] = '  <sitemap>';
            $xml[] = '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            if ($entry['lastmod'] !== null) {
                $xml[] = '    <lastmod>' . $entry['lastmod'] . '</lastmod>';
            }
            $xml[] = '  </sitemap>';
        }

        $xml[] = '</sitemapindex>';

        return implode("\n", $xml) . "\n";
    }

    /** @param array<int, array{loc:string,lastmod:?string}> $entries */
    private function renderUrlSet(array $entries): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($entries as $entry) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            if ($entry['lastmod'] !== null) {
                $xml[] = '    <lastmod>' . $entry['lastmod'] . '</lastmod>';
            }
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml) . "\n";
    }

    /** @param array<int, array<string,mixed>> $rows */
    private function latestLastmod(array $rows): ?string
    {
        $latest = null;
        foreach ($rows as $row) {
            $candidate = $this->toW3cDate($row['updated_at'] ?? null);
            if ($candidate === null) {
                continue;
            }
            if ($latest === null || $candidate > $latest) {
                $latest = $candidate;
            }
        }

        return $latest;
    }

    private function toW3cDate(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    private function absoluteUrl(string $path): string
    {
        $normalizedPath = str_starts_with($path, '/') ? $path : '/' . ltrim($path, '/');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000'));

        return $scheme . '://' . $host . $normalizedPath;
    }
}
