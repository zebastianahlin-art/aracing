<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Repositories\AiAssortmentGapInsightRepository;
use App\Modules\Fitment\Services\FitmentGapService;
use App\Modules\Supplier\Services\SupplierMonitoringService;
use Throwable;

final class AiAssortmentGapInsightService
{
    private const TYPE_SEARCH_GAP = 'search_gap';
    private const TYPE_SUPPLIER_GAP = 'supplier_gap';
    private const TYPE_FITMENT_GAP = 'fitment_gap';
    private const TYPE_WATCHLIST_GAP = 'watchlist_gap';
    private const TYPE_DEMAND_GAP = 'demand_gap';

    public function __construct(
        private readonly AiAssortmentGapInsightRepository $insights,
        private readonly AiSearchInsightService $searchInsights,
        private readonly SupplierMonitoringService $supplierMonitoring,
        private readonly FitmentGapService $fitmentGaps,
    ) {
    }

    /** @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function listInsights(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        $rows = array_merge(
            $this->searchGapInsights($normalized),
            $this->supplierGapInsights($normalized),
            $this->fitmentGapInsights($normalized),
            $this->watchlistGapInsights($normalized),
            $this->demandGapInsights($normalized),
        );

        usort($rows, function (array $a, array $b): int {
            $priority = ((int) ($b['priority'] ?? 0)) <=> ((int) ($a['priority'] ?? 0));
            if ($priority !== 0) {
                return $priority;
            }

            return strcmp((string) ($b['context'] ?? ''), (string) ($a['context'] ?? ''));
        });

        return [
            'filters' => $normalized,
            'rows' => $rows,
            'counts' => $this->buildCounts($rows),
            'gap_type_options' => $this->gapTypeOptions(),
            'rule_info' => $this->ruleInfo(),
            'supplier_options' => $this->safe(fn (): array => $this->insights->listSupplierOptions(), []),
            'brand_options' => $this->safe(fn (): array => $this->insights->listBrandOptions(), []),
            'category_options' => $this->safe(fn (): array => $this->insights->listCategoryOptions(), []),
        ];
    }

    /** @return array<string,string> */
    public function gapTypeOptions(): array
    {
        return [
            'all' => 'Alla gap-typer',
            self::TYPE_SEARCH_GAP => 'Search gap',
            self::TYPE_SUPPLIER_GAP => 'Supplier gap',
            self::TYPE_FITMENT_GAP => 'Fitment gap',
            self::TYPE_WATCHLIST_GAP => 'Watchlist gap',
            self::TYPE_DEMAND_GAP => 'Demand gap',
        ];
    }

    /** @return array<string,string> */
    public function ruleInfo(): array
    {
        return [
            self::TYPE_SEARCH_GAP => 'Återkommande query med 0/låga träffar och svag katalogtäckning.',
            self::TYPE_SUPPLIER_GAP => 'Leverantör har många supplier items utan produktmappning.',
            self::TYPE_FITMENT_GAP => 'Produkt/fordonsyta har tydliga fitment-gap eller låg category coverage.',
            self::TYPE_WATCHLIST_GAP => 'Bevakad supplier/brand visar assortment-avvikelse utan tydlig katalogmatch.',
            self::TYPE_DEMAND_GAP => 'Efterfrågesignal (sök + alerts) finns men katalogtäckning är låg.',
        ];
    }

    /** @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    private function searchGapInsights(array $filters): array
    {
        if (!$this->typeAllowed($filters, self::TYPE_SEARCH_GAP)) {
            return [];
        }

        $payload = $this->safe(fn (): array => $this->searchInsights->insights(), []);
        $queries = is_array($payload['problematic_queries'] ?? null) ? $payload['problematic_queries'] : [];

        $rows = [];
        foreach ($queries as $queryRow) {
            $query = trim((string) ($queryRow['query'] ?? ''));
            if ($query === '') {
                continue;
            }

            $zeroCount = (int) ($queryRow['zero_result_count'] ?? 0);
            $lowCount = (int) ($queryRow['low_result_count'] ?? 0);
            $searchCount = (int) ($queryRow['search_count'] ?? 0);
            $catalogHits = $this->safe(fn (): int => $this->insights->countCatalogMatchesForQuery($query), 0);

            if ($zeroCount < 2 && $lowCount < 4) {
                continue;
            }

            $rows[] = [
                'gap_type' => self::TYPE_SEARCH_GAP,
                'gap_label' => $this->gapTypeOptions()[self::TYPE_SEARCH_GAP],
                'context' => $query,
                'context_meta' => 'Sökfråga',
                'reason' => sprintf('Query har %d st 0-träffar och %d st låg-träffar, med %d katalogmatchningar.', $zeroCount, $lowCount, $catalogHits),
                'signals' => [
                    'Sökningar' => $searchCount,
                    '0-träffar' => $zeroCount,
                    'Låg träff (≤2)' => $lowCount,
                    'Katalogmatch' => $catalogHits,
                ],
                'combined_signal' => 'Baserat på återkommande zero-/low-result query + låg katalogtäckning.',
                'action_links' => [
                    ['label' => 'AI Search Insights', 'url' => '/admin/ai-search-insights'],
                    ['label' => 'Sök i produktadmin', 'url' => '/admin/products?query=' . urlencode($query)],
                    ['label' => 'Storefront-sök', 'url' => '/search?q=' . urlencode($query)],
                ],
                'priority' => min(100, 45 + ($zeroCount * 6) + ($searchCount >= 10 ? 10 : 0)),
            ];
        }

        return $rows;
    }

    /** @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    private function supplierGapInsights(array $filters): array
    {
        if (!$this->typeAllowed($filters, self::TYPE_SUPPLIER_GAP)) {
            return [];
        }

        $rows = [];
        foreach ($this->safe(fn (): array => $this->insights->listSupplierCoverageSignals($filters), []) as $row) {
            $supplierId = (int) ($row['supplier_id'] ?? 0);
            $supplierName = (string) ($row['supplier_name'] ?? 'Okänd leverantör');
            $total = (int) ($row['supplier_item_count'] ?? 0);
            $unmapped = (int) ($row['unmapped_count'] ?? 0);
            $unmatched = (int) ($row['review_unmatched_count'] ?? 0);
            $mapped = (int) ($row['mapped_count'] ?? 0);
            $ratio = $total > 0 ? ($mapped / $total) * 100.0 : 0.0;

            if ($unmapped < 6 || $ratio >= 80.0) {
                continue;
            }

            $rows[] = [
                'gap_type' => self::TYPE_SUPPLIER_GAP,
                'gap_label' => $this->gapTypeOptions()[self::TYPE_SUPPLIER_GAP],
                'context' => $supplierName,
                'context_meta' => 'Leverantör',
                'reason' => sprintf('%d av %d supplier-items saknar mappning (%.1f%% mappar idag).', $unmapped, $total, $ratio),
                'signals' => [
                    'Totala supplier-items' => $total,
                    'Utan mappning' => $unmapped,
                    'Review: unmatched' => $unmatched,
                    'Mappningsgrad (%)' => number_format($ratio, 1, ',', ' '),
                ],
                'combined_signal' => 'Baserat på supplier-katalog + produkt↔supplier-link coverage.',
                'action_links' => [
                    ['label' => 'Supplier monitoring', 'url' => '/admin/supplier-monitoring?supplier_id=' . $supplierId . '&deviation_scope=assortment'],
                    ['label' => 'Importgranskning', 'url' => '/admin/supplier-item-review?supplier_id=' . $supplierId . '&match_status=unmatched'],
                    ['label' => 'Importkörningar', 'url' => '/admin/import-runs'],
                ],
                'priority' => min(95, 40 + ($unmapped * 3)),
            ];
        }

        return $rows;
    }

    /** @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    private function fitmentGapInsights(array $filters): array
    {
        if (!$this->typeAllowed($filters, self::TYPE_FITMENT_GAP)) {
            return [];
        }

        $payload = $this->safe(fn (): array => $this->fitmentGaps->adminQueue($filters), ['rows' => []]);
        $queueRows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];

        $rows = [];
        foreach (array_slice($queueRows, 0, 50) as $gapRow) {
            $productId = (int) ($gapRow['id'] ?? 0);
            $productName = (string) ($gapRow['name'] ?? 'Produkt');
            $reasons = is_array($gapRow['gap_reasons'] ?? null) ? $gapRow['gap_reasons'] : [];
            $reasonLabels = array_map(static fn (array $reason): string => (string) ($reason['label'] ?? ''), $reasons);
            $coverage = is_array($gapRow['category_coverage'] ?? null) ? $gapRow['category_coverage'] : null;

            $rows[] = [
                'gap_type' => self::TYPE_FITMENT_GAP,
                'gap_label' => $this->gapTypeOptions()[self::TYPE_FITMENT_GAP],
                'context' => $productName,
                'context_meta' => 'Produkt / fitment',
                'reason' => $reasonLabels !== [] ? implode(', ', array_filter($reasonLabels)) : 'Fitment-signal i gap-kön.',
                'signals' => [
                    'Gap-anledningar' => count($reasonLabels),
                    'Pending fitment-candidates' => (int) ($gapRow['pending_supplier_candidates_count'] ?? 0),
                    'Fitment review-status' => (string) ($gapRow['fitment_review_status'] ?? '-'),
                    'Category coverage' => $coverage !== null ? sprintf('%.1f%%', (float) ($coverage['coverage_ratio'] ?? 0.0)) : '-',
                ],
                'combined_signal' => 'Baserat på fitment gap-kö + coverage-signal.',
                'action_links' => [
                    ['label' => 'Fitment gap-kö', 'url' => '/admin/fitment-gaps?product_id=' . $productId],
                    ['label' => 'Supplier fitment review', 'url' => '/admin/supplier-fitment-review?status=pending'],
                    ['label' => 'Produktadmin', 'url' => '/admin/products/' . $productId . '/edit'],
                ],
                'priority' => min(90, 50 + (count($reasonLabels) * 8) + ((int) ($gapRow['pending_supplier_candidates_count'] ?? 0) * 3)),
            ];
        }

        return $rows;
    }

    /** @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    private function watchlistGapInsights(array $filters): array
    {
        if (!$this->typeAllowed($filters, self::TYPE_WATCHLIST_GAP)) {
            return [];
        }

        $payload = $this->safe(fn (): array => $this->supplierMonitoring->deviations($filters + ['deviation_scope' => 'assortment']), ['rows' => []]);
        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];

        $grouped = [];
        foreach ($rows as $row) {
            if (((bool) ($row['is_watched'] ?? false)) === false) {
                continue;
            }

            $type = (string) ($row['type'] ?? '');
            if (!in_array($type, ['newly_seen_item', 'missing_in_recent_import'], true)) {
                continue;
            }

            $key = 'supplier:' . (int) ($row['supplier_id'] ?? 0);
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'supplier_id' => (int) ($row['supplier_id'] ?? 0),
                    'supplier_name' => (string) ($row['supplier_name'] ?? 'Okänd leverantör'),
                    'watch_priority_level' => (string) ($row['watch_priority_level'] ?? 'normal'),
                    'newly_seen_item' => 0,
                    'missing_in_recent_import' => 0,
                ];
            }

            $grouped[$key][$type]++;
        }

        $insights = [];
        foreach ($grouped as $row) {
            $newlySeen = (int) ($row['newly_seen_item'] ?? 0);
            $missing = (int) ($row['missing_in_recent_import'] ?? 0);
            $total = $newlySeen + $missing;
            if ($total <= 0) {
                continue;
            }

            $supplierId = (int) ($row['supplier_id'] ?? 0);
            $priorityLabel = (string) ($row['watch_priority_level'] ?? 'normal');
            $priorityBoost = $priorityLabel === 'critical' ? 20 : ($priorityLabel === 'high' ? 10 : 0);

            $insights[] = [
                'gap_type' => self::TYPE_WATCHLIST_GAP,
                'gap_label' => $this->gapTypeOptions()[self::TYPE_WATCHLIST_GAP],
                'context' => (string) ($row['supplier_name'] ?? 'Bevakad leverantör'),
                'context_meta' => 'Watchlist',
                'reason' => sprintf('Bevakad leverantör har %d nya och %d saknade items i senaste monitoring-snapshot.', $newlySeen, $missing),
                'signals' => [
                    'Nya items' => $newlySeen,
                    'Saknas i senaste import' => $missing,
                    'Watchlist-prioritet' => $priorityLabel,
                ],
                'combined_signal' => 'Baserat på watchlist + supplier monitoring assortment-avvikelse.',
                'action_links' => [
                    ['label' => 'Supplier monitoring', 'url' => '/admin/supplier-monitoring?supplier_id=' . $supplierId . '&deviation_scope=assortment'],
                    ['label' => 'Supplier watchlist', 'url' => '/admin/supplier-watchlist'],
                    ['label' => 'Importgranskning', 'url' => '/admin/supplier-item-review?supplier_id=' . $supplierId],
                ],
                'priority' => min(98, 48 + ($total * 4) + $priorityBoost),
            ];
        }

        return $insights;
    }

    /** @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    private function demandGapInsights(array $filters): array
    {
        if (!$this->typeAllowed($filters, self::TYPE_DEMAND_GAP)) {
            return [];
        }

        $payload = $this->safe(fn (): array => $this->searchInsights->insights(), []);
        $queries = is_array($payload['problematic_queries'] ?? null) ? $payload['problematic_queries'] : [];

        $rows = [];
        foreach ($queries as $queryRow) {
            $query = trim((string) ($queryRow['query'] ?? ''));
            if ($query === '') {
                continue;
            }

            $searchCount = (int) ($queryRow['search_count'] ?? 0);
            $zeroCount = (int) ($queryRow['zero_result_count'] ?? 0);
            if ($searchCount < 4 || $zeroCount < 2) {
                continue;
            }

            $catalogHits = $this->safe(fn (): int => $this->insights->countCatalogMatchesForQuery($query), 0);
            $activeAlerts = $this->safe(fn (): int => $this->insights->countActiveStockAlertsForQuery($query), 0);

            if ($catalogHits > 2 && $activeAlerts === 0) {
                continue;
            }

            $rows[] = [
                'gap_type' => self::TYPE_DEMAND_GAP,
                'gap_label' => $this->gapTypeOptions()[self::TYPE_DEMAND_GAP],
                'context' => $query,
                'context_meta' => 'Efterfrågesignal',
                'reason' => sprintf('Efterfrågan syns i sökdata (%d sökningar, %d 0-träffar) men endast %d katalogträffar och %d aktiva stock alerts i närliggande produkter.', $searchCount, $zeroCount, $catalogHits, $activeAlerts),
                'signals' => [
                    'Sökningar' => $searchCount,
                    '0-träffar' => $zeroCount,
                    'Katalogmatch' => $catalogHits,
                    'Aktiva stock alerts' => $activeAlerts,
                ],
                'combined_signal' => 'Baserat på search gap + demand-signal från stock alerts.',
                'action_links' => [
                    ['label' => 'AI Search Insights', 'url' => '/admin/ai-search-insights'],
                    ['label' => 'Artikelvårdskö', 'url' => '/admin/products/article-care?query=' . urlencode($query)],
                    ['label' => 'AI URL-import', 'url' => '/admin/ai-product-import'],
                ],
                'priority' => min(99, 52 + ($searchCount * 2) + ($activeAlerts * 5)),
            ];
        }

        return $rows;
    }

    /** @param array<string,mixed> $filters */
    private function typeAllowed(array $filters, string $type): bool
    {
        return (string) ($filters['gap_type'] ?? 'all') === 'all' || (string) ($filters['gap_type'] ?? '') === $type;
    }

    /** @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function normalizeFilters(array $input): array
    {
        $gapType = trim((string) ($input['gap_type'] ?? 'all'));
        if (!array_key_exists($gapType, $this->gapTypeOptions())) {
            $gapType = 'all';
        }

        return [
            'gap_type' => $gapType,
            'supplier_id' => $this->toNullableInt($input['supplier_id'] ?? null),
            'brand_id' => $this->toNullableInt($input['brand_id'] ?? null),
            'category_id' => $this->toNullableInt($input['category_id'] ?? null),
            'watched_only' => ((string) ($input['watched_only'] ?? '')) === '1' ? '1' : '0',
        ];
    }

    /** @param array<int,array<string,mixed>> $rows
     * @return array<string,int>
     */
    private function buildCounts(array $rows): array
    {
        $counts = [
            'total' => count($rows),
            self::TYPE_SEARCH_GAP => 0,
            self::TYPE_SUPPLIER_GAP => 0,
            self::TYPE_FITMENT_GAP => 0,
            self::TYPE_WATCHLIST_GAP => 0,
            self::TYPE_DEMAND_GAP => 0,
        ];

        foreach ($rows as $row) {
            $type = (string) ($row['gap_type'] ?? '');
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }

        return $counts;
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

    /** @template T
     * @param callable():T $callback
     * @param T $fallback
     * @return T
     */
    private function safe(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return $fallback;
        }
    }
}
