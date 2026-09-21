<?php

declare(strict_types=1);

namespace TechStore\Service;

final class CartCalculator
{
    public function calculate(array $items, int $discountPercent): array
    {
        if (!in_array($discountPercent, [0, 10, 20], true)) {
            $discountPercent = 0;
        }

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['unit_price_cents'] / 100) * (int) $item['quantity'];
        }

        $tax = $subtotal * 0.20;
        $discount = $subtotal * ($discountPercent / 100);
        $total = $subtotal + $tax - $discount;

        return [
            'subtotal_cents' => (int) round($subtotal * 100),
            'discount_percent' => $discountPercent,
            'discount_amount_cents' => (int) round($discount * 100),
            'discounted_subtotal_cents' => (int) round(($subtotal - $discount) * 100),
            'tax_cents' => (int) round($tax * 100),
            'total_cents' => (int) round($total * 100),
        ];
    }
}

