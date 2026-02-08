<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reloading_inventories', function (Blueprint $table) {
            $table->decimal('low_stock_threshold', 12, 2)->nullable()->after('cost_per_unit');
        });
    }

    public function down(): void
    {
        Schema::table('reloading_inventories', function (Blueprint $table) {
            $table->dropColumn('low_stock_threshold');
        });
    }
};
