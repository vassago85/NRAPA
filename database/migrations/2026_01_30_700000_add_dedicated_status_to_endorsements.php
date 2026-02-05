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
     * Adds dedicated status snapshot fields to endorsement_requests.
     * These fields are immutable after issuance for audit purposes.
     */
    public function up(): void
    {
        if (Schema::hasTable('endorsement_requests')) {
            Schema::table('endorsement_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('endorsement_requests', 'dedicated_status_compliant')) {
                    $table->boolean('dedicated_status_compliant')->nullable()->after('letter_file_path')->comment('Snapshot: Was member compliant at time of issuance');
                }
                if (!Schema::hasColumn('endorsement_requests', 'dedicated_category')) {
                    $table->string('dedicated_category')->nullable()->after('dedicated_status_compliant')->comment('Snapshot: Dedicated Sport Shooter, Dedicated Hunter, or Both');
                }
                if (!Schema::hasColumn('endorsement_requests', 'dedicated_status_snapshot_at')) {
                    $table->timestamp('dedicated_status_snapshot_at')->nullable()->after('dedicated_category')->comment('When the dedicated status snapshot was taken');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('endorsement_requests')) {
            Schema::table('endorsement_requests', function (Blueprint $table) {
                if (Schema::hasColumn('endorsement_requests', 'dedicated_status_snapshot_at')) {
                    $table->dropColumn('dedicated_status_snapshot_at');
                }
                if (Schema::hasColumn('endorsement_requests', 'dedicated_category')) {
                    $table->dropColumn('dedicated_category');
                }
                if (Schema::hasColumn('endorsement_requests', 'dedicated_status_compliant')) {
                    $table->dropColumn('dedicated_status_compliant');
                }
            });
        }
    }
};
