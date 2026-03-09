<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Import\Repositories\ImportProfileRepository;

final class ImportProfileService
{
    public function __construct(private readonly ImportProfileRepository $profiles)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->profiles->all();
    }

    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        return $this->profiles->findById($id);
    }

    /** @return array<string, mixed>|null */
    public function getWithSupplier(int $id): ?array
    {
        return $this->profiles->findByIdWithSupplier($id);
    }

    /** @param array<string, string> $input */
    public function create(array $input): int
    {
        return $this->profiles->create($this->normalizeData($input));
    }

    /** @param array<string, string> $input */
    public function update(int $id, array $input): void
    {
        $this->profiles->update($id, $this->normalizeData($input));
    }

    /** @param array<string, string> $input
     * @return array<string, mixed>
     */
    private function normalizeData(array $input): array
    {
        $mapping = trim($input['column_mapping_json'] ?? '{}');
        json_decode($mapping, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $mapping = '{}';
        }

        return [
            'supplier_id' => (int) ($input['supplier_id'] ?? 0),
            'name' => trim($input['name'] ?? ''),
            'file_type' => strtolower(trim($input['file_type'] ?? 'csv')),
            'delimiter' => $this->singleChar($input['delimiter'] ?? ','),
            'enclosure' => $this->nullableSingleChar($input['enclosure'] ?? '"'),
            'escape_char' => $this->nullableSingleChar($input['escape_char'] ?? '\\'),
            'column_mapping_json' => $mapping,
            'is_active' => isset($input['is_active']) ? 1 : 0,
        ];
    }

    private function singleChar(string $value): string
    {
        $value = trim($value);

        return $value === '' ? ',' : mb_substr($value, 0, 1);
    }

    private function nullableSingleChar(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 1);
    }
}
