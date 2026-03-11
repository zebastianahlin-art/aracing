<?php

declare(strict_types=1);

namespace App\Modules\Returns\Repositories;

use PDO;

final class ReturnRequestHistoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $event): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO return_request_history
            (return_request_id, event_type, from_value, to_value, comment, created_by_user_id, created_at)
            VALUES
            (:return_request_id, :event_type, :from_value, :to_value, :comment, :created_by_user_id, :created_at)');
        $stmt->execute([
            'return_request_id' => $event['return_request_id'],
            'event_type' => $event['event_type'],
            'from_value' => $event['from_value'],
            'to_value' => $event['to_value'],
            'comment' => $event['comment'],
            'created_by_user_id' => $event['created_by_user_id'],
            'created_at' => $event['created_at'],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listByReturnRequest(int $returnRequestId): array
    {
        $stmt = $this->pdo->prepare('SELECT rrh.*, u.email AS created_by_email
            FROM return_request_history rrh
            LEFT JOIN users u ON u.id = rrh.created_by_user_id
            WHERE rrh.return_request_id = :return_request_id
            ORDER BY rrh.created_at DESC, rrh.id DESC');
        $stmt->execute(['return_request_id' => $returnRequestId]);

        return $stmt->fetchAll();
    }
}
