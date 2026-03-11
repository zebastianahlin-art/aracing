<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\FitmentFlagRepository;
use App\Modules\Product\Repositories\ProductRepository;

final class FitmentWorkflowService
{
    private const ALLOWED_QUEUE = ['all', 'without_fitment', 'with_fitment', 'universal', 'needs_review'];
    private const ALLOWED_STATUS = ['needs_fitment', 'reviewed', 'handling'];

    public function __construct(
        private readonly ProductRepository $products,
        private readonly FitmentFlagRepository $flags
    ) {
    }

    /** @param array<string,mixed> $filters
     * @return array{rows:array<int,array<string,mixed>>,filters:array<string,string>,totals:array<string,int>}
     */
    public function adminQueue(array $filters): array
    {
        $normalized = $this->normalizeFilters($filters);
        $rows = $this->products->fitmentWorkflowOverview($normalized);

        $productIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $flags = [];
        foreach ($this->flags->byProductIds($productIds) as $row) {
            $flags[(int) $row['product_id']] = $row;
        }

        foreach ($rows as &$row) {
            $flag = $flags[(int) $row['id']] ?? null;
            $fitmentCount = (int) ($row['fitment_count'] ?? 0);
            $universalCount = (int) ($row['universal_fitment_count'] ?? 0);
            $confirmedCount = (int) ($row['confirmed_fitment_count'] ?? 0);

            $row['fitment_review_status'] = (string) ($flag['status'] ?? '');
            $row['fitment_note'] = (string) ($flag['note'] ?? '');
            $row['workflow_signal'] = $this->signalForRow($fitmentCount, $universalCount, $confirmedCount, $row['fitment_review_status']);
        }
        unset($row);


        if ($normalized['queue'] === 'needs_review') {
            $rows = array_values(array_filter($rows, static fn (array $row): bool => (string) ($row['workflow_signal']['code'] ?? '') === 'needs_review'));
        }

        return [
            'rows' => $rows,
            'filters' => $normalized,
            'totals' => $this->products->fitmentWorkflowTotals(),
        ];
    }

    /** @param array<string,mixed> $input */
    public function updateFlag(int $productId, array $input): void
    {
        $status = trim((string) ($input['status'] ?? 'needs_fitment'));
        if (!in_array($status, self::ALLOWED_STATUS, true)) {
            $status = 'needs_fitment';
        }

        $note = trim((string) ($input['note'] ?? ''));
        $this->flags->upsert($productId, $status, $note !== '' ? mb_substr($note, 0, 2000) : null);
    }

    /** @return array<int,string> */
    public function allowedStatuses(): array
    {
        return self::ALLOWED_STATUS;
    }

    private function signalForRow(int $fitmentCount, int $universalCount, int $confirmedCount, string $reviewStatus): array
    {
        if ($reviewStatus === 'needs_fitment' || $reviewStatus === 'handling') {
            return ['code' => 'needs_review', 'label' => 'Behöver granskning'];
        }

        if ($fitmentCount === 0) {
            return ['code' => 'no_fitment_links', 'label' => 'Saknar fitmentkoppling'];
        }

        if ($universalCount > 0 && $confirmedCount === 0) {
            return ['code' => 'universal_only', 'label' => 'Endast universal'];
        }

        if ($confirmedCount > 0) {
            return ['code' => 'has_confirmed_fitments', 'label' => 'Har bekräftade fitments'];
        }

        return ['code' => 'needs_review', 'label' => 'Behöver granskning'];
    }

    /** @param array<string,mixed> $filters
     * @return array<string,string>
     */
    private function normalizeFilters(array $filters): array
    {
        $queue = trim((string) ($filters['queue'] ?? 'all'));
        if (!in_array($queue, self::ALLOWED_QUEUE, true)) {
            $queue = 'all';
        }

        $brandId = $this->normalizeNumericFilter($filters['brand_id'] ?? '');
        $categoryId = $this->normalizeNumericFilter($filters['category_id'] ?? '');

        return [
            'queue' => $queue,
            'query' => mb_substr(trim((string) ($filters['query'] ?? '')), 0, 120),
            'brand_id' => $brandId,
            'category_id' => $categoryId,
            'fitment_count_band' => trim((string) ($filters['fitment_count_band'] ?? '')),
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
