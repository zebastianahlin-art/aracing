<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Repositories\AiDemandSignalInsightRepository;

final class AiDemandSignalInsightService
{
    private const TYPE_HIGH_INTEREST_LOW_CONVERSION = 'high_interest_low_conversion';
    private const TYPE_REPEATED_INTEREST_NO_STOCK = 'repeated_interest_no_stock';
    private const TYPE_COMPARE_INTEREST_SIGNAL = 'compare_interest_signal';
    private const TYPE_VIEWED_NOT_BOUGHT = 'viewed_not_bought';

    private const HIGH_INTEREST_THRESHOLD = 5;
    private const STOCK_ALERT_THRESHOLD = 3;
    private const LOW_CONVERSION_60_DAYS = 1;
    private const VIEWED_SIGNAL_THRESHOLD = 4;

    public function __construct(private readonly AiDemandSignalInsightRepository $insights)
    {
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function listInsights(array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $rows = $this->insights->listDemandSignals($normalizedFilters);

        $insightRows = [];
        foreach ($rows as $row) {
            $signals = $this->normalizeSignals($row);
            foreach ($this->evaluateInsightTypes($signals) as $type) {
                if ($normalizedFilters['insight_type'] !== 'all' && $normalizedFilters['insight_type'] !== $type) {
                    continue;
                }

                $insightRows[] = $this->buildInsightRow($signals, $type);
            }
        }

        usort($insightRows, function (array $a, array $b): int {
            $priorityCmp = ((int) ($b['priority'] ?? 0)) <=> ((int) ($a['priority'] ?? 0));
            if ($priorityCmp !== 0) {
                return $priorityCmp;
            }

            return ((int) ($b['interest_total'] ?? 0)) <=> ((int) ($a['interest_total'] ?? 0));
        });

        return [
            'filters' => $normalizedFilters,
            'rows' => $insightRows,
            'counts' => $this->buildCounts($insightRows),
            'supplier_options' => $this->insights->listSupplierOptions(),
            'insight_type_options' => $this->insightTypeOptions(),
            'rule_info' => $this->ruleInfo(),
            'signal_coverage_notes' => [
                'wishlist' => 'Querybar via wishlist_items.',
                'stock_alerts' => 'Querybar via stock_alert_subscriptions (status=active).',
                'orders' => 'Querybar via order_items + orders (30/60 dagar).',
                'compare' => 'Sessionbaserad i v1 och inte querybar som produktaggregerad signal i admin.',
                'recent_views' => 'Sessionbaserad i v1 och inte querybar som produktaggregerad signal i admin.',
            ],
        ];
    }

    /** @return array<string,string> */
    public function insightTypeOptions(): array
    {
        return [
            'all' => 'Alla insights',
            self::TYPE_HIGH_INTEREST_LOW_CONVERSION => 'High interest / low conversion',
            self::TYPE_REPEATED_INTEREST_NO_STOCK => 'Repeated interest / no stock',
            self::TYPE_COMPARE_INTEREST_SIGNAL => 'Compare interest signal',
            self::TYPE_VIEWED_NOT_BOUGHT => 'Viewed not bought',
        ];
    }

    /** @return array<string,string> */
    public function ruleInfo(): array
    {
        return [
            self::TYPE_HIGH_INTEREST_LOW_CONVERSION => sprintf('(wishlist + stock_alerts + recent_view_signal + compare_count) >= %d och sold_last_60_days <= %d', self::HIGH_INTEREST_THRESHOLD, self::LOW_CONVERSION_60_DAYS),
            self::TYPE_REPEATED_INTEREST_NO_STOCK => sprintf('active_stock_alerts >= %d och stock_status i [out_of_stock, backorder] eller stock_quantity <= 0', self::STOCK_ALERT_THRESHOLD),
            self::TYPE_COMPARE_INTEREST_SIGNAL => 'compare_count >= 2 och sold_last_60_days <= 1 (degraderas i v1 om compare ej querybar).',
            self::TYPE_VIEWED_NOT_BOUGHT => sprintf('recent_view_signal >= %d och sold_last_30_days = 0 (degraderas i v1 om recent views ej querybar).', self::VIEWED_SIGNAL_THRESHOLD),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,int>
     */
    private function buildCounts(array $rows): array
    {
        $counts = [
            'total' => count($rows),
            self::TYPE_HIGH_INTEREST_LOW_CONVERSION => 0,
            self::TYPE_REPEATED_INTEREST_NO_STOCK => 0,
            self::TYPE_COMPARE_INTEREST_SIGNAL => 0,
            self::TYPE_VIEWED_NOT_BOUGHT => 0,
        ];

        foreach ($rows as $row) {
            $type = (string) ($row['insight_type'] ?? '');
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }

        return $counts;
    }

    /**
     * @param array<string,mixed> $signals
     * @return array<int,string>
     */
    private function evaluateInsightTypes(array $signals): array
    {
        $sold30 = (int) ($signals['sold_last_30_days'] ?? 0);
        $sold60 = (int) ($signals['sold_last_60_days'] ?? 0);
        $stockStatus = (string) ($signals['stock_status'] ?? '');
        $stockQty = (int) ($signals['stock_quantity'] ?? 0);

        $types = [];

        if ((int) ($signals['interest_total'] ?? 0) >= self::HIGH_INTEREST_THRESHOLD && $sold60 <= self::LOW_CONVERSION_60_DAYS) {
            $types[] = self::TYPE_HIGH_INTEREST_LOW_CONVERSION;
        }

        if ((int) ($signals['active_stock_alerts'] ?? 0) >= self::STOCK_ALERT_THRESHOLD && ($stockQty <= 0 || in_array($stockStatus, ['out_of_stock', 'backorder'], true))) {
            $types[] = self::TYPE_REPEATED_INTEREST_NO_STOCK;
        }

        if ((int) ($signals['compare_count'] ?? 0) >= 2 && $sold60 <= self::LOW_CONVERSION_60_DAYS) {
            $types[] = self::TYPE_COMPARE_INTEREST_SIGNAL;
        }

        if ((int) ($signals['recent_view_signal'] ?? 0) >= self::VIEWED_SIGNAL_THRESHOLD && $sold30 === 0) {
            $types[] = self::TYPE_VIEWED_NOT_BOUGHT;
        }

        return $types;
    }

    /**
     * @param array<string,mixed> $signals
     * @return array<string,mixed>
     */
    private function buildInsightRow(array $signals, string $type): array
    {
        $productId = (int) ($signals['product_id'] ?? 0);
        $sku = (string) ($signals['sku'] ?? '');
        $name = (string) ($signals['product_name'] ?? '');
        $stockStatus = (string) ($signals['stock_status'] ?? '');
        $stockQty = (int) ($signals['stock_quantity'] ?? 0);

        $base = [
            'product_id' => $productId,
            'product_name' => $name,
            'sku' => $sku,
            'brand_name' => (string) ($signals['brand_name'] ?? ''),
            'category_name' => (string) ($signals['category_name'] ?? ''),
            'supplier_name' => (string) ($signals['supplier_name'] ?? ''),
            'wishlist_count' => (int) ($signals['wishlist_count'] ?? 0),
            'compare_count' => (int) ($signals['compare_count'] ?? 0),
            'active_stock_alerts' => (int) ($signals['active_stock_alerts'] ?? 0),
            'recent_view_signal' => (int) ($signals['recent_view_signal'] ?? 0),
            'sold_last_30_days' => (int) ($signals['sold_last_30_days'] ?? 0),
            'sold_last_60_days' => (int) ($signals['sold_last_60_days'] ?? 0),
            'stock_status' => $stockStatus,
            'stock_quantity' => $stockQty,
            'interest_total' => (int) ($signals['interest_total'] ?? 0),
            'insight_type' => $type,
            'insight_label' => $this->insightTypeOptions()[$type] ?? $type,
            'action_links' => $this->buildActionLinks($productId, $sku, $name),
        ];

        if ($type === self::TYPE_HIGH_INTEREST_LOW_CONVERSION) {
            $base['priority'] = 80 + min(15, (int) floor($base['interest_total'] / 2));
            $base['summary'] = 'Tydligt intresse men svag orderkonvertering.';
            $base['reason'] = sprintf(
                'Wishlist %d + alerts %d (+ compare/recent view i v1: %d/%d) men sålt %d senaste 60 dagar.',
                $base['wishlist_count'],
                $base['active_stock_alerts'],
                $base['compare_count'],
                $base['recent_view_signal'],
                $base['sold_last_60_days']
            );
        } elseif ($type === self::TYPE_REPEATED_INTEREST_NO_STOCK) {
            $base['priority'] = 90 + min(10, $base['active_stock_alerts']);
            $base['summary'] = 'Återkommande intresse men produkten är svår att köpa i nuläget.';
            $base['reason'] = sprintf('Aktiva stock alerts: %d, lagerstatus: %s, lagersaldo: %d.', $base['active_stock_alerts'], $stockStatus, $stockQty);
        } elseif ($type === self::TYPE_COMPARE_INTEREST_SIGNAL) {
            $base['priority'] = 65;
            $base['summary'] = 'Compare-signal finns men få köp.';
            $base['reason'] = sprintf('Compare-count: %d, sålt 60 dagar: %d.', $base['compare_count'], $base['sold_last_60_days']);
        } else {
            $base['priority'] = 60;
            $base['summary'] = 'Många visningar/sparningar utan köp.';
            $base['reason'] = sprintf('Recent-view-signal: %d, sålt 30 dagar: %d.', $base['recent_view_signal'], $base['sold_last_30_days']);
        }

        return $base;
    }

    /** @return array<int,array{label:string,url:string}> */
    private function buildActionLinks(int $productId, string $sku, string $name): array
    {
        $search = rawurlencode(trim($sku) !== '' ? $sku : $name);

        return [
            ['label' => 'Produktedit', 'url' => '/admin/products/' . $productId . '/edit'],
            ['label' => 'Pricing insights', 'url' => '/admin/ai-pricing-insights?search=' . $search],
            ['label' => 'Inventory insights', 'url' => '/admin/ai-inventory-insights?search=' . $search],
            ['label' => 'Merch suggestions', 'url' => '/admin/ai-merch-suggestions'],
            ['label' => 'Inköpsöversikt', 'url' => '/admin/purchasing?search=' . $search],
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{insight_type:string,supplier_id:string,brand_id:string,category_id:string,search:string}
     */
    private function normalizeFilters(array $filters): array
    {
        $insightType = trim((string) ($filters['insight_type'] ?? 'all'));
        if (!array_key_exists($insightType, $this->insightTypeOptions())) {
            $insightType = 'all';
        }

        return [
            'insight_type' => $insightType,
            'supplier_id' => trim((string) ($filters['supplier_id'] ?? '')),
            'brand_id' => trim((string) ($filters['brand_id'] ?? '')),
            'category_id' => trim((string) ($filters['category_id'] ?? '')),
            'search' => trim((string) ($filters['search'] ?? '')),
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function normalizeSignals(array $row): array
    {
        $wishlist = (int) ($row['wishlist_count'] ?? 0);
        $stockAlerts = (int) ($row['active_stock_alerts'] ?? 0);

        return [
            'product_id' => (int) ($row['id'] ?? 0),
            'product_name' => (string) ($row['name'] ?? ''),
            'sku' => (string) ($row['sku'] ?? ''),
            'brand_name' => (string) ($row['brand_name'] ?? ''),
            'category_name' => (string) ($row['category_name'] ?? ''),
            'supplier_name' => (string) ($row['supplier_name'] ?? ''),
            'stock_status' => (string) ($row['stock_status'] ?? ''),
            'stock_quantity' => (int) ($row['stock_quantity'] ?? 0),
            'wishlist_count' => $wishlist,
            'active_stock_alerts' => $stockAlerts,
            'sold_last_30_days' => (int) ($row['sold_last_30_days'] ?? 0),
            'sold_last_60_days' => (int) ($row['sold_last_60_days'] ?? 0),
            // Compare/recently viewed är sessionbaserade i nuvarande v1 och saknar querybar aggregering.
            'compare_count' => 0,
            'recent_view_signal' => 0,
            'interest_total' => $wishlist + $stockAlerts,
        ];
    }
}
