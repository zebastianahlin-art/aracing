<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Modules\Product\Repositories\ProductAttributeRepository;
use App\Modules\Product\Repositories\ProductImageRepository;
use App\Modules\Product\Repositories\ProductRepository;
use App\Shared\Support\Slugger;

final class ProductService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductAttributeRepository $attributes,
        private readonly ProductImageRepository $images,
        private readonly ProductSupplierLinkService $supplierLinks
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->products->all();
    }

    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        $product = $this->products->findById($id);
        if ($product === null) {
            return null;
        }

        $product['attributes'] = $this->attributes->byProductId($id);
        $product['images'] = $this->images->byProductId($id);
        $product['primary_supplier_link'] = $this->supplierLinks->primaryLinkForProduct($id);

        return $product;
    }

    /** @param array<string, string> $input */
    public function create(array $input): int
    {
        $data = $this->normalizeData($input);
        $id = $this->products->create($data);
        $this->attributes->replaceForProduct($id, $this->parseAttributes($input['attributes'] ?? ''));
        $this->images->replaceForProduct($id, $this->parseImages($input['images'] ?? ''));
        $this->supplierLinks->syncPrimaryFromInput($id, $input);

        return $id;
    }

    /** @param array<string, string> $input */
    public function update(int $id, array $input): void
    {
        $data = $this->normalizeData($input);
        $this->products->update($id, $data);
        $this->attributes->replaceForProduct($id, $this->parseAttributes($input['attributes'] ?? ''));
        $this->images->replaceForProduct($id, $this->parseImages($input['images'] ?? ''));
        $this->supplierLinks->syncPrimaryFromInput($id, $input);
    }

    /** @return array<string, mixed> */
    private function normalizeData(array $input): array
    {
        $name = trim($input['name'] ?? '');

        return [
            'brand_id' => $this->toNullableInt($input['brand_id'] ?? null),
            'category_id' => $this->toNullableInt($input['category_id'] ?? null),
            'name' => $name,
            'slug' => Slugger::slugify($input['slug'] ?? $name),
            'sku' => trim($input['sku'] ?? ''),
            'description' => trim($input['description'] ?? ''),
            'sale_price' => $this->toNullableDecimal($input['sale_price'] ?? null),
            'currency_code' => $this->normalizeCurrencyCode($input['currency_code'] ?? null),
            'stock_status' => $this->normalizeStockStatus($input['stock_status'] ?? null),
            'stock_quantity' => $this->toNullableInt($input['stock_quantity'] ?? null),
            'is_active' => isset($input['is_active']) ? 1 : 0,
        ];
    }

    private function toNullableDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || is_numeric($normalized) === false) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function normalizeCurrencyCode(?string $value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized !== '' ? substr($normalized, 0, 10) : 'SEK';
    }

    private function normalizeStockStatus(?string $value): ?string
    {
        $allowed = ['i lager', 'låg lagerstatus', 'slut i lager', 'okänd'];
        $normalized = mb_strtolower(trim((string) $value));

        return in_array($normalized, $allowed, true) ? $normalized : null;
    }

    /** @return array<int, array{attribute_key:string, attribute_value:string}> */
    private function parseAttributes(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $attributes = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('|', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);

            if ($key === '' || $value === '') {
                continue;
            }

            $attributes[] = ['attribute_key' => $key, 'attribute_value' => $value];
        }

        return $attributes;
    }

    /** @return array<int, array{image_url:string, alt_text:string, sort_order:int, is_primary:int}> */
    private function parseImages(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $images = [];
        $primaryMarked = false;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            [$url, $alt, $sort, $primary] = array_pad(explode('|', $line, 4), 4, '');
            $url = trim($url);

            if ($url === '') {
                continue;
            }

            $isPrimary = trim($primary) === '1' ? 1 : 0;
            if ($isPrimary === 1) {
                $primaryMarked = true;
            }

            $images[] = [
                'image_url' => $url,
                'alt_text' => trim($alt),
                'sort_order' => trim($sort) !== '' ? (int) $sort : 0,
                'is_primary' => $isPrimary,
            ];
        }

        if ($images !== [] && $primaryMarked === false) {
            $images[0]['is_primary'] = 1;
        }

        return $images;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (int) $value;
    }
}
