<?php

declare(strict_types=1);

namespace App\Modules\Product\Repositories;

use PDO;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $sql = 'SELECT p.id, p.name, p.slug, p.sku, p.is_active, b.name AS brand_name, c.name AS category_name
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                ORDER BY p.updated_at DESC, p.id DESC';

        return $this->pdo->query($sql)->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, brand_id, category_id, name, slug, sku, description, is_active FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO products (brand_id, category_id, name, slug, sku, description, is_active, created_at, updated_at)
                VALUES (:brand_id, :category_id, :name, :slug, :sku, :description, :is_active, NOW(), NOW())';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('brand_id', $data['brand_id'], $data['brand_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('category_id', $data['category_id'], $data['category_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('name', $data['name']);
        $stmt->bindValue('slug', $data['slug']);
        $stmt->bindValue('sku', $data['sku']);
        $stmt->bindValue('description', $data['description']);
        $stmt->bindValue('is_active', $data['is_active'], PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE products
                SET brand_id = :brand_id, category_id = :category_id, name = :name, slug = :slug,
                    sku = :sku, description = :description, is_active = :is_active, updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('brand_id', $data['brand_id'], $data['brand_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('category_id', $data['category_id'], $data['category_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue('name', $data['name']);
        $stmt->bindValue('slug', $data['slug']);
        $stmt->bindValue('sku', $data['sku']);
        $stmt->bindValue('description', $data['description']);
        $stmt->bindValue('is_active', $data['is_active'], PDO::PARAM_INT);
        $stmt->execute();
    }
}
