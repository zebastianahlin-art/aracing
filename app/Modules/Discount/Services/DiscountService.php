<?php

declare(strict_types=1);

namespace App\Modules\Discount\Services;

use App\Modules\Discount\Repositories\DiscountCodeRepository;
use InvalidArgumentException;

final class DiscountService
{
    private const ALLOWED_TYPES = ['fixed_amount', 'percent'];

    public function __construct(private readonly DiscountCodeRepository $codes)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForAdmin(): array
    {
        return $this->codes->allForAdmin();
    }

    /** @return array<string, mixed>|null */
    public function getById(int $id): ?array
    {
        return $this->codes->findById($id);
    }

    public function create(array $input): int
    {
        return $this->codes->create($this->normalizeInput($input));
    }

    public function update(int $id, array $input): void
    {
        if ($this->codes->findById($id) === null) {
            throw new InvalidArgumentException('Rabattkoden hittades inte.');
        }

        $this->codes->update($id, $this->normalizeInput($input));
    }

    /** @return array<string, mixed> */
    public function validateCodeForSubtotal(string $inputCode, float $productSubtotal): array
    {
        $code = strtoupper(trim($inputCode));
        if ($code === '') {
            throw new InvalidArgumentException('Ange en kampanjkod.');
        }

        $discount = $this->codes->findByCode($code);
        if ($discount === null) {
            throw new InvalidArgumentException('Kampanjkoden finns inte.');
        }

        if ((int) $discount['is_active'] !== 1) {
            throw new InvalidArgumentException('Kampanjkoden är inte aktiv.');
        }

        $now = date('Y-m-d H:i:s');
        if (!empty($discount['starts_at']) && (string) $discount['starts_at'] > $now) {
            throw new InvalidArgumentException('Kampanjkoden har inte startat ännu.');
        }

        if (!empty($discount['ends_at']) && (string) $discount['ends_at'] < $now) {
            throw new InvalidArgumentException('Kampanjkoden har gått ut.');
        }

        if ($discount['usage_limit'] !== null && (int) $discount['usage_count'] >= (int) $discount['usage_limit']) {
            throw new InvalidArgumentException('Kampanjkoden är fullständigt använd.');
        }

        $minimum = $discount['minimum_order_amount'] !== null ? (float) $discount['minimum_order_amount'] : null;
        if ($minimum !== null && $productSubtotal < $minimum) {
            throw new InvalidArgumentException('Minsta ordersumma för kampanjkoden är ' . number_format($minimum, 2, ',', ' ') . ' SEK.');
        }

        return $discount;
    }

    public function calculateDiscountAmount(array $discount, float $productSubtotal): float
    {
        $subtotal = max(0, $productSubtotal);
        $type = (string) ($discount['discount_type'] ?? '');
        $value = max(0, (float) ($discount['discount_value'] ?? 0));

        if ($subtotal <= 0 || !in_array($type, self::ALLOWED_TYPES, true)) {
            return 0.0;
        }

        $rawAmount = $type === 'percent' ? ($subtotal * ($value / 100)) : $value;

        return min($subtotal, max(0, $rawAmount));
    }

    /** @return array<string, mixed>|null */
    public function buildOrderSnapshot(?array $discount, float $discountAmount): ?array
    {
        if ($discount === null || $discountAmount <= 0) {
            return null;
        }

        return [
            'discount_code' => (string) $discount['code'],
            'discount_name' => (string) $discount['name'],
            'discount_type' => (string) $discount['discount_type'],
            'discount_value' => (float) $discount['discount_value'],
            'discount_amount_ex_vat' => $discountAmount,
            'discount_amount_inc_vat' => $discountAmount,
            'discount_id' => (int) $discount['id'],
        ];
    }

    public function incrementUsageCount(int $discountId): void
    {
        if (!$this->codes->incrementUsageCount($discountId)) {
            throw new InvalidArgumentException('Kampanjkoden kunde inte reserveras vid orderläggning. Försök igen.');
        }
    }

    /** @return array<string, mixed> */
    private function normalizeInput(array $input): array
    {
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $discountType = trim((string) ($input['discount_type'] ?? ''));
        $discountValue = max(0, (float) ($input['discount_value'] ?? 0));
        $minimumOrderAmount = trim((string) ($input['minimum_order_amount'] ?? ''));
        $usageLimit = trim((string) ($input['usage_limit'] ?? ''));
        $startsAt = trim((string) ($input['starts_at'] ?? ''));
        $endsAt = trim((string) ($input['ends_at'] ?? ''));

        if ($code === '' || mb_strlen($code) > 80) {
            throw new InvalidArgumentException('Code är obligatorisk och får vara max 80 tecken.');
        }

        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Namn är obligatoriskt och får vara max 120 tecken.');
        }

        if (!in_array($discountType, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException('Rabatt-typ måste vara fixed_amount eller percent.');
        }

        if ($discountType === 'percent' && $discountValue > 100) {
            throw new InvalidArgumentException('Procentrabatt får inte vara större än 100.');
        }

        return [
            'code' => $code,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'minimum_order_amount' => $minimumOrderAmount !== '' ? max(0, (float) $minimumOrderAmount) : null,
            'usage_limit' => $usageLimit !== '' ? max(1, (int) $usageLimit) : null,
            'starts_at' => $startsAt !== '' ? $startsAt : null,
            'ends_at' => $endsAt !== '' ? $endsAt : null,
            'is_active' => (int) (($input['is_active'] ?? '1') === '1' ? 1 : 0),
            'sort_order' => (int) ($input['sort_order'] ?? 0),
        ];
    }
}
