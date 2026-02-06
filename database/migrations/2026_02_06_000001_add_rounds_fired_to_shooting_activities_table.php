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
            $table->unsignedInteger('rounds_fired')->nullable()->after('load_data_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shooting_activities', function (Blueprint $table) {
            $table->dropColumn('rounds_fired');
        });
    }
};
