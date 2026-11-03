<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-render invoices after adding the NRAPA address to the footer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        DB::table('invoices')->whereNotNull('pdf_path')->update(['pdf_path' => null]);
    }

    public function down(): void
    {
        // Cached files on disk are not restored.
    }
};
