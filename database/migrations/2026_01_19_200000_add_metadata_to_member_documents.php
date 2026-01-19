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
        Schema::table('member_documents', function (Blueprint $table) {
            // Add metadata JSON column for document-specific data
            // For ID documents: surname, names, sex, identity_number, date_of_birth
            // For proof of address: street_address, suburb, city, province, postal_code
            $table->json('metadata')->nullable()->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_documents', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
