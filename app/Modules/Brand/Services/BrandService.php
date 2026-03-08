<?php

declare(strict_types=1);

namespace App\Modules\Brand\Services;

use App\Modules\Brand\Repositories\BrandRepository;
use App\Shared\Support\Slugger;

final class BrandService
{
    public function __construct(private readonly BrandRepository $brands)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->brands->all();
    }

    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        return $this->brands->findById($id);
    }

    /** @param array<string, string> $input */
    public function create(array $input): int
    {
        $name = trim($input['name'] ?? '');
        $slug = Slugger::slugify($input['slug'] ?? $name);

        return $this->brands->create($name, $slug);
    }

    /** @param array<string, string> $input */
    public function update(int $id, array $input): void
    {
        $name = trim($input['name'] ?? '');
        $slug = Slugger::slugify($input['slug'] ?? $name);
        $this->brands->update($id, $name, $slug);
    }
}
