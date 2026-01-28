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
        // MySQL doesn't support ALTER ENUM directly, so we need to modify the column
        // For SQLite, we'll use a different approach
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE endorsement_requests MODIFY COLUMN status ENUM('draft', 'submitted', 'under_review', 'pending_documents', 'approved', 'issued', 'rejected', 'cancelled') DEFAULT 'draft'");
        } else {
            // For SQLite, we need to recreate the table
            // This is a simplified approach - in production with SQLite, you'd want a more robust migration
            Schema::table('endorsement_requests', function (Blueprint $table) {
                // SQLite doesn't support ALTER ENUM, so we'll just note that the status can now be 'approved'
                // The application code will handle the validation
            });
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
