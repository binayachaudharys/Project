<?php

namespace App\Services;

/**
 * Nepal VAT (VAT Act) helpers — standard rate 13%.
 */
class NepalVat
{
    public const DEFAULT_RATE = 13.0;

    /**
     * @return array{taxable: float, tax: float, total: float, rate: float, inclusive: bool}
     */
    public static function compute(
        float $subtotal,
        float $discount,
        float $rate = self::DEFAULT_RATE,
        bool $inclusive = false,
        bool $enabled = true,
    ): array {
        $net = max(round($subtotal - $discount, 2), 0.0);

        if (! $enabled || $rate <= 0) {
            return [
                'taxable' => $net,
                'tax' => 0.0,
                'total' => $net,
                'rate' => 0.0,
                'inclusive' => false,
            ];
        }

        if ($inclusive) {
            $taxable = round($net / (1 + ($rate / 100)), 2);
            $tax = round($net - $taxable, 2);

            return [
                'taxable' => $taxable,
                'tax' => $tax,
                'total' => $net,
                'rate' => $rate,
                'inclusive' => true,
            ];
        }

        $tax = round($net * ($rate / 100), 2);

        return [
            'taxable' => $net,
            'tax' => $tax,
            'total' => round($net + $tax, 2),
            'rate' => $rate,
            'inclusive' => false,
        ];
    }
}
