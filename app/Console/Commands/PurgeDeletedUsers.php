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
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to PERMANENTLY delete these users? This cannot be undone.')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }
        
        // Purge the users
        $this->info('Purging users...');
        $purged = 0;
        
        foreach ($deletedUsers as $user) {
            try {
                // Force delete related records that might cause foreign key issues
                $user->memberships()->forceDelete();
                $user->certificates()->forceDelete();
                $user->knowledgeTestAttempts()->forceDelete();
                $user->securityQuestions()->forceDelete();
                $user->documents()->forceDelete();
                $user->deletionRequests()->forceDelete();
                $user->shootingActivities()->forceDelete();
                $user->dedicatedStatusApplications()->forceDelete();
                $user->firearmMotivationRequests()->forceDelete();
                $user->firearms()->forceDelete();
                $user->loadData()->forceDelete();
                $user->payments()->forceDelete();
                $user->termsAcceptances()->forceDelete();
                $user->statusHistory()->forceDelete();
                $user->accountResetLogs()->forceDelete();
                
                // Delete endorsement requests (no direct relationship)
                \App\Models\EndorsementRequest::where('user_id', $user->id)->forceDelete();
                
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
}
