<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('load_data')) {
            return;
        }

        $driver = DB::getDriverName();

        // Drop existing foreign key constraint pointing to old 'calibres' table
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'load_data' 
                AND COLUMN_NAME = 'calibre_id' 
                AND REFERENCED_TABLE_NAME = 'calibres'
            ");

            if (!empty($foreignKeys)) {
                foreach ($foreignKeys as $fk) {
                    try {
                        DB::statement("ALTER TABLE load_data DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Exception $e) {
                        // FK might already be dropped
                    }
                }
            }
        } else {
            try {
                Schema::table('load_data', function (Blueprint $table) {
                    $table->dropForeign(['calibre_id']);
                });
            } catch (\Exception $e) {
                // FK might not exist
            }
        }

        // Clean up invalid calibre_id values that don't exist in firearm_calibres
        if (Schema::hasTable('firearm_calibres')) {
            $validIds = DB::table('firearm_calibres')->pluck('id')->toArray();

            if (!empty($validIds)) {
                DB::table('load_data')
                    ->whereNotNull('calibre_id')
                    ->whereNotIn('calibre_id', $validIds)
                    ->update(['calibre_id' => null]);
            } else {
                DB::table('load_data')
                    ->whereNotNull('calibre_id')
                    ->update(['calibre_id' => null]);
            }
        }

        // Add new FK pointing to firearm_calibres
        if (Schema::hasTable('firearm_calibres')) {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $existingFk = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'load_data' 
                    AND COLUMN_NAME = 'calibre_id' 
                    AND REFERENCED_TABLE_NAME = 'firearm_calibres'
                ");

                if (empty($existingFk)) {
                    Schema::table('load_data', function (Blueprint $table) {
                        $table->foreign('calibre_id')
                            ->references('id')
                            ->on('firearm_calibres')
                            ->nullOnDelete();
                    });
                }
            } else {
                Schema::table('load_data', function (Blueprint $table) {
                    $table->foreign('calibre_id')
                        ->references('id')
                        ->on('firearm_calibres')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('load_data')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'load_data' 
                AND COLUMN_NAME = 'calibre_id' 
                AND REFERENCED_TABLE_NAME = 'firearm_calibres'
            ");

            if (!empty($foreignKeys)) {
                foreach ($foreignKeys as $fk) {
                    try {
                        DB::statement("ALTER TABLE load_data DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Exception $e) {
                        // FK might already be dropped
                    }
                }
            }
        } else {
            try {
                Schema::table('load_data', function (Blueprint $table) {
                    $table->dropForeign(['calibre_id']);
                });
            } catch (\Exception $e) {
                // Constraint might not exist
            }
        }

        if (Schema::hasTable('calibres')) {
            Schema::table('load_data', function (Blueprint $table) {
                $table->foreign('calibre_id')
                    ->references('id')
                    ->on('calibres')
                    ->nullOnDelete();
            });
        }
    }
};
