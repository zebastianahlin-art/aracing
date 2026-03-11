<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Modules\Product\Repositories\ProductRelationRepository;
use App\Modules\Product\Repositories\ProductRepository;

final class ProductRelationService
{
    private const ALLOWED_RELATION_TYPES = ['related', 'cross_sell'];

    public function __construct(
        private readonly ProductRelationRepository $relations,
        private readonly ProductRepository $products
    ) {
    }

    /** @return array<int,string> */
    public function allowedTypes(): array
    {
        return self::ALLOWED_RELATION_TYPES;
    }

    /** @return array<int,int> */
    public function manualRelatedProductIds(int $productId, string $relationType, int $limit): array
    {
        if (!in_array($relationType, self::ALLOWED_RELATION_TYPES, true)) {
            return [];
        }

        return $this->relations->relatedProductIds($productId, $relationType, max(1, $limit));
    }

    /** @return array<int,array<string,mixed>> */
    public function adminRelationsForProduct(int $productId): array
    {
        return $this->relations->forAdminProduct($productId);
    }

    /** @param array<string,mixed> $input */
    public function createForProduct(int $productId, array $input): void
    {
        $this->assertProductExists($productId);

        $relatedProductId = max(0, (int) ($input['related_product_id'] ?? 0));
        $relationType = $this->normalizeRelationType((string) ($input['relation_type'] ?? 'related'));
        $sortOrder = $this->normalizeSortOrder($input['sort_order'] ?? 0);
        $isActive = isset($input['is_active']) && (string) $input['is_active'] === '1' ? 1 : 0;

        $this->validateRelationTarget($productId, $relatedProductId);
        if ($this->relations->duplicateExists($productId, $relatedProductId, $relationType)) {
            throw new \RuntimeException('Koppling finns redan för vald relationstyp.');
        }

        $this->relations->create([
            'product_id' => $productId,
            'related_product_id' => $relatedProductId,
            'relation_type' => $relationType,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ]);
    }

    /** @param array<string,mixed> $input */
    public function updateForProduct(int $productId, int $relationId, array $input): void
    {
        $existing = $this->relations->findByIdForProduct($relationId, $productId);
        if ($existing === null) {
            throw new \RuntimeException('Relationen kunde inte hittas.');
        }

        $relationType = $this->normalizeRelationType((string) ($input['relation_type'] ?? $existing['relation_type']));
        $sortOrder = $this->normalizeSortOrder($input['sort_order'] ?? $existing['sort_order']);
        $isActive = isset($input['is_active']) && (string) $input['is_active'] === '1' ? 1 : 0;

        if ($this->relations->duplicateExists($productId, (int) $existing['related_product_id'], $relationType, $relationId)) {
            throw new \RuntimeException('Koppling finns redan för vald relationstyp.');
        }

        $this->relations->update($relationId, $productId, [
            'relation_type' => $relationType,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ]);
    }

    public function deleteForProduct(int $productId, int $relationId): void
    {
        $this->relations->delete($relationId, $productId);
    }

    private function normalizeRelationType(string $relationType): string
    {
        $normalized = trim(mb_strtolower($relationType));
        if (!in_array($normalized, self::ALLOWED_RELATION_TYPES, true)) {
            throw new \RuntimeException('Ogiltig relationstyp.');
        }

        return $normalized;
    }

    private function normalizeSortOrder(mixed $value): int
    {
        return max(-10000, min(10000, (int) $value));
    }

    private function validateRelationTarget(int $productId, int $relatedProductId): void
    {
        if ($relatedProductId <= 0) {
            throw new \RuntimeException('Välj en relaterad produkt.');
        }

        if ($relatedProductId === $productId) {
            throw new \RuntimeException('En produkt kan inte relateras till sig själv.');
        }

        $this->assertProductExists($relatedProductId);
    }

    private function assertProductExists(int $productId): void
    {
        if ($this->products->findById($productId) === null) {
            throw new \RuntimeException('Produkten finns inte.');
        }
    }
}
