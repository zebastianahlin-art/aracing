<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Services;

final class CheckoutTotalsService
{
    /** @return array<string, float> */
    public function calculate(float $productSubtotal, float $shippingCostIncVat, float $discountAmountIncVat = 0.0): array
    {
        $subtotal = max(0, $productSubtotal);
        $shipping = max(0, $shippingCostIncVat);
        $discount = min($subtotal, max(0, $discountAmountIncVat));

        return [
            'product_subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'discount_amount' => $discount,
            'grand_total' => ($subtotal - $discount) + $shipping,
        ];
    }
}
