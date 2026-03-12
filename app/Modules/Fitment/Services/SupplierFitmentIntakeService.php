<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\SupplierFitmentCandidateRepository;
use App\Modules\Import\Repositories\SupplierItemRepository;
use App\Modules\Product\Repositories\ProductSupplierLinkRepository;

final class SupplierFitmentIntakeService
{
    public function __construct(
        private readonly SupplierFitmentCandidateRepository $candidates,
        private readonly SupplierItemRepository $supplierItems,
        private readonly ProductSupplierLinkRepository $supplierLinks,
        private readonly SupplierFitmentMappingService $mapping
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

        $mapping = $this->mapping->map([
            'raw_make' => $make,
            'raw_model' => $model,
            'raw_generation' => $generation,
            'raw_engine' => $engine,
            'raw_year_from' => $yearFrom,
            'raw_year_to' => $yearTo,
        ]);

        return $this->candidates->create([
            'supplier_item_id' => $supplierItemId,
            'product_id' => $productId,
            'raw_make' => $make,
            'raw_model' => $model,
            'raw_generation' => $generation,
            'raw_engine' => $engine,
            'raw_year_from' => $mapping['raw_year_from'],
            'raw_year_to' => $mapping['raw_year_to'],
            'raw_text' => $rawText,
            'normalized_make' => $mapping['normalized_make'],
            'normalized_model' => $mapping['normalized_model'],
            'normalized_generation' => $mapping['normalized_generation'],
            'normalized_engine' => $mapping['normalized_engine'],
            'matched_vehicle_id' => $mapping['matched_vehicle_id'],
            'confidence_label' => $mapping['confidence_label'],
            'mapping_source' => $mapping['mapping_source'],
            'mapping_note' => $mapping['mapping_note'],
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
