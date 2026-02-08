<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reloading_inventories', function (Blueprint $table) {
            $table->decimal('bullet_weight', 6, 1)->nullable()->after('name');   // grains
            $table->decimal('bullet_bc', 5, 3)->nullable()->after('bullet_weight');
            $table->string('bullet_bc_type', 5)->default('G1')->after('bullet_bc');
            $table->string('bullet_type', 50)->nullable()->after('bullet_bc_type'); // HPBT, SP, FMJ, etc.
            $table->string('calibre', 50)->nullable()->after('bullet_type');        // .308, 6.5mm, etc.
        });
    }

    public function down(): void
    {
        Schema::table('reloading_inventories', function (Blueprint $table) {
            $table->dropColumn(['bullet_weight', 'bullet_bc', 'bullet_bc_type', 'bullet_type', 'calibre']);
        });
    }
};
