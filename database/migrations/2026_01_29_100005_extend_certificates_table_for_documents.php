<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Extends certificates table to support all document types:
     * - Dedicated Hunter Certificate
     * - Dedicated Sport Shooter Certificate  
     * - Proof of Paid-Up Membership Certificate
     * - Membership Card
     * - Welcome Letter
     * - Endorsement Letter (already supported)
     */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Add document checksum for tamper detection
            $table->string('checksum', 64)->nullable()->after('qr_code');
            
            // Add document type enum if not already comprehensive
            // Note: certificate_type_id already links to certificate_types table
            // This migration ensures the table structure supports all document types
        });
        
        // Ensure certificate_types table can handle all document types
        // The certificate_types table already exists, we just need to seed the new types
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('checksum');
        });
    }
};
