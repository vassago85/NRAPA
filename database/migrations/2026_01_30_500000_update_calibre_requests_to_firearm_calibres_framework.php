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
     * Updates calibre_requests table to match FirearmCalibre framework:
     * - Updates category enum to match FirearmCalibre categories
     * - Updates foreign key to point to firearm_calibres instead of calibres
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        if (Schema::hasTable('calibre_requests')) {
            // Drop the old foreign key constraint if it exists
            if ($driver === 'mysql') {
                // Check if foreign key exists before dropping (only drop if pointing to 'calibres' table)
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'calibre_requests' 
                    AND COLUMN_NAME = 'calibre_id' 
                    AND REFERENCED_TABLE_NAME = 'calibres'
                ");
                
                if (!empty($foreignKeys)) {
                    foreach ($foreignKeys as $fk) {
                        DB::statement("ALTER TABLE calibre_requests DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    }
                }
            } else {
                // For SQLite, foreign keys are handled differently - will be recreated below
                try {
                    Schema::table('calibre_requests', function (Blueprint $table) {
                        $table->dropForeign(['calibre_id']);
                    });
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
            }
            
            // Update category enum to match FirearmCalibre categories
            if ($driver === 'mysql') {
                // MySQL: Modify the enum column
                DB::statement("ALTER TABLE calibre_requests MODIFY COLUMN category ENUM('handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic') DEFAULT 'rifle'");
            } elseif ($driver === 'sqlite') {
                // SQLite: Need to recreate table with new CHECK constraint
                // This is a simplified approach - in production you might want to preserve data
                DB::statement("
                    CREATE TABLE IF NOT EXISTS calibre_requests_new (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        name TEXT NOT NULL,
                        category TEXT NOT NULL DEFAULT 'rifle' CHECK(category IN ('handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic')),
                        ignition_type TEXT NOT NULL DEFAULT 'centerfire' CHECK(ignition_type IN ('rimfire', 'centerfire')),
                        saps_code TEXT,
                        aliases TEXT,
                        reason TEXT,
                        status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected')),
                        reviewed_by INTEGER,
                        reviewed_at TIMESTAMP,
                        admin_notes TEXT,
                        calibre_id INTEGER,
                        created_at TIMESTAMP,
                        updated_at TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
                        FOREIGN KEY (calibre_id) REFERENCES firearm_calibres(id) ON DELETE SET NULL
                    )
                ");
                
                // Copy data from old table, mapping 'other' to 'rifle'
                DB::statement("
                    INSERT INTO calibre_requests_new 
                    SELECT 
                        id,
                        user_id,
                        name,
                        CASE WHEN category = 'other' THEN 'rifle' ELSE category END as category,
                        ignition_type,
                        saps_code,
                        aliases,
                        reason,
                        status,
                        reviewed_by,
                        reviewed_at,
                        admin_notes,
                        calibre_id,
                        created_at,
                        updated_at
                    FROM calibre_requests
                ");
                
                // Drop old table and rename new one
                DB::statement("DROP TABLE calibre_requests");
                DB::statement("ALTER TABLE calibre_requests_new RENAME TO calibre_requests");
                
                // Recreate indexes
                DB::statement("CREATE INDEX IF NOT EXISTS idx_calibre_requests_status_created ON calibre_requests(status, created_at)");
                DB::statement("CREATE INDEX IF NOT EXISTS idx_calibre_requests_user_id ON calibre_requests(user_id)");
            }
            
            // Re-add foreign key constraint pointing to firearm_calibres (if it doesn't already exist)
            if ($driver === 'mysql') {
                // Check if foreign key already exists
                $existingFk = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'calibre_requests' 
                    AND COLUMN_NAME = 'calibre_id' 
                    AND REFERENCED_TABLE_NAME = 'firearm_calibres'
                ");
                
                if (empty($existingFk)) {
                    Schema::table('calibre_requests', function (Blueprint $table) {
                        $table->foreign('calibre_id')->references('id')->on('firearm_calibres')->nullOnDelete();
                    });
                }
            } else {
                // For SQLite, foreign keys are handled in the table recreation above
                // No need to add separately
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if (Schema::hasTable('calibre_requests')) {
            // Drop the foreign key constraint
            Schema::table('calibre_requests', function (Blueprint $table) {
                $table->dropForeign(['calibre_id']);
            });
            
            // Revert category enum back to original
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE calibre_requests MODIFY COLUMN category ENUM('handgun', 'rifle', 'shotgun', 'other') DEFAULT 'rifle'");
            }
            // Note: SQLite rollback would require similar table recreation
            
            // Re-add old foreign key (if calibres table still exists)
            if (Schema::hasTable('calibres')) {
                Schema::table('calibre_requests', function (Blueprint $table) {
                    $table->foreign('calibre_id')->references('id')->on('calibres')->nullOnDelete();
                });
            }
        }
    }
};
