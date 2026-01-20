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
        Schema::table('calibre_requests', function (Blueprint $table) {
            $table->string('saps_code', 20)->nullable()->after('ignition_type');
            $table->json('aliases')->nullable()->after('saps_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calibre_requests', function (Blueprint $table) {
            $table->dropColumn(['saps_code', 'aliases']);
        });
    }
};
