<?php

declare(strict_types=1);

namespace App\Modules\Import\Repositories;

use PDO;

final class ImportProfileRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT p.id, p.supplier_id, s.name AS supplier_name, p.name, p.file_type, p.delimiter, p.enclosure, p.escape_char, p.column_mapping_json, p.is_active, p.created_at, p.updated_at
             FROM import_profiles p
             INNER JOIN suppliers s ON s.id = p.supplier_id
             ORDER BY p.id DESC'
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, supplier_id, name, file_type, delimiter, enclosure, escape_char, column_mapping_json, is_active FROM import_profiles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findByIdWithSupplier(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.supplier_id, s.name AS supplier_name, p.name, p.file_type, p.delimiter, p.enclosure, p.escape_char, p.column_mapping_json, p.is_active
             FROM import_profiles p
             INNER JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO import_profiles (supplier_id, name, file_type, delimiter, enclosure, escape_char, column_mapping_json, is_active, created_at, updated_at)
             VALUES (:supplier_id, :name, :file_type, :delimiter, :enclosure, :escape_char, :column_mapping_json, :is_active, NOW(), NOW())'
        );
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $data['id'] = $id;

        $stmt = $this->pdo->prepare(
            'UPDATE import_profiles
             SET supplier_id = :supplier_id,
                 name = :name,
                 file_type = :file_type,
                 delimiter = :delimiter,
                 enclosure = :enclosure,
                 escape_char = :escape_char,
                 column_mapping_json = :column_mapping_json,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute($data);
    }
}
