<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Import\Repositories\SupplierItemRepository;
use App\Modules\Import\Repositories\SupplierItemReviewRepository;
use App\Modules\Product\Repositories\ProductSupplierItemLookupRepository;
use App\Modules\Product\Repositories\ProductSupplierLinkRepository;

final class SupplierItemReviewService
{
    public function __construct(
        private readonly SupplierItemReviewRepository $reviewQueue,
        private readonly SupplierItemRepository $supplierItems,
        private readonly ProductSupplierLinkRepository $links,
        private readonly ProductSupplierItemLookupRepository $lookup
    ) {
    }

    /** @param array<string, mixed> $input
     *  @return array{rows: array<int, array<string, mixed>>, filters: array<string, mixed>, quality: array<string, int>}
     */
    public function reviewQueue(array $input): array
    {
        $filters = $this->normalizeFilters($input);
        $rows = $this->reviewQueue->search($filters);

        $quality = [
            'total' => count($rows),
            'linked' => 0,
            'unmatched' => 0,
            'needs_review' => 0,
            'missing_title' => 0,
            'missing_sku' => 0,
            'missing_price' => 0,
            'missing_stock' => 0,
            'missing_product_link' => 0,
        ];

        foreach ($rows as &$row) {
            $status = $this->resolveStatus($row);
            $gaps = $this->dataGaps($row);

            $row['resolved_status'] = $status;
            $row['data_gaps'] = $gaps;

            $quality[$status]++;
            foreach ($gaps as $gap) {
                $quality[$gap]++;
            }
        }
        unset($row);

        return ['rows' => $rows, 'filters' => $filters, 'quality' => $quality];
    }

    public function matchToProduct(int $supplierItemId, int $productId): void
    {
        $item = $this->lookup->findById($supplierItemId);
        if ($item === null || $item['supplier_id'] === null) {
            throw new \RuntimeException('Ogiltig leverantörsartikel för matchning.');
        }

        $this->links->clearBySupplierItemId($supplierItemId);
        $this->links->upsertPrimary($productId, [
            'supplier_item_id' => $supplierItemId,
            'supplier_id' => (int) $item['supplier_id'],
            'is_primary' => 1,
            'supplier_sku_snapshot' => $item['supplier_sku'] ?: null,
            'supplier_title_snapshot' => $item['supplier_title'] ?: null,
            'supplier_price_snapshot' => $item['price'],
            'supplier_stock_snapshot' => $item['stock_qty'],
        ]);

        $this->supplierItems->setMatchedAt($supplierItemId, true);
        $this->supplierItems->updateReviewStatus($supplierItemId, 'linked', true);
    }

    public function clearMatch(int $supplierItemId): void
    {
        $this->links->clearBySupplierItemId($supplierItemId);
        $this->supplierItems->setMatchedAt($supplierItemId, false);
        $this->supplierItems->updateReviewStatus($supplierItemId, 'needs_review', true);
    }

    public function markReviewed(int $supplierItemId): void
    {
        $linked = $this->links->primaryForSupplierItem($supplierItemId) !== null;
        $this->supplierItems->updateReviewStatus($supplierItemId, $linked ? 'linked' : 'reviewed', true);
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'supplier_sku' => trim((string) ($filters['supplier_sku'] ?? '')),
            'supplier_title' => trim((string) ($filters['supplier_title'] ?? '')),
            'supplier_id' => $this->toNullableInt($filters['supplier_id'] ?? null),
            'import_run_id' => $this->toNullableInt($filters['import_run_id'] ?? null),
            'match_status' => trim((string) ($filters['match_status'] ?? '')),
            'data_gap' => trim((string) ($filters['data_gap'] ?? '')),
            'product_query' => trim((string) ($filters['product_query'] ?? '')),
        ];
    }

    /** @param array<string, mixed> $row */
    private function resolveStatus(array $row): string
    {
        if ($row['product_id'] !== null) {
            return 'linked';
        }

        if (($row['review_status'] ?? null) === 'needs_review') {
            return 'needs_review';
        }

        return 'unmatched';
    }

    /** @param array<string, mixed> $row
     * @return array<int, string>
     */
    private function dataGaps(array $row): array
    {
        $gaps = [];

        if (trim((string) ($row['supplier_title'] ?? '')) === '') {
            $gaps[] = 'missing_title';
        }
        if (trim((string) ($row['supplier_sku'] ?? '')) === '') {
            $gaps[] = 'missing_sku';
        }
        if ($row['price'] === null) {
            $gaps[] = 'missing_price';
        }
        if ($row['stock_qty'] === null) {
            $gaps[] = 'missing_stock';
        }
        if ($row['product_id'] === null) {
            $gaps[] = 'missing_product_link';
        }

        return $gaps;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '' || ctype_digit($normalized) === false) {
            return null;
        }

        return (int) $normalized;
    }
}
