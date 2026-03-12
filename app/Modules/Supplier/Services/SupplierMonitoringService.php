<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Services;

use App\Modules\Supplier\Repositories\SupplierMonitoringRepository;

final class SupplierMonitoringService
{
    public function __construct(
        private readonly SupplierMonitoringRepository $monitoring,
        private readonly SupplierWatchlistService $watchlist,
    ) {
    }

    /** @param array<string, mixed> $input
     * @return array{rows: array<int, array<string, mixed>>, filters: array<string, mixed>, counts: array<string, int>}
     */
    public function deviations(array $input): array
    {
        $filters = $this->normalizeFilters($input);

        $rows = [];
        foreach ($this->monitoring->latestStates($filters['supplier_id'], $filters['linked_only']) as $state) {
            $rows = array_merge($rows, $this->stateDeviations($state));
        }

        foreach ($this->monitoring->latestCompletedRuns($filters['supplier_id']) as $run) {
            $supplierId = isset($run['supplier_id']) ? (int) $run['supplier_id'] : 0;
            $latestRunId = isset($run['latest_run_id']) ? (int) $run['latest_run_id'] : 0;
            if ($supplierId <= 0 || $latestRunId <= 0) {
                continue;
            }

            foreach ($this->monitoring->newlySeenForRun($supplierId, $latestRunId, $filters['linked_only']) as $newlySeen) {
                $rows[] = $this->buildAssortmentDeviation('newly_seen_item', $newlySeen);
            }

            foreach ($this->monitoring->missingInRecentImportForRun($supplierId, $latestRunId, $filters['linked_only']) as $missing) {
                $rows[] = $this->buildAssortmentDeviation('missing_in_recent_import', $missing);
            }
        }

        $rows = array_values(array_filter($rows, fn (array $row): bool => $this->matchesFilter($row, $filters)));
        $rows = $this->watchlist->attachWatchSignalsToMonitoringRows($rows);
        usort($rows, static fn (array $a, array $b): int => strcmp((string) $b['detected_at'], (string) $a['detected_at']));

        $counts = $this->counts($rows);

        return [
            'rows' => $rows,
            'filters' => $filters,
            'counts' => $counts,
        ];
    }

    /**
     * @return array{price_change_pressure_count:int,availability_drop_count:int,catalog_gap_count:int}
     */
    public function alertSummary(): array
    {
        $payload = $this->deviations(['linked_only' => '1']);
        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];

        $summary = [
            'price_change_pressure_count' => 0,
            'availability_drop_count' => 0,
            'catalog_gap_count' => 0,
        ];

        $watchSummary = [
            'watchlist_price_change_pressure_count' => 0,
            'watchlist_availability_drop_count' => 0,
            'watchlist_catalog_gap_count' => 0,
            'watchlist_critical_count' => 0,
            'watchlist_high_count' => 0,
            'watchlist_normal_count' => 0,
        ];

        foreach ($rows as $row) {
            $type = (string) ($row['type'] ?? '');
            if (in_array($type, ['price_increase', 'price_decrease'], true)) {
                $summary['price_change_pressure_count']++;
            }

            if (in_array($type, ['availability_lost', 'stock_dropped'], true)) {
                $summary['availability_drop_count']++;
            }

            if ($type === 'missing_in_recent_import') {
                $summary['catalog_gap_count']++;
            }

            if (((bool) ($row['is_watched'] ?? false)) === false) {
                continue;
            }

            if (in_array($type, ['price_increase', 'price_decrease'], true)) {
                $watchSummary['watchlist_price_change_pressure_count']++;
            }

            if (in_array($type, ['availability_lost', 'stock_dropped'], true)) {
                $watchSummary['watchlist_availability_drop_count']++;
            }

            if ($type === 'missing_in_recent_import') {
                $watchSummary['watchlist_catalog_gap_count']++;
            }

            $priority = (string) ($row['watch_priority_level'] ?? 'normal');
            if ($priority === 'critical') {
                $watchSummary['watchlist_critical_count']++;
            } elseif ($priority === 'high') {
                $watchSummary['watchlist_high_count']++;
            } else {
                $watchSummary['watchlist_normal_count']++;
            }
        }

        return [...$summary, ...$watchSummary];
    }

    /** @param array<string, mixed> $state
     * @return array<int, array<string, mixed>>
     */
    private function stateDeviations(array $state): array
    {
        $rows = [];

        $previousPrice = $this->toNullableFloat($state['previous_price'] ?? null);
        $latestPrice = $this->toNullableFloat($state['latest_price'] ?? null);

        if ($previousPrice !== null && $latestPrice !== null && $latestPrice !== $previousPrice) {
            $type = $latestPrice > $previousPrice ? 'price_increase' : 'price_decrease';
            $rows[] = $this->buildDeviation($type, $state, [
                'previous_value' => $this->money($previousPrice, (string) ($state['previous_currency'] ?? 'SEK')),
                'new_value' => $this->money($latestPrice, (string) ($state['latest_currency'] ?? 'SEK')),
                'change_pct' => $this->priceChangePercent($previousPrice, $latestPrice),
            ]);
        }

        $previousAvailable = $this->toNullableBool($state['previous_is_available'] ?? null);
        $latestAvailable = $this->toNullableBool($state['latest_is_available'] ?? null);
        if ($previousAvailable !== null && $latestAvailable !== null && $previousAvailable !== $latestAvailable) {
            $rows[] = $this->buildDeviation($latestAvailable ? 'availability_restored' : 'availability_lost', $state, [
                'previous_value' => $previousAvailable ? 'Tillgänglig' : 'Ej tillgänglig',
                'new_value' => $latestAvailable ? 'Tillgänglig' : 'Ej tillgänglig',
            ]);
        }

        $previousStock = $this->toNullableInt($state['previous_stock_quantity'] ?? null);
        $latestStock = $this->toNullableInt($state['latest_stock_quantity'] ?? null);

        if ($previousStock !== null && $latestStock !== null && $latestStock !== $previousStock) {
            if ($latestStock < $previousStock) {
                $rows[] = $this->buildDeviation('stock_dropped', $state, [
                    'previous_value' => (string) $previousStock,
                    'new_value' => (string) $latestStock,
                ]);
            }

            if ($latestStock > $previousStock) {
                $rows[] = $this->buildDeviation('stock_restored', $state, [
                    'previous_value' => (string) $previousStock,
                    'new_value' => (string) $latestStock,
                ]);
            }
        }

        return $rows;
    }

    /** @param array<string, mixed> $state
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function buildDeviation(string $type, array $state, array $override = []): array
    {
        return [
            'supplier_id' => (int) ($state['supplier_id'] ?? 0),
            'supplier_name' => (string) ($state['supplier_name'] ?? 'Okänd leverantör'),
            'supplier_item_id' => (int) ($state['supplier_item_id'] ?? 0),
            'supplier_sku' => (string) ($state['supplier_sku'] ?? ''),
            'supplier_title' => (string) ($state['supplier_title'] ?? ''),
            'product_id' => $state['product_id'] !== null ? (int) $state['product_id'] : null,
            'product_name' => (string) ($state['product_name'] ?? ''),
            'brand_id' => $state['brand_id'] !== null ? (int) $state['brand_id'] : null,
            'brand_name' => (string) ($state['brand_name'] ?? ''),
            'type' => $type,
            'group' => $this->groupFor($type),
            'detected_at' => (string) ($state['latest_captured_at'] ?? $state['previous_captured_at'] ?? ''),
            'latest_import_run_id' => isset($state['latest_import_run_id']) ? (int) $state['latest_import_run_id'] : null,
            'previous_value' => '-',
            'new_value' => '-',
            'change_pct' => null,
            'actions' => $this->actionLinks($state, $type),
            ...$override,
        ];
    }

    /** @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function buildAssortmentDeviation(string $type, array $state): array
    {
        if ($type === 'newly_seen_item') {
            return $this->buildDeviation($type, $state, [
                'previous_value' => 'Saknades i tidigare importer',
                'new_value' => 'Finns i senaste import',
                'detected_at' => (string) ($state['latest_captured_at'] ?? ''),
            ]);
        }

        return $this->buildDeviation($type, $state, [
            'previous_value' => 'Fanns i tidigare import',
            'new_value' => 'Saknas i senaste import',
            'detected_at' => (string) ($state['previous_captured_at'] ?? ''),
        ]);
    }

    /** @param array<string, mixed> $state
     * @return array<int, array{label:string,url:string}>
     */
    private function actionLinks(array $state, string $type): array
    {
        $sku = (string) ($state['supplier_sku'] ?? '');
        $supplierId = (int) ($state['supplier_id'] ?? 0);
        $itemId = (int) ($state['supplier_item_id'] ?? 0);
        $runId = isset($state['latest_import_run_id']) ? (int) $state['latest_import_run_id'] : 0;
        $productId = $state['product_id'] !== null ? (int) $state['product_id'] : 0;

        $links = [
            [
                'label' => 'Öppna supplier item review',
                'url' => '/admin/supplier-item-review?supplier_id=' . $supplierId . '&supplier_sku=' . urlencode($sku),
            ],
        ];

        if ($runId > 0) {
            $links[] = ['label' => 'Öppna importkörning', 'url' => '/admin/import-runs/' . $runId];
        }

        if ($productId > 0) {
            $links[] = ['label' => 'Öppna artikelvård', 'url' => '/admin/products/article-care?query=' . $productId];
        }

        if (in_array($type, ['stock_dropped', 'availability_lost', 'missing_in_recent_import'], true)) {
            $links[] = ['label' => 'Öppna inköp', 'url' => '/admin/purchasing'];
        }

        if ($itemId > 0) {
            $links[] = ['label' => 'Filtrera item i review', 'url' => '/admin/supplier-item-review?supplier_id=' . $supplierId . '&supplier_sku=' . urlencode($sku) . '&match_status=unmatched'];
        }

        return $links;
    }

    /** @return array<string, mixed> */
    private function normalizeFilters(array $input): array
    {
        return [
            'supplier_id' => $this->toNullableInt($input['supplier_id'] ?? null),
            'deviation_type' => trim((string) ($input['deviation_type'] ?? '')),
            'deviation_scope' => trim((string) ($input['deviation_scope'] ?? '')),
            'linked_only' => ((string) ($input['linked_only'] ?? '')) === '1',
        ];
    }

    /** @param array<string, mixed> $row
     * @param array<string, mixed> $filters
     */
    private function matchesFilter(array $row, array $filters): bool
    {
        if ($filters['deviation_type'] !== '' && $row['type'] !== $filters['deviation_type']) {
            return false;
        }

        if ($filters['deviation_scope'] !== '' && $row['group'] !== $filters['deviation_scope']) {
            return false;
        }

        return true;
    }

    private function groupFor(string $type): string
    {
        return match ($type) {
            'price_increase', 'price_decrease' => 'price',
            'availability_lost', 'availability_restored', 'stock_dropped', 'stock_restored' => 'stock',
            'missing_in_recent_import', 'newly_seen_item' => 'assortment',
            default => 'other',
        };
    }

    /** @param array<int, array<string, mixed>> $rows
     * @return array<string, int>
     */
    private function counts(array $rows): array
    {
        $counts = [
            'total' => count($rows),
            'price' => 0,
            'stock' => 0,
            'assortment' => 0,
            ...$this->watchlist->summarizeMonitoringRows($rows),
        ];

        foreach ($rows as $row) {
            $group = (string) ($row['group'] ?? '');
            if (isset($counts[$group])) {
                $counts[$group]++;
            }
        }

        return $counts;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric((string) $value)) {
            return null;
        }

        return (float) $value;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '' || ctype_digit(ltrim($normalized, '-')) === false) {
            return null;
        }

        return (int) $normalized;
    }

    private function toNullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value === 1;
    }

    private function money(float $value, string $currency): string
    {
        return number_format($value, 2, ',', ' ') . ' ' . $currency;
    }

    private function priceChangePercent(float $old, float $new): ?string
    {
        if ($old == 0.0) {
            return null;
        }

        $delta = (($new - $old) / $old) * 100;
        return number_format($delta, 1, ',', ' ') . ' %';
    }
}
