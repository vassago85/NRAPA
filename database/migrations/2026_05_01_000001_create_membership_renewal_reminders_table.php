<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_renewal_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')
                ->constrained('memberships')
                ->cascadeOnDelete();
            // 'thirty_days' | 'seven_days' | 'expired' — the bucket of reminder we sent.
            // Using a string (not enum) so future buckets don't need a migration.
            $table->string('kind', 32);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['membership_id', 'kind']);
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_renewal_reminders');
    }
};
