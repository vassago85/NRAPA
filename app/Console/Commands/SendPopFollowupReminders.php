<?php

namespace App\Console\Commands;

use App\Mail\PopFollowupReminder;
use App\Models\Membership;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPopFollowupReminders extends Command
{
    protected $signature = 'nrapa:send-pop-followup-reminders';

    protected $description = 'Send follow-up reminders to members whose approval was revoked 7+ days ago and still have no proof of payment';

    public function handle(): int
    {
        $this->info('Checking for outstanding proof of payment follow-ups...');

        $memberships = Membership::with(['user', 'type'])
            ->where('status', 'applied')
            ->whereNotNull('approval_revoked_at')
            ->where('approval_revoked_at', '<=', now()->subDays(7))
            ->whereNull('proof_of_payment_path')
            ->whereNull('pop_reminder_sent_at')
            ->get();

        if ($memberships->isEmpty()) {
            $this->info('No follow-up reminders needed.');
            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($memberships as $membership) {
            if (! $membership->user?->email) {
                $this->warn("Skipping membership {$membership->membership_number} — no email address.");
                continue;
            }

            try {
                Mail::to($membership->user->email)->queue(
                    new PopFollowupReminder($membership)
                );

                $membership->update(['pop_reminder_sent_at' => now()]);

                $sent++;
                $this->line("  → Sent reminder to {$membership->user->email} ({$membership->membership_number})");
            } catch (\Exception $e) {
                $failed++;
                Log::warning('Failed to send POP follow-up reminder', [
                    'membership_id' => $membership->id,
                    'email' => $membership->user->email,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  × Failed for {$membership->user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Sent: {$sent}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
