<?php

declare(strict_types=1);

namespace App\Modules\Checkout\Services;

use InvalidArgumentException;

final class CheckoutService
{
    private const PAYMENT_METHODS = [
        'invoice_request',
        'manual_card_phone',
        'bank_transfer',
    ];

    /** @param array<string, mixed> $input
     *  @return array<string, string|null>
     */
    public function normalize(array $input): array
    {
        $customerFirstName = trim((string) ($input['customer_first_name'] ?? ''));
        $customerLastName = trim((string) ($input['customer_last_name'] ?? ''));
        $customerEmail = trim((string) ($input['customer_email'] ?? ''));

        if ($customerFirstName === '' || $customerLastName === '' || $customerEmail === '') {
            throw new InvalidArgumentException('Fyll i obligatoriska kunduppgifter.');
        }

        if (filter_var($customerEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Ange en giltig e-postadress.');
        }

        return [
            'customer_first_name' => $customerFirstName,
            'customer_last_name' => $customerLastName,
            'customer_email' => $customerEmail,
            'customer_phone' => $this->nullable($input['customer_phone'] ?? null),
            'billing_address_line_1' => $this->required($input['billing_address_line_1'] ?? null, 'Fyll i fakturaadress.'),
            'billing_address_line_2' => $this->nullable($input['billing_address_line_2'] ?? null),
            'billing_postal_code' => $this->required($input['billing_postal_code'] ?? null, 'Fyll i postnummer för fakturaadress.'),
            'billing_city' => $this->required($input['billing_city'] ?? null, 'Fyll i stad för fakturaadress.'),
            'billing_country' => strtoupper($this->required($input['billing_country'] ?? null, 'Fyll i landkod för fakturaadress.')),
            'shipping_first_name' => $this->required($input['shipping_first_name'] ?? null, 'Fyll i förnamn för leveransadress.'),
            'shipping_last_name' => $this->required($input['shipping_last_name'] ?? null, 'Fyll i efternamn för leveransadress.'),
            'shipping_phone' => $this->nullable($input['shipping_phone'] ?? null),
            'shipping_address_line_1' => $this->required($input['shipping_address_line_1'] ?? null, 'Fyll i leveransadress.'),
            'shipping_address_line_2' => $this->nullable($input['shipping_address_line_2'] ?? null),
            'shipping_postal_code' => $this->required($input['shipping_postal_code'] ?? null, 'Fyll i postnummer för leveransadress.'),
            'shipping_city' => $this->required($input['shipping_city'] ?? null, 'Fyll i stad för leveransadress.'),
            'shipping_country' => strtoupper($this->required($input['shipping_country'] ?? null, 'Fyll i landkod för leveransadress.')),
            'order_notes' => $this->nullable($input['order_notes'] ?? null),
            'payment_method' => $this->paymentMethod($input['payment_method'] ?? null),
        ];
    }

    /** @return array<int, array<string, string>> */
    public function paymentMethodOptions(): array
    {
        return [
            [
                'value' => 'invoice_request',
                'label' => 'Fakturaförfrågan',
                'help_text' => 'Vi granskar ordern och återkommer med bekräftelse och betalningsinstruktion.',
            ],
            [
                'value' => 'manual_card_phone',
                'label' => 'Kortbetalning via telefon',
                'help_text' => 'Vi kontaktar dig manuellt för att slutföra kortbetalningen.',
            ],
            [
                'value' => 'bank_transfer',
                'label' => 'Banköverföring',
                'help_text' => 'Betalningsinstruktion skickas manuellt efter ordergranskning.',
            ],
        ];
    }

    private function required(mixed $value, string $message): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            throw new InvalidArgumentException($message);
        }

        return $normalized;
    }

    private function nullable(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function paymentMethod(mixed $value): string
    {
        $paymentMethod = trim((string) $value);
        if (!in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            throw new InvalidArgumentException('Välj en giltig betalmetod.');
        }

        return $paymentMethod;
    }
}
