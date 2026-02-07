<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ladder_tests', function (Blueprint $table) {
            $table->string('test_type', 20)->default('powder_charge')->after('primer_type');
            $table->string('value_unit', 10)->default('gr')->after('test_type');
        });

        // Increase precision for seating depth values (MySQL only, SQLite is flexible with precision)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('ladder_tests', function (Blueprint $table) {
                $table->decimal('start_charge', 8, 3)->change();
                $table->decimal('end_charge', 8, 3)->change();
                $table->decimal('increment', 6, 3)->change();
            });

            Schema::table('ladder_test_steps', function (Blueprint $table) {
                $table->decimal('charge_weight', 8, 3)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('ladder_tests', function (Blueprint $table) {
            $table->dropColumn(['test_type', 'value_unit']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('ladder_tests', function (Blueprint $table) {
                $table->decimal('start_charge', 5, 1)->change();
                $table->decimal('end_charge', 5, 1)->change();
                $table->decimal('increment', 4, 2)->change();
            });

            Schema::table('ladder_test_steps', function (Blueprint $table) {
                $table->decimal('charge_weight', 5, 1)->change();
            });
        }
    }
};
