<?php

declare(strict_types=1);

namespace App\Modules\Import\Repositories;

use PDO;

final class ImportRowRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function forRun(int $runId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, row_number, status, raw_row_json, mapped_row_json, error_message, created_at
             FROM import_rows
             WHERE import_run_id = :run_id
             ORDER BY row_number ASC'
        );
        $stmt->execute(['run_id' => $runId]);

        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $rawRow
     * @param array<string, mixed>|null $mappedRow
     */
    public function create(int $runId, int $rowNumber, string $status, array $rawRow, ?array $mappedRow, ?string $errorMessage): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO import_rows (import_run_id, row_number, status, raw_row_json, mapped_row_json, error_message, created_at, updated_at)
             VALUES (:import_run_id, :row_number, :status, :raw_row_json, :mapped_row_json, :error_message, NOW(), NOW())'
        );

        $stmt->execute([
            'import_run_id' => $runId,
            'row_number' => $rowNumber,
            'status' => $status,
            'raw_row_json' => json_encode($rawRow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'mapped_row_json' => $mappedRow !== null ? json_encode($mappedRow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'error_message' => $errorMessage,
        ]);
    }
}
