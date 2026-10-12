<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Endorsements no longer need a second admin signature. This migration
     * frees every open approval that was waiting on a second signature so
     * one admin can carry it over the line without re-submitting.
     *
     * - `requires_second_approval` is cleared on every open row (the enum
     *   now returns false, but existing rows were stamped at write time).
     * - `first_approved_by` / `first_approved_at` are kept as context on
     *   rows that already had a first signature, so the audit trail still
     *   shows who looked at it first.
     */
    public function up(): void
    {
        if (! Schema::hasTable('approvals')) {
            return;
        }

        DB::table('approvals')
            ->where('requires_second_approval', true)
            ->whereIn('state', ['waiting_on_admin', 'waiting_on_member'])
            ->update(['requires_second_approval' => false]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('approvals')) {
            return;
        }

        // Restore the previous default so a rollback does not leave
        // endorsements silently in single-signature mode.
        DB::table('approvals')
            ->where('type', 'endorsement')
            ->whereIn('state', ['waiting_on_admin', 'waiting_on_member'])
            ->update(['requires_second_approval' => true]);
    }
};
