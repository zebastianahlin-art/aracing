<?php

declare(strict_types=1);

namespace App\Modules\Redirect\Repositories;

use PDO;

final class RedirectRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string,mixed>> */
    public function listForAdmin(?int $isActive = null): array
    {
        $sql = 'SELECT id, source_path, target_path, redirect_type, is_active, hit_count, last_hit_at, notes, created_at, updated_at
                FROM redirects';
        $params = [];

        if ($isActive !== null) {
            $sql .= ' WHERE is_active = :is_active';
            $params['is_active'] = $isActive;
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, source_path, target_path, redirect_type, is_active, hit_count, last_hit_at, notes, created_at, updated_at FROM redirects WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string,mixed>|null */
    public function findBySourcePath(string $sourcePath): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, source_path, target_path, redirect_type, is_active, hit_count, last_hit_at, notes, created_at, updated_at FROM redirects WHERE source_path = :source_path');
        $stmt->execute(['source_path' => $sourcePath]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string,mixed>|null */
    public function findActiveBySourcePath(string $sourcePath): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, source_path, target_path, redirect_type, is_active, hit_count, last_hit_at FROM redirects WHERE source_path = :source_path AND is_active = 1 LIMIT 1');
        $stmt->execute(['source_path' => $sourcePath]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO redirects (source_path, target_path, redirect_type, is_active, notes, created_at, updated_at)
                                     VALUES (:source_path, :target_path, :redirect_type, :is_active, :notes, NOW(), NOW())');
        $stmt->execute([
            'source_path' => $data['source_path'],
            'target_path' => $data['target_path'],
            'redirect_type' => $data['redirect_type'],
            'is_active' => $data['is_active'],
            'notes' => $data['notes'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE redirects
                                     SET source_path = :source_path,
                                         target_path = :target_path,
                                         redirect_type = :redirect_type,
                                         is_active = :is_active,
                                         notes = :notes,
                                         updated_at = NOW()
                                     WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'source_path' => $data['source_path'],
            'target_path' => $data['target_path'],
            'redirect_type' => $data['redirect_type'],
            'is_active' => $data['is_active'],
            'notes' => $data['notes'],
        ]);
    }

    public function recordHit(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE redirects SET hit_count = hit_count + 1, last_hit_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
