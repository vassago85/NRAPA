<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SetFarNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nrapa:set-far-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set FAR accreditation numbers in system settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Setting FAR accreditation numbers...');

        try {
            // Set FAR Sport Shooting Number
            SystemSetting::set(
                'far_sport_number',
                '1300122',
                'string',
                'accreditation',
                'SAPS/FAR Accreditation Number for Sport Shooting'
            );
            Cache::forget('system_setting.far_sport_number');
            $this->info('  ✓ FAR Sport Shooting: 1300122');

            // Set FAR Hunting Number
            SystemSetting::set(
                'far_hunting_number',
                '1300127',
                'string',
                'accreditation',
                'SAPS/FAR Accreditation Number for Hunting'
            );
            Cache::forget('system_setting.far_hunting_number');
            $this->info('  ✓ FAR Hunting: 1300127');

            $this->newLine();
            $this->info('FAR accreditation numbers set successfully!');
            $this->info('Please refresh your browser to see the updated numbers on documents.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to set FAR numbers: '.$e->getMessage());
            $this->error('Make sure Laragon services are stopped and try again.');

            return Command::FAILURE;
        }
    }
}
