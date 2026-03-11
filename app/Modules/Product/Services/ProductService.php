<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Product\Repositories\ProductAttributeRepository;
use App\Modules\Product\Repositories\ProductImageRepository;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Repositories\ProductSupplierItemLookupRepository;
use App\Modules\Redirect\Services\RedirectService;
use App\Shared\Support\Slugger;

final class ProductService
{
    private const ALLOWED_META_ROBOTS = [
        'index,follow',
        'noindex,follow',
    ];
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductAttributeRepository $attributes,
        private readonly ProductImageRepository $images,
        private readonly ProductSupplierLinkService $supplierLinks,
        private readonly ProductSupplierItemLookupRepository $supplierItems,
        private readonly InventoryService $inventory,
        private readonly RedirectService $redirects
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



    /** @return array<int, array<string, mixed>> */
    public function searchForSupplierMatch(string $query): array
    {
        return $this->products->searchForSupplierMatch($query);
    }

    /** @return array<int, array<string, mixed>> */
    public function searchForRelationSelection(string $query, int $excludeProductId): array
    {
        $rows = $this->products->searchForSupplierMatch($query);

        return array_values(array_filter($rows, static fn (array $row): bool => (int) $row['id'] !== $excludeProductId));
    }

    /** @return array{supplier_item: array<string,mixed>, product_defaults: array<string,mixed>, source_data_gaps: array<int,string>, product_data_gaps: array<int,string>}|null */
    public function prefillDraftFromSupplierItem(int $supplierItemId): ?array
    {
        $item = $this->supplierItems->findById($supplierItemId);
        if ($item === null) {
            return null;
        }

        $name = trim((string) ($item['supplier_title'] ?? ''));
        $sku = trim((string) ($item['supplier_sku'] ?? ''));

        $defaults = [
            'name' => $name,
            'sku' => $sku,
            'description' => '',
            'sale_price' => '',
            'stock_status' => '',
            'stock_quantity' => '',
            'backorder_allowed' => 0,
            'currency_code' => 'SEK',
            'is_active' => 0,
            'is_search_hidden' => 0,
            'is_featured' => 0,
            'search_boost' => 0,
            'sort_priority' => 0,
            'supplier_item_id' => (int) $item['id'],
            'link_is_primary' => 1,
        ];

        $sourceGaps = [];
        if ($name === '') {
            $sourceGaps[] = 'missing_supplier_title';
        }
        if ($sku === '') {
            $sourceGaps[] = 'missing_supplier_sku';
        }
        if ($item['price'] === null) {
            $sourceGaps[] = 'missing_supplier_price';
        }
        if ($item['stock_qty'] === null) {
            $sourceGaps[] = 'missing_supplier_stock';
        }

        return [
            'supplier_item' => $item,
            'product_defaults' => $defaults,
            'source_data_gaps' => $sourceGaps,
            'product_data_gaps' => $this->computeProductDataGaps($defaults + ['brand_id' => null, 'category_id' => null]),
        ];
    }

    /** @param array<string,mixed> $filters
     * @return array{rows: array<int,array<string,mixed>>, filters: array<string,string>}
     */
    public function articleCareQueue(array $filters): array
    {
        $normalized = $this->normalizeArticleCareFilters($filters);
        $rows = $this->products->articleCareQueue($normalized);

        foreach ($rows as &$row) {
            $row['care_gaps'] = $this->computeProductDataGaps($row);
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
        if (array_key_exists('images', $input)) {
            $this->images->replaceForProduct($id, $this->parseImages($input['images'] ?? ''));
        }
        $this->supplierLinks->syncPrimaryFromInput($id, $input);

        return $id;
    }

    /** @param array<string, string> $input */
    public function update(int $id, array $input): void
    {
        $existing = $this->products->findById($id);
        $data = $this->normalizeData($input);
        $this->products->update($id, $data);
        $this->attributes->replaceForProduct($id, $this->parseAttributes($input['attributes'] ?? ''));
        if (array_key_exists('images', $input)) {
            $this->images->replaceForProduct($id, $this->parseImages($input['images'] ?? ''));
        }
        $this->supplierLinks->syncPrimaryFromInput($id, $input);

        if ($existing !== null && (string) ($existing['slug'] ?? '') !== (string) $data['slug']) {
            $this->redirects->createSlugChangeRedirect(
                '/product/' . (string) $existing['slug'],
                '/product/' . (string) $data['slug'],
                'Auto: produktslug uppdaterad.'
            );
        }
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
        $current = $this->products->findById($productId);
        if ($current === null) {
            return;
        }

        $newQuantity = $stock !== null ? max(0, (int) $stock) : 0;
        $this->products->updateStockQuantity($productId, $newQuantity);
        $this->inventory->logStockSync($productId, (int) ($current['stock_quantity'] ?? 0), $newQuantity, 'Synk från supplier snapshot.');
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

    /** @return array<int,array<string,mixed>> */
    public function stockMovements(int $productId): array
    {
        return $this->inventory->stockMovementsForProduct($productId);
    }

    /** @param array<string,string> $input */
    public function manualStockAdjustment(int $productId, array $input): void
    {
        $mode = (string) ($input['stock_adjustment_mode'] ?? 'set');
        $comment = trim((string) ($input['stock_comment'] ?? ''));

        if ($mode === 'delta') {
            $delta = (int) ($input['stock_delta'] ?? 0);
            $this->inventory->manualAdjustStock($productId, $delta, $comment !== '' ? $comment : null);

            return;
        }

        $quantity = max(0, (int) ($input['stock_quantity'] ?? 0));
        $status = (string) ($input['stock_status'] ?? 'out_of_stock');
        $backorderAllowed = isset($input['backorder_allowed']) && (string) $input['backorder_allowed'] === '1';

        $this->inventory->manualSetStock($productId, $quantity, $status, $backorderAllowed, $comment !== '' ? $comment : null);
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
            'seo_title' => $this->nullableString($input['seo_title'] ?? null, 255),
            'seo_description' => $this->nullableString($input['seo_description'] ?? null),
            'canonical_url' => $this->normalizeCanonicalUrl($input['canonical_url'] ?? null),
            'meta_robots' => $this->normalizeMetaRobots($input['meta_robots'] ?? null),
            'is_indexable' => isset($input['is_indexable']) ? 1 : 0,
            'sale_price' => $this->toNullableDecimal($input['sale_price'] ?? null),
            'currency_code' => $this->normalizeCurrencyCode($input['currency_code'] ?? null),
            'stock_status' => $this->normalizeStockStatus($input['stock_status'] ?? null),
            'stock_quantity' => max(0, (int) ($this->toNullableInt($input['stock_quantity'] ?? null) ?? 0)),
            'backorder_allowed' => isset($input['backorder_allowed']) ? 1 : 0,
            'stock_updated_at' => date('Y-m-d H:i:s'),
            'is_active' => isset($input['is_active']) ? 1 : 0,
            'is_search_hidden' => isset($input['is_search_hidden']) ? 1 : 0,
            'is_featured' => isset($input['is_featured']) ? 1 : 0,
            'search_boost' => $this->toIntInRange($input['search_boost'] ?? 0, -1000, 1000),
            'sort_priority' => $this->toIntInRange($input['sort_priority'] ?? 0, -1000, 1000),
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
        $lowStock = (string) ($filters['low_stock'] ?? '');
        $stockStatus = mb_strtolower(trim((string) ($filters['stock_status'] ?? '')));
        $featured = (string) ($filters['featured'] ?? '');
        $hidden = (string) ($filters['hidden'] ?? '');

        return [
            'name' => trim((string) ($filters['name'] ?? '')),
            'sku' => trim((string) ($filters['sku'] ?? '')),
            'active' => in_array($active, ['0', '1'], true) ? $active : '',
            'has_link' => in_array($hasLink, ['0', '1'], true) ? $hasLink : '',
            'deviation' => $deviation === '1' ? '1' : '',
            'low_stock' => $lowStock === '1' ? '1' : '',
            'stock_status' => in_array($stockStatus, ['in_stock', 'out_of_stock', 'backorder'], true) ? $stockStatus : '',
            'featured' => in_array($featured, ['0', '1'], true) ? $featured : '',
            'hidden' => in_array($hidden, ['0', '1'], true) ? $hidden : '',
        ];
    }

    /** @param array<string,mixed> $filters
     * @return array<string,string>
     */
    private function normalizeArticleCareFilters(array $filters): array
    {
        $active = (string) ($filters['active'] ?? '');
        $hasLink = (string) ($filters['has_supplier_link'] ?? '');
        $gap = trim((string) ($filters['gap'] ?? ''));
        $allowedGaps = [
            'missing_brand',
            'missing_category',
            'missing_sale_price',
            'missing_description',
            'missing_image',
            'missing_supplier_link',
            'inactive',
        ];

        return [
            'name' => trim((string) ($filters['name'] ?? '')),
            'sku' => trim((string) ($filters['sku'] ?? '')),
            'active' => in_array($active, ['0', '1'], true) ? $active : '',
            'has_supplier_link' => in_array($hasLink, ['0', '1'], true) ? $hasLink : '',
            'gap' => in_array($gap, $allowedGaps, true) ? $gap : '',
        ];
    }

    /** @param array<string,mixed> $row
     * @return array<int,string>
     */
    private function computeProductDataGaps(array $row): array
    {
        $gaps = [];

        if (($row['brand_id'] ?? null) === null) {
            $gaps[] = 'missing_brand';
        }
        if (($row['category_id'] ?? null) === null) {
            $gaps[] = 'missing_category';
        }
        if (($row['sale_price'] ?? null) === null || trim((string) $row['sale_price']) === '') {
            $gaps[] = 'missing_sale_price';
        }
        if (trim((string) ($row['description'] ?? '')) === '') {
            $gaps[] = 'missing_description';
        }
        if ((int) ($row['has_image'] ?? 0) === 0) {
            $gaps[] = 'missing_image';
        }
        if ((int) ($row['has_supplier_link'] ?? 0) === 0 && ($row['supplier_link_id'] ?? null) === null) {
            $gaps[] = 'missing_supplier_link';
        }
        if ((int) ($row['is_active'] ?? 0) === 0) {
            $gaps[] = 'inactive';
        }

        return $gaps;
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
            return 'out_of_stock';
        }

        $qty = (int) $quantity;
        if ($qty <= 0) {
            return 'out_of_stock';
        }

        return 'in_stock';
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

    private function normalizeStockStatus(?string $value): string
    {
        $allowed = ['in_stock', 'out_of_stock', 'backorder'];
        $normalized = mb_strtolower(trim((string) $value));

        return in_array($normalized, $allowed, true) ? $normalized : 'out_of_stock';
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


    private function toIntInRange(mixed $value, int $min, int $max): int
    {
        if ($value === null || trim((string) $value) === "") {
            return 0;
        }

        return max($min, min($max, (int) $value));
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (int) $value;
    }
}
