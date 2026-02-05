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
     * Adds diameter field for barrels and restricts component types to barrel, action, receiver only.
     */
    public function up(): void
    {
        if (Schema::hasTable('endorsement_components')) {
            Schema::table('endorsement_components', function (Blueprint $table) {
                // Add diameter field for barrels (needed before chamber is cut)
                if (!Schema::hasColumn('endorsement_components', 'diameter')) {
                    $table->string('diameter')->nullable()->after('calibre_manual')->comment('Barrel diameter (e.g., 6.5mm, .308) - used when barrel is licensed before chambering. Either diameter or calibre can be specified.');
                }
            });
            
            // Update component_type enum to only allow barrel, action, receiver
            // Note: MySQL doesn't support modifying ENUM directly, so we'll need to recreate the column
            if (DB::getDriverName() === 'mysql') {
                // First, update any existing invalid component types to 'barrel' as fallback
                DB::table('endorsement_components')
                    ->whereNotIn('component_type', ['barrel', 'action', 'receiver'])
                    ->update(['component_type' => 'barrel']);
                
                // Then modify the enum
                DB::statement("ALTER TABLE endorsement_components MODIFY COLUMN component_type ENUM('barrel', 'action', 'receiver') NOT NULL");
            } else {
                // For SQLite, we can't modify ENUM, but we'll rely on application-level validation
                // Update invalid types
                DB::table('endorsement_components')
                    ->whereNotIn('component_type', ['barrel', 'action', 'receiver'])
                    ->update(['component_type' => 'barrel']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('endorsement_components')) {
            Schema::table('endorsement_components', function (Blueprint $table) {
                if (Schema::hasColumn('endorsement_components', 'diameter')) {
                    $table->dropColumn('diameter');
                }
            });
            
            // Restore original enum values (if needed)
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE endorsement_components MODIFY COLUMN component_type ENUM('barrel', 'action', 'bolt', 'receiver', 'frame', 'slide', 'cylinder', 'trigger_group', 'other') NOT NULL");
            }
        }
    }
};
