<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify question_type enum to include new types (MySQL only, SQLite uses TEXT)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE knowledge_test_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'multiple_select', 'priority_order', 'written') DEFAULT 'multiple_choice'");
        }

        // Add correct_answers column for multi-select and priority questions
        if (!Schema::hasColumn('knowledge_test_questions', 'correct_answers')) {
            Schema::table('knowledge_test_questions', function (Blueprint $table) {
                $table->json('correct_answers')->nullable()->after('correct_answer')->comment('Array of correct answers for multi-select/priority questions');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_test_questions', function (Blueprint $table) {
            $table->dropColumn('correct_answers');
        });

        // Revert enum back to original values (MySQL only)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE knowledge_test_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'written') DEFAULT 'multiple_choice'");
        }
    }
};
