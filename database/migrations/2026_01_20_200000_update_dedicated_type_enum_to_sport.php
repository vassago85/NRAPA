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
        $driver = DB::getDriverName();

        // SQLite: Has CHECK constraints that prevent value changes without table rebuild
        // This migration is primarily for MySQL production. For SQLite (local dev), 
        // use fresh migration with correct seeders that already use 'sport'.
        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'mysql') {
            // MySQL: ALTER ENUM columns first, then update data
            DB::statement("ALTER TABLE activity_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'sport_shooter', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE firearm_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'sport_shooter', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE event_categories MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'sport_shooter', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE knowledge_tests MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'sport_shooter', 'both') NULL");
            DB::statement("ALTER TABLE dedicated_status_applications MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'sport_shooter') NOT NULL");

            // Update data values
            DB::table('activity_types')->where('dedicated_type', 'sport_shooter')->update(['dedicated_type' => 'sport']);
            DB::table('firearm_types')->where('dedicated_type', 'sport_shooter')->update(['dedicated_type' => 'sport']);
            DB::table('event_categories')->where('dedicated_type', 'sport_shooter')->update(['dedicated_type' => 'sport']);
            DB::table('knowledge_tests')->where('dedicated_type', 'sport_shooter')->update(['dedicated_type' => 'sport']);
            DB::table('dedicated_status_applications')->where('dedicated_type', 'sport_shooter')->update(['dedicated_type' => 'sport']);

            // Remove sport_shooter from ENUM now that data is migrated
            DB::statement("ALTER TABLE activity_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE firearm_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE event_categories MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE knowledge_tests MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'both') NULL");
            DB::statement("ALTER TABLE dedicated_status_applications MODIFY COLUMN dedicated_type ENUM('hunter', 'sport') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'mysql') {
            // Add sport_shooter back to ENUM
            DB::statement("ALTER TABLE activity_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'sport_shooter', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE firearm_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'sport_shooter', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE event_categories MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'sport_shooter', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE knowledge_tests MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'sport_shooter', 'both') NULL");
            DB::statement("ALTER TABLE dedicated_status_applications MODIFY COLUMN dedicated_type ENUM('hunter', 'sport', 'sport_shooter') NOT NULL");

            // Revert data values
            DB::table('activity_types')->where('dedicated_type', 'sport')->update(['dedicated_type' => 'sport_shooter']);
            DB::table('firearm_types')->where('dedicated_type', 'sport')->update(['dedicated_type' => 'sport_shooter']);
            DB::table('event_categories')->where('dedicated_type', 'sport')->update(['dedicated_type' => 'sport_shooter']);
            DB::table('knowledge_tests')->where('dedicated_type', 'sport')->update(['dedicated_type' => 'sport_shooter']);
            DB::table('dedicated_status_applications')->where('dedicated_type', 'sport')->update(['dedicated_type' => 'sport_shooter']);

            // Remove sport from ENUM
            DB::statement("ALTER TABLE activity_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport_shooter', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE firearm_types MODIFY COLUMN dedicated_type ENUM('hunter', 'sport_shooter', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE event_categories MODIFY COLUMN dedicated_type ENUM('hunter', 'sport_shooter', 'both') DEFAULT 'both'");
            DB::statement("ALTER TABLE knowledge_tests MODIFY COLUMN dedicated_type ENUM('hunter', 'sport_shooter', 'both') NULL");
            DB::statement("ALTER TABLE dedicated_status_applications MODIFY COLUMN dedicated_type ENUM('hunter', 'sport_shooter') NOT NULL");
        }
    }
};
