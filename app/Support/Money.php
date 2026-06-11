<?php

namespace App\Support;

/**
 * Centralised South African Rand formatter.
 *
 * Use everywhere a price is rendered (Blade, emails, JSON-LD, exports) so the
 * formatting is consistent and easy to change in one place.
 */
class Money
{
    /**
     * Format a Rand amount as "R1,450" (no cents) or "R1,450.50" (cents).
     */
    public static function format(float|int|string|null $amount, bool $cents = false): string
    {
        $value = (float) ($amount ?? 0);
        $decimals = $cents ? 2 : 0;

        return 'R' . number_format($value, $decimals, '.', ',');
    }

    /**
     * Format for Schema.org / JSON-LD price fields ("1450.00", no currency
     * symbol, no thousands separator). Currency lives in priceCurrency.
     */
    public static function schema(float|int|string|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 2, '.', '');
    }
}
