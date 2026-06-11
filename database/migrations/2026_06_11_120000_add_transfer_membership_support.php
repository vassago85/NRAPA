<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds support for the "transfer from another SAPS-accredited association" flow:
 *
 *  - membership_types.requires_transfer_documents (attribute-driven flag - keeps
 *    membership logic out of slug checks)
 *  - memberships.source extended with 'transfer'
 *  - memberships.transfer_competency_document_id
 *    + memberships.transfer_membership_document_id (FK to member_documents)
 *  - memberships.previous_association_name (free-text reference, no other
 *    associations are publicly named on the site).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->boolean('requires_transfer_documents')
                ->default(false)
                ->after('requires_knowledge_test')
                ->comment('When true, applicants must upload competency + current membership certificates before approval');
        });

        // Extend the enum to allow 'transfer' as a source. ENUM changes need raw SQL
        // on MySQL; SQLite stores ENUMs as TEXT (with a CHECK constraint that gets
        // recreated by Blueprint::enum) so we skip the MODIFY COLUMN there.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE memberships MODIFY COLUMN source ENUM('web', 'admin', 'import', 'transfer') NOT NULL DEFAULT 'web'");
        }

        Schema::table('memberships', function (Blueprint $table) {
            $table->foreignId('transfer_competency_document_id')
                ->nullable()
                ->after('source')
                ->constrained('member_documents')
                ->nullOnDelete();

            $table->foreignId('transfer_membership_document_id')
                ->nullable()
                ->after('transfer_competency_document_id')
                ->constrained('member_documents')
                ->nullOnDelete();

            $table->string('previous_association_name', 120)
                ->nullable()
                ->after('transfer_membership_document_id')
                ->comment('Name of previous SAPS-accredited association the applicant is transferring from');
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transfer_competency_document_id');
            $table->dropConstrainedForeignId('transfer_membership_document_id');
            $table->dropColumn('previous_association_name');
        });

        // Revert source enum (drop any 'transfer' rows back to 'web' first to be safe).
        DB::table('memberships')->where('source', 'transfer')->update(['source' => 'web']);
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE memberships MODIFY COLUMN source ENUM('web', 'admin', 'import') NOT NULL DEFAULT 'web'");
        }

        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropColumn('requires_transfer_documents');
        });
    }
};
