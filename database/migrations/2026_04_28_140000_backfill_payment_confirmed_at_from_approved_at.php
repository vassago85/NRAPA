<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time backfill so the Payments Received report can show historical
     * data that pre-dates the payment_confirmed_at column (added 10 Apr 2026).
     *
     * For every billable (web/admin) membership that was actually approved
     * (status in approved/active/expired and approved_at is set) but has no
     * payment_confirmed_at yet, copy approved_at into payment_confirmed_at.
     *
     * Excluded on purpose:
     *  - source = 'import' (imports are not billable; were never NRAPA cash)
     *  - status in revoked / applied / pending_payment / pending_change
     *    (rejections, in-flight applications, awaiting-payment members do
     *    NOT represent received payment; the prior reject() bug left phantom
     *    approved_at values on revoked rows that we must not promote)
     */
    public function up(): void
    {
        DB::table('memberships')
            ->whereNull('payment_confirmed_at')
            ->whereNotNull('approved_at')
            ->whereIn('source', ['web', 'admin'])
            ->whereIn('status', ['approved', 'active', 'expired'])
            ->update([
                'payment_confirmed_at' => DB::raw('approved_at'),
            ]);
    }

    /**
     * Reverse the backfill: clear payment_confirmed_at where it exactly
     * matches approved_at and there is no payment_confirmed_by recorded
     * (i.e. the value was set by this migration, not by a real admin click).
     */
    public function down(): void
    {
        DB::table('memberships')
            ->whereNotNull('payment_confirmed_at')
            ->whereNull('payment_confirmed_by')
            ->whereColumn('payment_confirmed_at', 'approved_at')
            ->update([
                'payment_confirmed_at' => null,
            ]);
    }
};
