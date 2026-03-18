<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('shooting_activities')) {
            return;
        }

        $driver = DB::getDriverName();

        // Drop existing foreign key constraint if it exists
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Check if foreign key exists before dropping (only drop if pointing to 'calibres' table)
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'shooting_activities' 
                AND COLUMN_NAME = 'calibre_id' 
                AND REFERENCED_TABLE_NAME = 'calibres'
            ");

            if (! empty($foreignKeys)) {
                foreach ($foreignKeys as $fk) {
                    try {
                        DB::statement("ALTER TABLE shooting_activities DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Exception $e) {
                        // Foreign key might have already been dropped, continue
                    }
                }
            }
        } else {
            // For SQLite, try to drop but catch if it doesn't exist
            try {
                Schema::table('shooting_activities', function (Blueprint $table) {
                    $table->dropForeign(['calibre_id']);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
        }

        // Clean up invalid calibre_id values before adding foreign key
        // Set calibre_id to NULL if it doesn't exist in firearm_calibres
        if (Schema::hasTable('firearm_calibres')) {
            // Find all shooting_activities with calibre_id that don't exist in firearm_calibres
            $invalidCalibres = DB::table('shooting_activities')
                ->whereNotNull('calibre_id')
                ->whereNotIn('calibre_id', function ($query) {
                    $query->select('id')->from('firearm_calibres');
                })
                ->pluck('calibre_id', 'id')
                ->toArray();

            if (! empty($invalidCalibres)) {
                // Set invalid calibre_id values to NULL
                DB::table('shooting_activities')
                    ->whereIn('id', array_keys($invalidCalibres))
                    ->update(['calibre_id' => null]);
            }
        }

        // Clean up invalid calibre_id values before adding foreign key
        // Set calibre_id to NULL if it doesn't exist in firearm_calibres
        if (Schema::hasTable('firearm_calibres')) {
            // Find all shooting_activities with calibre_id that don't exist in firearm_calibres
            $validCalibreIds = DB::table('firearm_calibres')->pluck('id')->toArray();

            if (! empty($validCalibreIds)) {
                // Set invalid calibre_id values to NULL
                DB::table('shooting_activities')
                    ->whereNotNull('calibre_id')
                    ->whereNotIn('calibre_id', $validCalibreIds)
                    ->update(['calibre_id' => null]);
            } else {
                // If firearm_calibres table is empty, set all calibre_id to NULL
                DB::table('shooting_activities')
                    ->whereNotNull('calibre_id')
                    ->update(['calibre_id' => null]);
            }
        }

        // Add new foreign key constraint pointing to firearm_calibres (if it doesn't already exist)
        if (Schema::hasTable('firearm_calibres')) {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                // Check if foreign key already exists
                $existingFk = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'shooting_activities' 
                    AND COLUMN_NAME = 'calibre_id' 
                    AND REFERENCED_TABLE_NAME = 'firearm_calibres'
                ");

                if (empty($existingFk)) {
                    Schema::table('shooting_activities', function (Blueprint $table) {
                        $table->foreign('calibre_id')
                            ->references('id')
                            ->on('firearm_calibres')
                            ->nullOnDelete();
                    });
                }
            } else {
                // For SQLite, add the foreign key
                Schema::table('shooting_activities', function (Blueprint $table) {
                    $table->foreign('calibre_id')
                        ->references('id')
                        ->on('firearm_calibres')
                        ->nullOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('shooting_activities')) {
            return;
        }

        $driver = DB::getDriverName();

        // Drop the firearm_calibres foreign key
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'shooting_activities' 
                AND COLUMN_NAME = 'calibre_id' 
                AND REFERENCED_TABLE_NAME = 'firearm_calibres'
            ");

            if (! empty($foreignKeys)) {
                foreach ($foreignKeys as $fk) {
                    try {
                        DB::statement("ALTER TABLE shooting_activities DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Exception $e) {
                        // Foreign key might have already been dropped, continue
                    }
                }
            }
        } else {
            try {
                Schema::table('shooting_activities', function (Blueprint $table) {
                    $table->dropForeign(['calibre_id']);
                });
            } catch (\Exception $e) {
                // Constraint might not exist, continue
            }
        }

        // Re-add the old calibres foreign key (if calibres table still exists)
        if (Schema::hasTable('calibres')) {
            Schema::table('shooting_activities', function (Blueprint $table) {
                $table->foreign('calibre_id')
                    ->references('id')
                    ->on('calibres')
                    ->nullOnDelete();
            });
        }
    }
};
