<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // NTFY Configuration
            $table->string('ntfy_topic')->nullable();
            $table->boolean('ntfy_enabled')->default(false);

            // Working Hours
            $table->time('working_hours_start')->default('08:00');
            $table->time('working_hours_end')->default('17:00');
            $table->json('working_days')->nullable(); // Mon-Fri by default (set in model/seeder, MySQL doesn't allow JSON defaults)
            $table->boolean('respect_working_hours')->default(true);

            // Notification Types (what to receive)
            $table->boolean('notify_new_member')->default(true);
            $table->boolean('notify_payment_received')->default(true);
            $table->boolean('notify_document_uploaded')->default(true);
            $table->boolean('notify_membership_expiring')->default(true);
            $table->boolean('notify_activity_submitted')->default(true);
            $table->boolean('notify_knowledge_test_completed')->default(true);
            $table->boolean('notify_system_errors')->default(false); // Only for developer

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
