<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sportTest = DB::table('knowledge_tests')->where('slug', 'dedicated-sport-shooter')->first();

        if (! $sportTest) {
            return;
        }

        DB::table('knowledge_test_questions')
            ->where('knowledge_test_id', $sportTest->id)
            ->where('question_text', 'LIKE', 'There are four standard bolt action rifle shooting positions%')
            ->delete();

        // Re-sequence sort_order
        $questions = DB::table('knowledge_test_questions')
            ->where('knowledge_test_id', $sportTest->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($questions as $index => $q) {
            DB::table('knowledge_test_questions')
                ->where('id', $q->id)
                ->update(['sort_order' => $index + 1]);
        }
    }

    public function down(): void
    {
        // Cannot reliably restore deleted question
    }
};
