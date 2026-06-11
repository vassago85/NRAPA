<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Member-controlled retention preference for personally-uploaded documents.
     *
     * - NULL / 'default' : standard POPIA behaviour — file purged from object
     *                     storage 7 days after admin verification.
     * - 'expiry_plus_1y': file kept until expires_at + 1 year, so the member
     *                     can re-download it for the entire calendar year
     *                     after the document expires (useful for licence
     *                     renewals, SAPS follow-ups, etc.). Once expires_at +
     *                     1y is in the past, the standard 7-day rule resumes.
     */
    public function up(): void
    {
        Schema::table('member_documents', function (Blueprint $table) {
            $table->string('retention_choice', 32)->nullable()->after('file_purged_at');
        });
    }

    public function down(): void
    {
        Schema::table('member_documents', function (Blueprint $table) {
            $table->dropColumn('retention_choice');
        });
    }
};
