<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('subject');
            $table->text('body')->nullable();
            $table->string('mailable_class')->nullable();
            $table->string('status')->default('sent'); // sent, failed, pending
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional data like membership_id, etc.
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['to_email', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
