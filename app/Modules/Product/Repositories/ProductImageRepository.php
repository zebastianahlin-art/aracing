<?php

declare(strict_types=1);

namespace App\Modules\Product\Repositories;

use PDO;

final class ProductImageRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function byProductId(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, image_url, alt_text, sort_order, is_primary FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC, id ASC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    /** @param array<int, array{image_url:string, alt_text:string, sort_order:int, is_primary:int}> $images */
    public function replaceForProduct(int $productId, array $images): void
    {
        $this->pdo->prepare('DELETE FROM product_images WHERE product_id = :product_id')->execute(['product_id' => $productId]);

        if ($images === []) {
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO product_images (product_id, image_url, alt_text, sort_order, is_primary, created_at, updated_at) VALUES (:product_id, :image_url, :alt_text, :sort_order, :is_primary, NOW(), NOW())');

        foreach ($images as $image) {
            $stmt->execute([
                'product_id' => $productId,
                'image_url' => $image['image_url'],
                'alt_text' => $image['alt_text'],
                'sort_order' => $image['sort_order'],
                'is_primary' => $image['is_primary'],
            ]);
        }
    }

    public function create(int $productId, string $imageUrl, string $altText, int $sortOrder, int $isPrimary): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO product_images (product_id, image_url, alt_text, sort_order, is_primary, created_at, updated_at) VALUES (:product_id, :image_url, :alt_text, :sort_order, :is_primary, NOW(), NOW())');
        $stmt->execute([
            'product_id' => $productId,
            'image_url' => $imageUrl,
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
            'is_primary' => $isPrimary,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findForProduct(int $productId, int $imageId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, product_id, image_url, alt_text, sort_order, is_primary FROM product_images WHERE id = :id AND product_id = :product_id LIMIT 1');
        $stmt->execute(['id' => $imageId, 'product_id' => $productId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function clearPrimaryForProduct(int $productId): void
    {
        $this->pdo->prepare('UPDATE product_images SET is_primary = 0, updated_at = NOW() WHERE product_id = :product_id')->execute(['product_id' => $productId]);
    }

    public function markPrimary(int $productId, int $imageId): void
    {
        $this->clearPrimaryForProduct($productId);
        $this->pdo->prepare('UPDATE product_images SET is_primary = 1, updated_at = NOW() WHERE id = :id AND product_id = :product_id')->execute([
            'id' => $imageId,
            'product_id' => $productId,
        ]);
    }

    public function updateMeta(int $productId, int $imageId, string $altText, int $sortOrder, bool $isPrimary): void
    {
        if ($isPrimary) {
            $this->clearPrimaryForProduct($productId);
        }

        $stmt = $this->pdo->prepare('UPDATE product_images
            SET alt_text = :alt_text,
                sort_order = :sort_order,
                is_primary = :is_primary,
                updated_at = NOW()
            WHERE id = :id AND product_id = :product_id');

        $stmt->execute([
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
            'is_primary' => $isPrimary ? 1 : 0,
            'id' => $imageId,
            'product_id' => $productId,
        ]);
    }

    public function deleteForProduct(int $productId, int $imageId): void
    {
        $this->pdo->prepare('DELETE FROM product_images WHERE id = :id AND product_id = :product_id')->execute([
            'id' => $imageId,
            'product_id' => $productId,
        ]);
    }

    public function nextSortOrder(int $productId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(sort_order), -10) + 10 FROM product_images WHERE product_id = :product_id');
        $stmt->execute(['product_id' => $productId]);

        return (int) $stmt->fetchColumn();
    }

    public function hasPrimary(int $productId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM product_images WHERE product_id = :product_id AND is_primary = 1 LIMIT 1');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchColumn() !== false;
    }
}
