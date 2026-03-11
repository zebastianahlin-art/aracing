<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\ProductFitmentRepository;
use App\Modules\Fitment\Repositories\SupplierFitmentCandidateRepository;
use App\Modules\Fitment\Repositories\VehicleRepository;
use App\Modules\Product\Repositories\ProductRepository;
use PDO;

final class SupplierFitmentReviewService
{
    private const ALLOWED_STATUS = ['pending', 'approved', 'rejected', 'skipped'];
    private const ALLOWED_CONFIDENCE = ['exact', 'likely', 'unknown'];

    public function __construct(
        private readonly PDO $pdo,
        private readonly SupplierFitmentCandidateRepository $candidates,
        private readonly SupplierFitmentIntakeService $intake,
        private readonly ProductFitmentRepository $fitments,
        private readonly ProductRepository $products,
        private readonly VehicleRepository $vehicles
    ) {
    }

    /** @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function adminQueue(array $filters): array
    {
        $normalized = $this->normalizeFilters($filters);
        $rows = $this->candidates->adminQueue($normalized);

        return [
            'rows' => $rows,
            'filters' => $normalized,
        ];
    }

    /** @param array<string,mixed> $input */
    public function intakeCandidate(array $input): int
    {
        return $this->intake->createCandidate($input);
    }

    /** @param array<string,mixed> $input */
    public function approve(int $candidateId, array $input): void
    {
        $candidate = $this->requireCandidate($candidateId);
        $productId = $this->resolveProductId($candidate, $input);
        $vehicleId = $this->resolveVehicleId($candidate, $input);

        if ($this->products->findById($productId) === null) {
            throw new \RuntimeException('Produkten saknas för godkännande.');
        }

        if ($this->vehicles->findById($vehicleId) === null) {
            throw new \RuntimeException('Fordonet saknas för godkännande.');
        }

        $reviewNote = $this->normalizeReviewNote($input['review_note'] ?? null);
        $reviewedBy = $this->normalizeNullableInt($input['reviewed_by_user_id'] ?? null);

        $existing = $this->fitments->productFitmentsForVehicle($productId, $vehicleId);

        $this->pdo->beginTransaction();
        try {
            if ($existing === []) {
                $this->fitments->create($productId, $vehicleId, 'confirmed', 'Skapad från supplier fitment candidate #' . $candidateId);
            }

            $this->candidates->markReviewed($candidateId, 'approved', $reviewNote, $vehicleId, $reviewedBy);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /** @param array<string,mixed> $input */
    public function reject(int $candidateId, array $input): void
    {
        $this->markNonApproved($candidateId, 'rejected', $input);
    }

    /** @param array<string,mixed> $input */
    public function skip(int $candidateId, array $input): void
    {
        $this->markNonApproved($candidateId, 'skipped', $input);
    }

    /** @return array<string> */
    public function allowedStatuses(): array
    {
        return self::ALLOWED_STATUS;
    }

    /** @return array<string> */
    public function allowedConfidenceLabels(): array
    {
        return self::ALLOWED_CONFIDENCE;
    }

    private function requireCandidate(int $candidateId): array
    {
        $candidate = $this->candidates->findById($candidateId);
        if ($candidate === null) {
            throw new \RuntimeException('Kandidaten finns inte.');
        }

        return $candidate;
    }

    /** @param array<string,mixed> $candidate
     * @param array<string,mixed> $input
     */
    private function resolveProductId(array $candidate, array $input): int
    {
        $fromInput = $this->normalizeNullableInt($input['product_id'] ?? null);
        if ($fromInput !== null) {
            return $fromInput;
        }

        $currentProductId = isset($candidate['product_id']) ? (int) $candidate['product_id'] : 0;
        if ($currentProductId > 0) {
            return $currentProductId;
        }

        throw new \RuntimeException('Godkännande kräver produktkoppling.');
    }

    /** @param array<string,mixed> $candidate
     * @param array<string,mixed> $input
     */
    private function resolveVehicleId(array $candidate, array $input): int
    {
        $fromInput = $this->normalizeNullableInt($input['matched_vehicle_id'] ?? null);
        if ($fromInput !== null) {
            return $fromInput;
        }

        $currentVehicleId = isset($candidate['matched_vehicle_id']) ? (int) $candidate['matched_vehicle_id'] : 0;
        if ($currentVehicleId > 0) {
            return $currentVehicleId;
        }

        throw new \RuntimeException('Godkännande kräver matchat fordon.');
    }

    /** @param array<string,mixed> $input */
    private function markNonApproved(int $candidateId, string $status, array $input): void
    {
        $this->requireCandidate($candidateId);

        if (!in_array($status, ['rejected', 'skipped'], true)) {
            throw new \RuntimeException('Ogiltig review-status.');
        }

        $reviewNote = $this->normalizeReviewNote($input['review_note'] ?? null);
        $reviewedBy = $this->normalizeNullableInt($input['reviewed_by_user_id'] ?? null);

        $vehicleId = $this->normalizeNullableInt($input['matched_vehicle_id'] ?? null);

        $this->candidates->markReviewed($candidateId, $status, $reviewNote, $vehicleId, $reviewedBy);
    }

    /** @param array<string,mixed> $filters
     * @return array<string,string>
     */
    private function normalizeFilters(array $filters): array
    {
        $status = trim((string) ($filters['status'] ?? 'pending'));
        if (!in_array($status, self::ALLOWED_STATUS, true) && $status !== '') {
            $status = 'pending';
        }

        $vehicleMatch = trim((string) ($filters['vehicle_match'] ?? ''));
        if (!in_array($vehicleMatch, ['', 'with_vehicle', 'without_vehicle'], true)) {
            $vehicleMatch = '';
        }

        $productLink = trim((string) ($filters['product_link'] ?? ''));
        if (!in_array($productLink, ['', 'without_product'], true)) {
            $productLink = '';
        }

        $supplierId = trim((string) ($filters['supplier_id'] ?? ''));
        if ($supplierId !== '' && ctype_digit($supplierId) === false) {
            $supplierId = '';
        }

        return [
            'status' => $status,
            'vehicle_match' => $vehicleMatch,
            'product_link' => $productLink,
            'supplier_id' => $supplierId,
            'query' => mb_substr(trim((string) ($filters['query'] ?? '')), 0, 120),
        ];
    }

    private function normalizeReviewNote(mixed $value): ?string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        return mb_substr($normalized, 0, 2000);
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        $normalized = trim((string) $value);
        if ($normalized === '' || ctype_digit($normalized) === false) {
            return null;
        }

        return (int) $normalized;
    }
}
