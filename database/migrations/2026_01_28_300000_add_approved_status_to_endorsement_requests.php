<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL doesn't support ALTER ENUM directly, so we need to modify the column
            DB::statement("ALTER TABLE endorsement_requests MODIFY COLUMN status ENUM('draft', 'submitted', 'under_review', 'pending_documents', 'approved', 'issued', 'rejected', 'cancelled') DEFAULT 'draft'");
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support ALTER ENUM, so we need to recreate the table with the new constraint
            // This is safe because we're in a migration and can recreate the constraint
            $tableName = 'endorsement_requests';

            // Use raw SQL to recreate the table with the updated CHECK constraint
            // SQLite allows us to recreate the table and copy data
            DB::statement("
                CREATE TABLE IF NOT EXISTS {$tableName}_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    uuid TEXT NOT NULL UNIQUE,
                    user_id INTEGER NOT NULL,
                    request_type TEXT NOT NULL DEFAULT 'new' CHECK(request_type IN ('new', 'renewal')),
                    status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft', 'submitted', 'under_review', 'pending_documents', 'approved', 'issued', 'rejected', 'cancelled')),
                    purpose TEXT CHECK(purpose IN ('section_16_application', 'status_confirmation', 'licence_renewal', 'additional_firearm', 'other')),
                    purpose_other_text TEXT,
                    declaration_accepted_at TIMESTAMP,
                    declaration_text TEXT,
                    submitted_at TIMESTAMP,
                    reviewed_at TIMESTAMP,
                    issued_at TIMESTAMP,
                    rejected_at TIMESTAMP,
                    cancelled_at TIMESTAMP,
                    reviewer_id INTEGER,
                    issued_by INTEGER,
                    member_notes TEXT,
                    admin_notes TEXT,
                    rejection_reason TEXT,
                    letter_reference TEXT UNIQUE,
                    letter_file_path TEXT,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    deleted_at TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ");

            // Copy data from old table to new table
            DB::statement("
                INSERT INTO {$tableName}_new 
                SELECT * FROM {$tableName}
            ");

            // Drop old table
            DB::statement("DROP TABLE {$tableName}");

            // Rename new table to original name
            DB::statement("ALTER TABLE {$tableName}_new RENAME TO {$tableName}");

            // Recreate indexes
            DB::statement("CREATE INDEX IF NOT EXISTS idx_endorsement_requests_user_status ON {$tableName}(user_id, status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_endorsement_requests_status_created ON {$tableName}(status, created_at)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_endorsement_requests_request_type ON {$tableName}(request_type)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE endorsement_requests MODIFY COLUMN status ENUM('draft', 'submitted', 'under_review', 'pending_documents', 'issued', 'rejected', 'cancelled') DEFAULT 'draft'");
        }
    }
};
