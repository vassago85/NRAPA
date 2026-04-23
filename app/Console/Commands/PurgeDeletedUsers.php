<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeDeletedUsers extends Command
{
    protected $signature = 'users:purge-deleted 
                            {--force : Skip confirmation prompt}';

    protected $description = 'Permanently remove any legacy soft-deleted user rows from the database';

    public function handle(): int
    {
        $deletedUsers = DB::table('users')->whereNotNull('deleted_at')->get();
        $count = $deletedUsers->count();

        if ($count === 0) {
            $this->info('No legacy soft-deleted users found.');

            return Command::SUCCESS;
        }

        $this->info("Found {$count} legacy soft-deleted user(s):");
        $this->newLine();

        $tableData = $deletedUsers->map(fn ($user) => [
            'ID' => $user->id,
            'Name' => $user->name,
            'Email' => $user->email,
            'Deleted At' => $user->deleted_at,
        ])->toArray();

        $this->table(['ID', 'Name', 'Email', 'Deleted At'], $tableData);
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('Permanently remove these rows? This cannot be undone.')) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        $purged = 0;

        foreach ($deletedUsers as $user) {
            try {
                if (! empty($user->member_number)) {
                    \App\Models\RetiredMemberNumber::retire(
                        memberNumber: (int) $user->member_number,
                        userId: $user->id,
                        name: $user->name,
                        email: $user->email,
                        reason: 'legacy soft-deleted user purge',
                    );
                }
                DB::table('memberships')->where('user_id', $user->id)->delete();
                DB::table('certificates')->where('user_id', $user->id)->delete();
                DB::table('member_documents')->where('user_id', $user->id)->delete();
                DB::table('login_logs')->where('user_id', $user->id)->delete();
                DB::table('user_deletion_requests')->where('user_id', $user->id)->delete();
                DB::table('users')->where('id', $user->id)->delete();
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
