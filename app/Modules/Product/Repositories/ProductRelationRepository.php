<?php

declare(strict_types=1);

namespace App\Modules\Product\Repositories;

use PDO;

final class ProductRelationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int,int> */
    public function relatedProductIds(int $productId, string $relationType, int $limit): array
    {
        $stmt = $this->pdo->prepare('SELECT related_product_id
            FROM product_relations
            WHERE product_id = :product_id
              AND relation_type = :relation_type
              AND is_active = 1
            ORDER BY sort_order ASC, id ASC
            LIMIT :limit');
        $stmt->bindValue('product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue('relation_type', $relationType);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn (array $row): int => (int) $row['related_product_id'], $stmt->fetchAll());
    }

    /** @return array<int,array<string,mixed>> */
    public function forAdminProduct(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT pr.id,
                    pr.related_product_id,
                    pr.relation_type,
                    pr.sort_order,
                    pr.is_active,
                    p.name AS related_product_name,
                    p.sku AS related_product_sku,
                    p.slug AS related_product_slug,
                    p.is_active AS related_is_active,
                    p.is_search_hidden AS related_is_hidden
            FROM product_relations pr
            INNER JOIN products p ON p.id = pr.related_product_id
            WHERE pr.product_id = :product_id
            ORDER BY pr.relation_type ASC, pr.sort_order ASC, pr.id ASC');
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO product_relations (
                product_id,
                related_product_id,
                relation_type,
                sort_order,
                is_active,
                created_at,
                updated_at
            ) VALUES (
                :product_id,
                :related_product_id,
                :relation_type,
                :sort_order,
                :is_active,
                NOW(),
                NULL
            )');
        $stmt->execute([
            'product_id' => $data['product_id'],
            'related_product_id' => $data['related_product_id'],
            'relation_type' => $data['relation_type'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function findByIdForProduct(int $id, int $productId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, product_id, related_product_id, relation_type, sort_order, is_active
            FROM product_relations
            WHERE id = :id AND product_id = :product_id');
        $stmt->execute(['id' => $id, 'product_id' => $productId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function update(int $id, int $productId, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE product_relations
            SET relation_type = :relation_type,
                sort_order = :sort_order,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id AND product_id = :product_id');
        $stmt->execute([
            'id' => $id,
            'product_id' => $productId,
            'relation_type' => $data['relation_type'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
        ]);
    }

    public function delete(int $id, int $productId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM product_relations WHERE id = :id AND product_id = :product_id');
        $stmt->execute(['id' => $id, 'product_id' => $productId]);
    }

    public function duplicateExists(int $productId, int $relatedProductId, string $relationType, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM product_relations
                WHERE product_id = :product_id
                  AND related_product_id = :related_product_id
                  AND relation_type = :relation_type';
        $params = [
            'product_id' => $productId,
            'related_product_id' => $relatedProductId,
            'relation_type' => $relationType,
        ];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }
}
