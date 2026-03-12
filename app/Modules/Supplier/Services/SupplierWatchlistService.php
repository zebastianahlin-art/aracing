<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Services;

use App\Modules\Brand\Repositories\BrandRepository;
use App\Modules\Supplier\Repositories\MonitoredSupplierEntityRepository;
use App\Modules\Supplier\Repositories\SupplierRepository;
use InvalidArgumentException;

final class SupplierWatchlistService
{
    private const ENTITY_TYPES = ['supplier', 'brand'];
    private const PRIORITY_LEVELS = ['normal', 'high', 'critical'];

    public function __construct(
        private readonly MonitoredSupplierEntityRepository $watchlist,
        private readonly SupplierRepository $suppliers,
        private readonly BrandRepository $brands,
    ) {
    }

    /** @return array<int,array<string,mixed>> */
    public function listForAdmin(): array
    {
        $rows = $this->watchlist->all();

        foreach ($rows as &$row) {
            $row['entity_label'] = $this->resolveEntityLabel((string) ($row['entity_type'] ?? ''), (int) ($row['entity_id'] ?? 0));
        }

        return $rows;
    }

    /** @param array<string,mixed> $input */
    public function createOrUpdate(array $input, ?int $createdByUserId = null): void
    {
        $entityType = $this->normalizeEntityType((string) ($input['entity_type'] ?? ''));
        $entityId = $this->normalizeEntityId($input['entity_id'] ?? null);
        $priority = $this->normalizePriority((string) ($input['priority_level'] ?? 'normal'));
        $note = $this->normalizeNote($input['note'] ?? null);

        $this->assertEntityExists($entityType, $entityId);

        $existing = $this->watchlist->findByEntity($entityType, $entityId);
        if ($existing === null) {
            $this->watchlist->create($entityType, $entityId, $priority, $note, $createdByUserId);
            return;
        }

        $this->watchlist->reactivateWithPayload((int) $existing['id'], $priority, $note, $createdByUserId);
    }

    /** @param array<string,mixed> $input */
    public function updateEntry(int $id, array $input): void
    {
        $existing = $this->watchlist->findById($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Bevakad post hittades inte.');
        }

        $priority = $this->normalizePriority((string) ($input['priority_level'] ?? 'normal'));
        $note = $this->normalizeNote($input['note'] ?? null);
        $isActive = ((string) ($input['is_active'] ?? '0')) === '1';

        $this->watchlist->update($id, $priority, $note, $isActive);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function attachWatchSignalsToMonitoringRows(array $rows): array
    {
        $supplierIds = [];
        $brandIds = [];

        foreach ($rows as $row) {
            $supplierId = isset($row['supplier_id']) ? (int) $row['supplier_id'] : 0;
            $brandId = isset($row['brand_id']) && $row['brand_id'] !== null ? (int) $row['brand_id'] : 0;
            if ($supplierId > 0) {
                $supplierIds[$supplierId] = $supplierId;
            }
            if ($brandId > 0) {
                $brandIds[$brandId] = $brandId;
            }
        }

        $signals = $this->watchlist->activeSignalMap(array_values($supplierIds), array_values($brandIds));

        foreach ($rows as &$row) {
            $row = $this->applySignalToRow($row, $signals);
        }

        return $rows;
    }

    /** @param array<int,array<string,mixed>> $rows
     * @return array<string,int>
     */
    public function summarizeMonitoringRows(array $rows): array
    {
        $summary = [
            'watched_total' => 0,
            'watched_critical' => 0,
            'watched_high' => 0,
            'watched_normal' => 0,
        ];

        foreach ($rows as $row) {
            if (!((bool) ($row['is_watched'] ?? false))) {
                continue;
            }

            $summary['watched_total']++;
            $priority = (string) ($row['watch_priority_level'] ?? 'normal');
            if ($priority === 'critical') {
                $summary['watched_critical']++;
            } elseif ($priority === 'high') {
                $summary['watched_high']++;
            } else {
                $summary['watched_normal']++;
            }
        }

        return $summary;
    }

    /** @return array<string,int> */
    public function activeCounts(): array
    {
        return $this->watchlist->activeCountByType();
    }

    /** @param array<string,mixed> $row
     * @param array<string,array{priority_level:string,note:?string,id:int}> $signalMap
     * @return array<string,mixed>
     */
    private function applySignalToRow(array $row, array $signalMap): array
    {
        $candidates = [];

        $supplierId = isset($row['supplier_id']) ? (int) $row['supplier_id'] : 0;
        $brandId = isset($row['brand_id']) && $row['brand_id'] !== null ? (int) $row['brand_id'] : 0;

        if ($supplierId > 0) {
            $supplierSignal = $signalMap['supplier:' . $supplierId] ?? null;
            if (is_array($supplierSignal)) {
                $candidates[] = [
                    'source' => 'supplier',
                    'entity_id' => $supplierId,
                    'priority_level' => (string) ($supplierSignal['priority_level'] ?? 'normal'),
                    'note' => isset($supplierSignal['note']) ? (string) $supplierSignal['note'] : null,
                ];
            }
        }

        if ($brandId > 0) {
            $brandSignal = $signalMap['brand:' . $brandId] ?? null;
            if (is_array($brandSignal)) {
                $candidates[] = [
                    'source' => 'brand',
                    'entity_id' => $brandId,
                    'priority_level' => (string) ($brandSignal['priority_level'] ?? 'normal'),
                    'note' => isset($brandSignal['note']) ? (string) $brandSignal['note'] : null,
                ];
            }
        }

        if ($candidates === []) {
            $row['is_watched'] = false;
            $row['watch_priority_level'] = null;
            $row['watch_sources'] = [];
            $row['watch_note'] = null;
            return $row;
        }

        usort($candidates, fn (array $a, array $b): int => $this->priorityWeight((string) $b['priority_level']) <=> $this->priorityWeight((string) $a['priority_level']));
        $top = $candidates[0];

        $row['is_watched'] = true;
        $row['watch_priority_level'] = (string) ($top['priority_level'] ?? 'normal');
        $row['watch_sources'] = array_values(array_map(static fn (array $candidate): string => (string) ($candidate['source'] ?? ''), $candidates));
        $row['watch_note'] = $top['note'] ?? null;

        return $row;
    }

    private function normalizeEntityType(string $entityType): string
    {
        $entityType = trim(strtolower($entityType));
        if (!in_array($entityType, self::ENTITY_TYPES, true)) {
            throw new InvalidArgumentException('Ogiltig entity_type. Tillåtna: supplier, brand.');
        }

        return $entityType;
    }

    private function normalizeEntityId(mixed $entityId): int
    {
        $normalized = trim((string) $entityId);
        if ($normalized === '' || ctype_digit($normalized) === false || (int) $normalized <= 0) {
            throw new InvalidArgumentException('entity_id måste vara ett giltigt ID.');
        }

        return (int) $normalized;
    }

    private function normalizePriority(string $priority): string
    {
        $priority = trim(strtolower($priority));
        if (!in_array($priority, self::PRIORITY_LEVELS, true)) {
            throw new InvalidArgumentException('Ogiltig prioritet. Tillåtna: normal, high, critical.');
        }

        return $priority;
    }

    private function normalizeNote(mixed $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : mb_substr($note, 0, 2000);
    }

    private function assertEntityExists(string $entityType, int $entityId): void
    {
        if ($entityType === 'supplier' && $this->suppliers->findById($entityId) === null) {
            throw new InvalidArgumentException('Vald leverantör finns inte.');
        }

        if ($entityType === 'brand' && $this->brands->findById($entityId) === null) {
            throw new InvalidArgumentException('Valt brand finns inte.');
        }
    }

    private function resolveEntityLabel(string $entityType, int $entityId): string
    {
        if ($entityType === 'supplier') {
            $supplier = $this->suppliers->findById($entityId);
            return $supplier !== null ? (string) ($supplier['name'] ?? ('Supplier #' . $entityId)) : ('Saknad supplier #' . $entityId);
        }

        if ($entityType === 'brand') {
            $brand = $this->brands->findById($entityId);
            return $brand !== null ? (string) ($brand['name'] ?? ('Brand #' . $entityId)) : ('Saknat brand #' . $entityId);
        }

        return 'Okänt objekt #' . $entityId;
    }

    private function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'critical' => 3,
            'high' => 2,
            default => 1,
        };
    }
}
