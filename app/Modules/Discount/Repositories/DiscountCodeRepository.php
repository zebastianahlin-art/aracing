<?php

declare(strict_types=1);

namespace App\Modules\Discount\Repositories;

use PDO;

final class DiscountCodeRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function allForAdmin(): array
    {
        return $this->pdo->query('SELECT * FROM discount_codes ORDER BY sort_order ASC, code ASC, id ASC')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM discount_codes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM discount_codes WHERE code = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO discount_codes (
            code, name, description, discount_type, discount_value, minimum_order_amount,
            usage_limit, usage_count, starts_at, ends_at, is_active, sort_order, created_at, updated_at
        ) VALUES (
            :code, :name, :description, :discount_type, :discount_value, :minimum_order_amount,
            :usage_limit, 0, :starts_at, :ends_at, :is_active, :sort_order, NOW(), NOW()
        )');
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->pdo->prepare('UPDATE discount_codes
            SET code = :code,
                name = :name,
                description = :description,
                discount_type = :discount_type,
                discount_value = :discount_value,
                minimum_order_amount = :minimum_order_amount,
                usage_limit = :usage_limit,
                starts_at = :starts_at,
                ends_at = :ends_at,
                is_active = :is_active,
                sort_order = :sort_order,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute($data);
    }

    public function incrementUsageCount(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE discount_codes
            SET usage_count = usage_count + 1,
                updated_at = NOW()
            WHERE id = :id
              AND (usage_limit IS NULL OR usage_count < usage_limit)');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() === 1;
    }
}
