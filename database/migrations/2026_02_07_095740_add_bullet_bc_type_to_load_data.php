<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('load_data', function (Blueprint $table) {
            $table->string('bullet_bc_type', 5)->default('G1')->after('bullet_bc');
        });
    }

    public function down(): void
    {
        Schema::table('load_data', function (Blueprint $table) {
            $table->dropColumn('bullet_bc_type');
        });
    }
};
