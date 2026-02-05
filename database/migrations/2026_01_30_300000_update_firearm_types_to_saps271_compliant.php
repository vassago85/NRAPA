<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates firearm types to be SAPS 271 Form Section E compliant:
     * - Rifle
     * - Shotgun
     * - Handgun
     * - Combination
     * - Other (with specification field)
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        // Update endorsement_firearms table
        if (Schema::hasTable('endorsement_firearms')) {
            // Migrate existing data: rifle_manual and rifle_self_loading -> rifle
            DB::table('endorsement_firearms')
                ->whereIn('firearm_category', ['rifle_manual', 'rifle_self_loading'])
                ->update(['firearm_category' => 'rifle']);
            
            // Add firearm_type_other field for "other" specification
            if (!Schema::hasColumn('endorsement_firearms', 'firearm_type_other')) {
                Schema::table('endorsement_firearms', function (Blueprint $table) {
                    $table->string('firearm_type_other')->nullable()->after('firearm_category');
                });
            }
            
            // Modify enum based on database driver
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE endorsement_firearms MODIFY COLUMN firearm_category ENUM('rifle', 'shotgun', 'handgun', 'combination', 'other') NOT NULL");
            }
            // Note: For SQLite, the CHECK constraint is stored in schema but SQLite doesn't strictly enforce it on existing data
            // The application-level validation in EndorsementFirearm model will ensure data integrity
        }
        
        // Update user_firearms table - add 'other' option
        if (Schema::hasTable('user_firearms') && Schema::hasColumn('user_firearms', 'firearm_type')) {
            // Add firearm_type_other field first (before migrating data)
            if (!Schema::hasColumn('user_firearms', 'firearm_type_other')) {
                Schema::table('user_firearms', function (Blueprint $table) {
                    $table->string('firearm_type_other')->nullable()->after('firearm_type');
                });
            }
            
            // Migrate hand_machine_carbine to 'other' with specification
            DB::table('user_firearms')
                ->where('firearm_type', 'hand_machine_carbine')
                ->update([
                    'firearm_type' => 'other',
                    'firearm_type_other' => 'Hand Machine Carbine'
                ]);
            
            // Modify enum based on database driver
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE user_firearms MODIFY COLUMN firearm_type ENUM('rifle', 'shotgun', 'handgun', 'combination', 'other') NULL");
            }
            // Note: For SQLite, the CHECK constraint is stored in schema but SQLite doesn't strictly enforce it on existing data
            // The application-level validation in UserFirearm model will ensure data integrity
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        // Revert endorsement_firearms
        if (Schema::hasTable('endorsement_firearms')) {
            // Migrate rifle back to rifle_manual (can't determine which was which, so default to rifle_manual)
            DB::table('endorsement_firearms')
                ->where('firearm_category', 'rifle')
                ->update(['firearm_category' => 'rifle_manual']);
            
            if (Schema::hasColumn('endorsement_firearms', 'firearm_type_other')) {
                Schema::table('endorsement_firearms', function (Blueprint $table) {
                    $table->dropColumn('firearm_type_other');
                });
            }
            
            // Modify enum based on database driver
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE endorsement_firearms MODIFY COLUMN firearm_category ENUM('handgun', 'rifle_manual', 'rifle_self_loading', 'shotgun') NOT NULL");
            } elseif ($driver === 'sqlite') {
                // Note: Reverting SQLite table recreation is complex and may cause data loss
                // This down migration is primarily for MySQL
            }
        }
        
        // Revert user_firearms
        if (Schema::hasTable('user_firearms') && Schema::hasColumn('user_firearms', 'firearm_type')) {
            // Migrate 'other' back to hand_machine_carbine if specification matches
            DB::table('user_firearms')
                ->where('firearm_type', 'other')
                ->where('firearm_type_other', 'Hand Machine Carbine')
                ->update([
                    'firearm_type' => 'hand_machine_carbine',
                    'firearm_type_other' => null
                ]);
            
            if (Schema::hasColumn('user_firearms', 'firearm_type_other')) {
                Schema::table('user_firearms', function (Blueprint $table) {
                    $table->dropColumn('firearm_type_other');
                });
            }
            
            // Modify enum based on database driver
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE user_firearms MODIFY COLUMN firearm_type ENUM('rifle', 'shotgun', 'handgun', 'hand_machine_carbine', 'combination') NULL");
            }
        }
    }
};
