<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add license expiry notification preferences to notification_preferences table
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('notify_license_expiry')->default(true)->after('notify_knowledge_test_completed');
            // JSON array of months before expiry to notify: [18, 12, 6]
            $table->json('license_expiry_intervals')->nullable()->after('notify_license_expiry');
        });

        // Update user_firearms table with new notification tracking columns
        Schema::table('user_firearms', function (Blueprint $table) {
            // Replace old notification columns with new ones for 18/12/6 month intervals
            $table->boolean('expiry_notification_sent_18m')->default(false)->after('expiry_notification_sent_7');
            $table->boolean('expiry_notification_sent_12m')->default(false)->after('expiry_notification_sent_18m');
            $table->boolean('expiry_notification_sent_6m')->default(false)->after('expiry_notification_sent_12m');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn(['notify_license_expiry', 'license_expiry_intervals']);
        });

        Schema::table('user_firearms', function (Blueprint $table) {
            $table->dropColumn(['expiry_notification_sent_18m', 'expiry_notification_sent_12m', 'expiry_notification_sent_6m']);
        });
    }
};
