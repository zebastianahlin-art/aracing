<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\SupplierFitmentCandidateRepository;
use App\Modules\Fitment\Repositories\VehicleRepository;
use App\Modules\Import\Repositories\SupplierItemRepository;
use App\Modules\Product\Repositories\ProductSupplierLinkRepository;

final class SupplierFitmentIntakeService
{
    private const ALLOWED_CONFIDENCE = ['exact', 'likely', 'unknown'];

    public function __construct(
        private readonly SupplierFitmentCandidateRepository $candidates,
        private readonly SupplierItemRepository $supplierItems,
        private readonly ProductSupplierLinkRepository $supplierLinks,
        private readonly VehicleRepository $vehicles
    ) {
    }

    /** @param array<string,mixed> $input */
    public function createCandidate(array $input): int
    {
        $supplierItemId = (int) ($input['supplier_item_id'] ?? 0);
        if ($supplierItemId <= 0 || $this->supplierItems->findById($supplierItemId) === null) {
            throw new \InvalidArgumentException('Leverantörsartikel saknas.');
        }

        $productId = $this->normalizeNullableInt($input['product_id'] ?? null);
        if ($productId === null) {
            $primaryLink = $this->supplierLinks->primaryForSupplierItem($supplierItemId);
            if ($primaryLink !== null && isset($primaryLink['product_id'])) {
                $productId = (int) $primaryLink['product_id'];
            }
        }

        $make = $this->normalizeNullableString($input['raw_make'] ?? null, 120);
        $model = $this->normalizeNullableString($input['raw_model'] ?? null, 120);
        $generation = $this->normalizeNullableString($input['raw_generation'] ?? null, 120);
        $engine = $this->normalizeNullableString($input['raw_engine'] ?? null, 120);
        $yearFrom = $this->normalizeNullableInt($input['raw_year_from'] ?? null);
        $yearTo = $this->normalizeNullableInt($input['raw_year_to'] ?? null);
        $rawText = $this->normalizeNullableString($input['raw_text'] ?? null, 65000);

        $matchedVehicleId = $this->normalizeNullableInt($input['matched_vehicle_id'] ?? null);
        if ($matchedVehicleId === null) {
            $exactMatch = $this->vehicles->findExactMatch($make, $model, $generation, $engine, $yearFrom, $yearTo);
            if ($exactMatch !== null) {
                $matchedVehicleId = (int) $exactMatch['id'];
            }
        }

        $confidence = $this->normalizeNullableString($input['confidence_label'] ?? null, 40);
        if ($confidence !== null && !in_array($confidence, self::ALLOWED_CONFIDENCE, true)) {
            $confidence = 'unknown';
        }

        return $this->candidates->create([
            'supplier_item_id' => $supplierItemId,
            'product_id' => $productId,
            'raw_make' => $make,
            'raw_model' => $model,
            'raw_generation' => $generation,
            'raw_engine' => $engine,
            'raw_year_from' => $yearFrom,
            'raw_year_to' => $yearTo,
            'raw_text' => $rawText,
            'matched_vehicle_id' => $matchedVehicleId,
            'confidence_label' => $confidence,
            'status' => 'pending',
        ]);
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        $normalized = trim((string) $value);
        if ($normalized === '' || ctype_digit($normalized) === false) {
            return null;
        }

        return (int) $normalized;
    }

    private function normalizeNullableString(mixed $value, int $limit): ?string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        return mb_substr($normalized, 0, $limit);
    }
}
