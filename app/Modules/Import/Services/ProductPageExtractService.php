<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

final class ProductPageExtractService
{
    /** @return array<string,mixed> */
    public function extract(string $url, string $html): array
    {
        $title = $this->extractTitle($html);
        $metaDescription = $this->extractMetaDescription($html);
        $visibleText = $this->extractVisibleText($html);
        $images = $this->extractImageUrls($url, $html);

        $rawText = trim($title . "\n\n" . $metaDescription . "\n\n" . $visibleText);

        return [
            'title' => $title,
            'meta_description' => $metaDescription,
            'visible_text' => $visibleText,
            'raw_text' => mb_substr($rawText, 0, 40000),
            'images' => $images,
        ];
    }

    private function extractTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches) === 1) {
            return $this->cleanText($matches[1]);
        }

        return '';
    }

    private function extractMetaDescription(string $html): string
    {
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/is', $html, $matches) === 1) {
            return $this->cleanText($matches[1]);
        }

        return '';
    }

    /** @return array<int,string> */
    private function extractImageUrls(string $url, string $html): array
    {
        $urls = [];
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches) === false) {
            return [];
        }

        $base = parse_url($url);
        $scheme = (string) ($base['scheme'] ?? 'https');
        $host = (string) ($base['host'] ?? '');

        foreach ($matches[1] as $src) {
            $src = trim((string) $src);
            if ($src === '' || str_starts_with($src, 'data:')) {
                continue;
            }

            if (str_starts_with($src, '//')) {
                $src = $scheme . ':' . $src;
            } elseif (str_starts_with($src, '/')) {
                $src = $scheme . '://' . $host . $src;
            }

            if (preg_match('/^https?:\/\//i', $src) !== 1) {
                continue;
            }

            $urls[] = $src;
        }

        return array_values(array_slice(array_unique($urls), 0, 15));
    }

    private function extractVisibleText(string $html): string
    {
        $withoutScripts = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', ' ', $html) ?? $html;
        $text = strip_tags($withoutScripts);

        return mb_substr($this->cleanText($text), 0, 35000);
    }

    private function cleanText(string $text): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $collapsed = preg_replace('/\s+/u', ' ', $decoded) ?? $decoded;

        return trim($collapsed);
    }
}
