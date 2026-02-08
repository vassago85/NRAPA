<?php

namespace App\Console\Commands\Bullets;

use App\Services\Bullets\Parsers\HornadyParser;

class ImportHornady extends ImportBulletsCommand
{
    protected $signature = 'bullets:import-hornady
                            {--bc-only : Only import from BC table}
                            {--catalog : Import from catalog pages}
                            {--dry-run : Parse without saving}';

    protected $description = 'Import Hornady bullets from hornady.com';

    public function handle(): int
    {
        $parser = new HornadyParser();
        $this->info('Importing Hornady bullets...');

        if ($this->option('bc-only') || !$this->option('catalog')) {
            $this->importBcTable($parser);
        }

        if ($this->option('catalog')) {
            $this->importCatalog($parser);
        }

        $this->printSummary();
        return self::SUCCESS;
    }

    private function importBcTable(HornadyParser $parser): void
    {
        $this->info('Fetching BC table from hornady.com/bc ...');
        $html = $this->fetchUrl('https://www.hornady.com/bc');

        if (!$html) {
            $this->error('Failed to fetch BC table.');
            return;
        }

        $bullets = $parser->parseBcTable($html);
        $this->info('Parsed ' . count($bullets) . ' entries from BC table.');

        if ($this->option('dry-run')) {
            $this->table(
                ['Caliber', 'Weight', 'Line', 'G1', 'G7'],
                collect($bullets)->map(fn ($b) => [
                    $b['caliber_label'], $b['weight_gr'], $b['brand_line'],
                    $b['bc_g1'] ?? '-', $b['bc_g7'] ?? '-',
                ])->toArray()
            );
            return;
        }

        $bar = $this->output->createProgressBar(count($bullets));
        foreach ($bullets as $data) {
            $this->importBullet($data, 'bc_table');
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    private function importCatalog(HornadyParser $parser): void
    {
        $this->info('Catalog import not yet implemented. Use --bc-only or CSV import.');
        $this->warn('Catalog pages require JavaScript rendering; consider using the admin CSV import instead.');
    }
}
