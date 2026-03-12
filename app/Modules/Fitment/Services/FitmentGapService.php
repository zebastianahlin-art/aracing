<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Catalog\Repositories\CatalogRepository;
use App\Modules\Fitment\Repositories\FitmentFlagRepository;
use App\Modules\Fitment\Repositories\SupplierFitmentCandidateRepository;
use App\Modules\Product\Repositories\ProductRepository;

final class FitmentGapService
{
    private const ALLOWED_REASON_FILTERS = [
        'all',
        'no_fitment_links',
        'universal_only',
        'pending_supplier_candidates',
        'needs_review_flag',
        'category_low_coverage',
    ];

    public function __construct(
        private readonly ProductRepository $products,
        private readonly FitmentFlagRepository $flags,
        private readonly SupplierFitmentCandidateRepository $supplierCandidates,
        private readonly CatalogRepository $catalog
    ) {
    }

    /** @param array<string,mixed> $filters
     * @return array{rows:array<int,array<string,mixed>>,filters:array<string,string>,totals:array<string,int>}
     */
    public function adminQueue(array $filters): array
    {
        $normalized = $this->normalizeFilters($filters);
        $rows = $this->products->fitmentGapQueueOverview($normalized);
        $productIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows);

        $flagsByProduct = [];
        foreach ($this->flags->byProductIds($productIds) as $row) {
            $flagsByProduct[(int) ($row['product_id'] ?? 0)] = $row;
        }

        $pendingByProduct = $this->supplierCandidates->pendingCountByProductIds($productIds);
        $lowCoverageByCategory = $this->buildLowCoverageCategoryMap($rows);

        $queueRows = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['id'] ?? 0);
            $categoryId = (int) ($row['category_id'] ?? 0);
            $fitmentCount = (int) ($row['fitment_count'] ?? 0);
            $confirmedCount = (int) ($row['confirmed_fitment_count'] ?? 0);
            $universalCount = (int) ($row['universal_fitment_count'] ?? 0);
            $pendingCount = (int) ($pendingByProduct[$productId] ?? 0);
            $flagStatus = (string) (($flagsByProduct[$productId]['status'] ?? ''));

            $reasons = $this->gapReasonsForRow($fitmentCount, $confirmedCount, $universalCount, $pendingCount, $flagStatus, isset($lowCoverageByCategory[$categoryId]));
            if ($reasons === []) {
                continue;
            }

            if ($normalized['reason'] !== 'all' && $this->hasReason($reasons, $normalized['reason']) === false) {
                continue;
            }

            $queueRows[] = [
                ...$row,
                'pending_supplier_candidates_count' => $pendingCount,
                'fitment_review_status' => $flagStatus,
                'fitment_note' => (string) (($flagsByProduct[$productId]['note'] ?? '')),
                'gap_reasons' => $reasons,
                'gap_count' => count($reasons),
                'category_coverage' => $lowCoverageByCategory[$categoryId] ?? null,
                'actions' => $this->actionLinks($productId),
            ];
        }

        usort($queueRows, [$this, 'compareRows']);

        return [
            'rows' => $queueRows,
            'filters' => $normalized,
            'totals' => $this->buildTotals($queueRows),
        ];
    }

    /** @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildLowCoverageCategoryMap(array $rows): array
    {
        $categoryIds = [];
        foreach ($rows as $row) {
            $categoryId = (int) ($row['category_id'] ?? 0);
            if ($categoryId > 0) {
                $categoryIds[$categoryId] = true;
            }
        }

        $coverageRows = $this->catalog->adminFitmentCoverageByCategory(['sort' => 'worst', 'only_missing' => '1', 'query' => '']);
        $map = [];
        foreach ($coverageRows as $coverageRow) {
            $categoryId = (int) ($coverageRow['category_id'] ?? 0);
            if ($categoryId <= 0 || !isset($categoryIds[$categoryId])) {
                continue;
            }

            $ratio = (float) ($coverageRow['coverage_ratio'] ?? 0);
            if ($ratio >= 40.0) {
                continue;
            }

            $map[$categoryId] = [
                'coverage_ratio' => $ratio,
                'label' => sprintf('Låg kategori-coverage (%.1f%%)', $ratio),
            ];
        }

        return $map;
    }

    /** @return array<int,array{code:string,label:string,description:string}> */
    private function gapReasonsForRow(int $fitmentCount, int $confirmedCount, int $universalCount, int $pendingCount, string $flagStatus, bool $categoryLowCoverage): array
    {
        $reasons = [];

        if ($fitmentCount <= 0) {
            $reasons[] = [
                'code' => 'no_fitment_links',
                'label' => 'Saknar fitmentkoppling',
                'description' => 'Produkten har inga product_fitments.',
            ];
        }

        if ($fitmentCount > 0 && $universalCount > 0 && $confirmedCount === 0) {
            $reasons[] = [
                'code' => 'universal_only',
                'label' => 'Endast universal',
                'description' => 'Produkten har bara universal-fitments och saknar confirmed.',
            ];
        }

        if ($pendingCount > 0) {
            $reasons[] = [
                'code' => 'pending_supplier_candidates',
                'label' => 'Väntande supplier-underlag',
                'description' => sprintf('%d supplier_fitment_candidates väntar review.', $pendingCount),
            ];
        }

        if ($flagStatus === 'needs_fitment' || $flagStatus === 'handling') {
            $reasons[] = [
                'code' => 'needs_review_flag',
                'label' => 'Behöver intern review',
                'description' => 'Produktens fitment_flag markerar behov av granskning.',
            ];
        }

        if ($categoryLowCoverage) {
            $reasons[] = [
                'code' => 'category_low_coverage',
                'label' => 'Kategori med låg coverage',
                'description' => 'Produktens kategori ligger under enkel coverage-tröskel i admin coverage-vyn.',
            ];
        }

        return $reasons;
    }

    /** @param array<int,array<string,mixed>> $queueRows
     * @return array<string,int>
     */
    private function buildTotals(array $queueRows): array
    {
        $totals = [
            'rows' => count($queueRows),
            'no_fitment_links' => 0,
            'pending_supplier_candidates' => 0,
            'needs_review_flag' => 0,
        ];

        foreach ($queueRows as $row) {
            foreach (($row['gap_reasons'] ?? []) as $reason) {
                $code = (string) ($reason['code'] ?? '');
                if (array_key_exists($code, $totals)) {
                    $totals[$code]++;
                }
            }
        }

        return $totals;
    }

    /** @param array<string,mixed> $left
     * @param array<string,mixed> $right
     */
    private function compareRows(array $left, array $right): int
    {
        $byGapCount = ((int) ($right['gap_count'] ?? 0)) <=> ((int) ($left['gap_count'] ?? 0));
        if ($byGapCount !== 0) {
            return $byGapCount;
        }

        $byPending = ((int) ($right['pending_supplier_candidates_count'] ?? 0)) <=> ((int) ($left['pending_supplier_candidates_count'] ?? 0));
        if ($byPending !== 0) {
            return $byPending;
        }

        $leftFitments = (int) ($left['fitment_count'] ?? 0);
        $rightFitments = (int) ($right['fitment_count'] ?? 0);
        if ($leftFitments !== $rightFitments) {
            return $leftFitments <=> $rightFitments;
        }

        return ((int) ($right['id'] ?? 0)) <=> ((int) ($left['id'] ?? 0));
    }

    /** @return array<string,string> */
    private function actionLinks(int $productId): array
    {
        return [
            'product_url' => '/admin/products/' . $productId . '/edit#fitment',
            'workflow_url' => '/admin/fitment-workflow?query=' . urlencode((string) $productId),
            'supplier_review_url' => '/admin/supplier-fitment-review?status=pending&query=' . urlencode((string) $productId),
        ];
    }

    /** @param array<int,array<string,mixed>> $reasons */
    private function hasReason(array $reasons, string $reasonCode): bool
    {
        foreach ($reasons as $reason) {
            if ((string) ($reason['code'] ?? '') === $reasonCode) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string,mixed> $filters
     * @return array<string,string>
     */
    private function normalizeFilters(array $filters): array
    {
        $reason = trim((string) ($filters['reason'] ?? 'all'));
        if (!in_array($reason, self::ALLOWED_REASON_FILTERS, true)) {
            $reason = 'all';
        }

        return [
            'reason' => $reason,
            'query' => mb_substr(trim((string) ($filters['query'] ?? '')), 0, 120),
            'brand_id' => $this->normalizeNumericFilter($filters['brand_id'] ?? ''),
            'category_id' => $this->normalizeNumericFilter($filters['category_id'] ?? ''),
        ];
    }

    private function normalizeNumericFilter(mixed $value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '' || ctype_digit($normalized) === false) {
            return '';
        }

        return $normalized;
    }
}
