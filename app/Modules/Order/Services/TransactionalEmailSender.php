<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

final class TransactionalEmailSender
{
    /** @return array{provider:string, provider_message_id:?string} */
    public function send(string $to, string $subject, string $htmlBody): array
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromHeader(),
        ];

        $sent = mail($to, $subject, $htmlBody, implode("\r\n", $headers));

        if ($sent === false) {
            throw new \RuntimeException('mail() returnerade false.');
        }

        return [
            'provider' => 'native_php_mail',
            'provider_message_id' => null,
        ];
    }

    private function fromHeader(): string
    {
        $configured = trim((string) getenv('MAIL_FROM'));
        if ($configured !== '') {
            return $configured;
        }

        $appName = trim((string) getenv('APP_NAME')) ?: 'A-Racing';

        return sprintf('%s <%s>', $appName, 'no-reply@localhost');
    }
}
