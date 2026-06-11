<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Re-runs the standard-annual -> basic consolidation idempotently.
 *
 * The original 2026_04_10_100000_migrate_standard_annual_to_basic_membership
 * migration did the same thing, but production reports are still showing
 * memberships pinned to the standard-annual type. This may be because:
 *  - the original migration never ran on production, or
 *  - the standard-annual row got re-enabled in the admin panel and new
 *    sign-ups attached to it after the original migration.
 *
 * This migration is safe to re-run any number of times: it only updates rows
 * that are still pointing at the legacy type, and re-locks the type itself to
 * hidden/inactive so future sign-ups can't pick it up.
 *
 * It does NOT delete the standard-annual row — that would orphan audit-log
 * JSON references and historical certificates.
 */
return new class extends Migration
{
    public function up(): void
    {
        $basic = DB::table('membership_types')->where('slug', 'basic')->first();
        $standard = DB::table('membership_types')->where('slug', 'standard-annual')->first();

        if (! $basic) {
            Log::warning('[reconsolidate] basic membership_type not found; skipping.');
            return;
        }

        if (! $standard) {
            // Nothing to consolidate — fresh install or already cleaned up.
            return;
        }

        $remaining = DB::table('memberships')
            ->where('membership_type_id', $standard->id)
            ->count();

        if ($remaining > 0) {
            $moved = DB::table('memberships')
                ->where('membership_type_id', $standard->id)
                ->update(['membership_type_id' => $basic->id]);

            Log::info("[reconsolidate] Moved {$moved} memberships from standard-annual (id={$standard->id}) to basic (id={$basic->id}).");
        }

        // Always re-lock the legacy row even if it had zero memberships
        // attached — defends against the row being re-enabled in the admin panel.
        DB::table('membership_types')
            ->where('id', $standard->id)
            ->update([
                'is_active' => false,
                'display_on_signup' => false,
                'display_on_landing' => false,
                'is_featured' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Reversing this migration would require knowing which memberships were
        // originally standard-annual, which we no longer track. Manual data
        // restore from a backup is the only safe rollback.
    }
};
