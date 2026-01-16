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
        Schema::create('knowledge_tests', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('passing_score')->default(70)->comment('Percentage');
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('knowledge_test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_test_id')->constrained()->cascadeOnDelete();
            $table->enum('question_type', ['multiple_choice', 'written'])->default('multiple_choice');
            $table->text('question_text');
            $table->json('options')->nullable()->comment('MC options array');
            $table->string('correct_answer')->nullable()->comment('MC correct answer');
            $table->unsignedTinyInteger('points')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('knowledge_test_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_test_id')->constrained();

            // Timing
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();

            // Scores
            $table->unsignedSmallInteger('auto_score')->nullable()->comment('MC auto-calculated');
            $table->unsignedSmallInteger('manual_score')->nullable()->comment('Written manual');
            $table->unsignedSmallInteger('total_score')->nullable();
            $table->boolean('passed')->nullable();

            // Manual marking
            $table->timestamp('marked_at')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users');
            $table->text('marker_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'knowledge_test_id']);
        });

        Schema::create('knowledge_test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('knowledge_test_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('knowledge_test_questions');
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->nullable()->comment('Auto-marked for MC');
            $table->unsignedTinyInteger('points_awarded')->nullable();
            $table->text('marker_feedback')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_test_answers');
        Schema::dropIfExists('knowledge_test_attempts');
        Schema::dropIfExists('knowledge_test_questions');
        Schema::dropIfExists('knowledge_tests');
    }
};
