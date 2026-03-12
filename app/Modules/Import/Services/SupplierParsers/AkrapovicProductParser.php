<?php

declare(strict_types=1);

namespace App\Modules\Import\Services\SupplierParsers;

final class AkrapovicProductParser extends AbstractJsonLdProductParser
{
    public function getParserKey(): string
    {
        return 'akrapovic_product_v1';
    }

    public function getParserVersion(): string
    {
        return '1.0.0';
    }

    public function supportsDomain(string $domain): bool
    {
        $normalized = strtolower(trim($domain));

        return $normalized === 'www.akrapovic.com' || $normalized === 'akrapovic.com';
    }

    public function parse(string $url, string $html): array
    {
        $product = $this->extractJsonLdProduct($html) ?? [];

        $brand = $this->cleanText((string) (($product['brand']['name'] ?? null) ?: 'Akrapovič'));
        $title = $this->cleanText((string) ($product['name'] ?? $this->findMetaContent($html, 'og:title') ?? ''));
        $sku = $this->cleanText((string) ($product['sku'] ?? $product['mpn'] ?? ''));
        $description = $this->cleanText((string) ($product['description'] ?? $this->findMetaContent($html, 'description') ?? ''));
        $offers = is_array($product['offers'] ?? null) ? $product['offers'] : [];

        $images = $product['image'] ?? [];
        if (is_string($images)) {
            $images = [$images];
        }

        $fields = [
            'title' => $title,
            'brand' => $brand,
            'sku' => $sku,
            'description' => $description,
            'price' => $this->normalizePrice($offers['price'] ?? null),
            'currency' => $this->cleanText((string) ($offers['priceCurrency'] ?? '')),
            'stock_text' => $this->cleanText((string) ($offers['availability'] ?? '')),
            'image_urls' => array_values(array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : '', is_array($images) ? $images : []))),
            'attributes' => [
                'mpn' => $this->cleanText((string) ($product['mpn'] ?? '')),
                'gtin' => $this->cleanText((string) ($product['gtin'] ?? '')),
            ],
        ];

        $ok = trim((string) ($fields['title'] ?? '')) !== '' || trim((string) ($fields['sku'] ?? '')) !== '';

        return [
            'ok' => $ok,
            'fields' => $fields,
            'raw_text' => $description,
            'metadata' => [
                'matched_domain' => 'akrapovic.com',
                'source' => 'json_ld+meta',
            ],
            'error' => $ok ? null : 'Parsern hittade inte tillräcklig produktdata.',
        ];
    }
}
