<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds SAPS 350A codes to firearm reference tables so admin can
     * cross-reference our data with the official SAPS dropdown codes.
     */
    public function up(): void
    {
        if (Schema::hasTable('firearm_types') && !Schema::hasColumn('firearm_types', 'saps_code')) {
            Schema::table('firearm_types', function (Blueprint $table) {
                $table->string('saps_code')->nullable()->after('slug')
                    ->comment('SAPS 350A FirearmTypes dropdown code');
                $table->index('saps_code');
            });
        }

        if (Schema::hasTable('firearm_makes') && !Schema::hasColumn('firearm_makes', 'saps_code')) {
            Schema::table('firearm_makes', function (Blueprint $table) {
                $table->string('saps_code')->nullable()->after('id')
                    ->comment('SAPS 350A FirearmMakes dropdown code');
                $table->index('saps_code');
            });
        }

        if (Schema::hasTable('firearm_calibres') && !Schema::hasColumn('firearm_calibres', 'saps_code')) {
            Schema::table('firearm_calibres', function (Blueprint $table) {
                $table->string('saps_code')->nullable()->after('id')
                    ->comment('SAPS 350A FirearmCalibres dropdown code');
                $table->index('saps_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('firearm_types') && Schema::hasColumn('firearm_types', 'saps_code')) {
            Schema::table('firearm_types', function (Blueprint $table) {
                $table->dropIndex(['saps_code']);
                $table->dropColumn('saps_code');
            });
        }

        if (Schema::hasTable('firearm_makes') && Schema::hasColumn('firearm_makes', 'saps_code')) {
            Schema::table('firearm_makes', function (Blueprint $table) {
                $table->dropIndex(['saps_code']);
                $table->dropColumn('saps_code');
            });
        }

        if (Schema::hasTable('firearm_calibres') && Schema::hasColumn('firearm_calibres', 'saps_code')) {
            Schema::table('firearm_calibres', function (Blueprint $table) {
                $table->dropIndex(['saps_code']);
                $table->dropColumn('saps_code');
            });
        }
    }
};
