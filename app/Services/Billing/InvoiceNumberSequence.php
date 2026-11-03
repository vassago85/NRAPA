<?php

namespace App\Services\Billing;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;

/**
 * Minter for the I-NRAPA-NNNNNN sequence.
 *
 * The next number is stored in SystemSettings so it survives deploys and
 * stays single-sourced. `next()` runs the increment inside a transaction
 * with a row-level lock so two concurrent approvals can never mint the
 * same number.
 *
 * The counter is also what the owner-settings UI reads to decide whether
 * the admin can still change the start — once anything has been issued,
 * the counter is above its initial value and the UI should go read-only.
 */
class InvoiceNumberSequence
{
    public const SETTING_PREFIX = 'invoice_number_prefix';
    public const SETTING_WIDTH = 'invoice_number_width';
    public const SETTING_NEXT = 'invoice_number_next';

    public const DEFAULT_PREFIX = 'I-NRAPA-';
    public const DEFAULT_WIDTH = 6;
    public const DEFAULT_NEXT = 1;

    public function prefix(): string
    {
        return (string) SystemSetting::get(self::SETTING_PREFIX, self::DEFAULT_PREFIX);
    }

    public function width(): int
    {
        $width = (int) SystemSetting::get(self::SETTING_WIDTH, self::DEFAULT_WIDTH);

        return $width > 0 ? $width : self::DEFAULT_WIDTH;
    }

    /**
     * Peek without advancing the counter. Used by the owner-settings screen
     * so the admin can see what the next-issued number will look like.
     */
    public function peek(): string
    {
        $next = (int) SystemSetting::get(self::SETTING_NEXT, self::DEFAULT_NEXT);

        return $this->format($next > 0 ? $next : self::DEFAULT_NEXT);
    }

    /**
     * Mint the next invoice number and advance the counter atomically.
     */
    public function next(): string
    {
        return DB::transaction(function (): string {
            // lockForUpdate on the settings row prevents two concurrent
            // approvals from reading the same counter and minting duplicates.
            // If the row doesn't exist yet we create it at the default and
            // start the sequence there.
            $row = SystemSetting::where('key', self::SETTING_NEXT)->lockForUpdate()->first();

            if (! $row) {
                $current = self::DEFAULT_NEXT;
            } else {
                $current = (int) $row->value;
                if ($current <= 0) {
                    $current = self::DEFAULT_NEXT;
                }
            }

            SystemSetting::set(
                self::SETTING_NEXT,
                (string) ($current + 1),
                'integer',
                'invoicing',
                'Next invoice number to mint'
            );

            return $this->format($current);
        });
    }

    private function format(int $n): string
    {
        return $this->prefix().str_pad((string) $n, $this->width(), '0', STR_PAD_LEFT);
    }
}
