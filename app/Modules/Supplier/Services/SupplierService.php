<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Services;

use App\Modules\Supplier\Repositories\SupplierRepository;
use App\Shared\Support\Slugger;

final class SupplierService
{
    public function __construct(private readonly SupplierRepository $suppliers)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->suppliers->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function listActive(): array
    {
        return $this->suppliers->allActive();
    }

    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        return $this->suppliers->findById($id);
    }

    /** @param array<string, string> $input */
    public function create(array $input): int
    {
        return $this->suppliers->create($this->normalizeData($input));
    }

    /** @param array<string, string> $input */
    public function update(int $id, array $input): void
    {
        $this->suppliers->update($id, $this->normalizeData($input));
    }

    /** @param array<string, string> $input
     * @return array<string, mixed>
     */
    private function normalizeData(array $input): array
    {
        $name = trim($input['name'] ?? '');

        return [
            'name' => $name,
            'slug' => Slugger::slugify($input['slug'] ?? $name),
            'is_active' => isset($input['is_active']) ? 1 : 0,
            'contact_name' => $this->nullableString($input['contact_name'] ?? ''),
            'contact_email' => $this->nullableString($input['contact_email'] ?? ''),
            'notes' => $this->nullableString($input['notes'] ?? ''),
        ];
    }

    private function nullableString(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
