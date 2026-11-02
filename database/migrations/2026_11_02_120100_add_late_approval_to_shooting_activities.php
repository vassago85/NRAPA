<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A shooting activity is "late" when it is submitted after the activity-year
 * deadline (see config/compliance.php, default 31 October). Late submissions
 * still move through the normal approval queue, but they do NOT count toward
 * the member's 2/2 dedicated-status compliance until an admin ticks the
 * `is_late_approved` box when they approve the activity.
 *
 * This gives admins an explicit, auditable "we accept this late submission
 * for compliance" decision while keeping the member's record truthful.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shooting_activities', function (Blueprint $table) {
            $table->boolean('is_late_approved')->default(false)->after('verified_by');
            $table->timestamp('late_decided_at')->nullable()->after('is_late_approved');
            $table->foreignId('late_decided_by')
                ->nullable()
                ->after('late_decided_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shooting_activities', function (Blueprint $table) {
            $table->dropForeign(['late_decided_by']);
            $table->dropColumn(['is_late_approved', 'late_decided_at', 'late_decided_by']);
        });
    }
};
