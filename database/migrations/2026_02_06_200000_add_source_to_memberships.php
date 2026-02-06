<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->enum('source', ['web', 'admin', 'import'])->default('web')->after('notes');
        });

        // Set all existing memberships to 'import' so they don't count toward billing
        // These are the initial imported members before the billing system was implemented
        DB::table('memberships')->update(['source' => 'import']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
