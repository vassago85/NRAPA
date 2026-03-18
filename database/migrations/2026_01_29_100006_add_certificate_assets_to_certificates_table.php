<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table exists and columns don't exist before adding
        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                if (! Schema::hasColumn('certificates', 'signatory_name')) {
                    $table->string('signatory_name')->nullable()->after('issued_by');
                }
                if (! Schema::hasColumn('certificates', 'signatory_title')) {
                    $table->string('signatory_title')->nullable()->after('signatory_name');
                }
                if (! Schema::hasColumn('certificates', 'signatory_signature_path')) {
                    $table->string('signatory_signature_path')->nullable()->after('signatory_title');
                }
                if (! Schema::hasColumn('certificates', 'commissioner_oaths_scan_path')) {
                    $table->string('commissioner_oaths_scan_path')->nullable()->after('signatory_signature_path');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                if (Schema::hasColumn('certificates', 'signatory_name')) {
                    $table->dropColumn('signatory_name');
                }
                if (Schema::hasColumn('certificates', 'signatory_title')) {
                    $table->dropColumn('signatory_title');
                }
                if (Schema::hasColumn('certificates', 'signatory_signature_path')) {
                    $table->dropColumn('signatory_signature_path');
                }
                if (Schema::hasColumn('certificates', 'commissioner_oaths_scan_path')) {
                    $table->dropColumn('commissioner_oaths_scan_path');
                }
            });
        }
    }
};
