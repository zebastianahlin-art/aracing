<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\AiFitmentSuggestionRepository;
use App\Modules\Fitment\Repositories\ProductFitmentRepository;
use App\Modules\Fitment\Repositories\SupplierFitmentCandidateRepository;
use App\Modules\Fitment\Repositories\VehicleRepository;
use App\Modules\Import\Repositories\AiProductImportDraftRepository;
use App\Modules\Product\Repositories\ProductAttributeRepository;
use App\Modules\Product\Repositories\ProductRepository;
use PDO;

final class AiFitmentSuggestionService
{
    private const ALLOWED_SOURCE_TYPES = ['supplier_fitment_candidate', 'ai_import_draft', 'product_text', 'mixed'];
    private const ALLOWED_CONFIDENCE = ['exact', 'likely', 'unknown'];

    public function __construct(
        private readonly PDO $pdo,
        private readonly AiFitmentSuggestionRepository $suggestions,
        private readonly ProductRepository $products,
        private readonly ProductAttributeRepository $attributes,
        private readonly SupplierFitmentCandidateRepository $supplierCandidates,
        private readonly ProductFitmentRepository $fitments,
        private readonly VehicleRepository $vehicles,
        private readonly AiProductImportDraftRepository $aiImportDrafts,
    ) {
    }

    /** @return array<int,array<string,mixed>> */
    public function listForProduct(int $productId): array
    {
        return $this->suggestions->listByProductId($productId);
    }

    /** @return array{created:int,skipped_duplicates:int,skipped_existing_fitments:int} */
    public function createSuggestionsForProduct(int $productId, ?int $createdByUserId = null): array
    {
        $snapshot = $this->buildInputSnapshot($productId);
        $records = $this->buildSuggestionCandidates($snapshot);

        $created = 0;
        $skippedDuplicates = 0;
        $skippedExistingFitments = 0;

        foreach ($records as $record) {
            $vehicleId = (int) $record['suggested_vehicle_id'];

            if ($this->fitments->productFitmentsForVehicle($productId, $vehicleId) !== []) {
                ++$skippedExistingFitments;
                continue;
            }

            if ($this->suggestions->findPendingDuplicate($productId, $vehicleId) !== null) {
                ++$skippedDuplicates;
                continue;
            }

            $this->suggestions->create([
                'product_id' => $productId,
                'suggested_vehicle_id' => $vehicleId,
                'source_type' => $record['source_type'],
                'source_reference_id' => $record['source_reference_id'],
                'confidence_label' => $record['confidence_label'],
                'suggestion_reason' => $record['suggestion_reason'],
                'input_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'status' => 'pending',
                'created_by_user_id' => $createdByUserId,
            ]);
            ++$created;
        }

        return [
            'created' => $created,
            'skipped_duplicates' => $skippedDuplicates,
            'skipped_existing_fitments' => $skippedExistingFitments,
        ];
    }

    public function approveSuggestion(int $suggestionId, ?int $reviewedByUserId = null): void
    {
        $suggestion = $this->requireSuggestion($suggestionId);
        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('Endast pending-förslag kan godkännas.');
        }

        $productId = (int) ($suggestion['product_id'] ?? 0);
        $vehicleId = (int) ($suggestion['suggested_vehicle_id'] ?? 0);

        if ($this->products->findById($productId) === null) {
            throw new \RuntimeException('Produkten saknas för förslaget.');
        }

        if ($this->vehicles->findById($vehicleId) === null) {
            throw new \RuntimeException('Fordonet saknas för förslaget.');
        }

        $this->pdo->beginTransaction();
        try {
            if ($this->fitments->productFitmentsForVehicle($productId, $vehicleId) === []) {
                $note = 'Skapad från AI-fitmentförslag #' . $suggestionId;
                $this->fitments->create($productId, $vehicleId, 'confirmed', $note);
            }

            $this->suggestions->markReviewed($suggestionId, 'approved', $reviewedByUserId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function rejectSuggestion(int $suggestionId, ?int $reviewedByUserId = null): void
    {
        $suggestion = $this->requireSuggestion($suggestionId);
        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('Endast pending-förslag kan avvisas.');
        }

        $this->suggestions->markReviewed($suggestionId, 'rejected', $reviewedByUserId);
    }

    /** @return array<string,mixed> */
    public function buildInputSnapshot(int $productId): array
    {
        $product = $this->products->findById($productId);
        if ($product === null) {
            throw new \RuntimeException('Produkten finns inte.');
        }

        $attributes = $this->attributes->byProductId($productId);
        $supplierCandidates = $this->supplierCandidates->byProductId($productId);
        $existingFitments = $this->fitments->byProductId($productId);

        $aiImportSource = null;
        if ((string) ($product['source_type'] ?? '') === 'ai_url_import' && (int) ($product['source_reference_id'] ?? 0) > 0) {
            $draft = $this->aiImportDrafts->findById((int) $product['source_reference_id']);
            if ($draft !== null) {
                $aiImportSource = [
                    'id' => (int) ($draft['id'] ?? 0),
                    'source_url' => (string) ($draft['source_url'] ?? ''),
                    'source_type' => (string) ($draft['source_type'] ?? ''),
                    'import_title' => (string) ($draft['import_title'] ?? ''),
                    'import_brand' => (string) ($draft['import_brand'] ?? ''),
                    'import_sku' => (string) ($draft['import_sku'] ?? ''),
                    'import_short_description' => (string) ($draft['import_short_description'] ?? ''),
                    'import_description' => (string) ($draft['import_description'] ?? ''),
                ];
            }
        }

        return [
            'product' => [
                'id' => (int) $product['id'],
                'title' => (string) ($product['name'] ?? ''),
                'brand_id' => isset($product['brand_id']) ? (int) $product['brand_id'] : null,
                'sku' => (string) ($product['sku'] ?? ''),
                'description' => (string) ($product['description'] ?? ''),
                'source_type' => (string) ($product['source_type'] ?? ''),
                'source_reference_id' => isset($product['source_reference_id']) ? (int) $product['source_reference_id'] : null,
                'source_url' => (string) ($product['source_url'] ?? ''),
            ],
            'attributes' => $attributes,
            'supplier_fitment_candidates' => $supplierCandidates,
            'existing_fitments' => $existingFitments,
            'ai_import_source' => $aiImportSource,
        ];
    }

    /** @param array<string,mixed> $snapshot
     * @return array<int,array<string,mixed>>
     */
    private function buildSuggestionCandidates(array $snapshot): array
    {
        $product = is_array($snapshot['product'] ?? null) ? $snapshot['product'] : [];
        $name = mb_strtolower(trim((string) ($product['title'] ?? '')));
        $description = mb_strtolower(trim((string) ($product['description'] ?? '')));
        $haystack = trim($name . ' ' . $description . ' ' . mb_strtolower((string) ($product['sku'] ?? '')));

        $recordsByVehicle = [];
        foreach (($snapshot['supplier_fitment_candidates'] ?? []) as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $status = (string) ($candidate['status'] ?? '');
            if (in_array($status, ['rejected', 'skipped'], true)) {
                continue;
            }

            $vehicleId = (int) ($candidate['matched_vehicle_id'] ?? 0);
            $sourceType = 'supplier_fitment_candidate';
            $reasonPrefix = 'Baserat på supplier-fitmentunderlag';

            if ($vehicleId <= 0) {
                $normalizedMake = mb_strtolower(trim((string) ($candidate['normalized_make'] ?? '')));
                $normalizedModel = mb_strtolower(trim((string) ($candidate['normalized_model'] ?? '')));
                if ($normalizedMake === '' || $normalizedModel === '') {
                    continue;
                }

                $matches = $this->vehicles->findActiveByMakeAndModelNormalized($normalizedMake, $normalizedModel);
                if (count($matches) !== 1) {
                    continue;
                }

                $vehicleId = (int) ($matches[0]['id'] ?? 0);
                if ($vehicleId <= 0) {
                    continue;
                }

                $sourceType = 'mixed';
                $reasonPrefix = 'Baserat på kombinerat underlag (supplier-fitment + normaliserad fordonsmatchning)';
            }

            $vehicle = $this->vehicles->findActiveById($vehicleId);
            if ($vehicle === null) {
                continue;
            }

            $confidence = $this->normalizeConfidence((string) ($candidate['confidence_label'] ?? 'unknown'));
            if ($confidence === 'unknown') {
                continue;
            }

            $vehicleMatchToken = mb_strtolower(trim((string) $vehicle['make'] . ' ' . (string) $vehicle['model']));
            if ($vehicleMatchToken !== '' && str_contains($haystack, $vehicleMatchToken)) {
                $sourceType = 'mixed';
                $reasonPrefix = 'Baserat på kombinerat underlag (supplier-fitment + produkttext)';
            }

            if (!in_array($sourceType, self::ALLOWED_SOURCE_TYPES, true)) {
                $sourceType = 'mixed';
            }

            $reason = $reasonPrefix . ': '
                . trim((string) ($candidate['raw_make'] ?? '')) . ' '
                . trim((string) ($candidate['raw_model'] ?? ''))
                . ' → ' . (string) $vehicle['make'] . ' ' . (string) $vehicle['model']
                . ' (' . $confidence . ').';

            $existing = $recordsByVehicle[$vehicleId] ?? null;
            if ($existing === null || $this->confidenceRank($confidence) > $this->confidenceRank((string) $existing['confidence_label'])) {
                $recordsByVehicle[$vehicleId] = [
                    'suggested_vehicle_id' => $vehicleId,
                    'source_type' => $sourceType,
                    'source_reference_id' => (int) ($candidate['id'] ?? 0) ?: null,
                    'confidence_label' => $confidence,
                    'suggestion_reason' => mb_substr($reason, 0, 1000),
                ];
            }
        }

        $records = array_values($recordsByVehicle);
        usort($records, fn (array $a, array $b): int => $this->confidenceRank((string) $b['confidence_label']) <=> $this->confidenceRank((string) $a['confidence_label']));

        return array_slice($records, 0, 3);
    }

    /** @return array<string,mixed> */
    private function requireSuggestion(int $suggestionId): array
    {
        $suggestion = $this->suggestions->findById($suggestionId);
        if ($suggestion === null) {
            throw new \RuntimeException('AI-fitmentförslaget finns inte.');
        }

        return $suggestion;
    }

    private function normalizeConfidence(string $value): string
    {
        $normalized = trim($value);
        if (!in_array($normalized, self::ALLOWED_CONFIDENCE, true)) {
            return 'unknown';
        }

        return $normalized;
    }

    private function confidenceRank(string $label): int
    {
        return match ($label) {
            'exact' => 3,
            'likely' => 2,
            default => 1,
        };
    }
}
