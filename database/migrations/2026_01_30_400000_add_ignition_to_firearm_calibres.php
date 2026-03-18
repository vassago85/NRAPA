<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds ignition field (rimfire/centerfire) and updates category to only include firearm types.
     * Migrates existing data: rimfire category -> ignition=rimfire, category=rifle
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (Schema::hasTable('firearm_calibres')) {
            // Add ignition field
            Schema::table('firearm_calibres', function (Blueprint $table) {
                if (! Schema::hasColumn('firearm_calibres', 'ignition')) {
                    $table->enum('ignition', ['rimfire', 'centerfire'])->nullable()->after('category');
                }
            });

            // Migrate existing data: rimfire category -> ignition=rimfire, category=rifle
            DB::table('firearm_calibres')
                ->where('category', 'rimfire')
                ->update([
                    'ignition' => 'rimfire',
                    'category' => 'rifle', // Default to rifle, can be updated manually if needed
                ]);

            // Set ignition for all other calibres to centerfire
            DB::table('firearm_calibres')
                ->whereNull('ignition')
                ->update(['ignition' => 'centerfire']);

            // Update category enum to remove 'rimfire' (only firearm types remain)
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE firearm_calibres MODIFY COLUMN category ENUM('handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic') DEFAULT 'rifle'");
            }
            // Note: For SQLite, the CHECK constraint will be enforced on new inserts
            // Existing data migration is handled above
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if (Schema::hasTable('firearm_calibres')) {
            // Revert ignition=rimfire back to category=rimfire
            DB::table('firearm_calibres')
                ->where('ignition', 'rimfire')
                ->update(['category' => 'rimfire']);

            // Drop ignition column
            if (Schema::hasColumn('firearm_calibres', 'ignition')) {
                Schema::table('firearm_calibres', function (Blueprint $table) {
                    $table->dropColumn('ignition');
                });
            }

            // Restore rimfire to category enum
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE firearm_calibres MODIFY COLUMN category ENUM('rimfire', 'handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic') DEFAULT 'rifle'");
            }
        }
    }
};
