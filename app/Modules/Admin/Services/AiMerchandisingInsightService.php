<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Repositories\AiMerchandisingInsightRepository;

final class AiMerchandisingInsightService
{
    private const TYPE_WEAK_SECTION_STOCK = 'weak_section_stock';
    private const TYPE_STALE_SECTION = 'stale_section';
    private const TYPE_LOW_SIGNAL_SECTION = 'low_signal_section';
    private const TYPE_PROMISING_SECTION = 'promising_section';

    private const WEAK_STOCK_UNAVAILABLE_SHARE = 0.45;
    private const STALE_FRESH_SHARE_MAX = 0.20;
    private const LOW_SIGNAL_SCORE_MAX = 3;
    private const PROMISING_SIGNAL_SCORE_MIN = 10;

    public function __construct(private readonly AiMerchandisingInsightRepository $insights)
    {
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function listInsights(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $rows = $this->insights->listSectionSignals();

        $insightRows = [];
        $counts = ['total' => 0];
        foreach ($this->insightTypeOptions() as $type => $label) {
            if ($type === 'all') {
                continue;
            }
            $counts[$type] = 0;
        }

        foreach ($rows as $row) {
            $candidate = $this->buildInsightRow($row);
            if ($candidate === null) {
                continue;
            }

            $type = (string) ($candidate['insight_type'] ?? '');
            if ($normalized['insight_type'] !== 'all' && $type !== $normalized['insight_type']) {
                continue;
            }

            ++$counts['total'];
            $counts[$type] = (int) ($counts[$type] ?? 0) + 1;
            $insightRows[] = $candidate;
        }

        usort($insightRows, static fn (array $a, array $b): int => (int) ($b['priority'] ?? 0) <=> (int) ($a['priority'] ?? 0));

        return [
            'filters' => $normalized,
            'rows' => $insightRows,
            'counts' => $counts,
            'insight_type_options' => $this->insightTypeOptions(),
            'rule_info' => $this->ruleInfo(),
        ];
    }

    /** @return array<string,string> */
    public function insightTypeOptions(): array
    {
        return [
            'all' => 'Alla',
            self::TYPE_WEAK_SECTION_STOCK => 'Weak stock',
            self::TYPE_STALE_SECTION => 'Stale',
            self::TYPE_LOW_SIGNAL_SECTION => 'Low signal',
            self::TYPE_PROMISING_SECTION => 'Promising',
        ];
    }

    /** @return array<string,string> */
    private function ruleInfo(): array
    {
        return [
            self::TYPE_WEAK_SECTION_STOCK => 'Andel ej köpbara produkter i sektionen är minst 45%.',
            self::TYPE_STALE_SECTION => 'Låg andel nya produkter (under 20%) och svag till medel signal.',
            self::TYPE_LOW_SIGNAL_SECTION => 'Låg order-/demand-signal (score <= 3).',
            self::TYPE_PROMISING_SECTION => 'Stark signal (score >= 10) och hög köpbarhet i sektionen.',
        ];
    }

    /** @param array<string,mixed> $row
     * @return array<string,mixed>|null
     */
    private function buildInsightRow(array $row): ?array
    {
        $sectionId = (int) ($row['section_id'] ?? 0);
        $productCount = (int) ($row['product_count'] ?? 0);
        if ($sectionId <= 0 || $productCount <= 0) {
            return null;
        }

        $buyableCount = (int) ($row['buyable_count'] ?? 0);
        $freshCount = (int) ($row['fresh_product_count'] ?? 0);
        $sold30 = (int) ($row['sold_last_30_days'] ?? 0);
        $sold60 = (int) ($row['sold_last_60_days'] ?? 0);
        $wishlist = (int) ($row['wishlist_count'] ?? 0);
        $stockAlerts = (int) ($row['active_stock_alerts'] ?? 0);

        $unavailableShare = ($productCount - $buyableCount) / max(1, $productCount);
        $freshShare = $freshCount / max(1, $productCount);
        $demandScore = $sold30 + $wishlist + $stockAlerts;

        $type = null;
        $summary = '';
        $reason = '';
        $priority = 40;

        if ($unavailableShare >= self::WEAK_STOCK_UNAVAILABLE_SHARE) {
            $type = self::TYPE_WEAK_SECTION_STOCK;
            $summary = 'Hög andel ej köpbara produkter i sektionen.';
            $reason = sprintf(
                '%d av %d produkter är köpbara (%.0f%% ej köpbara).',
                $buyableCount,
                $productCount,
                $unavailableShare * 100
            );
            $priority = 90;
        } elseif ($demandScore >= self::PROMISING_SIGNAL_SCORE_MIN && $buyableCount >= (int) ceil($productCount * 0.70)) {
            $type = self::TYPE_PROMISING_SECTION;
            $summary = 'Sektionen visar lovande signaler och bra köpbarhet.';
            $reason = sprintf(
                'Demand-score %d (sålt30=%d, wishlist=%d, alerts=%d), köpbara %d/%d.',
                $demandScore,
                $sold30,
                $wishlist,
                $stockAlerts,
                $buyableCount,
                $productCount
            );
            $priority = 75;
        } elseif ($freshShare <= self::STALE_FRESH_SHARE_MAX && $demandScore <= (self::LOW_SIGNAL_SCORE_MAX + 1)) {
            $type = self::TYPE_STALE_SECTION;
            $summary = 'Sektionen ser ut att vara låg på fräschhet.';
            $reason = sprintf(
                'Nya produkter: %d/%d (%.0f%%), demand-score %d.',
                $freshCount,
                $productCount,
                $freshShare * 100,
                $demandScore
            );
            $priority = 60;
        } elseif ($demandScore <= self::LOW_SIGNAL_SCORE_MAX) {
            $type = self::TYPE_LOW_SIGNAL_SECTION;
            $summary = 'Sektionen har svag demand-/ordersignal.';
            $reason = sprintf(
                'Demand-score %d (sålt30=%d, wishlist=%d, alerts=%d).',
                $demandScore,
                $sold30,
                $wishlist,
                $stockAlerts
            );
            $priority = 55;
        }

        if ($type === null) {
            return null;
        }

        $insightLabel = (string) ($this->insightTypeOptions()[$type] ?? $type);
        $sectionTitle = trim((string) ($row['section_title'] ?? 'Sektion'));

        return [
            'section_id' => $sectionId,
            'section_title' => $sectionTitle,
            'section_key' => (string) ($row['section_key'] ?? ''),
            'insight_type' => $type,
            'insight_label' => $insightLabel,
            'summary' => $summary,
            'reason' => $reason,
            'product_count' => $productCount,
            'buyable_count' => $buyableCount,
            'fresh_product_count' => $freshCount,
            'sold_last_30_days' => $sold30,
            'sold_last_60_days' => $sold60,
            'wishlist_count' => $wishlist,
            'active_stock_alerts' => $stockAlerts,
            'demand_score' => $demandScore,
            'priority' => $priority,
            'action_links' => $this->buildActionLinks(
                $sectionId,
                $sectionTitle,
                (int) ($row['sample_product_id'] ?? 0)
            ),
        ];
    }

    /** @return array<int,array{label:string,url:string}> */
    private function buildActionLinks(int $sectionId, string $sectionTitle, int $sampleProductId): array
    {
        $search = rawurlencode($sectionTitle);
        $links = [
            ['label' => 'Homepage sections', 'url' => '/admin/homepage-sections'],
            ['label' => 'Section edit', 'url' => '/admin/homepage-sections#section-' . $sectionId],
            ['label' => 'AI merch suggestions', 'url' => '/admin/ai-merch-suggestions'],
            ['label' => 'Inventory insights', 'url' => '/admin/ai-inventory-insights?search=' . $search],
        ];

        if ($sampleProductId > 0) {
            $links[] = ['label' => 'Produktedit', 'url' => '/admin/products/' . $sampleProductId . '/edit'];
        }

        return $links;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{insight_type:string}
     */
    private function normalizeFilters(array $filters): array
    {
        $insightType = trim((string) ($filters['insight_type'] ?? 'all'));
        if (!array_key_exists($insightType, $this->insightTypeOptions())) {
            $insightType = 'all';
        }

        return ['insight_type' => $insightType];
    }
}
