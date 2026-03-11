<?php

declare(strict_types=1);

namespace App\Modules\Checkout\Services;

use InvalidArgumentException;

final class CheckoutService
{
    private const PAYMENT_METHODS = [
        'stripe_checkout',
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
            'shipping_method_code' => $this->shippingMethodCode($input['shipping_method_code'] ?? null),
            'payment_method' => $this->paymentMethod($input['payment_method'] ?? null),
        ];
    }

    /** @param array<string, mixed>|null $customer
     *  @return array<string, string>
     */
    public function buildDefaultsForCustomer(?array $customer): array
    {
        if ($customer === null) {
            return $this->emptyDefaults();
        }

        $addressLine1 = trim((string) ($customer['address_line_1'] ?? ''));
        $addressLine2 = trim((string) ($customer['address_line_2'] ?? ''));
        $postalCode = trim((string) ($customer['postal_code'] ?? ''));
        $city = trim((string) ($customer['city'] ?? ''));
        $countryCode = strtoupper(trim((string) ($customer['country_code'] ?? '')));

        return [
            'customer_first_name' => trim((string) ($customer['first_name'] ?? '')),
            'customer_last_name' => trim((string) ($customer['last_name'] ?? '')),
            'customer_email' => trim((string) ($customer['email'] ?? '')),
            'customer_phone' => trim((string) ($customer['phone'] ?? '')),
            'billing_address_line_1' => $addressLine1,
            'billing_address_line_2' => $addressLine2,
            'billing_postal_code' => $postalCode,
            'billing_city' => $city,
            'billing_country' => $countryCode !== '' ? $countryCode : 'SE',
            'shipping_first_name' => trim((string) ($customer['first_name'] ?? '')),
            'shipping_last_name' => trim((string) ($customer['last_name'] ?? '')),
            'shipping_phone' => trim((string) ($customer['phone'] ?? '')),
            'shipping_address_line_1' => $addressLine1,
            'shipping_address_line_2' => $addressLine2,
            'shipping_postal_code' => $postalCode,
            'shipping_city' => $city,
            'shipping_country' => $countryCode !== '' ? $countryCode : 'SE',
        ];
    }

    /** @return array<int, array<string, string>> */
    public function paymentMethodOptions(): array
    {
        return [
            [
                'value' => 'stripe_checkout',
                'label' => 'Kort / direktbetalning (Stripe)',
                'help_text' => 'Du skickas till Stripe Checkout för säker betalning och återvänder sedan till orderstatus.',
            ],
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

    /** @return array<string, string> */
    private function emptyDefaults(): array
    {
        return [
            'customer_first_name' => '',
            'customer_last_name' => '',
            'customer_email' => '',
            'customer_phone' => '',
            'billing_address_line_1' => '',
            'billing_address_line_2' => '',
            'billing_postal_code' => '',
            'billing_city' => '',
            'billing_country' => 'SE',
            'shipping_first_name' => '',
            'shipping_last_name' => '',
            'shipping_phone' => '',
            'shipping_address_line_1' => '',
            'shipping_address_line_2' => '',
            'shipping_postal_code' => '',
            'shipping_city' => '',
            'shipping_country' => 'SE',
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


    private function shippingMethodCode(mixed $value): string
    {
        $shippingMethodCode = trim((string) $value);
        if ($shippingMethodCode === '') {
            throw new InvalidArgumentException('Välj en fraktmetod.');
        }

        return $shippingMethodCode;
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
