<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Fitment\Services\FitmentGapService;
use App\Modules\Fitment\Services\SupplierFitmentReviewService;
use App\Modules\Import\Services\AiProductImportService;
use App\Modules\Order\Services\OrderService;
use App\Modules\Purchasing\Services\PurchaseOrderDraftService;
use App\Modules\Purchasing\Services\PurchasingService;
use App\Modules\Returns\Services\ReturnRequestService;
use App\Modules\StockAlert\Repositories\StockAlertRepository;
use App\Modules\Support\Services\SupportCaseService;
use Throwable;

final class AiOperationalInsightsService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PurchasingService $purchasing,
        private readonly PurchaseOrderDraftService $purchaseDrafts,
        private readonly AiProductImportService $aiImports,
        private readonly FitmentGapService $fitmentGaps,
        private readonly SupplierFitmentReviewService $fitmentReview,
        private readonly SupportCaseService $support,
        private readonly ReturnRequestService $returns,
        private readonly StockAlertRepository $stockAlerts,
    ) {
    }

    /** @return array<string,mixed> */
    public function buildDailyOperationsReport(): array
    {
        $sections = [
            $this->ordersSection(),
            $this->restockSection(),
            $this->aiImportSection(),
            $this->fitmentSection(),
            $this->supportReturnsSection(),
        ];

        $payload = [
            'report_type' => 'daily_operations',
            'generated_at' => date('Y-m-d H:i:s'),
            'sections' => $sections,
            'totals' => $this->buildTotals($sections),
        ];

        return [
            'title' => 'AI Operational Insights / Daily Report v1',
            'summary_text' => $this->buildSummaryText($payload),
            'structured_payload' => $payload,
            'sections' => $sections,
            'generated_at' => $payload['generated_at'],
        ];
    }

    /** @param array<string,mixed> $payload */
    private function buildSummaryText(array $payload): string
    {
        $totals = $payload['totals'] ?? [];

        return sprintf(
            'Idag kräver %d ordrar fulfillment-åtgärd, %d produkter behöver restockgranskning, %d AI-importutkast väntar review, %d fitment-ärenden väntar hantering och %d support/returärenden är öppna.',
            (int) ($totals['orders_attention'] ?? 0),
            (int) ($totals['restock_attention'] ?? 0),
            (int) ($totals['ai_import_attention'] ?? 0),
            (int) ($totals['fitment_attention'] ?? 0),
            (int) ($totals['support_returns_attention'] ?? 0),
        );
    }

    /** @return array<string,mixed> */
    private function ordersSection(): array
    {
        $counts = $this->safe(static fn (): array => $this->orders->fulfillmentQueueCounts(), [
            'to_process' => 0,
            'pick' => 0,
            'pack' => 0,
            'ready_to_ship' => 0,
        ]);

        $attention = (int) ($counts['pack'] ?? 0) + (int) ($counts['ready_to_ship'] ?? 0);

        return [
            'key' => 'orders_fulfillment',
            'label' => 'Orders / fulfillment',
            'status' => $attention > 0 ? 'needs_attention' : 'stable',
            'attention_count' => $attention,
            'metrics' => [
                ['label' => 'Att behandla', 'count' => (int) ($counts['to_process'] ?? 0)],
                ['label' => 'Att plocka', 'count' => (int) ($counts['pick'] ?? 0)],
                ['label' => 'Att packa', 'count' => (int) ($counts['pack'] ?? 0)],
                ['label' => 'Redo att skicka', 'count' => (int) ($counts['ready_to_ship'] ?? 0)],
            ],
            'action_links' => [
                ['label' => 'Öppna orderkö (pack)', 'url' => '/admin/orders?queue=pack'],
                ['label' => 'Öppna orderkö (redo att skicka)', 'url' => '/admin/orders?queue=ready_to_ship'],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function restockSection(): array
    {
        $refillRows = $this->safe(static fn (): array => $this->purchasing->listRefillNeeds([]), []);
        $openDrafts = $this->safe(static fn (): array => $this->purchaseDrafts->listDrafts('draft', null), []);
        $receivingPending = $this->safe(static fn (): array => $this->purchaseDrafts->listDrafts(null, 'partially_received'), []);

        $attention = count($refillRows) + count($openDrafts) + count($receivingPending);

        return [
            'key' => 'restock_purchasing',
            'label' => 'Restock / purchasing',
            'status' => $attention > 0 ? 'needs_attention' : 'stable',
            'attention_count' => $attention,
            'metrics' => [
                ['label' => 'Restock-kandidater', 'count' => count($refillRows)],
                ['label' => 'Öppna inköpsutkast', 'count' => count($openDrafts)],
                ['label' => 'Mottagning delvis mottagen', 'count' => count($receivingPending)],
            ],
            'action_links' => [
                ['label' => 'Öppna restock-vy', 'url' => '/admin/purchasing'],
                ['label' => 'Öppna inköpsutkast', 'url' => '/admin/purchase-order-drafts?status=draft'],
                ['label' => 'Öppna receiving (partiellt)', 'url' => '/admin/purchase-order-drafts?receiving_status=partially_received'],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function aiImportSection(): array
    {
        $pending = $this->safe(static fn (): array => $this->aiImports->listDrafts(['status' => 'pending']), []);
        $lowQuality = $this->countWhereQualityIsLow($pending);

        return [
            'key' => 'ai_import_queue',
            'label' => 'AI import queue',
            'status' => count($pending) > 0 ? 'needs_attention' : 'stable',
            'attention_count' => count($pending),
            'metrics' => [
                ['label' => 'Pending review', 'count' => count($pending)],
                ['label' => 'Låg kvalitet', 'count' => $lowQuality],
            ],
            'action_links' => [
                ['label' => 'Öppna AI URL-import (pending)', 'url' => '/admin/ai-product-import?status=pending'],
                ['label' => 'Öppna AI URL-import (low quality)', 'url' => '/admin/ai-product-import?status=pending&quality_label=low'],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function fitmentSection(): array
    {
        $gapPayload = $this->safe(static fn (): array => $this->fitmentGaps->adminQueue([]), ['rows' => [], 'totals' => []]);
        $reviewPayload = $this->safe(static fn (): array => $this->fitmentReview->adminQueue(['status' => 'pending']), ['rows' => []]);

        $gapRows = is_array($gapPayload['rows'] ?? null) ? $gapPayload['rows'] : [];
        $reviewRows = is_array($reviewPayload['rows'] ?? null) ? $reviewPayload['rows'] : [];

        $attention = count($gapRows) + count($reviewRows);

        return [
            'key' => 'fitment_review',
            'label' => 'Fitment review / fitment gaps',
            'status' => $attention > 0 ? 'needs_attention' : 'stable',
            'attention_count' => $attention,
            'metrics' => [
                ['label' => 'Fitment gap-kö', 'count' => count($gapRows)],
                ['label' => 'Supplier fitment review (pending)', 'count' => count($reviewRows)],
            ],
            'action_links' => [
                ['label' => 'Öppna fitment gap-kö', 'url' => '/admin/fitment-gaps'],
                ['label' => 'Öppna supplier fitment review', 'url' => '/admin/supplier-fitment-review?status=pending'],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function supportReturnsSection(): array
    {
        $supportOpen = $this->safe(static fn (): int => count($this->support->listAdmin(['status' => 'open'])), 0);
        $supportInProgress = $this->safe(static fn (): int => count($this->support->listAdmin(['status' => 'in_progress'])), 0);
        $returnsRequested = $this->safe(static fn (): int => count($this->returns->listAdmin(['status' => 'requested'])), 0);
        $returnsReview = $this->safe(static fn (): int => count($this->returns->listAdmin(['status' => 'under_review'])), 0);
        $activeStockAlerts = $this->safe(static fn (): int => $this->stockAlerts->countActiveSubscriptions(), 0);

        $attention = $supportOpen + $supportInProgress + $returnsRequested + $returnsReview;

        return [
            'key' => 'support_returns',
            'label' => 'Support / returns',
            'status' => $attention > 0 ? 'needs_attention' : 'stable',
            'attention_count' => $attention,
            'metrics' => [
                ['label' => 'Support öppna', 'count' => $supportOpen],
                ['label' => 'Support under arbete', 'count' => $supportInProgress],
                ['label' => 'Returer: requested', 'count' => $returnsRequested],
                ['label' => 'Returer: under review', 'count' => $returnsReview],
                ['label' => 'Aktiva stock alerts', 'count' => $activeStockAlerts],
            ],
            'action_links' => [
                ['label' => 'Öppna supportärenden', 'url' => '/admin/support-cases?status=open'],
                ['label' => 'Öppna returer', 'url' => '/admin/returns?status=under_review'],
            ],
        ];
    }

    /** @param array<string,mixed> $sections
     *  @return array<string,int>
     */
    private function buildTotals(array $sections): array
    {
        $totals = [
            'orders_attention' => 0,
            'restock_attention' => 0,
            'ai_import_attention' => 0,
            'fitment_attention' => 0,
            'support_returns_attention' => 0,
        ];

        foreach ($sections as $section) {
            $key = (string) ($section['key'] ?? '');
            $count = (int) ($section['attention_count'] ?? 0);
            if ($key === 'orders_fulfillment') {
                $totals['orders_attention'] = $count;
            }
            if ($key === 'restock_purchasing') {
                $totals['restock_attention'] = $count;
            }
            if ($key === 'ai_import_queue') {
                $totals['ai_import_attention'] = $count;
            }
            if ($key === 'fitment_review') {
                $totals['fitment_attention'] = $count;
            }
            if ($key === 'support_returns') {
                $totals['support_returns_attention'] = $count;
            }
        }

        return $totals;
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function countWhereQualityIsLow(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $label = mb_strtolower(trim((string) ($row['quality_label'] ?? '')));
            if (in_array($label, ['low', 'critical', 'poor'], true)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @template T
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
