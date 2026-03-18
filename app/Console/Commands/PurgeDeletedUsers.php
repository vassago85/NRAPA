<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PurgeDeletedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:purge-deleted 
                            {--force : Skip confirmation prompt}
                            {--days= : Only purge users deleted more than X days ago}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete all soft-deleted users from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = User::onlyTrashed();

        // Filter by days if specified
        if ($days = $this->option('days')) {
            $query->where('deleted_at', '<', now()->subDays((int) $days));
            $this->info("Finding users deleted more than {$days} days ago...");
        }

        $deletedUsers = $query->get();
        $count = $deletedUsers->count();

        if ($count === 0) {
            $this->info('No soft-deleted users found.');

            return Command::SUCCESS;
        }

        // Show list of users to be purged
        $this->info("Found {$count} soft-deleted user(s):");
        $this->newLine();

        $tableData = $deletedUsers->map(function ($user) {
            return [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Deleted At' => $user->deleted_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $this->table(['ID', 'Name', 'Email', 'Deleted At'], $tableData);
        $this->newLine();

        // Confirm unless --force is used
        if (! $this->option('force')) {
            if (! $this->confirm('Are you sure you want to PERMANENTLY delete these users? This cannot be undone.')) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        // Purge the users
        $this->info('Purging users...');
        $purged = 0;

        foreach ($deletedUsers as $user) {
            try {
                // Force delete related records - wrap each in try-catch to handle missing tables
                $this->safeDelete(fn () => $user->memberships()->forceDelete());
                $this->safeDelete(fn () => $user->certificates()->forceDelete());
                $this->safeDelete(fn () => $user->knowledgeTestAttempts()->forceDelete());
                $this->safeDelete(fn () => $user->securityQuestions()->forceDelete());
                $this->safeDelete(fn () => $user->documents()->forceDelete());
                $this->safeDelete(fn () => $user->deletionRequests()->forceDelete());
                $this->safeDelete(fn () => $user->shootingActivities()->forceDelete());
                $this->safeDelete(fn () => $user->dedicatedStatusApplications()->forceDelete());
                $this->safeDelete(fn () => $user->firearmMotivationRequests()->forceDelete());
                $this->safeDelete(fn () => $user->firearms()->forceDelete());
                $this->safeDelete(fn () => $user->loadData()->forceDelete());
                $this->safeDelete(fn () => $user->payments()->forceDelete());
                $this->safeDelete(fn () => $user->termsAcceptances()->forceDelete());
                $this->safeDelete(fn () => $user->statusHistory()->forceDelete());
                $this->safeDelete(fn () => $user->accountResetLogs()->forceDelete());
                $this->safeDelete(fn () => $user->notificationPreference()?->forceDelete());

                // Delete endorsement requests and their children (firearm, components, documents)
                $this->safeDelete(function () use ($user) {
                    $endorsementIds = \App\Models\EndorsementRequest::withTrashed()->where('user_id', $user->id)->pluck('id');
                    if ($endorsementIds->isNotEmpty()) {
                        \App\Models\EndorsementDocument::whereIn('endorsement_request_id', $endorsementIds)->forceDelete();
                        \App\Models\EndorsementFirearm::whereIn('endorsement_request_id', $endorsementIds)->forceDelete();
                        \App\Models\EndorsementComponent::whereIn('endorsement_request_id', $endorsementIds)->forceDelete();
                        \App\Models\EndorsementRequest::withTrashed()->where('user_id', $user->id)->forceDelete();
                    }
                });

                // Delete calibre requests
                $this->safeDelete(fn () => \App\Models\CalibreRequest::where('user_id', $user->id)->forceDelete());

                // Force delete the user
                $user->forceDelete();
                $purged++;

                $this->line("  Purged: {$user->name} ({$user->email})");
            } catch (\Exception $e) {
                $this->error("  Failed to purge {$user->email}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Successfully purged {$purged} of {$count} user(s).");

        return Command::SUCCESS;
    }

    /**
     * Safely execute a delete operation, ignoring errors for missing tables.
     */
    protected function safeDelete(callable $callback): void
    {
        try {
            $callback();
        } catch (\Exception $e) {
            // Ignore errors for missing tables or other issues
        }
    }
}
