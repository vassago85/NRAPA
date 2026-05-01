<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            // Member-facing renewal reminder opt-out. Distinct from the existing
            // admin-facing `notify_membership_expiring`, which controls NTFY for staff.
            $table->boolean('notify_membership_expiry')
                ->default(true)
                ->after('notify_license_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn('notify_membership_expiry');
        });
    }
};
