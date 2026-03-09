<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use PDO;

final class CatalogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function latestActiveProducts(int $limit = 12): array
    {
        $stmt = $this->pdo->prepare('SELECT p.id, p.name, p.slug, p.sku, p.description, p.sale_price, p.currency_code, p.stock_status, p.stock_quantity, b.name AS brand_name
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            WHERE p.is_active = 1
            ORDER BY p.updated_at DESC, p.id DESC
            LIMIT :limit');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function categoryBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, slug FROM categories WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function activeProductsByCategory(int $categoryId): array
    {
        $stmt = $this->pdo->prepare('SELECT p.id, p.name, p.slug, p.sku, p.sale_price, p.currency_code, p.stock_status, p.stock_quantity, b.name AS brand_name
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            WHERE p.category_id = :category_id AND p.is_active = 1
            ORDER BY p.name ASC');
        $stmt->execute(['category_id' => $categoryId]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function activeProductBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT p.id, p.name, p.slug, p.sku, p.description, p.sale_price, p.currency_code, p.stock_status, p.stock_quantity, b.name AS brand_name
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            WHERE p.slug = :slug AND p.is_active = 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function productAttributes(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT attribute_key, attribute_value FROM product_attributes WHERE product_id = :product_id ORDER BY attribute_key ASC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function productImages(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT image_url, alt_text, sort_order, is_primary FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC, id ASC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }
}
