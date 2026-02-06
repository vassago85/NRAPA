<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('load_data', function (Blueprint $table) {
            $table->decimal('powder_price_per_kg', 10, 2)->nullable()->after('powder_charge');
            $table->decimal('primer_price_per_unit', 8, 2)->nullable()->after('primer_type');
            $table->decimal('bullet_price_per_unit', 8, 2)->nullable()->after('bullet_type');
            $table->decimal('brass_price_per_unit', 8, 2)->nullable()->after('brass_annealed');
        });
    }

    public function down(): void
    {
        Schema::table('load_data', function (Blueprint $table) {
            $table->dropColumn(['powder_price_per_kg', 'primer_price_per_unit', 'bullet_price_per_unit', 'brass_price_per_unit']);
        });
    }
};
