<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('sage_company_id')->nullable()->after('welcome_letter_seen_at');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->uuid('sage_invoice_id')->nullable()->after('change_amount');
            $table->string('sage_invoice_number')->nullable()->after('sage_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sage_company_id');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn(['sage_invoice_id', 'sage_invoice_number']);
        });
    }
};
