<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->timestamp('approval_revoked_at')->nullable()->after('welcome_email_sent_at');
            $table->unsignedBigInteger('approval_revoked_by')->nullable()->after('approval_revoked_at');
            $table->text('approval_revoked_reason')->nullable()->after('approval_revoked_by');
            $table->timestamp('pop_reminder_sent_at')->nullable()->after('approval_revoked_reason');
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn([
                'approval_revoked_at',
                'approval_revoked_by',
                'approval_revoked_reason',
                'pop_reminder_sent_at',
            ]);
        });
    }
};
