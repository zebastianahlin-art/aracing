<?php

declare(strict_types=1);

namespace App\Modules\Import\Services\SupplierParsers;

final class HksProductParser extends AbstractJsonLdProductParser
{
    public function getParserKey(): string
    {
        return 'hks_product_v1';
    }

    public function getParserVersion(): string
    {
        return '1.0.0';
    }

    public function supportsDomain(string $domain): bool
    {
        $normalized = strtolower(trim($domain));

        return $normalized === 'www.hks-power.co.jp' || $normalized === 'hks-power.co.jp';
    }

    public function parse(string $url, string $html): array
    {
        $product = $this->extractJsonLdProduct($html) ?? [];

        $title = $this->cleanText((string) ($product['name'] ?? $this->findMetaContent($html, 'og:title') ?? ''));
        $description = $this->cleanText((string) ($product['description'] ?? $this->findMetaContent($html, 'description') ?? ''));

        if ($title === null && preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches) === 1) {
            $title = $this->cleanText($matches[1]);
        }

        $fields = [
            'title' => $title,
            'brand' => 'HKS',
            'sku' => $this->cleanText((string) ($product['sku'] ?? $product['mpn'] ?? '')),
            'description' => $description,
            'price' => null,
            'currency' => null,
            'stock_text' => null,
            'image_urls' => [],
            'attributes' => [
                'mpn' => $this->cleanText((string) ($product['mpn'] ?? '')),
            ],
        ];

        $ok = trim((string) ($fields['title'] ?? '')) !== '' || trim((string) ($fields['sku'] ?? '')) !== '';

        return [
            'ok' => $ok,
            'fields' => $fields,
            'raw_text' => $description,
            'metadata' => [
                'matched_domain' => 'hks-power.co.jp',
                'source' => 'json_ld+meta+h1',
            ],
            'error' => $ok ? null : 'Parsern hittade inte tillräcklig produktdata.',
        ];
    }
}
