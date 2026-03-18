<?php

namespace App\Console\Commands\Bullets;

class ImportLapua extends ImportBulletsCommand
{
    protected $signature = 'bullets:import-lapua {--dry-run : Parse without saving}';

    protected $description = 'Import Lapua bullets from lapua.com';

    public function handle(): int
    {
        $this->info('Lapua import - stub implementation.');
        $this->warn('Lapua product pages should be crawled for Scenar/Naturalis lines.');
        $this->warn('Use CSV import for manual data entry.');
        $this->printSummary();

        return self::SUCCESS;
    }
}
