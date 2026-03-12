<?php

declare(strict_types=1);

namespace App\Modules\Import\Services\SupplierParsers;

abstract class AbstractJsonLdProductParser implements SupplierProductParserInterface
{
    /** @return array<string,mixed>|null */
    protected function extractJsonLdProduct(string $html): ?array
    {
        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches) < 1 || empty($matches[1])) {
            return null;
        }

        foreach ($matches[1] as $scriptBody) {
            $decoded = json_decode(trim((string) $scriptBody), true);
            if (!is_array($decoded)) {
                continue;
            }

            $product = $this->findProductNode($decoded);
            if ($product !== null) {
                return $product;
            }
        }

        return null;
    }

    /** @param array<string,mixed> $node */
    protected function findProductNode(array $node): ?array
    {
        $type = strtolower((string) ($node['@type'] ?? ''));
        if ($type === 'product') {
            return $node;
        }

        if (isset($node['@graph']) && is_array($node['@graph'])) {
            foreach ($node['@graph'] as $child) {
                if (is_array($child)) {
                    $found = $this->findProductNode($child);
                    if ($found !== null) {
                        return $found;
                    }
                }
            }
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                $found = $this->findProductNode($child);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    protected function findMetaContent(string $html, string $name): ?string
    {
        if (preg_match('/<meta[^>]+(?:name|property)=["\']' . preg_quote($name, '/') . '["\'][^>]+content=["\'](.*?)["\']/is', $html, $matches) === 1) {
            return $this->cleanText($matches[1]);
        }

        return null;
    }

    protected function cleanText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleaned = trim((string) (preg_replace('/\s+/u', ' ', $decoded) ?? $decoded));

        return $cleaned === '' ? null : $cleaned;
    }

    protected function normalizePrice(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $numeric = preg_replace('/[^\d\.,-]/', '', (string) $value);
        $numeric = str_replace(',', '.', (string) $numeric);
        if ($numeric === '' || !is_numeric($numeric)) {
            return null;
        }

        return number_format((float) $numeric, 2, '.', '');
    }
}
