<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use PDO;

final class SearchQueryLogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function log(string $queryText, ?string $normalizedQuery, int $resultCount, ?string $sessionId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO search_query_logs (query_text, normalized_query, result_count, searched_at, session_id, selected_product_id)
            VALUES (:query_text, :normalized_query, :result_count, NOW(), :session_id, NULL)');
        $stmt->bindValue('query_text', mb_substr($queryText, 0, 255));
        $stmt->bindValue('normalized_query', $normalizedQuery !== null ? mb_substr($normalizedQuery, 0, 255) : null, $normalizedQuery === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue('result_count', max(0, $resultCount), PDO::PARAM_INT);
        $stmt->bindValue('session_id', $sessionId !== null ? mb_substr($sessionId, 0, 120) : null, $sessionId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }

    /** @return array<int,array<string,mixed>> */
    public function aggregateProblematicQueries(int $limit = 80): array
    {
        $sql = 'SELECT normalized_query,
                       MAX(query_text) AS sample_query,
                       COUNT(*) AS search_count,
                       SUM(CASE WHEN result_count = 0 THEN 1 ELSE 0 END) AS zero_result_count,
                       SUM(CASE WHEN result_count <= 2 THEN 1 ELSE 0 END) AS low_result_count,
                       AVG(result_count) AS avg_results,
                       MAX(searched_at) AS last_searched_at
                FROM search_query_logs
                WHERE normalized_query IS NOT NULL AND normalized_query <> ""
                GROUP BY normalized_query
                HAVING search_count >= 2 AND (zero_result_count >= 1 OR low_result_count >= 2)
                ORDER BY zero_result_count DESC, low_result_count DESC, search_count DESC, last_searched_at DESC
                LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
