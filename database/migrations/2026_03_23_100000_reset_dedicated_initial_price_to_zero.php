<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remove stale R150 admin fee from dedicated membership types.
 *
 * Dedicated types should have initial_price = 0 (sign-up is basic + upgrade).
 * A stale admin fee (R150) was left on the dedicated-both type's upgrade_price.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('membership_types')
            ->whereNotNull('upgrade_price')
            ->where('initial_price', '>', 0)
            ->update(['initial_price' => 0]);

        DB::table('membership_types')
            ->where('slug', 'dedicated-both')
            ->update(['upgrade_price' => DB::raw('upgrade_price - 150')]);
    }

    public function down(): void
    {
        DB::table('membership_types')
            ->where('slug', 'dedicated-both')
            ->update(['upgrade_price' => DB::raw('upgrade_price + 150')]);
    }
};
