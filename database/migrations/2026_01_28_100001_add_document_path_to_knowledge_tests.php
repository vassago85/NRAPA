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
        Schema::table('knowledge_tests', function (Blueprint $table) {
            $table->string('document_path')->nullable()->after('description')->comment('Uploaded document file (PDF, DOC, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_tests', function (Blueprint $table) {
            $table->dropColumn('document_path');
        });
    }
};
