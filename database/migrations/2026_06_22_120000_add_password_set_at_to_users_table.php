<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_set_at')->nullable()->after('password');
        });

        // Backfill: existing members who have clearly used their account already
        // (verified email, seen the welcome letter, or logged in at least once)
        // are treated as having set their own password. This prevents valid members
        // from being told to "set a password" when an old reset link is reused.
        // Fresh imports (temporary password, no activity) stay null until they set
        // their own password via the single-use link.
        DB::table('users')
            ->whereNull('password_set_at')
            ->where(function ($q) {
                $q->whereNotNull('email_verified_at')
                    ->orWhereNotNull('welcome_letter_seen_at')
                    ->orWhere('logins_without_2fa', '>', 0);
            })
            ->update(['password_set_at' => DB::raw('COALESCE(email_verified_at, welcome_letter_seen_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });
    }
};
