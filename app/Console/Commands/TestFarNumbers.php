<?php

namespace App\Console\Commands;

use App\Helpers\DocumentDataHelper;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestFarNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nrapa:test-far-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test FAR number retrieval and display';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing FAR Number Retrieval...');
        $this->newLine();

        // Check if system_settings table exists
        if (!Schema::hasTable('system_settings')) {
            $this->error('❌ system_settings table does not exist. Run migrations first.');
            return Command::FAILURE;
        }
        $this->info('✓ system_settings table exists');

        // Check if settings exist in database
        $sportSetting = DB::table('system_settings')->where('key', 'far_sport_number')->first();
        $huntingSetting = DB::table('system_settings')->where('key', 'far_hunting_number')->first();

        if (!$sportSetting) {
            $this->warn('⚠ far_sport_number not found in database');
            $this->info('  Attempting to set it...');
            try {
                SystemSetting::set('far_sport_number', '1300122', 'string', 'accreditation', 'SAPS/FAR Accreditation Number for Sport Shooting');
                $this->info('  ✓ far_sport_number set to: 1300122');
            } catch (\Exception $e) {
                $this->error('  ✗ Failed: ' . $e->getMessage());
            }
        } else {
            $this->info('✓ far_sport_number found in database');
            $this->info('  Value: ' . $sportSetting->value);
            $this->info('  Type: ' . $sportSetting->type);
        }

        if (!$huntingSetting) {
            $this->warn('⚠ far_hunting_number not found in database');
            $this->info('  Attempting to set it...');
            try {
                SystemSetting::set('far_hunting_number', '1300127', 'string', 'accreditation', 'SAPS/FAR Accreditation Number for Hunting');
                $this->info('  ✓ far_hunting_number set to: 1300127');
            } catch (\Exception $e) {
                $this->error('  ✗ Failed: ' . $e->getMessage());
            }
        } else {
            $this->info('✓ far_hunting_number found in database');
            $this->info('  Value: ' . $huntingSetting->value);
            $this->info('  Type: ' . $huntingSetting->type);
        }

        $this->newLine();
        $this->info('Testing DocumentDataHelper::getFarNumbers()...');
        
        try {
            $farNumbers = DocumentDataHelper::getFarNumbers();
            $this->info('✓ Method executed successfully');
            $this->info('  Sport: ' . ($farNumbers['sport'] ?? 'NULL'));
            $this->info('  Hunting: ' . ($farNumbers['hunting'] ?? 'NULL'));
            
            if ($farNumbers['sport'] === 'N/A' || $farNumbers['hunting'] === 'N/A') {
                $this->warn('⚠ FAR numbers are showing as "N/A" - settings may not be in database');
            } elseif ($farNumbers['sport'] === '1300122' && $farNumbers['hunting'] === '1300127') {
                $this->info('✓ FAR numbers are correct!');
            } else {
                $this->warn('⚠ FAR numbers are set but may have incorrect values');
            }
        } catch (\Exception $e) {
            $this->error('✗ Error retrieving FAR numbers: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Testing SystemSetting::get() directly...');
        
        try {
            $sportDirect = SystemSetting::get('far_sport_number', 'N/A');
            $huntingDirect = SystemSetting::get('far_hunting_number', 'N/A');
            
            $this->info('  far_sport_number: ' . $sportDirect);
            $this->info('  far_hunting_number: ' . $huntingDirect);
        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('Clearing cache...');
        try {
            \Illuminate\Support\Facades\Cache::forget('system_setting.far_sport_number');
            \Illuminate\Support\Facades\Cache::forget('system_setting.far_hunting_number');
            $this->info('✓ Cache cleared');
        } catch (\Exception $e) {
            $this->warn('⚠ Cache clear failed: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('Test complete!');
        
        return Command::SUCCESS;
    }
}
