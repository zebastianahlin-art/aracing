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
        $signals = $this->collectOperationalSnapshot();
        $sections = [
            $this->ordersSection($signals),
            $this->restockSection($signals),
            $this->aiImportSection($signals),
            $this->fitmentSection($signals),
            $this->supportReturnsSection($signals),
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

    /** @return array<string,int> */
    public function collectOperationalSnapshot(): array
    {
        $orderCounts = $this->safe(fn (): array => $this->orders->fulfillmentQueueCounts(), [
            'to_process' => 0,
            'pick' => 0,
            'pack' => 0,
            'ready_to_ship' => 0,
        ]);
        $refillRows = $this->safe(fn (): array => $this->purchasing->listRefillNeeds([]), []);
        $openDrafts = $this->safe(fn (): array => $this->purchaseDrafts->listDrafts('draft', null), []);
        $receivingPending = $this->safe(fn (): array => $this->purchaseDrafts->listDrafts(null, 'partially_received'), []);
        $pendingAiDrafts = $this->safe(fn (): array => $this->aiImports->listDrafts(['status' => 'pending']), []);
        $gapPayload = $this->safe(fn (): array => $this->fitmentGaps->adminQueue([]), ['rows' => []]);
        $reviewPayload = $this->safe(fn (): array => $this->fitmentReview->adminQueue(['status' => 'pending']), ['rows' => []]);

        $gapRows = is_array($gapPayload['rows'] ?? null) ? $gapPayload['rows'] : [];
        $reviewRows = is_array($reviewPayload['rows'] ?? null) ? $reviewPayload['rows'] : [];

        $supportOpen = $this->safe(fn (): int => count($this->support->listAdmin(['status' => 'open'])), 0);
        $supportInProgress = $this->safe(fn (): int => count($this->support->listAdmin(['status' => 'in_progress'])), 0);
        $returnsRequested = $this->safe(fn (): int => count($this->returns->listAdmin(['status' => 'requested'])), 0);
        $returnsReview = $this->safe(fn (): int => count($this->returns->listAdmin(['status' => 'under_review'])), 0);
        $activeStockAlerts = $this->safe(fn (): int => $this->stockAlerts->countActiveSubscriptions(), 0);

        return [
            'orders_to_process' => (int) ($orderCounts['to_process'] ?? 0),
            'orders_pick' => (int) ($orderCounts['pick'] ?? 0),
            'orders_pack' => (int) ($orderCounts['pack'] ?? 0),
            'orders_ready_to_ship' => (int) ($orderCounts['ready_to_ship'] ?? 0),
            'restock_candidates' => count($refillRows),
            'purchase_drafts_open' => count($openDrafts),
            'purchase_receiving_partial' => count($receivingPending),
            'ai_import_pending' => count($pendingAiDrafts),
            'ai_import_low_quality' => $this->countWhereQualityIsLow($pendingAiDrafts),
            'fitment_gap_queue' => count($gapRows),
            'fitment_review_pending' => count($reviewRows),
            'support_open' => $supportOpen,
            'support_in_progress' => $supportInProgress,
            'returns_requested' => $returnsRequested,
            'returns_under_review' => $returnsReview,
            'stock_alert_active' => $activeStockAlerts,
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

    /**
     * @param array<string,int> $signals
     * @return array<string,mixed>
     */
    private function ordersSection(array $signals): array
    {
        $attention = (int) ($signals['orders_pack'] ?? 0) + (int) ($signals['orders_ready_to_ship'] ?? 0);

        return [
            'key' => 'orders_fulfillment',
            'label' => 'Orders / fulfillment',
            'status' => $attention > 0 ? 'needs_attention' : 'stable',
            'attention_count' => $attention,
            'metrics' => [
                                ['label' => 'Att behandla', 'count' => (int) ($signals['orders_to_process'] ?? 0)],
                ['label' => 'Att plocka', 'count' => (int) ($signals['orders_pick'] ?? 0)],
                ['label' => 'Att packa', 'count' => (int) ($signals['orders_pack'] ?? 0)],
                ['label' => 'Redo att skicka', 'count' => (int) ($signals['orders_ready_to_ship'] ?? 0)],
            ],
            'action_links' => [
                ['label' => 'Öppna orderkö (pack)', 'url' => '/admin/orders?queue=pack'],
                ['label' => 'Öppna orderkö (redo att skicka)', 'url' => '/admin/orders?queue=ready_to_ship'],
            ],
        ];
    }

    /**
     * @param array<string,int> $signals
     * @return array<string,mixed>
     */
    private function restockSection(array $signals): array
    {
        $attention = (int) ($signals['restock_candidates'] ?? 0)
            + (int) ($signals['purchase_drafts_open'] ?? 0)
            + (int) ($signals['purchase_receiving_partial'] ?? 0);

        return [
            'key' => 'restock_purchasing',
            'label' => 'Restock / purchasing',
            'status' => $attention > 0 ? 'needs_attention' : 'stable',
            'attention_count' => $attention,
            'metrics' => [
                ['label' => 'Restock-kandidater', 'count' => (int) ($signals['restock_candidates'] ?? 0)],
                ['label' => 'Öppna inköpsutkast', 'count' => (int) ($signals['purchase_drafts_open'] ?? 0)],
                ['label' => 'Mottagning delvis mottagen', 'count' => (int) ($signals['purchase_receiving_partial'] ?? 0)],
            ],
            'action_links' => [
                ['label' => 'Öppna restock-vy', 'url' => '/admin/purchasing'],
                ['label' => 'Öppna inköpsutkast', 'url' => '/admin/purchase-order-drafts?status=draft'],
                ['label' => 'Öppna receiving (partiellt)', 'url' => '/admin/purchase-order-drafts?receiving_status=partially_received'],
            ],
        ];
    }

    /**
     * @param array<string,int> $signals
     * @return array<string,mixed>
     */
    private function aiImportSection(array $signals): array
    {
        return [
            'key' => 'ai_import_queue',
            'label' => 'AI import queue',
            'status' => ((int) ($signals['ai_import_pending'] ?? 0)) > 0 ? 'needs_attention' : 'stable',
            'attention_count' => (int) ($signals['ai_import_pending'] ?? 0),
            'metrics' => [
                ['label' => 'Pending review', 'count' => (int) ($signals['ai_import_pending'] ?? 0)],
                ['label' => 'Låg kvalitet', 'count' => (int) ($signals['ai_import_low_quality'] ?? 0)],
            ],
            'action_links' => [
                ['label' => 'Öppna AI URL-import (pending)', 'url' => '/admin/ai-product-import?status=pending'],
                ['label' => 'Öppna AI URL-import (low quality)', 'url' => '/admin/ai-product-import?status=pending&quality_label=low'],
            ],
        ];
    }

    /**
     * @param array<string,int> $signals
     * @return array<string,mixed>
     */
    private function fitmentSection(array $signals): array
    {
        $attention = (int) ($signals['fitment_gap_queue'] ?? 0) + (int) ($signals['fitment_review_pending'] ?? 0);

        return [
            'key' => 'fitment_review',
            'label' => 'Fitment review / fitment gaps',
            'status' => $attention > 0 ? 'needs_attention' : 'stable',
            'attention_count' => $attention,
            'metrics' => [
                ['label' => 'Fitment gap-kö', 'count' => (int) ($signals['fitment_gap_queue'] ?? 0)],
                ['label' => 'Supplier fitment review (pending)', 'count' => (int) ($signals['fitment_review_pending'] ?? 0)],
            ],
            'action_links' => [
                ['label' => 'Öppna fitment gap-kö', 'url' => '/admin/fitment-gaps'],
                ['label' => 'Öppna supplier fitment review', 'url' => '/admin/supplier-fitment-review?status=pending'],
            ],
        ];
    }

    /**
     * @param array<string,int> $signals
     * @return array<string,mixed>
     */
    private function supportReturnsSection(array $signals): array
    {
        $supportOpen = (int) ($signals['support_open'] ?? 0);
        $supportInProgress = (int) ($signals['support_in_progress'] ?? 0);
        $returnsRequested = (int) ($signals['returns_requested'] ?? 0);
        $returnsReview = (int) ($signals['returns_under_review'] ?? 0);
        $activeStockAlerts = (int) ($signals['stock_alert_active'] ?? 0);

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
