<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Services;

final class CheckoutTotalsService
{
    /** @return array<string, float> */
    public function calculate(float $productSubtotal, float $shippingCostIncVat): array
    {
        $subtotal = max(0, $productSubtotal);
        $shipping = max(0, $shippingCostIncVat);

        return [
            'product_subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'grand_total' => $subtotal + $shipping,
        ];
    }
}
