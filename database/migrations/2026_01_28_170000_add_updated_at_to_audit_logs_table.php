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
        // Check if table exists and column doesn't exist before altering
        if (Schema::hasTable('audit_logs') && ! Schema::hasColumn('audit_logs', 'updated_at')) {
            try {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                });
            } catch (\Exception $e) {
                // If column already exists or table structure is different, skip
                // This handles cases where the original migration already had timestamps()
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
