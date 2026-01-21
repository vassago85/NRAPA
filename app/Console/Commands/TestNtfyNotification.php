<?php

namespace App\Console\Commands;

use App\Services\NtfyService;
use Illuminate\Console\Command;

class TestNtfyNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ntfy:test 
                            {topic? : The ntfy topic to send to (uses NTFY_DEVELOPER_TOPIC if not provided)}
                            {--message= : Custom message to send}
                            {--title= : Custom title}
                            {--priority=default : Priority (min, low, default, high, urgent)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification via ntfy.sh to verify configuration';

    /**
     * Execute the console command.
     */
    public function handle(NtfyService $ntfyService): int
    {
        $topic = $this->argument('topic') ?? config('services.ntfy.developer_topic');
        
        if (!$topic) {
            $this->error('No topic provided and NTFY_DEVELOPER_TOPIC is not set.');
            $this->info('');
            $this->info('Usage:');
            $this->info('  php artisan ntfy:test your-topic-name');
            $this->info('  php artisan ntfy:test --message="Custom message"');
            $this->info('');
            $this->info('Or set NTFY_DEVELOPER_TOPIC in your .env file.');
            return Command::FAILURE;
        }

        $title = $this->option('title') ?? 'NRAPA Test Notification';
        $message = $this->option('message') ?? 'This is a test notification from NRAPA at ' . now()->format('Y-m-d H:i:s');
        $priority = $this->option('priority');

        $this->info("Sending test notification...");
        $this->table(
            ['Setting', 'Value'],
            [
                ['URL', config('services.ntfy.url', 'https://ntfy.sh')],
                ['Topic', $topic],
                ['Title', $title],
                ['Message', $message],
                ['Priority', $priority],
            ]
        );

        $success = $ntfyService->send($topic, $title, $message, $priority, ['white_check_mark', 'test_tube']);

        if ($success) {
            $this->newLine();
            $this->info('✓ Notification sent successfully!');
            $this->newLine();
            $this->info("Check your ntfy app or visit: https://ntfy.sh/{$topic}");
            return Command::SUCCESS;
        } else {
            $this->newLine();
            $this->error('✗ Failed to send notification.');
            $this->info('Check the Laravel logs for more details.');
            return Command::FAILURE;
        }
    }
}
