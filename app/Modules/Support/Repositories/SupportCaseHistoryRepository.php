<?php

declare(strict_types=1);

namespace App\Modules\Support\Repositories;

use PDO;

final class SupportCaseHistoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO support_case_history
            (support_case_id, event_type, from_value, to_value, comment, created_by_user_id, created_at)
            VALUES (:support_case_id, :event_type, :from_value, :to_value, :comment, :created_by_user_id, NOW())');
        $stmt->execute([
            'support_case_id' => $data['support_case_id'],
            'event_type' => $data['event_type'],
            'from_value' => $data['from_value'],
            'to_value' => $data['to_value'],
            'comment' => $data['comment'],
            'created_by_user_id' => $data['created_by_user_id'],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listForCase(int $caseId): array
    {
        $stmt = $this->pdo->prepare('SELECT *
            FROM support_case_history
            WHERE support_case_id = :support_case_id
            ORDER BY created_at DESC, id DESC');
        $stmt->execute(['support_case_id' => $caseId]);

        return $stmt->fetchAll();
    }
}
