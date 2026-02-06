<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'matching' to the question_type enum
        DB::statement("ALTER TABLE knowledge_test_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'multiple_select', 'priority_order', 'matching', 'written') DEFAULT 'multiple_choice'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'matching' from the enum (only if no matching questions exist)
        DB::statement("ALTER TABLE knowledge_test_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'multiple_select', 'priority_order', 'written') DEFAULT 'multiple_choice'");
    }
};
