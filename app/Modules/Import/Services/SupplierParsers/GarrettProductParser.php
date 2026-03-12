<?php

declare(strict_types=1);

namespace App\Modules\Import\Services\SupplierParsers;

final class GarrettProductParser extends AbstractJsonLdProductParser
{
    public function getParserKey(): string
    {
        return 'garrett_product_v1';
    }

    public function getParserVersion(): string
    {
        return '1.0.0';
    }

    public function supportsDomain(string $domain): bool
    {
        $normalized = strtolower(trim($domain));

        return $normalized === 'www.garrettmotion.com' || $normalized === 'garrettmotion.com';
    }

    public function parse(string $url, string $html): array
    {
        $product = $this->extractJsonLdProduct($html) ?? [];
        $title = $this->cleanText((string) ($product['name'] ?? $this->findMetaContent($html, 'og:title') ?? ''));
        $description = $this->cleanText((string) ($product['description'] ?? $this->findMetaContent($html, 'description') ?? ''));
        $sku = $this->cleanText((string) ($product['sku'] ?? $product['mpn'] ?? ''));

        $fields = [
            'title' => $title,
            'brand' => 'Garrett',
            'sku' => $sku,
            'description' => $description,
            'price' => null,
            'currency' => null,
            'stock_text' => null,
            'image_urls' => [],
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
                'matched_domain' => 'garrettmotion.com',
                'source' => 'json_ld+meta',
            ],
            'error' => $ok ? null : 'Parsern hittade inte tillräcklig produktdata.',
        ];
    }
}
