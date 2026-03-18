<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes dedicated_type from activity_types after data migration is complete.
     */
    public function up(): void
    {
        Schema::table('activity_types', function (Blueprint $table) {
            $table->dropColumn('dedicated_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_types', function (Blueprint $table) {
            $table->enum('dedicated_type', ['hunter', 'sport_shooter', 'both'])->default('both')->after('description');
        });
    }
};
