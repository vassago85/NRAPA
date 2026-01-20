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
        // Security questions that users answer during registration/profile setup
        Schema::create('user_security_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->string('answer_hash'); // Hashed answer for security
            $table->timestamps();
            
            $table->index('user_id');
        });

        // Log of account reset actions for audit trail
        Schema::create('account_reset_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reset_by')->constrained('users')->cascadeOnDelete();
            $table->enum('reset_type', ['password', '2fa']);
            $table->boolean('verification_passed')->default(false);
            $table->text('notes')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['reset_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_reset_logs');
        Schema::dropIfExists('user_security_questions');
    }
};
