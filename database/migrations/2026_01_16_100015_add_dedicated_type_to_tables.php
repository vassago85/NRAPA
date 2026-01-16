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
        // Add dedicated_type to knowledge_tests
        // Tests can be for: hunter, sport_shooter, or both
        Schema::table('knowledge_tests', function (Blueprint $table) {
            $table->enum('dedicated_type', ['hunter', 'sport_shooter', 'both'])
                ->nullable()
                ->after('is_active')
                ->comment('Which dedicated status type this test is for');
        });

        // Add dedicated_type to dedicated_status_applications
        // Tracks which type of dedicated status is being applied for
        Schema::table('dedicated_status_applications', function (Blueprint $table) {
            $table->enum('dedicated_type', ['hunter', 'sport_shooter'])
                ->after('membership_id')
                ->comment('Type of dedicated status being applied for');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_tests', function (Blueprint $table) {
            $table->dropColumn('dedicated_type');
        });

        Schema::table('dedicated_status_applications', function (Blueprint $table) {
            $table->dropColumn('dedicated_type');
        });
    }
};
