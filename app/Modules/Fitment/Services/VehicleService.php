<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Services;

use App\Modules\Fitment\Repositories\VehicleRepository;

final class VehicleService
{
    public function __construct(private readonly VehicleRepository $vehicles)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function adminList(): array
    {
        return $this->vehicles->allForAdmin();
    }

    /** @return array<string,mixed>|null */
    public function get(int $id): ?array
    {
        return $this->vehicles->findById($id);
    }

    /** @param array<string,mixed> $input */
    public function create(array $input): int
    {
        return $this->vehicles->create($this->normalizeData($input));
    }

    /** @param array<string,mixed> $input */
    public function update(int $id, array $input): void
    {
        $this->vehicles->update($id, $this->normalizeData($input));
    }

    /** @return array<string,mixed> */
    private function normalizeData(array $input): array
    {
        $yearFrom = $this->toNullableInt($input['year_from'] ?? null);
        $yearTo = $this->toNullableInt($input['year_to'] ?? null);
        if ($yearFrom !== null && $yearTo !== null && $yearFrom > $yearTo) {
            [$yearFrom, $yearTo] = [$yearTo, $yearFrom];
        }

        return [
            'make' => mb_substr(trim((string) ($input['make'] ?? '')), 0, 120),
            'model' => mb_substr(trim((string) ($input['model'] ?? '')), 0, 120),
            'generation' => $this->nullableString($input['generation'] ?? null, 120),
            'engine' => $this->nullableString($input['engine'] ?? null, 120),
            'fuel_type' => $this->nullableString($input['fuel_type'] ?? null, 60),
            'year_from' => $yearFrom,
            'year_to' => $yearTo,
            'body_type' => $this->nullableString($input['body_type'] ?? null, 60),
            'is_active' => isset($input['is_active']) ? 1 : 0,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
        ];
    }

    private function nullableString(mixed $value, int $max = 255): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : mb_substr($trimmed, 0, $max);
    }

    private function toNullableInt(mixed $value): ?int
    {
        $normalized = trim((string) $value);
        if ($normalized === '' || ctype_digit($normalized) === false) {
            return null;
        }

        return (int) $normalized;
    }
}
