<?php

namespace App\Concerns;

trait NormalizesPhone
{
    /**
     * Normalize a South African phone number to 10-digit format (0XX XXX XXXX).
     *
     * Handles: leading-zero loss from Excel, +27/27 prefixes, spaces, dashes, parens.
     * Returns null for empty/unrecoverable input.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '' || $digits === '0') {
            return null;
        }

        // +27 or 27 prefix → strip to local (27 + 9 digits = 11)
        if (str_starts_with($digits, '27') && strlen($digits) === 11) {
            $digits = '0' . substr($digits, 2);
        }

        // 9 digits → missing leading zero
        if (strlen($digits) === 9 && $digits[0] !== '0') {
            $digits = '0' . $digits;
        }

        // Must be exactly 10 digits starting with 0
        if (strlen($digits) !== 10 || $digits[0] !== '0') {
            return null;
        }

        return $digits;
    }
}
