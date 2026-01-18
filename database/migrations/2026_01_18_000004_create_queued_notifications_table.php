<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queued_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // new_member, payment_received, etc.
            $table->string('title');
            $table->text('message');
            $table->string('priority')->default('default'); // min, low, default, high, urgent
            $table->json('data')->nullable(); // Additional notification data
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->timestamp('scheduled_for')->nullable(); // When to send (working hours start)
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queued_notifications');
    }
};
