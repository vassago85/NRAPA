<?php

namespace App\Console\Commands;

use App\Services\NtfyService;
use Illuminate\Console\Command;

class ProcessQueuedNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process queued notifications that are ready to be sent';

    /**
     * Execute the console command.
     */
    public function handle(NtfyService $ntfyService): int
    {
        $sent = $ntfyService->processQueue();

        $this->info("Processed {$sent} queued notifications.");

        return Command::SUCCESS;
    }
}
