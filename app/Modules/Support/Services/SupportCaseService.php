<?php

declare(strict_types=1);

namespace App\Modules\Support\Services;

use App\Modules\Support\Repositories\SupportCaseHistoryRepository;
use App\Modules\Support\Repositories\SupportCaseRepository;
use App\Modules\Support\Repositories\SupportOrderRepository;
use InvalidArgumentException;

final class SupportCaseService
{
    private const STATUSES = ['open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed'];
    private const PRIORITIES = ['low', 'normal', 'high'];
    private const SOURCES = ['contact_form', 'account', 'order'];

    private const STATUS_TRANSITIONS = [
        'open' => ['in_progress', 'closed'],
        'in_progress' => ['waiting_for_customer', 'resolved', 'closed'],
        'waiting_for_customer' => ['in_progress', 'closed'],
        'resolved' => ['closed'],
        'closed' => [],
    ];

    public function __construct(
        private readonly SupportCaseRepository $cases,
        private readonly SupportCaseHistoryRepository $history,
        private readonly SupportOrderRepository $orders
    ) {
    }

    /** @return array<int, string> */
    public function statuses(): array
    {
        return self::STATUSES;
    }

    /** @return array<int, string> */
    public function priorities(): array
    {
        return self::PRIORITIES;
    }

    /** @return array<int, string> */
    public function sources(): array
    {
        return self::SOURCES;
    }

    /** @return array<string, string> */
    public function statusLabels(): array
    {
        return [
            'open' => 'Öppet',
            'in_progress' => 'Under arbete',
            'waiting_for_customer' => 'Väntar på kund',
            'resolved' => 'Löst',
            'closed' => 'Stängt',
        ];
    }

    /** @return array<string, string> */
    public function priorityLabels(): array
    {
        return [
            'low' => 'Låg',
            'normal' => 'Normal',
            'high' => 'Hög',
        ];
    }

    /** @return array<string, string> */
    public function sourceLabels(): array
    {
        return [
            'contact_form' => 'Kontaktformulär',
            'account' => 'Mina sidor',
            'order' => 'Order',
        ];
    }

    public function createFromContactForm(array $input, ?array $customer = null): int
    {
        $userId = $customer !== null ? (int) $customer['id'] : null;

        return $this->createCase(
            $input,
            'contact_form',
            $userId,
            null,
            $userId
        );
    }

    public function createFromAccount(int $userId, array $input): int
    {
        $orderId = isset($input['order_id']) && trim((string) $input['order_id']) !== '' ? (int) $input['order_id'] : null;
        $source = $orderId !== null ? 'order' : 'account';

        return $this->createCase($input, $source, $userId, $orderId, $userId);
    }

    public function createFromOrder(int $userId, int $orderId, array $input): int
    {
        return $this->createCase($input, 'order', $userId, $orderId, $userId);
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        return $this->cases->listForUser($userId);
    }

    /** @return array<string, mixed>|null */
    public function getDetailForUser(int $caseId, int $userId): ?array
    {
        $case = $this->cases->findById($caseId);
        if ($case === null || (int) ($case['user_id'] ?? 0) !== $userId) {
            return null;
        }

        return [
            'case' => $case,
            'history' => $this->publicHistory($this->history->listForCase($caseId)),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function listAdmin(array $filters): array
    {
        return $this->cases->listAdmin($filters);
    }

    /** @return array<string, mixed>|null */
    public function getDetailAdmin(int $caseId): ?array
    {
        $case = $this->cases->findById($caseId);
        if ($case === null) {
            return null;
        }

        return [
            'case' => $case,
            'history' => $this->history->listForCase($caseId),
        ];
    }

    public function updateStatusAdmin(int $caseId, string $targetStatus): void
    {
        $case = $this->requireCase($caseId);

        if (!in_array($targetStatus, self::STATUSES, true)) {
            throw new InvalidArgumentException('Ogiltig status för supportärende.');
        }

        $fromStatus = (string) $case['status'];
        if ($fromStatus === $targetStatus) {
            return;
        }

        $allowedTransitions = self::STATUS_TRANSITIONS[$fromStatus] ?? [];
        if (!in_array($targetStatus, $allowedTransitions, true)) {
            throw new InvalidArgumentException('Statusövergången är inte tillåten i v1-flödet.');
        }

        $closedAt = $targetStatus === 'closed' ? date('Y-m-d H:i:s') : null;
        $this->cases->updateStatus($caseId, $targetStatus, $closedAt);

        $this->history->create([
            'support_case_id' => $caseId,
            'event_type' => 'status_changed',
            'from_value' => $fromStatus,
            'to_value' => $targetStatus,
            'comment' => 'Status uppdaterad av admin.',
            'created_by_user_id' => null,
        ]);
    }

    public function updatePriorityAdmin(int $caseId, string $priority): void
    {
        $this->requireCase($caseId);
        if (!in_array($priority, self::PRIORITIES, true)) {
            throw new InvalidArgumentException('Ogiltig prioritet.');
        }

        $this->cases->updatePriority($caseId, $priority);
        $this->history->create([
            'support_case_id' => $caseId,
            'event_type' => 'priority_changed',
            'from_value' => null,
            'to_value' => $priority,
            'comment' => 'Prioritet uppdaterad av admin.',
            'created_by_user_id' => null,
        ]);
    }

    public function updateAdminNote(int $caseId, string $adminNote): void
    {
        $case = $this->requireCase($caseId);
        $note = trim($adminNote);
        if ($note === '') {
            throw new InvalidArgumentException('Adminnotering kan inte vara tom.');
        }

        $this->cases->updateAdminNote($caseId, $note);
        $this->history->create([
            'support_case_id' => $caseId,
            'event_type' => 'admin_note',
            'from_value' => null,
            'to_value' => null,
            'comment' => $note,
            'created_by_user_id' => null,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listForOrder(int $orderId): array
    {
        return $this->cases->listForOrder($orderId);
    }

    private function createCase(array $input, string $source, ?int $userId, ?int $orderId, ?int $createdByUserId): int
    {
        if (!in_array($source, self::SOURCES, true)) {
            throw new InvalidArgumentException('Ogiltig källa för supportärende.');
        }

        $subject = $this->limit(trim((string) ($input['subject'] ?? '')), 190, 'Ämne måste anges och vara max 190 tecken.');
        $message = trim((string) ($input['message'] ?? ''));
        if ($message === '') {
            throw new InvalidArgumentException('Meddelande måste anges.');
        }

        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Ange en giltig e-postadress.');
        }

        $name = $this->nullableLimited(trim((string) ($input['name'] ?? '')), 190, 'Namn är för långt.');
        $phone = $this->nullableLimited(trim((string) ($input['phone'] ?? '')), 60, 'Telefon är för långt.');

        $linkedOrder = null;
        if ($orderId !== null) {
            if ($userId === null) {
                throw new InvalidArgumentException('Orderkoppling kräver inloggat konto.');
            }

            $linkedOrder = $this->orders->findUserOrderById($userId, $orderId);
            if ($linkedOrder === null) {
                throw new InvalidArgumentException('Ordern kunde inte kopplas till supportärendet.');
            }
        }

        $caseId = $this->cases->create([
            'case_number' => $this->generateCaseNumber(),
            'user_id' => $userId,
            'order_id' => $linkedOrder !== null ? (int) $linkedOrder['id'] : null,
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
            'status' => 'open',
            'priority' => 'normal',
            'source' => $source,
            'admin_note' => null,
            'closed_at' => null,
        ]);

        $this->history->create([
            'support_case_id' => $caseId,
            'event_type' => 'created',
            'from_value' => null,
            'to_value' => 'open',
            'comment' => 'Supportärende skapat.',
            'created_by_user_id' => $createdByUserId,
        ]);

        if ($linkedOrder !== null) {
            $this->history->create([
                'support_case_id' => $caseId,
                'event_type' => 'linked_order',
                'from_value' => null,
                'to_value' => (string) $linkedOrder['order_number'],
                'comment' => 'Order kopplad till ärendet.',
                'created_by_user_id' => $createdByUserId,
            ]);
        }

        return $caseId;
    }

    private function generateCaseNumber(): string
    {
        $prefix = 'CASE-' . date('Ymd') . '-';
        for ($i = 0; $i < 10; $i++) {
            $caseNumber = $prefix . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            if (!$this->cases->existsByCaseNumber($caseNumber)) {
                return $caseNumber;
            }
        }

        throw new InvalidArgumentException('Kunde inte skapa unikt ärendenummer. Försök igen.');
    }

    /** @return array<string, mixed> */
    private function requireCase(int $caseId): array
    {
        $case = $this->cases->findById($caseId);
        if ($case === null) {
            throw new InvalidArgumentException('Supportärendet hittades inte.');
        }

        return $case;
    }

    private function limit(string $value, int $maxLength, string $message): string
    {
        if ($value === '' || mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    private function nullableLimited(string $value, int $maxLength, string $message): ?string
    {
        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    /** @param array<int, array<string, mixed>> $historyRows
     * @return array<int, array<string, mixed>>
     */
    private function publicHistory(array $historyRows): array
    {
        return array_values(array_filter($historyRows, static function (array $event): bool {
            return (string) ($event['event_type'] ?? '') !== 'admin_note';
        }));
    }
}
