<?php

declare(strict_types=1);

namespace App\Modules\Returns\Repositories;

use PDO;

final class ReturnRequestItemRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO return_request_items
            (return_request_id, order_item_id, product_id, quantity, reason_code, comment, created_at)
            VALUES
            (:return_request_id, :order_item_id, :product_id, :quantity, :reason_code, :comment, NOW())');
        $stmt->execute([
            'return_request_id' => $data['return_request_id'],
            'order_item_id' => $data['order_item_id'],
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'reason_code' => $data['reason_code'],
            'comment' => $data['comment'],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listByReturnRequest(int $returnRequestId): array
    {
        $stmt = $this->pdo->prepare('SELECT rri.*, oi.product_name_snapshot, oi.sku_snapshot
            FROM return_request_items rri
            INNER JOIN order_items oi ON oi.id = rri.order_item_id
            WHERE rri.return_request_id = :return_request_id
            ORDER BY rri.id ASC');
        $stmt->execute(['return_request_id' => $returnRequestId]);

        return $stmt->fetchAll();
    }
}
