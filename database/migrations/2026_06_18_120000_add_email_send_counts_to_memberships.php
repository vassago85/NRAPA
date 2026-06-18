<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->unsignedInteger('payment_email_count')->default(0)->after('payment_email_sent_at');
            $table->unsignedInteger('pop_reminder_count')->default(0)->after('pop_reminder_sent_at');
        });

        // Best-effort backfill: we have no historical send log per membership,
        // so seed the count to 1 wherever a "last sent" timestamp already exists.
        DB::table('memberships')->whereNotNull('payment_email_sent_at')->update(['payment_email_count' => 1]);
        DB::table('memberships')->whereNotNull('pop_reminder_sent_at')->update(['pop_reminder_count' => 1]);
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn(['payment_email_count', 'pop_reminder_count']);
        });
    }
};
