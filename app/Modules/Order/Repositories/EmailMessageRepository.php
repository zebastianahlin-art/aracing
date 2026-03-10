<?php

declare(strict_types=1);

namespace App\Modules\Order\Repositories;

use PDO;

final class EmailMessageRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createPending(
        string $relatedType,
        int $relatedId,
        string $emailType,
        string $recipientEmail,
        string $subject
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO email_messages (
                related_type, related_id, email_type, recipient_email, subject, status,
                provider, provider_message_id, error_message, sent_at, created_at
            ) VALUES (
                :related_type, :related_id, :email_type, :recipient_email, :subject, :status,
                :provider, :provider_message_id, :error_message, :sent_at, NOW()
            )'
        );

        $stmt->execute([
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'email_type' => $emailType,
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'status' => 'pending',
            'provider' => null,
            'provider_message_id' => null,
            'error_message' => null,
            'sent_at' => null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function markSent(int $id, ?string $provider, ?string $providerMessageId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE email_messages
             SET status = :status,
                 provider = :provider,
                 provider_message_id = :provider_message_id,
                 error_message = NULL,
                 sent_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'status' => 'sent',
            'provider' => $provider,
            'provider_message_id' => $providerMessageId,
        ]);
    }

    public function markFailed(int $id, ?string $provider, string $errorMessage): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE email_messages
             SET status = :status,
                 provider = :provider,
                 error_message = :error_message,
                 sent_at = NULL
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'status' => 'failed',
            'provider' => $provider,
            'error_message' => mb_substr(trim($errorMessage), 0, 5000),
        ]);
    }

    public function hasSentMessage(string $relatedType, int $relatedId, string $emailType): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM email_messages
             WHERE related_type = :related_type
               AND related_id = :related_id
               AND email_type = :email_type
               AND status = :status
             LIMIT 1'
        );

        $stmt->execute([
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'email_type' => $emailType,
            'status' => 'sent',
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public function forRelated(string $relatedType, int $relatedId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, related_type, related_id, email_type, recipient_email, subject, status,
                    provider, provider_message_id, error_message, sent_at, created_at
             FROM email_messages
             WHERE related_type = :related_type
               AND related_id = :related_id
             ORDER BY id DESC'
        );

        $stmt->execute([
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
