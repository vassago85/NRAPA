<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds 'barrel' and 'action' to firearm_category enum and component_diameter column.
     */
    public function up(): void
    {
        if (Schema::hasTable('endorsement_firearms')) {
            $driver = DB::getDriverName();

            // Expand enum to include barrel and action
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE endorsement_firearms MODIFY COLUMN firearm_category ENUM('rifle', 'shotgun', 'handgun', 'combination', 'other', 'barrel', 'action') NOT NULL");
            }

            // Add component_diameter column for barrel components
            if (! Schema::hasColumn('endorsement_firearms', 'component_diameter')) {
                Schema::table('endorsement_firearms', function (Blueprint $table) {
                    $table->string('component_diameter')->nullable()->after('firearm_type_other')
                        ->comment('Barrel diameter for barrel component endorsements (e.g. 6.5mm)');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('endorsement_firearms')) {
            $driver = DB::getDriverName();

            if (Schema::hasColumn('endorsement_firearms', 'component_diameter')) {
                Schema::table('endorsement_firearms', function (Blueprint $table) {
                    $table->dropColumn('component_diameter');
                });
            }

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE endorsement_firearms MODIFY COLUMN firearm_category ENUM('rifle', 'shotgun', 'handgun', 'combination', 'other') NOT NULL");
            }
        }
    }
};
