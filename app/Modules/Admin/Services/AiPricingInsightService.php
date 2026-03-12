<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Repositories\AiPricingInsightRepository;

final class AiPricingInsightService
{
    private const TYPE_MARGIN_PRESSURE = 'margin_pressure';
    private const TYPE_SUPPLIER_PRICE_MOVED = 'supplier_price_moved';
    private const TYPE_PRICE_GAP_CHECK = 'price_gap_check';
    private const TYPE_DISCOUNT_MARGIN_RISK = 'discount_margin_risk';

    private const MARGIN_PRESSURE_MIN_PERCENT = 18.0;
    private const MARGIN_PRESSURE_MIN_AMOUNT = 120.0;
    private const SUPPLIER_MOVE_MIN_PERCENT = 8.0;
    private const SUPPLIER_MOVE_MIN_AMOUNT = 50.0;
    private const PRICE_GAP_NEAR_PERCENT = 10.0;
    private const DISCOUNT_SAFE_MARGIN_PERCENT = 12.0;

    public function __construct(private readonly AiPricingInsightRepository $insights)
    {
    }

    /** @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function listInsights(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $products = $this->insights->listProductPricingSignals($normalized);
        $productIds = array_values(array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $products));
        $supplierRows = $this->insights->listSupplierSignalsByProduct($productIds, $normalized['supplier_id'] !== '' ? (int) $normalized['supplier_id'] : null);
        $supplierMap = $this->indexSupplierRowsByProduct($supplierRows);
        $activeDiscount = $this->normalizeDiscount($this->insights->activeTopPercentDiscount());

        $rows = [];
        foreach ($products as $product) {
            $productId = (int) ($product['id'] ?? 0);
            $relevantSupplierSignal = $this->selectRelevantSupplierSignal($supplierMap[$productId] ?? []);
            if ($relevantSupplierSignal === null) {
                continue;
            }

            foreach ($this->evaluateInsightTypes($product, $relevantSupplierSignal, $activeDiscount) as $type) {
                if ($normalized['insight_type'] !== 'all' && $normalized['insight_type'] !== $type) {
                    continue;
                }

                $rows[] = $this->buildInsightRow($product, $relevantSupplierSignal, $type, $activeDiscount);
            }
        }

        usort($rows, static function (array $a, array $b): int {
            $priorityCmp = ((int) ($b['priority'] ?? 0)) <=> ((int) ($a['priority'] ?? 0));
            if ($priorityCmp !== 0) {
                return $priorityCmp;
            }

            return ((float) ($b['supplier_change_percent'] ?? 0.0)) <=> ((float) ($a['supplier_change_percent'] ?? 0.0));
        });

        return [
            'filters' => $normalized,
            'rows' => $rows,
            'counts' => $this->buildCounts($rows),
            'supplier_options' => $this->insights->listSupplierOptions(),
            'insight_type_options' => $this->insightTypeOptions(),
            'rule_info' => $this->ruleInfo(),
            'active_discount' => $activeDiscount,
        ];
    }

    /** @return array<string,string> */
    public function insightTypeOptions(): array
    {
        return [
            'all' => 'Alla signaler',
            self::TYPE_MARGIN_PRESSURE => 'Margin pressure',
            self::TYPE_SUPPLIER_PRICE_MOVED => 'Supplierpris ändrat',
            self::TYPE_PRICE_GAP_CHECK => 'Prisglapp-kontroll',
            self::TYPE_DISCOUNT_MARGIN_RISK => 'Rabattrelaterad marginalrisk',
        ];
    }

    /** @return array<string,string> */
    public function ruleInfo(): array
    {
        return [
            self::TYPE_MARGIN_PRESSURE => sprintf('Enkel bruttomarginal < %.1f%% eller < %.0f SEK.', self::MARGIN_PRESSURE_MIN_PERCENT, self::MARGIN_PRESSURE_MIN_AMOUNT),
            self::TYPE_SUPPLIER_PRICE_MOVED => sprintf('Supplierprisförändring >= %.1f%% eller >= %.0f SEK jämfört med föregående snapshot.', self::SUPPLIER_MOVE_MIN_PERCENT, self::SUPPLIER_MOVE_MIN_AMOUNT),
            self::TYPE_PRICE_GAP_CHECK => sprintf('Retailpris ligger inom %.1f%% från supplierpris eller under supplierpris.', self::PRICE_GAP_NEAR_PERCENT),
            self::TYPE_DISCOUNT_MARGIN_RISK => sprintf('Aktiv procent-rabatt pressar enkel marginal under %.1f%%.', self::DISCOUNT_SAFE_MARGIN_PERCENT),
        ];
    }

    /** @param array<int,array<string,mixed>> $rows
     * @return array<string,int>
     */
    private function buildCounts(array $rows): array
    {
        $counts = [
            'total' => count($rows),
            self::TYPE_MARGIN_PRESSURE => 0,
            self::TYPE_SUPPLIER_PRICE_MOVED => 0,
            self::TYPE_PRICE_GAP_CHECK => 0,
            self::TYPE_DISCOUNT_MARGIN_RISK => 0,
        ];

        foreach ($rows as $row) {
            $type = (string) ($row['insight_type'] ?? '');
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }

        return $counts;
    }

    /** @param array<string,mixed> $product
     * @param array<string,mixed> $signal
     * @param array<string,mixed>|null $activeDiscount
     * @return array<int,string>
     */
    private function evaluateInsightTypes(array $product, array $signal, ?array $activeDiscount): array
    {
        $types = [];

        $retail = (float) ($product['sale_price'] ?? 0.0);
        $supplier = (float) ($signal['selected_supplier_price'] ?? 0.0);
        if ($retail <= 0 || $supplier <= 0) {
            return $types;
        }

        $marginAmount = $retail - $supplier;
        $marginPercent = $retail > 0 ? ($marginAmount / $retail) * 100.0 : 0.0;

        if ($marginPercent < self::MARGIN_PRESSURE_MIN_PERCENT || $marginAmount < self::MARGIN_PRESSURE_MIN_AMOUNT) {
            $types[] = self::TYPE_MARGIN_PRESSURE;
        }

        $previous = (float) ($signal['previous_supplier_price'] ?? 0.0);
        if ($previous > 0.0) {
            $delta = $supplier - $previous;
            $deltaPercent = ($delta / $previous) * 100.0;
            if (abs($deltaPercent) >= self::SUPPLIER_MOVE_MIN_PERCENT || abs($delta) >= self::SUPPLIER_MOVE_MIN_AMOUNT) {
                $types[] = self::TYPE_SUPPLIER_PRICE_MOVED;
            }
        }

        $gapPercent = $supplier > 0.0 ? (($retail - $supplier) / $supplier) * 100.0 : 0.0;
        if ($retail <= $supplier || $gapPercent <= self::PRICE_GAP_NEAR_PERCENT) {
            $types[] = self::TYPE_PRICE_GAP_CHECK;
        }

        if ($activeDiscount !== null) {
            $discountPercent = (float) ($activeDiscount['discount_value'] ?? 0.0);
            $discountedRetail = $retail * (1 - ($discountPercent / 100));
            $discountedMarginPercent = $discountedRetail > 0.0 ? (($discountedRetail - $supplier) / $discountedRetail) * 100.0 : 0.0;
            if ($discountedRetail > 0 && $discountedMarginPercent < self::DISCOUNT_SAFE_MARGIN_PERCENT) {
                $types[] = self::TYPE_DISCOUNT_MARGIN_RISK;
            }
        }

        return $types;
    }

    /** @param array<string,mixed> $product
     * @param array<string,mixed> $signal
     * @param array<string,mixed>|null $activeDiscount
     * @return array<string,mixed>
     */
    private function buildInsightRow(array $product, array $signal, string $type, ?array $activeDiscount): array
    {
        $productId = (int) ($product['id'] ?? 0);
        $retail = (float) ($product['sale_price'] ?? 0.0);
        $supplier = (float) ($signal['selected_supplier_price'] ?? 0.0);
        $marginAmount = $retail - $supplier;
        $marginPercent = $retail > 0 ? ($marginAmount / $retail) * 100.0 : 0.0;

        $previous = (float) ($signal['previous_supplier_price'] ?? 0.0);
        $supplierDelta = $previous > 0 ? ($supplier - $previous) : 0.0;
        $supplierDeltaPercent = $previous > 0 ? (($supplierDelta / $previous) * 100.0) : 0.0;

        $discountedRetail = null;
        $discountedMarginPercent = null;
        if ($activeDiscount !== null) {
            $discountedRetail = $retail * (1 - (((float) ($activeDiscount['discount_value'] ?? 0.0)) / 100));
            $discountedMarginPercent = $discountedRetail > 0 ? (($discountedRetail - $supplier) / $discountedRetail) * 100.0 : null;
        }

        $row = [
            'product_id' => $productId,
            'product_name' => (string) ($product['name'] ?? ''),
            'sku' => (string) ($product['sku'] ?? ''),
            'brand_name' => (string) ($product['brand_name'] ?? ''),
            'category_name' => (string) ($product['category_name'] ?? ''),
            'currency_code' => (string) ($product['currency_code'] ?? 'SEK'),
            'retail_price' => $retail,
            'supplier_price' => $supplier,
            'supplier_name' => (string) ($signal['supplier_name'] ?? ''),
            'supplier_id' => (int) ($signal['supplier_id'] ?? 0),
            'supplier_sku' => (string) ($signal['supplier_sku'] ?? ''),
            'supplier_item_id' => (int) ($signal['supplier_item_id'] ?? 0),
            'selection_rule' => (string) ($signal['selection_rule'] ?? ''),
            'margin_amount' => $marginAmount,
            'margin_percent' => $marginPercent,
            'previous_supplier_price' => $previous,
            'supplier_change_amount' => $supplierDelta,
            'supplier_change_percent' => $supplierDeltaPercent,
            'insight_type' => $type,
            'insight_label' => $this->insightTypeOptions()[$type] ?? $type,
            'discount_code' => (string) ($activeDiscount['code'] ?? ''),
            'discount_name' => (string) ($activeDiscount['name'] ?? ''),
            'discount_percent' => (float) ($activeDiscount['discount_value'] ?? 0.0),
            'discounted_retail_price' => $discountedRetail,
            'discounted_margin_percent' => $discountedMarginPercent,
            'action_links' => $this->buildActionLinks($productId, $signal, $type),
            'priority' => 50,
            'summary' => '',
            'reason' => '',
        ];

        if ($type === self::TYPE_MARGIN_PRESSURE) {
            $row['priority'] = 85;
            $row['summary'] = 'Låg enkel bruttomarginal mellan retailpris och inköpssignal.';
            $row['reason'] = sprintf('Retail %.2f, supplier %.2f, marginal %.2f SEK (%.1f%%).', $retail, $supplier, $marginAmount, $marginPercent);
        } elseif ($type === self::TYPE_SUPPLIER_PRICE_MOVED) {
            $row['priority'] = 75;
            $row['summary'] = 'Supplierpris har ändrats tydligt sedan tidigare snapshot.';
            $row['reason'] = sprintf('Tidigare %.2f → nu %.2f (%.2f SEK / %.1f%%).', $previous, $supplier, $supplierDelta, $supplierDeltaPercent);
        } elseif ($type === self::TYPE_PRICE_GAP_CHECK) {
            $row['priority'] = $retail <= $supplier ? 90 : 65;
            $row['summary'] = 'Retailpris ligger ovanligt nära inköpspris och bör dubbelkollas.';
            $row['reason'] = sprintf('Retail %.2f mot supplier %.2f (gap %.1f%%).', $retail, $supplier, $supplier > 0 ? (($retail - $supplier) / $supplier) * 100.0 : 0.0);
        } else {
            $row['priority'] = 70;
            $row['summary'] = 'Aktiv rabattkod kan pressa marginalen under säker nivå.';
            $row['reason'] = sprintf('Kod %s (%s%%): effektiv marginal %.1f%%.', (string) ($activeDiscount['code'] ?? '-'), (string) ($activeDiscount['discount_value'] ?? '0'), (float) ($discountedMarginPercent ?? 0.0));
        }

        return $row;
    }

    /**
     * @param array<int,array<string,mixed>> $signals
     * @return array<string,mixed>|null
     */
    private function selectRelevantSupplierSignal(array $signals): ?array
    {
        if ($signals === []) {
            return null;
        }

        $primary = array_values(array_filter($signals, static fn (array $signal): bool => (int) ($signal['is_primary'] ?? 0) === 1));
        if ($primary !== []) {
            $selected = $primary[0];
            $selected['selection_rule'] = 'Primär produktkoppling (is_primary=1).';
            return $selected;
        }

        $validPriced = array_values(array_filter($signals, static fn (array $signal): bool => (float) ($signal['selected_supplier_price'] ?? 0.0) > 0));
        if ($validPriced !== []) {
            usort($validPriced, static fn (array $a, array $b): int => ((float) $a['selected_supplier_price']) <=> ((float) $b['selected_supplier_price']));
            $selected = $validPriced[0];
            $selected['selection_rule'] = 'Ingen primär koppling: lägsta giltiga supplierpris bland aktiva kopplingar.';
            return $selected;
        }

        $selected = $signals[0];
        $selected['selection_rule'] = 'Fallback: första tillgängliga koppling utan giltigt pris.';

        return $selected;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function normalizeSupplierRows(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            $latest = (float) ($row['latest_snapshot_price'] ?? 0.0);
            $supplierItem = (float) ($row['supplier_item_price'] ?? 0.0);
            $snapshot = (float) ($row['supplier_price_snapshot'] ?? 0.0);
            $selected = $latest > 0 ? $latest : ($supplierItem > 0 ? $supplierItem : $snapshot);

            $normalized[] = [
                'product_id' => (int) ($row['product_id'] ?? 0),
                'supplier_item_id' => (int) ($row['supplier_item_id'] ?? 0),
                'supplier_id' => (int) ($row['supplier_id'] ?? 0),
                'supplier_name' => (string) ($row['supplier_name'] ?? ''),
                'supplier_sku' => (string) ($row['supplier_sku'] ?? ($row['supplier_sku_snapshot'] ?? '')),
                'is_primary' => (int) ($row['is_primary'] ?? 0),
                'selected_supplier_price' => $selected,
                'previous_supplier_price' => (float) ($row['previous_snapshot_price'] ?? 0.0),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<int,array<string,mixed>>>
     */
    private function indexSupplierRowsByProduct(array $rows): array
    {
        $indexed = [];
        foreach ($this->normalizeSupplierRows($rows) as $row) {
            $indexed[(int) $row['product_id']][] = $row;
        }

        return $indexed;
    }

    /** @return array<int,array{label:string,url:string}> */
    private function buildActionLinks(int $productId, array $signal, string $type): array
    {
        $links = [
            ['label' => 'Produktedit', 'url' => '/admin/products/' . $productId . '/edit'],
            ['label' => 'Supplier monitoring', 'url' => '/admin/supplier-monitoring?supplier_id=' . (int) ($signal['supplier_id'] ?? 0)],
            ['label' => 'Supplier item review', 'url' => '/admin/supplier-item-review?supplier_sku=' . rawurlencode((string) ($signal['supplier_sku'] ?? ''))],
        ];

        if ($type === self::TYPE_DISCOUNT_MARGIN_RISK) {
            $links[] = ['label' => 'Kampanjkoder', 'url' => '/admin/discount-codes'];
        }

        return $links;
    }

    /** @param array<string,mixed> $filters
     * @return array{insight_type:string,supplier_id:string,brand_id:string,category_id:string,linked_only:string,search:string}
     */
    private function normalizeFilters(array $filters): array
    {
        $type = trim((string) ($filters['insight_type'] ?? 'all'));
        if (!array_key_exists($type, $this->insightTypeOptions())) {
            $type = 'all';
        }

        return [
            'insight_type' => $type,
            'supplier_id' => trim((string) ($filters['supplier_id'] ?? '')),
            'brand_id' => trim((string) ($filters['brand_id'] ?? '')),
            'category_id' => trim((string) ($filters['category_id'] ?? '')),
            'linked_only' => ((string) ($filters['linked_only'] ?? '') === '1') ? '1' : '0',
            'search' => trim((string) ($filters['search'] ?? '')),
        ];
    }

    /** @param array<string,mixed>|null $discount
     * @return array<string,mixed>|null
     */
    private function normalizeDiscount(?array $discount): ?array
    {
        if ($discount === null) {
            return null;
        }

        return [
            'id' => (int) ($discount['id'] ?? 0),
            'code' => (string) ($discount['code'] ?? ''),
            'name' => (string) ($discount['name'] ?? ''),
            'discount_value' => (float) ($discount['discount_value'] ?? 0.0),
        ];
    }
}
