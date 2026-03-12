<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\VehicleRepository;

final class SupplierFitmentMappingService
{
    /** @var array<string,array<string,string>> */
    private const ALIASES = [
        'make' => [
            'vw' => 'volkswagen',
            'mb' => 'mercedes-benz',
        ],
        'model' => [],
        'generation' => [],
        'engine' => [],
    ];

    public function __construct(private readonly VehicleRepository $vehicles)
    {
    }

    /** @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function map(array $input): array
    {
        $normalized = [
            'make' => $this->normalizeField($input['raw_make'] ?? null, 'make'),
            'model' => $this->normalizeField($input['raw_model'] ?? null, 'model'),
            'generation' => $this->normalizeField($input['raw_generation'] ?? null, 'generation'),
            'engine' => $this->normalizeField($input['raw_engine'] ?? null, 'engine'),
        ];

        $yearFrom = $this->normalizeYear($input['raw_year_from'] ?? null);
        $yearTo = $this->normalizeYear($input['raw_year_to'] ?? null);
        if ($yearFrom !== null && $yearTo !== null && $yearFrom > $yearTo) {
            [$yearFrom, $yearTo] = [$yearTo, $yearFrom];
        }

        $usedAlias = ($normalized['make']['alias_used'] ?? false)
            || ($normalized['model']['alias_used'] ?? false)
            || ($normalized['generation']['alias_used'] ?? false)
            || ($normalized['engine']['alias_used'] ?? false);

        $match = $this->findSafeVehicleMatch(
            $normalized['make']['value'],
            $normalized['model']['value'],
            $normalized['generation']['value'],
            $normalized['engine']['value'],
            $yearFrom,
            $yearTo
        );

        if ($match !== null) {
            return [
                'normalized_make' => $normalized['make']['value'],
                'normalized_model' => $normalized['model']['value'],
                'normalized_generation' => $normalized['generation']['value'],
                'normalized_engine' => $normalized['engine']['value'],
                'raw_year_from' => $yearFrom,
                'raw_year_to' => $yearTo,
                'matched_vehicle_id' => (int) $match['id'],
                'confidence_label' => 'exact',
                'mapping_source' => $usedAlias ? 'exact_alias' : 'exact_normalized',
                'mapping_note' => $usedAlias
                    ? 'Alias-normalisering användes och gav exakt träff mot aktiv vehicle-post.'
                    : 'Exakt match mot aktiv vehicle-post efter normalisering.',
            ];
        }

        return [
            'normalized_make' => $normalized['make']['value'],
            'normalized_model' => $normalized['model']['value'],
            'normalized_generation' => $normalized['generation']['value'],
            'normalized_engine' => $normalized['engine']['value'],
            'raw_year_from' => $yearFrom,
            'raw_year_to' => $yearTo,
            'matched_vehicle_id' => null,
            'confidence_label' => 'unknown',
            'mapping_source' => $usedAlias ? 'alias_no_match' : 'no_safe_match',
            'mapping_note' => $usedAlias
                ? 'Alias-normalisering användes men ingen säker träff hittades.'
                : 'Ingen säker match hittades.',
        ];
    }

    /** @return array<string,mixed>|null */
    private function findSafeVehicleMatch(
        ?string $make,
        ?string $model,
        ?string $generation,
        ?string $engine,
        ?int $yearFrom,
        ?int $yearTo
    ): ?array {
        if ($make === null || $model === null) {
            return null;
        }

        $candidates = $this->vehicles->findActiveByMakeAndModelNormalized($make, $model);
        if ($candidates === []) {
            return null;
        }

        $filtered = array_values(array_filter($candidates, function (array $vehicle) use ($generation, $engine, $yearFrom, $yearTo): bool {
            if ($generation !== null && $this->normalizeForLookup($vehicle['generation'] ?? null) !== $generation) {
                return false;
            }

            if ($engine !== null && $this->normalizeForLookup($vehicle['engine'] ?? null) !== $engine) {
                return false;
            }

            return $this->yearRangeFitsVehicle($yearFrom, $yearTo, $vehicle);
        }));

        return count($filtered) === 1 ? $filtered[0] : null;
    }

    /** @param array<string,mixed> $vehicle */
    private function yearRangeFitsVehicle(?int $yearFrom, ?int $yearTo, array $vehicle): bool
    {
        if ($yearFrom === null && $yearTo === null) {
            return true;
        }

        $vehicleFrom = isset($vehicle['year_from']) ? (int) $vehicle['year_from'] : null;
        $vehicleTo = isset($vehicle['year_to']) ? (int) $vehicle['year_to'] : null;

        if ($vehicleFrom === null || $vehicleTo === null) {
            return false;
        }

        $candidateFrom = $yearFrom ?? $yearTo;
        $candidateTo = $yearTo ?? $yearFrom;

        if ($candidateFrom === null || $candidateTo === null) {
            return false;
        }

        return $candidateFrom >= $vehicleFrom && $candidateTo <= $vehicleTo;
    }

    /** @return array{value:?string,alias_used:bool} */
    private function normalizeField(mixed $value, string $field): array
    {
        $normalized = $this->normalizeForLookup($value);
        if ($normalized === null) {
            return ['value' => null, 'alias_used' => false];
        }

        $aliasValue = self::ALIASES[$field][$normalized] ?? null;
        if ($aliasValue === null) {
            return ['value' => $normalized, 'alias_used' => false];
        }

        return [
            'value' => $this->normalizeForLookup($aliasValue),
            'alias_used' => true,
        ];
    }

    private function normalizeForLookup(mixed $value): ?string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = mb_strtolower($normalized);
        $normalized = str_replace(['_', '-', '/', '\\', '.'], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        return $normalized === '' ? null : mb_substr($normalized, 0, 120);
    }

    private function normalizeYear(mixed $value): ?int
    {
        $normalized = trim((string) $value);
        if ($normalized === '' || preg_match('/^\d{4}$/', $normalized) !== 1) {
            return null;
        }

        return (int) $normalized;
    }
}
