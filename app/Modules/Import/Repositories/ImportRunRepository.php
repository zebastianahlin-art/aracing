<?php

declare(strict_types=1);

namespace App\Modules\Import\Repositories;

use PDO;

final class ImportRunRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT r.id, r.status, r.filename, r.total_rows, r.processed_rows, r.success_rows, r.failed_rows, r.started_at, r.finished_at, r.created_at,
                    s.name AS supplier_name, p.name AS profile_name
             FROM import_runs r
             LEFT JOIN suppliers s ON s.id = r.supplier_id
             LEFT JOIN import_profiles p ON p.id = r.import_profile_id
             ORDER BY r.id DESC'
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.supplier_id, r.import_profile_id, r.filename, r.status, r.total_rows, r.processed_rows, r.success_rows, r.failed_rows,
                    r.started_at, r.finished_at, r.created_at, r.updated_at,
                    s.name AS supplier_name, p.name AS profile_name
             FROM import_runs r
             LEFT JOIN suppliers s ON s.id = r.supplier_id
             LEFT JOIN import_profiles p ON p.id = r.import_profile_id
             WHERE r.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(?int $supplierId, ?int $profileId, string $filename): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO import_runs (supplier_id, import_profile_id, source, filename, status, started_at, created_at, updated_at)
             VALUES (:supplier_id, :import_profile_id, 'admin_csv', :filename, 'running', NOW(), NOW(), NOW())"
        );
        $stmt->execute([
            'supplier_id' => $supplierId,
            'import_profile_id' => $profileId,
            'filename' => $filename,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function markCompleted(int $runId): void
    {
        $stmt = $this->pdo->prepare("UPDATE import_runs SET status = 'completed', finished_at = NOW(), updated_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $runId]);
    }

    public function markFailed(int $runId): void
    {
        $stmt = $this->pdo->prepare("UPDATE import_runs SET status = 'failed', finished_at = NOW(), updated_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $runId]);
    }

    public function incrementCounters(int $runId, bool $success): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE import_runs
             SET total_rows = total_rows + 1,
                 processed_rows = processed_rows + 1,
                 success_rows = success_rows + :success,
                 failed_rows = failed_rows + :failed,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $runId,
            'success' => $success ? 1 : 0,
            'failed' => $success ? 0 : 1,
        ]);
    }
}
