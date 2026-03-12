<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\ProductFitmentRepository;

final class FitmentStorefrontService
{
    public function __construct(
        private readonly FitmentService $fitment,
        private readonly SavedVehicleService $savedVehicles,
        private readonly ProductFitmentRepository $productFitments
    ) {
    }

    /** @return array<string,mixed> */
    public function activeVehiclePayload(?int $customerId = null): array
    {
        $selected = $this->fitment->selectedVehicle();
        $savedVehicles = $customerId !== null && $customerId > 0
            ? $this->savedVehicles->listVehicles($customerId)
            : [];

        return [
            'has_active_vehicle' => $selected !== null,
            'active_vehicle' => $selected,
            'active_vehicle_label' => $selected !== null ? (string) ($selected['display_name'] ?? '') : '',
            'saved_vehicles_count' => count($savedVehicles),
            'saved_vehicles' => array_slice($savedVehicles, 0, 3),
            'has_saved_vehicles' => $savedVehicles !== [],
        ];
    }

    /** @return array{code:string,label:string,badge_class:string,tone:string,description:string} */
    public function fitmentSignalForProduct(int $productId): array
    {
        $vehicle = $this->fitment->selectedVehicle();
        if ($vehicle === null) {
            return [
                'code' => 'not_selected',
                'label' => 'Välj bil för passform',
                'badge_class' => '',
                'tone' => 'neutral',
                'description' => 'Välj bil i YMM för att se om produkten passar.',
            ];
        }

        return $this->signalFromTypes(
            $this->productFitments->productFitmentsForVehicle($productId, (int) $vehicle['id'])
        );
    }

    /**
     * @param array<int,array<string,mixed>> $products
     * @return array<int,array<string,mixed>>
     */
    public function decorateProductCardsWithFitment(array $products): array
    {
        $vehicle = $this->fitment->selectedVehicle();
        if ($vehicle === null || $products === []) {
            return $products;
        }

        $ids = array_values(array_filter(array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $products)));
        $typesByProduct = $this->productFitments->fitmentTypesForProductsAndVehicle($ids, (int) $vehicle['id']);

        foreach ($products as &$product) {
            $productId = (int) ($product['id'] ?? 0);
            $types = array_map(static fn (string $type): array => ['fitment_type' => $type], $typesByProduct[$productId] ?? []);
            $product['fitment_signal'] = $this->signalFromTypes($types);
        }
        unset($product);

        return $products;
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<string,mixed>|null $vehicle
     * @return array<string,mixed>
     */
    public function catalogFitmentUiPayload(array $filters, ?array $vehicle): array
    {
        $hasVehicle = $vehicle !== null;
        $fitmentOnly = ((string) ($filters['fitment_only'] ?? '0')) === '1';

        return [
            'has_active_vehicle' => $hasVehicle,
            'active_vehicle_label' => $hasVehicle ? (string) ($vehicle['display_name'] ?? '') : '',
            'fitment_filter_active' => $hasVehicle && $fitmentOnly,
            'fitment_filter_label' => $hasVehicle && $fitmentOnly
                ? 'Passar vald bil-filter aktivt'
                : 'Visar alla produkter oavsett vald bil',
            'fitment_result_context' => $hasVehicle
                ? 'Visar produkter för ' . (string) ($vehicle['display_name'] ?? '')
                : 'Ingen aktiv bil vald – välj bil för tydligare passformsignaler.',
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array{code:string,label:string,badge_class:string,tone:string,description:string}
     */
    private function signalFromTypes(array $rows): array
    {
        foreach ($rows as $row) {
            if ((string) ($row['fitment_type'] ?? '') === 'confirmed') {
                return [
                    'code' => 'confirmed',
                    'label' => 'Passar vald bil',
                    'badge_class' => 'ok',
                    'tone' => 'positive',
                    'description' => 'Bekräftad passform mot aktiv bil i YMM.',
                ];
            }
        }

        foreach ($rows as $row) {
            if ((string) ($row['fitment_type'] ?? '') === 'universal') {
                return [
                    'code' => 'universal',
                    'label' => 'Universell',
                    'badge_class' => '',
                    'tone' => 'neutral',
                    'description' => 'Universell produkt – verifiera alltid specifikation före köp.',
                ];
            }
        }

        return [
            'code' => 'unknown',
            'label' => 'Passform ej bekräftad',
            'badge_class' => 'bad',
            'tone' => 'caution',
            'description' => 'Ingen säker bekräftelse för vald bil just nu.',
        ];
    }
}
