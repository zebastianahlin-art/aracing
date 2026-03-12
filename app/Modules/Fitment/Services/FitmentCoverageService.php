<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Catalog\Repositories\CatalogRepository;

final class FitmentCoverageService
{
    public function __construct(private readonly CatalogRepository $catalog)
    {
    }

    /**
     * @param array<int,array<string,mixed>> $categories
     * @param array<string,mixed>|null $activeVehicle
     * @return array<int,array<string,mixed>>
     */
    public function decorateStorefrontCategories(array $categories, ?array $activeVehicle): array
    {
        if ($activeVehicle === null || (int) ($activeVehicle['id'] ?? 0) <= 0 || $categories === []) {
            return $categories;
        }

        $categoryIds = array_values(array_filter(array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $categories)));
        $totalByCategory = $this->catalog->publicProductCountsByCategoryIds($categoryIds);
        $matchedByCategory = $this->catalog->publicFitmentMatchedCountsByCategoryIds((int) $activeVehicle['id'], $categoryIds);

        foreach ($categories as &$category) {
            $categoryId = (int) ($category['id'] ?? 0);
            $total = (int) ($totalByCategory[$categoryId] ?? 0);
            $matched = (int) ($matchedByCategory[$categoryId] ?? 0);

            $category['coverage'] = $this->storefrontCoverageSignal($matched, $total);
        }
        unset($category);

        return $categories;
    }

    /** @return array<string,mixed>|null */
    public function categoryContextSignal(array $filters, ?array $activeVehicle, int $resultCount): ?array
    {
        if ($activeVehicle === null || (int) ($activeVehicle['id'] ?? 0) <= 0) {
            return null;
        }

        if (((string) ($filters['fitment_only'] ?? '0')) !== '1') {
            return null;
        }

        return [
            'label' => sprintf('%d produkter matchar vald bil i denna kategori.', max(0, $resultCount)),
            'disclaimer' => 'Fitment coverage är vägledning baserat på confirmed/universal-kopplingar i aktuell data.',
        ];
    }

    /** @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function adminCategoryCoverage(array $filters): array
    {
        $normalized = $this->normalizeAdminFilters($filters);
        $rows = $this->catalog->adminFitmentCoverageByCategory($normalized);

        foreach ($rows as &$row) {
            $row['signal'] = $this->adminSignal((float) ($row['coverage_ratio'] ?? 0), (int) ($row['without_fitment_count'] ?? 0));
            $row['fitment_workflow_url'] = '/admin/fitment-workflow?queue=without_fitment&category_id=' . (int) ($row['category_id'] ?? 0);
        }
        unset($row);

        $totals = [
            'categories' => count($rows),
            'public_products' => 0,
            'with_fitment' => 0,
            'without_fitment' => 0,
        ];

        foreach ($rows as $row) {
            $totals['public_products'] += (int) ($row['public_product_count'] ?? 0);
            $totals['with_fitment'] += (int) ($row['with_fitment_count'] ?? 0);
            $totals['without_fitment'] += (int) ($row['without_fitment_count'] ?? 0);
        }

        return [
            'rows' => $rows,
            'filters' => $normalized,
            'totals' => $totals,
        ];
    }

    /** @return array<string,mixed> */
    private function storefrontCoverageSignal(int $matched, int $total): array
    {
        $coveragePercent = $total > 0 ? (int) round(($matched / $total) * 100) : 0;

        return [
            'matched_products' => max(0, $matched),
            'public_products' => max(0, $total),
            'coverage_percent' => $coveragePercent,
            'label' => $matched > 0
                ? sprintf('%d produkter för vald bil', $matched)
                : 'Inga bekräftade träffar just nu',
            'hint' => 'Vägledning baserad på confirmed/universal-fitment för synliga produkter.',
            'has_matches' => $matched > 0,
        ];
    }

    /** @return array{code:string,label:string} */
    private function adminSignal(float $coverageRatio, int $withoutFitmentCount): array
    {
        if ($withoutFitmentCount <= 0) {
            return ['code' => 'good', 'label' => 'Hög coverage'];
        }

        if ($coverageRatio >= 70.0) {
            return ['code' => 'ok', 'label' => 'Bra coverage'];
        }

        if ($coverageRatio >= 40.0) {
            return ['code' => 'warn', 'label' => 'Behöver förbättras'];
        }

        return ['code' => 'bad', 'label' => 'Låg coverage'];
    }

    /** @param array<string,mixed> $filters
     * @return array<string,string>
     */
    private function normalizeAdminFilters(array $filters): array
    {
        $sort = trim((string) ($filters['sort'] ?? 'worst'));
        if (!in_array($sort, ['worst', 'best'], true)) {
            $sort = 'worst';
        }

        return [
            'sort' => $sort,
            'only_missing' => ((string) ($filters['only_missing'] ?? '0')) === '1' ? '1' : '0',
            'query' => mb_substr(trim((string) ($filters['query'] ?? '')), 0, 120),
        ];
    }
}
