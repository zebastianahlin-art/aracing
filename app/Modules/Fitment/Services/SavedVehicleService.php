<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\UserVehicleRepository;
use App\Modules\Fitment\Repositories\VehicleRepository;
use InvalidArgumentException;

final class SavedVehicleService
{
    public function __construct(
        private readonly UserVehicleRepository $userVehicles,
        private readonly VehicleRepository $vehicles,
        private readonly FitmentService $fitment
    ) {
    }

    public function saveVehicle(int $userId, int $vehicleId): string
    {
        $this->assertUser($userId);
        if ($vehicleId <= 0) {
            throw new InvalidArgumentException('Ogiltigt fordonsval.');
        }

        $vehicle = $this->vehicles->findActiveById($vehicleId);
        if ($vehicle === null) {
            throw new InvalidArgumentException('Bilen är inte aktiv och kan inte sparas.');
        }

        if ($this->userVehicles->exists($userId, $vehicleId)) {
            return 'already_saved';
        }

        $this->userVehicles->add($userId, $vehicleId);

        return 'saved';
    }

    /** @return array<int,array<string,mixed>> */
    public function listVehicles(int $userId): array
    {
        $this->assertUser($userId);

        $rows = $this->userVehicles->listForUser($userId);
        foreach ($rows as &$row) {
            $row['display_name'] = $this->fitment->vehicleDisplayName($row);
            $row['is_selectable'] = (int) ($row['vehicle_is_active'] ?? 0) === 1;
        }
        unset($row);

        return $rows;
    }

    public function setPrimary(int $userId, int $vehicleId): void
    {
        $this->assertUser($userId);
        if (!$this->userVehicles->exists($userId, $vehicleId)) {
            throw new InvalidArgumentException('Bilen finns inte i dina sparade bilar.');
        }

        $vehicle = $this->vehicles->findActiveById($vehicleId);
        if ($vehicle === null) {
            throw new InvalidArgumentException('Endast aktiva bilar kan vara primära.');
        }

        $this->userVehicles->clearPrimary($userId);
        $this->userVehicles->setPrimary($userId, $vehicleId);
    }

    public function remove(int $userId, int $vehicleId): void
    {
        $this->assertUser($userId);
        $this->userVehicles->remove($userId, $vehicleId);
    }

    public function useSavedVehicle(int $userId, int $vehicleId): void
    {
        $this->assertUser($userId);
        if (!$this->userVehicles->exists($userId, $vehicleId)) {
            throw new InvalidArgumentException('Bilen finns inte i dina sparade bilar.');
        }

        $this->fitment->selectVehicleById($vehicleId);
    }

    public function saveCurrentSelection(int $userId): string
    {
        $this->assertUser($userId);
        $selected = $this->fitment->selectedVehicle();
        if ($selected === null) {
            throw new InvalidArgumentException('Välj en bil i YMM-väljaren innan du sparar.');
        }

        return $this->saveVehicle($userId, (int) $selected['id']);
    }

    public function applyPrimaryVehicleIfNoActiveSelection(int $userId): void
    {
        $this->assertUser($userId);
        if ($this->fitment->selectedVehicle() !== null) {
            return;
        }

        $primary = $this->userVehicles->primaryForUser($userId);
        if ($primary === null) {
            return;
        }

        if ((int) ($primary['vehicle_is_active'] ?? 0) !== 1) {
            return;
        }

        $this->fitment->selectVehicleById((int) $primary['vehicle_id']);
    }

    private function assertUser(int $userId): void
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Ogiltig användare.');
        }
    }
}
