<?php

declare(strict_types=1);

namespace App\Modules\StockAlert\Services;

use App\Core\View\ViewFactory;
use App\Modules\Order\Repositories\EmailMessageRepository;
use App\Modules\Order\Services\TransactionalEmailSender;

final class StockAlertNotificationService
{
    public function __construct(
        private readonly EmailMessageRepository $emailMessages,
        private readonly TransactionalEmailSender $sender,
        private readonly ViewFactory $views
    ) {
    }

    /** @param array<string,mixed> $subscription
     *  @param array<string,mixed> $product
     */
    public function sendBackInStockEmail(array $subscription, array $product): bool
    {
        $recipient = (string) ($subscription['email'] ?? '');
        if ($recipient === '') {
            return false;
        }

        $subject = sprintf('A-Racing: %s finns i lager igen', (string) ($product['name'] ?? 'Produkt'));
        $messageId = $this->emailMessages->createPending(
            'stock_alert_subscription',
            (int) ($subscription['id'] ?? 0),
            'stock_alert_back_in_stock',
            $recipient,
            $subject
        );

        try {
            $html = $this->views->render('emails.stock_alerts.back_in_stock', [
                'productName' => (string) ($product['name'] ?? 'Produkt'),
                'productUrl' => $this->productUrl((string) ($product['slug'] ?? '')),
            ]);
            $meta = $this->sender->send($recipient, $subject, $html);
            $this->emailMessages->markSent($messageId, $meta['provider'], $meta['provider_message_id']);

            return true;
        } catch (\Throwable $exception) {
            $this->emailMessages->markFailed($messageId, 'native_php_mail', $exception->getMessage());

            return false;
        }
    }

    private function productUrl(string $slug): string
    {
        $baseUrl = rtrim((string) (getenv('APP_URL') ?: 'http://127.0.0.1:8000'), '/');

        return $baseUrl . '/product/' . rawurlencode($slug);
    }
}
