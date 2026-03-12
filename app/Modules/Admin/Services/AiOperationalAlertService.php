<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use Throwable;

final class AiOperationalAlertService
{
    /** @var array<string,array{warning:int,critical:int}> */
    public const THRESHOLDS = [
        'fulfillment_backlog' => ['warning' => 8, 'critical' => 20],
        'restock_pressure' => ['warning' => 15, 'critical' => 35],
        'ai_import_low_quality' => ['warning' => 5, 'critical' => 12],
        'fitment_review_backlog' => ['warning' => 12, 'critical' => 30],
        'support_backlog' => ['warning' => 10, 'critical' => 24],
        'stock_alert_pressure' => ['warning' => 20, 'critical' => 50],
    ];

    public function __construct(private readonly AiOperationalInsightsService $insights)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function buildActiveAlerts(): array
    {
        $signals = $this->safe(fn (): array => $this->insights->collectOperationalSnapshot(), []);

        $alerts = array_values(array_filter([
            $this->buildFulfillmentBacklogAlert($signals),
            $this->buildRestockPressureAlert($signals),
            $this->buildAiImportLowQualityAlert($signals),
            $this->buildFitmentReviewBacklogAlert($signals),
            $this->buildSupportBacklogAlert($signals),
            $this->buildStockAlertPressureAlert($signals),
        ]));

        usort($alerts, function (array $a, array $b): int {
            $severityCmp = $this->severityWeight((string) ($b['severity'] ?? 'info')) <=> $this->severityWeight((string) ($a['severity'] ?? 'info'));
            if ($severityCmp !== 0) {
                return $severityCmp;
            }

            return ((int) ($b['count'] ?? 0)) <=> ((int) ($a['count'] ?? 0));
        });

        return $alerts;
    }

    /** @return array<string,mixed> */
    public function buildDashboardSummary(int $limit = 3): array
    {
        $alerts = $this->buildActiveAlerts();
        $critical = 0;
        $warning = 0;
        $info = 0;

        foreach ($alerts as $alert) {
            $severity = (string) ($alert['severity'] ?? 'info');
            if ($severity === 'critical') {
                $critical++;
            } elseif ($severity === 'warning') {
                $warning++;
            } else {
                $info++;
            }
        }

        return [
            'active_count' => count($alerts),
            'critical_count' => $critical,
            'warning_count' => $warning,
            'info_count' => $info,
            'top_alerts' => array_slice($alerts, 0, max(1, $limit)),
        ];
    }

    /** @param array<string,int> $signals
     *  @return array<string,mixed>|null
     */
    private function buildFulfillmentBacklogAlert(array $signals): ?array
    {
        $count = (int) ($signals['orders_pack'] ?? 0) + (int) ($signals['orders_ready_to_ship'] ?? 0);

        return $this->buildAlert(
            'fulfillment_backlog',
            $count,
            'Fulfillment-kö växer',
            sprintf('%d ordrar väntar i pack/redo att skicka. Kontrollera orderkön för att undvika leveransförseningar.', $count),
            '/admin/orders?queue=pack'
        );
    }

    /** @param array<string,int> $signals
     *  @return array<string,mixed>|null
     */
    private function buildRestockPressureAlert(array $signals): ?array
    {
        $count = (int) ($signals['restock_candidates'] ?? 0)
            + (int) ($signals['purchase_drafts_open'] ?? 0)
            + (int) ($signals['purchase_receiving_partial'] ?? 0);

        return $this->buildAlert(
            'restock_pressure',
            $count,
            'Högt tryck i restock/inköp',
            sprintf('%d signaler i restock, inköpsutkast eller receiving kräver uppföljning.', $count),
            '/admin/purchasing'
        );
    }

    /** @param array<string,int> $signals
     *  @return array<string,mixed>|null
     */
    private function buildAiImportLowQualityAlert(array $signals): ?array
    {
        $count = (int) ($signals['ai_import_low_quality'] ?? 0);

        return $this->buildAlert(
            'ai_import_low_quality',
            $count,
            'Låg kvalitet i AI-importutkast',
            sprintf('%d pending AI-importutkast har låg kvalitet och bör granskas manuellt.', $count),
            '/admin/ai-product-import?status=pending&quality_label=low'
        );
    }

    /** @param array<string,int> $signals
     *  @return array<string,mixed>|null
     */
    private function buildFitmentReviewBacklogAlert(array $signals): ?array
    {
        $count = (int) ($signals['fitment_gap_queue'] ?? 0) + (int) ($signals['fitment_review_pending'] ?? 0);

        return $this->buildAlert(
            'fitment_review_backlog',
            $count,
            'Fitment-kö kräver åtgärd',
            sprintf('%d fitment gaps/reviews väntar, vilket kan påverka fordonsmatchning i butik.', $count),
            '/admin/fitment-gaps'
        );
    }

    /** @param array<string,int> $signals
     *  @return array<string,mixed>|null
     */
    private function buildSupportBacklogAlert(array $signals): ?array
    {
        $count = (int) ($signals['support_open'] ?? 0)
            + (int) ($signals['support_in_progress'] ?? 0)
            + (int) ($signals['returns_requested'] ?? 0)
            + (int) ($signals['returns_under_review'] ?? 0);

        return $this->buildAlert(
            'support_backlog',
            $count,
            'Support/returer har backlog',
            sprintf('%d support- och returärenden väntar handläggning.', $count),
            '/admin/support-cases?status=open'
        );
    }

    /** @param array<string,int> $signals
     *  @return array<string,mixed>|null
     */
    private function buildStockAlertPressureAlert(array $signals): ?array
    {
        $count = (int) ($signals['stock_alert_active'] ?? 0);

        return $this->buildAlert(
            'stock_alert_pressure',
            $count,
            'Många aktiva lagerbevakningar',
            sprintf('%d aktiva stock alerts signalerar efterfrågan utan leverans.', $count),
            '/admin/purchasing'
        );
    }

    /** @return array<string,mixed>|null */
    private function buildAlert(string $type, int $count, string $title, string $message, string $targetUrl): ?array
    {
        if ($count <= 0) {
            return null;
        }

        $severity = $this->resolveSeverity($type, $count);

        return [
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'count' => $count,
            'target_url' => $targetUrl,
            'action_label' => 'Öppna åtgärdsvy',
            'explanation' => sprintf('Regel: %s >= %d (%s) / >= %d (critical).', $type, self::THRESHOLDS[$type]['warning'], $severity, self::THRESHOLDS[$type]['critical']),
        ];
    }

    private function resolveSeverity(string $type, int $count): string
    {
        $warning = self::THRESHOLDS[$type]['warning'] ?? 1;
        $critical = self::THRESHOLDS[$type]['critical'] ?? PHP_INT_MAX;

        if ($count >= $critical) {
            return 'critical';
        }

        if ($count >= $warning) {
            return 'warning';
        }

        return 'info';
    }

    private function severityWeight(string $severity): int
    {
        return match ($severity) {
            'critical' => 3,
            'warning' => 2,
            default => 1,
        };
    }

    private function safe(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return $fallback;
        }
    }
}
