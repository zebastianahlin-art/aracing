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
        $stmt = $this->pdo->prepare('SELECT image_url, alt_text, sort_order, is_primary FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC, id ASC');
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
}
