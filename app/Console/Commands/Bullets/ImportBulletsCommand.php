<?php

namespace App\Console\Commands\Bullets;

use App\Services\Bullets\BulletUpsertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

abstract class ImportBulletsCommand extends Command
{
    protected BulletUpsertService $upsertService;
    protected int $importedCount = 0;
    protected int $skippedCount = 0;
    protected int $errorCount = 0;

    public function __construct(BulletUpsertService $upsertService)
    {
        parent::__construct();
        $this->upsertService = $upsertService;
    }

    /**
     * Fetch URL content with retries.
     */
    protected function fetchUrl(string $url, int $retries = 3): ?string
    {
        for ($i = 0; $i < $retries; $i++) {
            try {
                $response = Http::timeout(30)
                    ->withUserAgent('NRAPA-BulletDB/1.0')
                    ->get($url);

                if ($response->successful()) {
                    return $response->body();
                }

                $this->warn("HTTP {$response->status()} for {$url}");
            } catch (\Exception $e) {
                $this->warn("Error fetching {$url}: {$e->getMessage()}");
            }

            if ($i < $retries - 1) {
                sleep(2);
            }
        }

        return null;
    }

    /**
     * Import a single bullet from parsed data.
     */
    protected function importBullet(array $data, string $sourceType = 'product_page'): void
    {
        try {
            $bullet = $this->upsertService->upsert($data);
            $this->upsertService->attachSource($bullet, $sourceType, $data['source_url']);
            $this->importedCount++;
        } catch (\Exception $e) {
            $this->error("Failed: {$data['bullet_label'] ?? 'unknown'} - {$e->getMessage()}");
            $this->errorCount++;
        }
    }

    /**
     * Print summary.
     */
    protected function printSummary(): void
    {
        $this->newLine();
        $this->info("Import complete:");
        $this->line("  Imported/updated: {$this->importedCount}");
        $this->line("  Skipped: {$this->skippedCount}");
        $this->line("  Errors: {$this->errorCount}");
    }
}
