<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $basicType = DB::table('membership_types')->where('slug', 'basic')->first();
        $standardType = DB::table('membership_types')->where('slug', 'standard-annual')->first();

        if (! $basicType || ! $standardType) {
            Log::warning('Migration skipped: basic or standard-annual membership type not found.');
            return;
        }

        $affected = DB::table('memberships')
            ->where('membership_type_id', $standardType->id)
            ->update(['membership_type_id' => $basicType->id]);

        Log::info("Migrated {$affected} memberships from standard-annual (ID:{$standardType->id}) to basic (ID:{$basicType->id}).");

        DB::table('membership_types')
            ->where('id', $standardType->id)
            ->update([
                'is_active' => false,
                'display_on_signup' => false,
                'display_on_landing' => false,
            ]);
    }

    public function down(): void
    {
        // Reversing this would require knowing which memberships were originally standard-annual,
        // which we don't track. Manual intervention needed if rollback is required.
    }
};
