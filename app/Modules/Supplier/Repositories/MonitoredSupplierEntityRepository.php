<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Repositories;

use PDO;

final class MonitoredSupplierEntityRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT id, entity_type, entity_id, priority_level, note, is_active, created_by_user_id, created_at, updated_at
             FROM monitored_supplier_entities
             ORDER BY is_active DESC, FIELD(priority_level, "critical", "high", "normal"), entity_type ASC, id DESC'
        )->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, entity_type, entity_id, priority_level, note, is_active, created_by_user_id, created_at, updated_at
             FROM monitored_supplier_entities
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string,mixed>|null */
    public function findByEntity(string $entityType, int $entityId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, entity_type, entity_id, priority_level, note, is_active, created_by_user_id, created_at, updated_at
             FROM monitored_supplier_entities
             WHERE entity_type = :entity_type AND entity_id = :entity_id
             LIMIT 1'
        );
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(string $entityType, int $entityId, string $priority, ?string $note, ?int $createdByUserId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO monitored_supplier_entities
                (entity_type, entity_id, priority_level, note, is_active, created_by_user_id, created_at, updated_at)
             VALUES
                (:entity_type, :entity_id, :priority_level, :note, 1, :created_by_user_id, NOW(), NOW())'
        );

        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'priority_level' => $priority,
            'note' => $note,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $priority, ?string $note, bool $isActive): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE monitored_supplier_entities
             SET priority_level = :priority_level,
                 note = :note,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'priority_level' => $priority,
            'note' => $note,
            'is_active' => $isActive ? 1 : 0,
        ]);
    }

    public function reactivateWithPayload(int $id, string $priority, ?string $note, ?int $createdByUserId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE monitored_supplier_entities
             SET priority_level = :priority_level,
                 note = :note,
                 is_active = 1,
                 created_by_user_id = COALESCE(created_by_user_id, :created_by_user_id),
                 updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'priority_level' => $priority,
            'note' => $note,
            'created_by_user_id' => $createdByUserId,
        ]);
    }

    /**
     * @param array<int,int> $supplierIds
     * @param array<int,int> $brandIds
     * @return array<string,array{priority_level:string,note:?string,id:int}>
     */
    public function activeSignalMap(array $supplierIds, array $brandIds): array
    {
        $conditions = [];
        $params = [];

        if ($supplierIds !== []) {
            $supplierPlaceholders = [];
            foreach (array_values($supplierIds) as $index => $supplierId) {
                $key = 'supplier_' . $index;
                $supplierPlaceholders[] = ':' . $key;
                $params[$key] = $supplierId;
            }

            $conditions[] = '(entity_type = "supplier" AND entity_id IN (' . implode(',', $supplierPlaceholders) . '))';
        }

        if ($brandIds !== []) {
            $brandPlaceholders = [];
            foreach (array_values($brandIds) as $index => $brandId) {
                $key = 'brand_' . $index;
                $brandPlaceholders[] = ':' . $key;
                $params[$key] = $brandId;
            }

            $conditions[] = '(entity_type = "brand" AND entity_id IN (' . implode(',', $brandPlaceholders) . '))';
        }

        if ($conditions === []) {
            return [];
        }

        $sql = 'SELECT id, entity_type, entity_id, priority_level, note
                FROM monitored_supplier_entities
                WHERE is_active = 1
                  AND (' . implode(' OR ', $conditions) . ')';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(string) $row['entity_type'] . ':' . (string) $row['entity_id']] = [
                'id' => (int) $row['id'],
                'priority_level' => (string) ($row['priority_level'] ?? 'normal'),
                'note' => isset($row['note']) ? (string) $row['note'] : null,
            ];
        }

        return $map;
    }

    /** @return array<string,int> */
    public function activeCountByType(): array
    {
        $rows = $this->pdo->query(
            'SELECT entity_type, COUNT(*) AS total
             FROM monitored_supplier_entities
             WHERE is_active = 1
             GROUP BY entity_type'
        )->fetchAll();

        $counts = ['supplier' => 0, 'brand' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $type = (string) ($row['entity_type'] ?? '');
            $count = (int) ($row['total'] ?? 0);
            if (isset($counts[$type])) {
                $counts[$type] = $count;
                $counts['total'] += $count;
            }
        }

        return $counts;
    }
}
