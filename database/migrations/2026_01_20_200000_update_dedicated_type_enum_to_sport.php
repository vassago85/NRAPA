<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Update dedicated_type enum from 'sport_shooter' to 'sport' for consistency
     * with MembershipType constants.
     */
    public function up(): void
    {
        // Update activity_types table
        DB::statement("ALTER TABLE activity_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'both') DEFAULT 'both'");
        DB::table('activity_types')->where('dedicated_type', 'sport_shooter')->update(['dedicated_type' => 'sport']);

        // Update firearm_types table
        DB::statement("ALTER TABLE firearm_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'both') DEFAULT 'both'");
        DB::table('firearm_types')->where('dedicated_type', 'sport_shooter')->update(['dedicated_type' => 'sport']);

        // Update event_categories table
        DB::statement("ALTER TABLE event_categories MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'both') DEFAULT 'both'");
        DB::table('event_categories')->where('dedicated_type', 'sport_shooter')->update(['dedicated_type' => 'sport']);

        // Update knowledge_tests table
        DB::statement("ALTER TABLE knowledge_tests MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'both') NULL");
        DB::table('knowledge_tests')->where('dedicated_type', 'sport_shooter')->update(['dedicated_type' => 'sport']);

        // Update dedicated_status_applications table
        DB::statement("ALTER TABLE dedicated_status_applications MODIFY COLUMN dedicated_type ENUM('hunter', 'sport') NOT NULL");
        DB::table('dedicated_status_applications')->where('dedicated_type', 'sport_shooter')->update(['dedicated_type' => 'sport']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert activity_types table
        DB::table('activity_types')->where('dedicated_type', 'sport')->update(['dedicated_type' => 'sport_shooter']);
        DB::statement("ALTER TABLE activity_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport_shooter', 'both') DEFAULT 'both'");

        // Revert firearm_types table
        DB::table('firearm_types')->where('dedicated_type', 'sport')->update(['dedicated_type' => 'sport_shooter']);
        DB::statement("ALTER TABLE firearm_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport_shooter', 'both') DEFAULT 'both'");

        // Revert event_categories table
        DB::table('event_categories')->where('dedicated_type', 'sport')->update(['dedicated_type' => 'sport_shooter']);
        DB::statement("ALTER TABLE event_categories MODIFY COLUMN dedicated_type ENUM('hunter', 'sport_shooter', 'both') DEFAULT 'both'");

        // Revert knowledge_tests table
        DB::table('knowledge_tests')->where('dedicated_type', 'sport')->update(['dedicated_type' => 'sport_shooter']);
        DB::statement("ALTER TABLE knowledge_tests MODIFY COLUMN dedicated_type ENUM('hunter', 'sport_shooter', 'both') NULL");

        // Revert dedicated_status_applications table
        DB::table('dedicated_status_applications')->where('dedicated_type', 'sport')->update(['dedicated_type' => 'sport_shooter']);
        DB::statement("ALTER TABLE dedicated_status_applications MODIFY COLUMN dedicated_type ENUM('hunter', 'sport_shooter') NOT NULL");
    }
};
