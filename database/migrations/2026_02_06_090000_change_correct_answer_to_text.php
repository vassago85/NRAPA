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
        // SQLite treats TEXT and VARCHAR the same, so change() is unnecessary
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('knowledge_test_questions', function (Blueprint $table) {
                $table->text('correct_answer')->nullable()->comment('Correct answer (supports long written answers)')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('knowledge_test_questions', function (Blueprint $table) {
                $table->string('correct_answer')->nullable()->comment('MC correct answer')->change();
            });
        }
    }
};
