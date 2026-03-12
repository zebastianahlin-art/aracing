<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Catalog\Repositories\CatalogRepository;

final class VehicleNavigationService
{
    public function __construct(
        private readonly FitmentService $fitment,
        private readonly FitmentStorefrontService $fitmentStorefront,
        private readonly CatalogRepository $catalog,
        private readonly FitmentCoverageService $coverage
    ) {
    }

    /** @return array<string,mixed> */
    public function storefrontPayload(?int $customerId = null, int $maxCategories = 8): array
    {
        $activeVehiclePayload = $this->fitmentStorefront->activeVehiclePayload($customerId);
        $activeVehicle = is_array($activeVehiclePayload['active_vehicle'] ?? null)
            ? $activeVehiclePayload['active_vehicle']
            : null;

        return [
            'active_vehicle' => $activeVehicle,
            'active_vehicle_label' => (string) ($activeVehiclePayload['active_vehicle_label'] ?? ''),
            'has_active_vehicle' => (bool) ($activeVehiclePayload['has_active_vehicle'] ?? false),
            'entry_label' => $activeVehicle !== null
                ? 'Handla till vald bil'
                : 'Välj bil och handla med passform',
            'entry_url' => $activeVehicle !== null
                ? '/shop-by-vehicle'
                : '/shop-by-vehicle?prompt=select-vehicle',
            'entry_categories' => $this->entryCategories($activeVehicle, $maxCategories),
            'empty_state' => $this->emptyStatePayload(),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function entryCategories(?array $activeVehicle, int $maxCategories = 8): array
    {
        $rows = array_slice($this->catalog->filterCategories(), 0, max(1, $maxCategories));
        $categories = [];

        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $categories[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'slug' => $slug,
                'url' => $this->categoryUrl($slug, $activeVehicle),
            ];
        }

        return $this->coverage->decorateStorefrontCategories($categories, $activeVehicle);
    }

    /** @return array<string,string> */
    public function emptyStatePayload(): array
    {
        return [
            'title' => 'Välj bil för att se kategorier med fordonskontext',
            'description' => 'När du har valt bil i YMM-väljaren kan du gå direkt till relevanta kategorier och filtrera på passform.',
            'cta_label' => 'Välj bil i YMM-väljaren ovan',
        ];
    }

    private function categoryUrl(string $slug, ?array $activeVehicle): string
    {
        $base = '/category/' . rawurlencode($slug);
        if ($activeVehicle === null) {
            return $base;
        }

        return $base . '?fitment_only=1&fitment_vehicle_id=' . (int) $activeVehicle['id'];
    }
}
