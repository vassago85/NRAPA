<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixEndorsementStatusEnum extends Command
{
    protected $signature = 'nrapa:fix-endorsement-status-enum';
    protected $description = 'Fix the endorsement_requests status enum to include approved status (SQLite only)';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->info('This command is only needed for SQLite databases.');
            $this->info('For MySQL, please run: php artisan migrate');
            return Command::SUCCESS;
        }

        $this->info('Fixing endorsement_requests status enum to include "approved"...');

        try {
            $tableName = 'endorsement_requests';
            
            if (!Schema::hasTable($tableName)) {
                $this->error("Table {$tableName} does not exist!");
                return Command::FAILURE;
            }

            // Check if 'approved' is already allowed
            $testQuery = DB::selectOne("SELECT status FROM {$tableName} LIMIT 1");
            if ($testQuery) {
                // Try to update a test row to 'approved' to see if it's allowed
                try {
                    DB::statement("UPDATE {$tableName} SET status = 'approved' WHERE id = -999");
                    // If we get here, 'approved' is already allowed
                    $this->info('Status "approved" is already allowed in the enum constraint.');
                    return Command::SUCCESS;
                } catch (\Exception $e) {
                    // Constraint doesn't allow 'approved', need to fix it
                }
            }

            $this->info('Recreating table with updated status constraint...');

            // Create new table with updated constraint
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

            // Copy data
            $this->info('Copying data...');
            DB::statement("INSERT INTO {$tableName}_new SELECT * FROM {$tableName}");

            // Drop old table
            $this->info('Replacing old table...');
            DB::statement("DROP TABLE {$tableName}");

            // Rename new table
            DB::statement("ALTER TABLE {$tableName}_new RENAME TO {$tableName}");

            // Recreate indexes
            $this->info('Recreating indexes...');
            DB::statement("CREATE INDEX IF NOT EXISTS idx_endorsement_requests_user_status ON {$tableName}(user_id, status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_endorsement_requests_status_created ON {$tableName}(status, created_at)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_endorsement_requests_request_type ON {$tableName}(request_type)");

            $this->info('✓ Successfully updated status enum to include "approved"!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to fix status enum: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
