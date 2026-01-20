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
        Schema::create('membership_type_knowledge_test', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_test_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true)->comment('Whether this test must be passed for endorsement');
            $table->timestamps();

            // Prevent duplicate assignments
            $table->unique(['membership_type_id', 'knowledge_test_id'], 'mt_kt_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_type_knowledge_test');
    }
};
