<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_chases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_id')->constrained('approvals')->cascadeOnDelete();
            $table->unsignedTinyInteger('stage');
            // When the waiting-on-member spell started, so a second spell
            // (changes requested again) gets its own 7/14/21-day chases.
            $table->timestamp('cycle_started_at');
            $table->timestamp('sent_at');
            $table->foreignId('email_log_id')->nullable()->constrained('email_logs')->nullOnDelete();
            $table->timestamps();

            $table->unique(['approval_id', 'cycle_started_at', 'stage']);
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_chases');
    }
};
