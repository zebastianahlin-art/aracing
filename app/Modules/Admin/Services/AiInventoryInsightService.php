<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Repositories\AiInventoryInsightRepository;

final class AiInventoryInsightService
{
    private const TYPE_SLOW_MOVER = 'slow_mover';
    private const TYPE_STOCKOUT_RISK = 'stockout_risk';
    private const TYPE_HIGH_STOCK_LOW_VELOCITY = 'high_stock_low_velocity';
    private const TYPE_DEMAND_WITHOUT_STOCK = 'demand_without_stock';

    private const STOCKOUT_LOW_STOCK_THRESHOLD = 2;
    private const HIGH_STOCK_THRESHOLD = 12;
    private const LOW_VELOCITY_60_DAYS_THRESHOLD = 2;

    public function __construct(private readonly AiInventoryInsightRepository $insights)
    {
    }

    /** @param array<string,mixed> $filters
     *  @return array<string,mixed>
     */
    public function listInsights(array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $rows = $this->insights->listInventorySignals($normalizedFilters);

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

            return ((int) ($b['sold_last_30_days'] ?? 0)) <=> ((int) ($a['sold_last_30_days'] ?? 0));
        });

        return [
            'filters' => $normalizedFilters,
            'rows' => $insightRows,
            'counts' => $this->buildCounts($insightRows),
            'supplier_options' => $this->insights->listSupplierOptions(),
            'insight_type_options' => $this->insightTypeOptions(),
            'rule_info' => $this->ruleInfo(),
        ];
    }

    /** @return array<string,string> */
    public function insightTypeOptions(): array
    {
        return [
            'all' => 'Alla insights',
            self::TYPE_SLOW_MOVER => 'Slow movers',
            self::TYPE_STOCKOUT_RISK => 'Stockout-risk',
            self::TYPE_HIGH_STOCK_LOW_VELOCITY => 'Högt lager / låg rörelse',
            self::TYPE_DEMAND_WITHOUT_STOCK => 'Efterfrågan utan lager',
        ];
    }

    /** @return array<string,string> */
    public function ruleInfo(): array
    {
        return [
            self::TYPE_SLOW_MOVER => 'stock_quantity > 0 och sold_last_60_days = 0',
            self::TYPE_HIGH_STOCK_LOW_VELOCITY => sprintf('stock_quantity >= %d och sold_last_60_days <= %d', self::HIGH_STOCK_THRESHOLD, self::LOW_VELOCITY_60_DAYS_THRESHOLD),
            self::TYPE_STOCKOUT_RISK => sprintf('stock_quantity <= %d och (sold_last_30_days > 0 eller active_stock_alerts > 0)', self::STOCKOUT_LOW_STOCK_THRESHOLD),
            self::TYPE_DEMAND_WITHOUT_STOCK => 'stock_status = out_of_stock/backorder och (active_stock_alerts > 0 eller sold_last_30_days > 0)',
        ];
    }

    /** @param array<string,mixed> $signals
     * @return array<int,string>
     */
    private function evaluateInsightTypes(array $signals): array
    {
        $stock = (int) ($signals['stock_quantity'] ?? 0);
        $sold30 = (int) ($signals['sold_last_30_days'] ?? 0);
        $sold60 = (int) ($signals['sold_last_60_days'] ?? 0);
        $alerts = (int) ($signals['active_stock_alerts'] ?? 0);
        $status = (string) ($signals['stock_status'] ?? '');

        $types = [];

        if ($stock > 0 && $sold60 === 0) {
            $types[] = self::TYPE_SLOW_MOVER;
        }

        if ($stock >= self::HIGH_STOCK_THRESHOLD && $sold60 <= self::LOW_VELOCITY_60_DAYS_THRESHOLD) {
            $types[] = self::TYPE_HIGH_STOCK_LOW_VELOCITY;
        }

        if ($stock <= self::STOCKOUT_LOW_STOCK_THRESHOLD && ($sold30 > 0 || $alerts > 0)) {
            $types[] = self::TYPE_STOCKOUT_RISK;
        }

        if (in_array($status, ['out_of_stock', 'backorder'], true) && ($alerts > 0 || $sold30 > 0)) {
            $types[] = self::TYPE_DEMAND_WITHOUT_STOCK;
        }

        return $types;
    }

    /** @param array<string,mixed> $signals
     * @return array<string,mixed>
     */
    private function buildInsightRow(array $signals, string $type): array
    {
        $productId = (int) ($signals['id'] ?? 0);

        $base = [
            'product_id' => $productId,
            'product_name' => (string) ($signals['name'] ?? ''),
            'sku' => (string) ($signals['sku'] ?? ''),
            'brand_name' => (string) ($signals['brand_name'] ?? ''),
            'category_name' => (string) ($signals['category_name'] ?? ''),
            'supplier_name' => (string) ($signals['supplier_name'] ?? ''),
            'stock_quantity' => (int) ($signals['stock_quantity'] ?? 0),
            'stock_status' => (string) ($signals['stock_status'] ?? ''),
            'sold_last_30_days' => (int) ($signals['sold_last_30_days'] ?? 0),
            'sold_last_60_days' => (int) ($signals['sold_last_60_days'] ?? 0),
            'active_stock_alerts' => (int) ($signals['active_stock_alerts'] ?? 0),
            'pending_restock_qty' => (int) ($signals['pending_restock_qty'] ?? 0),
            'restock_status' => (string) ($signals['restock_status'] ?? ''),
            'last_sale_at' => (string) ($signals['last_sale_at'] ?? ''),
            'insight_type' => $type,
            'insight_label' => $this->insightTypeOptions()[$type] ?? $type,
            'action_links' => $this->buildActionLinks($productId, (string) ($signals['sku'] ?? ''), (string) ($signals['name'] ?? ''), (int) ($signals['supplier_id'] ?? 0)),
        ];

        if ($type === self::TYPE_SLOW_MOVER) {
            $base['priority'] = 35;
            $base['summary'] = 'Produkten har lager men ingen försäljning senaste 60 dagarna.';
            $base['reason'] = sprintf('Lager %d st, sålt 60 dagar: 0.', $base['stock_quantity']);
        } elseif ($type === self::TYPE_HIGH_STOCK_LOW_VELOCITY) {
            $base['priority'] = 45 + min(20, (int) floor($base['stock_quantity'] / 5));
            $base['summary'] = 'Högt lager i kombination med låg orderrörelse binder kapital.';
            $base['reason'] = sprintf('Lager %d st och sålt %d st på 60 dagar.', $base['stock_quantity'], $base['sold_last_60_days']);
        } elseif ($type === self::TYPE_STOCKOUT_RISK) {
            $base['priority'] = 70 + min(20, $base['active_stock_alerts'] * 2);
            $base['summary'] = 'Låg lagernivå med aktuell efterfrågesignal.';
            $base['reason'] = sprintf('Lager %d st, sålt 30 dagar: %d, aktiva stock alerts: %d.', $base['stock_quantity'], $base['sold_last_30_days'], $base['active_stock_alerts']);
        } else {
            $base['priority'] = 85 + min(10, $base['active_stock_alerts']);
            $base['summary'] = 'Efterfrågan finns men produkten är inte tillgänglig i lager.';
            $base['reason'] = sprintf('Status %s, aktiva stock alerts: %d, sålt 30 dagar: %d.', $base['stock_status'], $base['active_stock_alerts'], $base['sold_last_30_days']);
        }

        return $base;
    }

    /** @return array<int,array{label:string,url:string}> */
    private function buildActionLinks(int $productId, string $sku, string $name, int $supplierId): array
    {
        $search = rawurlencode(trim($sku) !== '' ? $sku : $name);
        $links = [
            ['label' => 'Produktedit', 'url' => '/admin/products/' . $productId . '/edit'],
            ['label' => 'Inköpsöversikt', 'url' => '/admin/purchasing?search=' . $search],
        ];

        if ($supplierId > 0) {
            $links[] = ['label' => 'Supplier monitoring', 'url' => '/admin/supplier-monitoring?supplier_id=' . $supplierId];
        }

        return $links;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,int>
     */
    private function buildCounts(array $rows): array
    {
        $counts = [
            'total' => count($rows),
            self::TYPE_SLOW_MOVER => 0,
            self::TYPE_STOCKOUT_RISK => 0,
            self::TYPE_HIGH_STOCK_LOW_VELOCITY => 0,
            self::TYPE_DEMAND_WITHOUT_STOCK => 0,
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

    /** @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function normalizeSignals(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'sku' => (string) ($row['sku'] ?? ''),
            'brand_name' => (string) ($row['brand_name'] ?? ''),
            'category_name' => (string) ($row['category_name'] ?? ''),
            'supplier_id' => (int) ($row['supplier_id'] ?? 0),
            'supplier_name' => (string) ($row['supplier_name'] ?? ''),
            'stock_quantity' => (int) ($row['stock_quantity'] ?? 0),
            'stock_status' => (string) ($row['stock_status'] ?? ''),
            'sold_last_30_days' => (int) ($row['sold_last_30_days'] ?? 0),
            'sold_last_60_days' => (int) ($row['sold_last_60_days'] ?? 0),
            'active_stock_alerts' => (int) ($row['active_stock_alerts'] ?? 0),
            'pending_restock_qty' => (int) ($row['pending_restock_qty'] ?? 0),
            'restock_status' => (string) ($row['restock_status'] ?? ''),
            'last_sale_at' => (string) ($row['last_sale_at'] ?? ''),
        ];
    }
}
