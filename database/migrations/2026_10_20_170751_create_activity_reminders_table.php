<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency log for the year-end "upload your activities" reminder.
 *
 * One row per (user, year) means the admin can click "Send reminder" on the
 * admin activities page twice in a row (or re-run `nrapa:send-activity-reminders`)
 * without blasting members a second time. A fresh calendar year gets a fresh
 * log entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // Activity year the reminder is for (1 Jan–31 Oct window of that year).
            $table->unsignedSmallInteger('year');
            // Whether the recipient was actually emailed (vs inbox-only when
            // their address is bounced/complained/missing).
            $table->boolean('emailed')->default(false);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'year']);
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reminders');
    }
};
