<?php

declare(strict_types=1);

namespace App\Modules\Review\Repositories;

use PDO;

final class ProductReviewRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string,mixed>|null */
    public function findByUserAndProduct(int $userId, int $productId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM product_reviews WHERE user_id = :user_id AND product_id = :product_id LIMIT 1');
        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int,array<string,mixed>> */
    public function listApprovedForProduct(int $productId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare('SELECT id, rating, title, review_text, reviewer_name, is_verified_purchase, published_at, created_at
            FROM product_reviews
            WHERE product_id = :product_id AND status = :status
            ORDER BY published_at DESC, id DESC
            LIMIT :limit');
        $stmt->bindValue('product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue('status', 'approved');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO product_reviews (
            product_id, user_id, order_id, rating, title, review_text, status,
            is_verified_purchase, reviewer_name, published_at, created_at, updated_at
        ) VALUES (
            :product_id, :user_id, :order_id, :rating, :title, :review_text, :status,
            :is_verified_purchase, :reviewer_name, :published_at, NOW(), NOW()
        )');

        $stmt->execute([
            'product_id' => $data['product_id'],
            'user_id' => $data['user_id'],
            'order_id' => $data['order_id'],
            'rating' => $data['rating'],
            'title' => $data['title'],
            'review_text' => $data['review_text'],
            'status' => $data['status'],
            'is_verified_purchase' => $data['is_verified_purchase'],
            'reviewer_name' => $data['reviewer_name'],
            'published_at' => $data['published_at'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string,mixed> */
    public function summaryForProduct(int $productId): array
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS review_count, AVG(rating) AS average_rating
            FROM product_reviews
            WHERE product_id = :product_id AND status = :status');
        $stmt->execute([
            'product_id' => $productId,
            'status' => 'approved',
        ]);
        $row = $stmt->fetch() ?: [];

        return [
            'review_count' => (int) ($row['review_count'] ?? 0),
            'average_rating' => round((float) ($row['average_rating'] ?? 0), 2),
        ];
    }

    public function updateProductSummary(int $productId, int $reviewCount, float $averageRating): void
    {
        $stmt = $this->pdo->prepare('UPDATE products
            SET review_count = :review_count,
                average_rating = :average_rating,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $productId,
            'review_count' => $reviewCount,
            'average_rating' => $averageRating,
        ]);
    }

    /** @return array<string,mixed>|null */
    public function findVerifiedOrderForUserAndProduct(int $userId, int $productId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT o.id, o.order_number
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            WHERE o.user_id = :user_id
              AND oi.product_id = :product_id
            ORDER BY o.created_at DESC, o.id DESC
            LIMIT 1');
        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int,array<string,mixed>> */
    public function listAdmin(array $filters): array
    {
        $conditions = [];
        $params = [];

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $conditions[] = 'pr.status = :status';
            $params['status'] = $status;
        }

        $productId = trim((string) ($filters['product_id'] ?? ''));
        if ($productId !== '' && ctype_digit($productId)) {
            $conditions[] = 'pr.product_id = :product_id';
            $params['product_id'] = (int) $productId;
        }

        $sql = 'SELECT pr.id, pr.product_id, pr.user_id, pr.order_id, pr.rating, pr.status, pr.is_verified_purchase,
                pr.reviewer_name, pr.created_at, p.name AS product_name, p.slug AS product_slug,
                u.email AS user_email
            FROM product_reviews pr
            INNER JOIN products p ON p.id = pr.product_id
            LEFT JOIN users u ON u.id = pr.user_id';

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY pr.created_at DESC, pr.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findAdminById(int $reviewId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT pr.*, p.name AS product_name, p.slug AS product_slug, p.sku AS product_sku,
                u.email AS user_email, u.first_name AS user_first_name, u.last_name AS user_last_name,
                o.order_number
            FROM product_reviews pr
            INNER JOIN products p ON p.id = pr.product_id
            LEFT JOIN users u ON u.id = pr.user_id
            LEFT JOIN orders o ON o.id = pr.order_id
            WHERE pr.id = :id
            LIMIT 1');
        $stmt->execute(['id' => $reviewId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string,mixed>|null */
    public function findById(int $reviewId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM product_reviews WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $reviewId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function updateModeration(int $reviewId, string $status, ?string $publishedAt): void
    {
        $stmt = $this->pdo->prepare('UPDATE product_reviews
            SET status = :status,
                published_at = :published_at,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $reviewId,
            'status' => $status,
            'published_at' => $publishedAt,
        ]);
    }
}
