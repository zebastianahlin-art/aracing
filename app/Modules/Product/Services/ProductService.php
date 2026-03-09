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

    /** @param array<string, string> $filters
     *  @return array{rows: array<int, array<string, mixed>>, filters: array<string, string>}
     */
    public function operationalOverview(array $filters): array
    {
        $normalized = $this->normalizeOverviewFilters($filters);
        $rows = $this->products->adminOverview($normalized);

        foreach ($rows as &$row) {
            $row['has_supplier_link'] = $row['supplier_link_id'] !== null;
            $row['deviation_flags'] = $this->buildDeviationFlags($row);
        }
        unset($row);

        return ['rows' => $rows, 'filters' => $normalized];
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

    public function syncPrimarySnapshot(int $productId): void
    {
        $this->supplierLinks->syncPrimarySnapshot($productId);
    }

    public function copySupplierPriceToPublished(int $productId): void
    {
        $link = $this->supplierLinks->primaryLinkForProduct($productId);
        if ($link === null || $link['supplier_price_snapshot'] === null) {
            return;
        }

        $this->products->updateSalePrice($productId, number_format((float) $link['supplier_price_snapshot'], 2, '.', ''));
    }

    public function copySupplierStockToPublished(int $productId): void
    {
        $link = $this->supplierLinks->primaryLinkForProduct($productId);
        if ($link === null) {
            return;
        }

        $stock = $link['supplier_stock_snapshot'];
        $this->products->updateStockQuantity($productId, $stock !== null ? (int) $stock : null);
    }

    public function refreshPublishedStockStatusFromQuantity(int $productId): void
    {
        $product = $this->products->findById($productId);
        if ($product === null) {
            return;
        }

        $status = $this->deriveStockStatusFromQuantity($product['stock_quantity']);
        $this->products->updateStockStatus($productId, $status);
    }

    public function setActiveStatus(int $productId, bool $active): void
    {
        $this->products->updateActiveStatus($productId, $active ? 1 : 0);
    }

    /** @param array<int, int> $productIds */
    public function applyBulkOperation(array $productIds, string $operation): void
    {
        foreach ($productIds as $productId) {
            if ($operation === 'set_active') {
                $this->setActiveStatus($productId, true);
                continue;
            }

            if ($operation === 'set_inactive') {
                $this->setActiveStatus($productId, false);
                continue;
            }

            if ($operation === 'refresh_stock_status') {
                $this->refreshPublishedStockStatusFromQuantity($productId);
                continue;
            }

            if ($operation === 'sync_snapshot') {
                $this->syncPrimarySnapshot($productId);
            }
        }
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

    /** @param array<string, string> $filters
     *  @return array<string, string>
     */
    private function normalizeOverviewFilters(array $filters): array
    {
        $active = (string) ($filters['active'] ?? '');
        $hasLink = (string) ($filters['has_link'] ?? '');
        $deviation = (string) ($filters['deviation'] ?? '');
        $stockStatus = mb_strtolower(trim((string) ($filters['stock_status'] ?? '')));

        return [
            'name' => trim((string) ($filters['name'] ?? '')),
            'sku' => trim((string) ($filters['sku'] ?? '')),
            'active' => in_array($active, ['0', '1'], true) ? $active : '',
            'has_link' => in_array($hasLink, ['0', '1'], true) ? $hasLink : '',
            'deviation' => $deviation === '1' ? '1' : '',
            'stock_status' => in_array($stockStatus, ['i lager', 'låg lagerstatus', 'slut i lager', 'okänd'], true) ? $stockStatus : '',
        ];
    }

    /** @param array<string, mixed> $row
     *  @return array<int, string>
     */
    private function buildDeviationFlags(array $row): array
    {
        $flags = [];

        if ($row['supplier_link_id'] === null) {
            $flags[] = 'saknar leverantörskoppling';
        }

        if ($row['sale_price'] === null) {
            $flags[] = 'saknar sale_price';
        }

        if ((int) ($row['is_active'] ?? 0) === 0) {
            $flags[] = 'inaktiv produkt';
        }

        if ($row['sale_price'] !== null && $row['supplier_price_snapshot'] !== null && (float) $row['sale_price'] !== (float) $row['supplier_price_snapshot']) {
            $flags[] = 'pris avviker';
        }

        $stockDiffers = false;
        if ($row['stock_quantity'] === null xor $row['supplier_stock_snapshot'] === null) {
            $stockDiffers = true;
        }

        if ($row['stock_quantity'] !== null && $row['supplier_stock_snapshot'] !== null && (int) $row['stock_quantity'] !== (int) $row['supplier_stock_snapshot']) {
            $stockDiffers = true;
        }

        if ($stockDiffers) {
            $flags[] = 'lager avviker';
        }

        return $flags;
    }

    private function deriveStockStatusFromQuantity(mixed $quantity): string
    {
        if ($quantity === null) {
            return 'okänd';
        }

        $qty = (int) $quantity;
        if ($qty <= 0) {
            return 'slut i lager';
        }

        if ($qty <= 3) {
            return 'låg lagerstatus';
        }

        return 'i lager';
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
