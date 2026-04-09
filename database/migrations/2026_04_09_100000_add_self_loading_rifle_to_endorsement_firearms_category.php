<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('endorsement_firearms')) {
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE endorsement_firearms MODIFY COLUMN firearm_category ENUM('rifle', 'self_loading_rifle', 'shotgun', 'handgun', 'combination', 'other', 'barrel', 'action') NOT NULL");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('endorsement_firearms')) {
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE endorsement_firearms MODIFY COLUMN firearm_category ENUM('rifle', 'shotgun', 'handgun', 'combination', 'other', 'barrel', 'action') NOT NULL");
            }
        }
    }
};
