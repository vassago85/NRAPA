<?php

namespace App\Console\Commands\Bullets;

class ImportSierra extends ImportBulletsCommand
{
    protected $signature = 'bullets:import-sierra {--dry-run : Parse without saving}';
    protected $description = 'Import Sierra bullets from sierrabullets.com';

    public function handle(): int
    {
        $this->info('Sierra import - stub implementation.');
        $this->warn('Sierra product pages may require JavaScript. Use CSV import for manual data entry.');
        $this->printSummary();
        return self::SUCCESS;
    }
}
