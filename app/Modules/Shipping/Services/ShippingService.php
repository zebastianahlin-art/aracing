<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Services;

use App\Modules\Shipping\Repositories\ShippingMethodRepository;
use InvalidArgumentException;

final class ShippingService
{
    public function __construct(private readonly ShippingMethodRepository $methods)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listActiveForCheckout(): array
    {
        return $this->methods->activeMethods();
    }

    /** @return array<int, array<string, mixed>> */
    public function listForAdmin(): array
    {
        return $this->methods->allForAdmin();
    }

    /** @return array<string, mixed>|null */
    public function getById(int $id): ?array
    {
        return $this->methods->findById($id);
    }

    /** @return array<string, mixed> */
    public function validateSelectedMethod(?string $code): array
    {
        $normalizedCode = trim((string) $code);
        if ($normalizedCode === '') {
            throw new InvalidArgumentException('Välj en fraktmetod.');
        }

        $method = $this->methods->findActiveByCode($normalizedCode);
        if ($method === null) {
            throw new InvalidArgumentException('Vald fraktmetod är inte tillgänglig.');
        }

        return $method;
    }

    /** @return array<string, mixed> */
    public function buildOrderSnapshot(array $method): array
    {
        return [
            'shipping_method_code' => (string) $method['code'],
            'shipping_method_name' => (string) $method['name'],
            'shipping_method_description' => $this->nullable($method['description'] ?? null),
            'shipping_cost_ex_vat' => (float) $method['price_ex_vat'],
            'shipping_cost_inc_vat' => (float) $method['price_inc_vat'],
        ];
    }

    public function create(array $input): int
    {
        return $this->methods->create($this->normalizeInput($input));
    }

    public function update(int $id, array $input): void
    {
        if ($this->methods->findById($id) === null) {
            throw new InvalidArgumentException('Fraktmetoden hittades inte.');
        }

        $this->methods->update($id, $this->normalizeInput($input));
    }

    /** @return array<string, mixed> */
    private function normalizeInput(array $input): array
    {
        $code = trim((string) ($input['code'] ?? ''));
        $name = trim((string) ($input['name'] ?? ''));
        if ($code === '' || $name === '') {
            throw new InvalidArgumentException('Code och namn är obligatoriska.');
        }

        return [
            'code' => $code,
            'name' => $name,
            'description' => $this->nullable($input['description'] ?? null),
            'price_ex_vat' => (float) ($input['price_ex_vat'] ?? 0),
            'price_inc_vat' => (float) ($input['price_inc_vat'] ?? 0),
            'is_active' => ((string) ($input['is_active'] ?? '0')) === '1' ? 1 : 0,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
        ];
    }

    private function nullable(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
