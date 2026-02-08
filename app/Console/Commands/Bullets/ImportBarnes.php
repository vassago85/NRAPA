<?php

namespace App\Console\Commands\Bullets;

use App\Services\Bullets\Parsers\BarnesParser;

class ImportBarnes extends ImportBulletsCommand
{
    protected $signature = 'bullets:import-barnes
                            {--page=1 : Starting page number}
                            {--dry-run : Parse without saving}';

    protected $description = 'Import Barnes bullets from barnesbullets.com';

    public function handle(): int
    {
        $parser = new BarnesParser();
        $this->info('Importing Barnes bullets...');
        $this->info('Fetching product listing from barnesbullets.com/bullets/ ...');

        $html = $this->fetchUrl('https://barnesbullets.com/bullets/');

        if (!$html) {
            $this->error('Failed to fetch Barnes bullet listing.');
            return self::FAILURE;
        }

        $bullets = $parser->parse($html, 'https://barnesbullets.com/bullets/');
        $this->info('Parsed ' . count($bullets) . ' products.');

        if (empty($bullets)) {
            $this->warn('No bullets parsed. The site may require JavaScript rendering.');
            $this->warn('Use the admin CSV import for manual entry, or the BulletSeeder for known products.');
        }

        if (!$this->option('dry-run')) {
            $bar = $this->output->createProgressBar(count($bullets));
            foreach ($bullets as $data) {
                $this->importBullet($data, 'product_page');
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        $this->printSummary();
        return self::SUCCESS;
    }
}
