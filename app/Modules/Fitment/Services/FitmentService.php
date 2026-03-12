<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\ProductFitmentRepository;
use App\Modules\Fitment\Repositories\VehicleRepository;

final class FitmentService
{
    private const SESSION_KEY = 'selected_vehicle_id';

    public function __construct(
        private readonly VehicleRepository $vehicles,
        private readonly ProductFitmentRepository $fitments
    ) {
    }

    /** @return array<string,mixed> */
    public function selectorData(): array
    {
        $vehicles = $this->vehicles->activeForSelector();
        $selectedVehicle = $this->selectedVehicle();

        $makes = [];
        $models = [];
        $generations = [];
        $engines = [];

        foreach ($vehicles as $vehicle) {
            $make = (string) $vehicle['make'];
            $model = (string) $vehicle['model'];
            $generation = trim((string) ($vehicle['generation'] ?? ''));
            $engine = trim((string) ($vehicle['engine'] ?? ''));

            $makes[$make] = true;

            if ($selectedVehicle !== null && $make === (string) $selectedVehicle['make']) {
                $models[$model] = true;
            }
            if ($selectedVehicle !== null
                && $make === (string) $selectedVehicle['make']
                && $model === (string) $selectedVehicle['model']) {
                if ($generation !== '') {
                    $generations[$generation] = true;
                }
            }
            if ($selectedVehicle !== null
                && $make === (string) $selectedVehicle['make']
                && $model === (string) $selectedVehicle['model']
                && ($generation === '' || $generation === (string) ($selectedVehicle['generation'] ?? ''))) {
                if ($engine !== '') {
                    $engines[$engine] = true;
                }
            }
        }

        ksort($makes);
        ksort($models);
        ksort($generations);
        ksort($engines);

        return [
            'selected_vehicle' => $selectedVehicle,
            'vehicles' => $vehicles,
            'makes' => array_keys($makes),
            'models' => array_keys($models),
            'generations' => array_keys($generations),
            'engines' => array_keys($engines),
        ];
    }

    public function selectVehicleFromInput(array $input): bool
    {
        $make = trim((string) ($input['make'] ?? ''));
        $model = trim((string) ($input['model'] ?? ''));
        $generation = trim((string) ($input['generation'] ?? ''));
        $engine = trim((string) ($input['engine'] ?? ''));

        if ($make === '' || $model === '') {
            return false;
        }

        foreach ($this->vehicles->activeForSelector() as $vehicle) {
            if ((string) $vehicle['make'] !== $make || (string) $vehicle['model'] !== $model) {
                continue;
            }

            if ($generation !== '' && (string) ($vehicle['generation'] ?? '') !== $generation) {
                continue;
            }

            if ($engine !== '' && (string) ($vehicle['engine'] ?? '') !== $engine) {
                continue;
            }

            $_SESSION[self::SESSION_KEY] = (int) $vehicle['id'];

            return true;
        }

        return false;
    }

    public function clearSelectedVehicle(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public function selectVehicleById(int $vehicleId): bool
    {
        if ($vehicleId <= 0) {
            return false;
        }

        $vehicle = $this->vehicles->findActiveById($vehicleId);
        if ($vehicle === null) {
            return false;
        }

        $_SESSION[self::SESSION_KEY] = (int) $vehicle['id'];

        return true;
    }

    /** @return array<string,mixed>|null */
    public function selectedVehicle(): ?array
    {
        $vehicleId = isset($_SESSION[self::SESSION_KEY]) ? (int) $_SESSION[self::SESSION_KEY] : 0;
        if ($vehicleId <= 0) {
            return null;
        }

        $vehicle = $this->vehicles->findActiveById($vehicleId);
        if ($vehicle === null) {
            unset($_SESSION[self::SESSION_KEY]);
            return null;
        }

        $vehicle['display_name'] = $this->vehicleDisplayName($vehicle);

        return $vehicle;
    }

    /** @return array<string,mixed> */
    public function catalogQueryWithFitment(array $query): array
    {
        $selectedVehicle = $this->selectedVehicle();
        $query['fitment_vehicle_id'] = $selectedVehicle !== null ? (string) $selectedVehicle['id'] : '';

        if ($selectedVehicle !== null && !isset($query['fitment_only'])) {
            $query['fitment_only'] = '1';
        }

        return $query;
    }

    /** @return array{code:string,label:string} */
    public function productFitmentStatus(int $productId): array
    {
        $vehicle = $this->selectedVehicle();
        if ($vehicle === null) {
            return ['code' => 'not_selected', 'label' => 'Välj bil för att se passform'];
        }

        $rows = $this->fitments->productFitmentsForVehicle($productId, (int) $vehicle['id']);

        foreach ($rows as $row) {
            if ((string) $row['fitment_type'] === 'confirmed') {
                return ['code' => 'confirmed', 'label' => 'Passar vald bil'];
            }
        }

        foreach ($rows as $row) {
            if ((string) $row['fitment_type'] === 'universal') {
                return ['code' => 'universal', 'label' => 'Universell produkt'];
            }
        }

        return ['code' => 'unknown', 'label' => 'Passform ej bekräftad'];
    }

    /** @param array<string,mixed> $vehicle */
    public function vehicleDisplayName(array $vehicle): string
    {
        $parts = [trim((string) $vehicle['make']), trim((string) $vehicle['model'])];

        $generation = trim((string) ($vehicle['generation'] ?? ''));
        if ($generation !== '') {
            $parts[] = $generation;
        }

        $engine = trim((string) ($vehicle['engine'] ?? ''));
        if ($engine !== '') {
            $parts[] = $engine;
        }

        $years = [];
        if (!empty($vehicle['year_from'])) {
            $years[] = (string) $vehicle['year_from'];
        }
        if (!empty($vehicle['year_to'])) {
            $years[] = (string) $vehicle['year_to'];
        }
        if ($years !== []) {
            $parts[] = implode('–', $years);
        }

        return trim(implode(' ', $parts));
    }
}
