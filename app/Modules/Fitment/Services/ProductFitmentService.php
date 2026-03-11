<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\ProductFitmentRepository;
use App\Modules\Fitment\Repositories\VehicleRepository;
use App\Modules\Product\Repositories\ProductRepository;
use PDOException;

final class ProductFitmentService
{
    private const ALLOWED_TYPES = ['confirmed', 'universal', 'unknown'];

    public function __construct(
        private readonly ProductFitmentRepository $fitments,
        private readonly VehicleRepository $vehicles,
        private readonly ProductRepository $products
    ) {
    }

    /** @return array<int,array<string,mixed>> */
    public function listForProduct(int $productId): array
    {
        return $this->fitments->byProductId($productId);
    }

    /** @return array<int,array<string,mixed>> */
    public function activeVehicles(): array
    {
        return $this->vehicles->activeForSelector();
    }

    /** @param array<string,mixed> $input */
    public function addToProduct(int $productId, array $input): void
    {
        if ($this->products->findById($productId) === null) {
            throw new \RuntimeException('Produkt saknas.');
        }

        $vehicleId = (int) ($input['vehicle_id'] ?? 0);
        if ($this->vehicles->findById($vehicleId) === null) {
            throw new \RuntimeException('Fordon saknas.');
        }

        $type = trim((string) ($input['fitment_type'] ?? 'confirmed'));
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $type = 'confirmed';
        }

        $note = trim((string) ($input['note'] ?? ''));

        try {
            $this->fitments->create($productId, $vehicleId, $type, $note !== '' ? $note : null);
        } catch (PDOException $exception) {
            if ((int) $exception->getCode() === 23000) {
                throw new \RuntimeException('Kopplingen finns redan.');
            }

            throw $exception;
        }
    }

    public function deleteFromProduct(int $productId, int $fitmentId): void
    {
        $this->fitments->deleteForProduct($productId, $fitmentId);
    }

    /** @return array<int,string> */
    public function allowedTypes(): array
    {
        return self::ALLOWED_TYPES;
    }
}
