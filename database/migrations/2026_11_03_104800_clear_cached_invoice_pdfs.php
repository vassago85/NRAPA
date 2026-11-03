<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The invoice template was simplified after the first render cache was
 * written. Drop stored PDF paths so the next admin download uses the new
 * layout. The invoice row (number, snapshot, totals) is unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('invoices')) {
            return;
        }

        DB::table('invoices')->whereNotNull('pdf_path')->update(['pdf_path' => null]);
    }

    public function down(): void
    {
        // Cached files on disk are not restored.
    }
};
