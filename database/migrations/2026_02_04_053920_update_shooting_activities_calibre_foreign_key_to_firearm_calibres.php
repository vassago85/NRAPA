<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('shooting_activities')) {
            return;
        }

        Schema::table('shooting_activities', function (Blueprint $table) {
            // Drop existing foreign key constraint if it exists
            // Use column-based drop which is database-agnostic
            try {
                $table->dropForeign(['calibre_id']);
            } catch (\Exception $e) {
                // Constraint might not exist or have different name
                // Only try MySQL-specific approach if not SQLite
                $driver = DB::getDriverName();
                $databaseName = DB::connection()->getDatabaseName();
                
                // Skip information_schema queries for SQLite (including :memory:)
                if (($driver === 'mysql' || $driver === 'mariadb') && $databaseName !== ':memory:') {
                    // Try to get actual constraint name for MySQL/MariaDB
                    try {
                        $constraints = DB::select("
                            SELECT CONSTRAINT_NAME 
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = 'shooting_activities' 
                            AND COLUMN_NAME = 'calibre_id' 
                            AND REFERENCED_TABLE_NAME IS NOT NULL
                        ");
                        
                        foreach ($constraints as $constraint) {
                            $constraintName = $constraint->CONSTRAINT_NAME;
                            // dropForeign can accept constraint name as string
                            try {
                                $table->dropForeign($constraintName);
                            } catch (\Exception $e2) {
                                // Try as array
                                try {
                                    $table->dropForeign([$constraintName]);
                                } catch (\Exception $e3) {
                                    // Skip if can't drop
                                }
                            }
                        }
                    } catch (\Exception $e2) {
                        // information_schema query failed, skip
                    }
                }
                // For SQLite, foreign keys are handled differently, so we continue
            }

            // Add new foreign key constraint pointing to firearm_calibres
            if (Schema::hasTable('firearm_calibres')) {
                $table->foreign('calibre_id')
                    ->references('id')
                    ->on('firearm_calibres')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('shooting_activities')) {
            return;
        }

        Schema::table('shooting_activities', function (Blueprint $table) {
            // Drop the firearm_calibres foreign key
            try {
                $table->dropForeign(['calibre_id']);
            } catch (\Exception $e) {
                // Constraint might not exist, continue
            }

            // Re-add the old calibres foreign key (if calibres table still exists)
            if (Schema::hasTable('calibres')) {
                $table->foreign('calibre_id')
                    ->references('id')
                    ->on('calibres')
                    ->nullOnDelete();
            }
        });
    }
};
