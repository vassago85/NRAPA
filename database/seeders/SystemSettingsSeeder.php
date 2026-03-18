<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use raw SQL for SQLite compatibility and to avoid locking issues
        $now = now()->toDateTimeString();

        // FAR Sport Shooting Number
        try {
            // Try raw SQL first (works better with SQLite)
            DB::statement("
                INSERT INTO system_settings (key, value, type, \"group\", description, created_at, updated_at)
                VALUES ('far_sport_number', '1300122', 'string', 'accreditation', 'SAPS/FAR Accreditation Number for Sport Shooting', '{$now}', '{$now}')
                ON CONFLICT(key) DO UPDATE SET 
                    value = '1300122',
                    updated_at = '{$now}'
            ");
            Cache::forget('system_setting.far_sport_number');
            $this->command->info('  ✓ FAR Sport Shooting: 1300122');
        } catch (\Exception $e) {
            // Fallback to Eloquent if raw SQL fails
            try {
                SystemSetting::set(
                    'far_sport_number',
                    '1300122',
                    'string',
                    'accreditation',
                    'SAPS/FAR Accreditation Number for Sport Shooting'
                );
                $this->command->info('  ✓ FAR Sport Shooting: 1300122');
            } catch (\Exception $e2) {
                $this->command->warn('  ✗ Failed to set far_sport_number: '.$e2->getMessage());
            }
        }

        // FAR Hunting Number
        try {
            DB::statement("
                INSERT INTO system_settings (key, value, type, \"group\", description, created_at, updated_at)
                VALUES ('far_hunting_number', '1300127', 'string', 'accreditation', 'SAPS/FAR Accreditation Number for Hunting', '{$now}', '{$now}')
                ON CONFLICT(key) DO UPDATE SET 
                    value = '1300127',
                    updated_at = '{$now}'
            ");
            Cache::forget('system_setting.far_hunting_number');
            $this->command->info('  ✓ FAR Hunting: 1300127');
        } catch (\Exception $e) {
            // Fallback to Eloquent if raw SQL fails
            try {
                SystemSetting::set(
                    'far_hunting_number',
                    '1300127',
                    'string',
                    'accreditation',
                    'SAPS/FAR Accreditation Number for Hunting'
                );
                $this->command->info('  ✓ FAR Hunting: 1300127');
            } catch (\Exception $e2) {
                $this->command->warn('  ✗ Failed to set far_hunting_number: '.$e2->getMessage());
            }
        }

        $this->command->info('FAR accreditation numbers set successfully!');
    }
}
