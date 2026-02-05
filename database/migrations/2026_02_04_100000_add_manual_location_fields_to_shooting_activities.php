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
        Schema::table('shooting_activities', function (Blueprint $table) {
            // Add fields for manual country/province entry when "Other" is selected
            $table->string('country_name')->nullable()->after('country_id');
            $table->string('province_name')->nullable()->after('province_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shooting_activities', function (Blueprint $table) {
            $table->dropColumn(['country_name', 'province_name']);
        });
    }
};
