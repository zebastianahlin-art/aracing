<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use PDO;

final class SearchQueryAliasRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function resolveActiveTarget(string $sourceQuery): ?string
    {
        $stmt = $this->pdo->prepare('SELECT target_query FROM search_query_aliases
            WHERE source_query = :source_query AND is_active = 1
            ORDER BY id DESC LIMIT 1');
        $stmt->execute(['source_query' => $sourceQuery]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? trim((string) $row['target_query']) : null;
    }

    public function upsertActive(string $sourceQuery, string $targetQuery, string $aliasType): void
    {
        $sourceQuery = mb_substr($sourceQuery, 0, 255);
        $targetQuery = mb_substr($targetQuery, 0, 255);
        $aliasType = mb_substr($aliasType, 0, 60);

        $this->pdo->beginTransaction();
        try {
            $disableStmt = $this->pdo->prepare('UPDATE search_query_aliases SET is_active = 0 WHERE source_query = :source_query');
            $disableStmt->execute(['source_query' => $sourceQuery]);

            $insertStmt = $this->pdo->prepare('INSERT INTO search_query_aliases (source_query, target_query, alias_type, is_active, created_at)
                VALUES (:source_query, :target_query, :alias_type, 1, NOW())');
            $insertStmt->execute([
                'source_query' => $sourceQuery,
                'target_query' => $targetQuery,
                'alias_type' => $aliasType,
            ]);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
