<?php

declare(strict_types=1);

namespace App\Modules\Product\Repositories;

use PDO;

final class ProductAttributeRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function byProductId(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT attribute_key, attribute_value FROM product_attributes WHERE product_id = :product_id ORDER BY attribute_key ASC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    /** @param array<int, array{attribute_key:string, attribute_value:string}> $attributes */
    public function replaceForProduct(int $productId, array $attributes): void
    {
        $this->pdo->prepare('DELETE FROM product_attributes WHERE product_id = :product_id')->execute(['product_id' => $productId]);

        if ($attributes === []) {
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO product_attributes (product_id, attribute_key, attribute_value, created_at, updated_at) VALUES (:product_id, :key, :value, NOW(), NOW())');

        foreach ($attributes as $attribute) {
            $stmt->execute([
                'product_id' => $productId,
                'key' => $attribute['attribute_key'],
                'value' => $attribute['attribute_value'],
            ]);
        }
    }
}
