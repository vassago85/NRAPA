<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reset initial_price to 0 for all dedicated membership types.
 *
 * Dedicated types should always have initial_price = 0 because members
 * upgrade from basic; their sign-up cost is basic.initial_price + upgrade_price.
 * A stale admin fee (R150) was left in initial_price on some dedicated rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('membership_types')
            ->whereNotNull('upgrade_price')
            ->where('initial_price', '>', 0)
            ->update(['initial_price' => 0]);
    }

    public function down(): void
    {
        // Cannot reliably restore the old values
    }
};
