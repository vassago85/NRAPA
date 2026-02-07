<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE user_firearms MODIFY COLUMN `action` ENUM(
                'semi_automatic',
                'automatic',
                'manual',
                'bolt_action',
                'pump_action',
                'lever_action',
                'other'
            ) NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE user_firearms MODIFY COLUMN `action` ENUM(
                'semi_automatic',
                'automatic',
                'manual',
                'other'
            ) NULL");
        }
    }
};
