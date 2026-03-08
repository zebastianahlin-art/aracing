<?php

declare(strict_types=1);

namespace App\Modules\Category\Services;

use App\Modules\Category\Repositories\CategoryRepository;
use App\Shared\Support\Slugger;

final class CategoryService
{
    public function __construct(private readonly CategoryRepository $categories)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->categories->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function listForSelect(): array
    {
        return $this->categories->listForSelect();
    }

    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        return $this->categories->findById($id);
    }

    /** @param array<string, string> $input */
    public function create(array $input): int
    {
        $name = trim($input['name'] ?? '');
        $slug = Slugger::slugify($input['slug'] ?? $name);
        $parentId = $this->toNullableInt($input['parent_id'] ?? null);

        return $this->categories->create($name, $slug, $parentId);
    }

    /** @param array<string, string> $input */
    public function update(int $id, array $input): void
    {
        $name = trim($input['name'] ?? '');
        $slug = Slugger::slugify($input['slug'] ?? $name);
        $parentId = $this->toNullableInt($input['parent_id'] ?? null);
        if ($parentId === $id) {
            $parentId = null;
        }

        $this->categories->update($id, $name, $slug, $parentId);
    }

    private function toNullableInt(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return (int) $value;
    }
}
