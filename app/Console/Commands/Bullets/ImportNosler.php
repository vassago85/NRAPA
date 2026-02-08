<?php

namespace App\Console\Commands\Bullets;

class ImportNosler extends ImportBulletsCommand
{
    protected $signature = 'bullets:import-nosler
                            {--catalog-pdf= : URL to current catalog PDF}
                            {--dry-run : Parse without saving}';

    protected $description = 'Import Nosler bullets from nosler.com or catalog PDF';

    public function handle(): int
    {
        $pdfUrl = $this->option('catalog-pdf');

        if ($pdfUrl) {
            $this->info("PDF import from: {$pdfUrl}");
            $this->warn('PDF parsing requires additional tooling. Use CSV import for structured data from PDF content.');
        } else {
            $this->info('Nosler import - stub implementation.');
            $this->warn('Use CSV import for manual data entry, or --catalog-pdf for PDF source.');
        }

        $this->printSummary();
        return self::SUCCESS;
    }
}
