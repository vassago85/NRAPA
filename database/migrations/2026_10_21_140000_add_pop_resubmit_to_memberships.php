<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->timestamp('pop_resubmit_requested_at')->nullable()->after('pop_reminder_count');
            $table->text('pop_resubmit_message')->nullable()->after('pop_resubmit_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn(['pop_resubmit_requested_at', 'pop_resubmit_message']);
        });
    }
};
