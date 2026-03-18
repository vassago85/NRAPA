<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add dedicated_type to learning categories and articles.
     * - null = General content (visible to all members)
     * - 'hunter' = Only for dedicated hunters
     * - 'sport' = Only for dedicated sport shooters
     * - 'both' = For members with both dedicated statuses
     */
    public function up(): void
    {
        Schema::table('learning_categories', function (Blueprint $table) {
            $table->string('dedicated_type')->nullable()->after('is_active')
                ->comment('null=general, hunter, sport, or both');
        });

        Schema::table('learning_articles', function (Blueprint $table) {
            $table->string('dedicated_type')->nullable()->after('is_featured')
                ->comment('null=general (uses category default), hunter, sport, or both');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_categories', function (Blueprint $table) {
            $table->dropColumn('dedicated_type');
        });

        Schema::table('learning_articles', function (Blueprint $table) {
            $table->dropColumn('dedicated_type');
        });
    }
};
