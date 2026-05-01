<?php

namespace App\Console\Commands;

use App\Mail\MembershipExpiry;
use App\Models\Membership;
use App\Models\MembershipRenewalReminder;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send renewal reminders to members for expiring/expired memberships.
 *
 * Cadence buckets (per membership, idempotent):
 *   - thirty_days: expires in 8..30 days
 *   - seven_days:  expires in 1..7 days
 *   - expired:     already past expires_at, but still inside the configured grace period
 *
 * "Compress on first run": if a member is already inside a more urgent bucket and
 * we never sent an earlier one, we only send the most urgent applicable bucket so
 * imported members aren't blasted with a 30-day notice the day after they're imported
 * already at 5 days out.
 *
 * Idempotency is enforced by `membership_renewal_reminders (membership_id, kind)`
 * so re-runs the same day (or repeated runs after a renewal cycle creates a *new*
 * Membership row) behave correctly.
 */
class SendMembershipExpiryNotifications extends Command
{
    protected $signature = 'nrapa:send-membership-expiry-notifications
                            {--dry-run : Don\'t send mail or write reminder rows; just report what would happen}
                            {--throttle=2 : Seconds to stagger between queued sends, to keep Mailgun happy on bulk runs}';

    protected $description = 'Email members whose membership is expiring soon or has just expired (within the grace period).';

    /**
     * Counter used to compute per-message delay so successive queued mails are
     * staggered (avoids hammering Mailgun on first run after a bulk import).
     */
    protected int $sendIndex = 0;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $throttleSeconds = max(0, (int) $this->option('throttle'));

        if ($dryRun) {
            $this->warn('[dry-run] No emails will be sent and no reminder rows will be written.');
        }

        if ($throttleSeconds > 0) {
            $this->line("Throttle: {$throttleSeconds}s between sends.");
        }

        $today = now()->startOfDay();
        $graceDays = Membership::renewalGracePeriodDays();
        $graceFloor = $today->copy()->subDays($graceDays);

        // Renewable memberships only:
        //   - non-lifetime type
        //   - status active or expired (not pending/suspended/revoked)
        //   - has expires_at
        //   - within renewal+grace window: expires_at >= today - graceDays AND expires_at <= today + 30 days
        $memberships = Membership::query()
            ->whereNotNull('expires_at')
            ->whereIn('status', ['active', 'expired'])
            ->where('expires_at', '>=', $graceFloor)
            ->where('expires_at', '<=', $today->copy()->addDays(30)->endOfDay())
            ->whereHas('type', function ($q) {
                $q->where('requires_renewal', true)
                    ->where(function ($q2) {
                        $q2->whereNull('duration_type')->orWhere('duration_type', '!=', 'lifetime');
                    })
                    ->where(function ($q2) {
                        $q2->whereNull('expiry_rule')->orWhere('expiry_rule', '!=', 'none');
                    });
            })
            ->with([
                'user.notificationPreference',
                'type',
                'renewalReminders',
            ])
            ->get();

        $this->info("Considering {$memberships->count()} renewable memberships within window.");

        $sent = 0;
        $skipped = 0;

        foreach ($memberships as $membership) {
            $result = $this->processMembership($membership, $today, $dryRun, $throttleSeconds);

            if ($result === 'sent') {
                $sent++;
            } else {
                $skipped++;
            }
        }

        $this->info(sprintf(
            'Done. %s reminders %s; %d skipped.',
            $sent,
            $dryRun ? 'would-be sent' : 'sent',
            $skipped
        ));

        return Command::SUCCESS;
    }

    /**
     * Decide what (if anything) to send for one membership.
     */
    protected function processMembership(Membership $membership, CarbonInterface $today, bool $dryRun, int $throttleSeconds): string
    {
        $user = $membership->user;

        if (! $user) {
            return 'skipped';
        }

        if (! $user->email || $user->hasPlaceholderEmail()) {
            // Phone-only / placeholder import — admins can chase manually.
            return 'skipped';
        }

        $prefs = $user->notificationPreference;
        if ($prefs && $prefs->notify_membership_expiry === false) {
            return 'skipped';
        }

        $kind = $this->bucketFor($membership, $today);

        if ($kind === null) {
            return 'skipped';
        }

        // Compression: when entering a more urgent bucket we want to send that bucket
        // even if earlier buckets were never sent. We don't want to also send the
        // earlier bucket retroactively. The unique-per-kind log + only-fire-current-bucket
        // logic handles this naturally — `bucketFor` returns the *current* bucket only.
        $alreadySent = $membership->renewalReminders
            ->where('kind', $kind)
            ->isNotEmpty();

        if ($alreadySent) {
            return 'skipped';
        }

        // Stagger successive queued sends so we don't burst-dispatch hundreds of jobs
        // at Mailgun the moment the worker drains the queue.
        $delaySeconds = $throttleSeconds * $this->sendIndex;
        $delayLabel = $delaySeconds > 0 ? sprintf(' (+%ds)', $delaySeconds) : '';

        $this->line(sprintf(
            '  - %s [%s]  %s  expires %s  -> %s%s',
            $membership->membership_number ?? "ID#{$membership->id}",
            $kind,
            $user->email,
            $membership->expires_at->format('Y-m-d'),
            $dryRun ? '[would send]' : 'queueing',
            $delayLabel
        ));

        if ($dryRun) {
            $this->sendIndex++;

            return 'sent';
        }

        try {
            $mail = new MembershipExpiry($user, $membership, $kind);

            if ($delaySeconds > 0) {
                Mail::to($user->email)->later(now()->addSeconds($delaySeconds), $mail);
            } else {
                Mail::to($user->email)->send($mail);
            }

            MembershipRenewalReminder::create([
                'membership_id' => $membership->id,
                'kind' => $kind,
                'sent_at' => now(),
            ]);

            Log::info('Membership expiry notification queued', [
                'user_id' => $user->id,
                'membership_id' => $membership->id,
                'kind' => $kind,
                'expires_at' => $membership->expires_at->toDateString(),
                'delay_seconds' => $delaySeconds,
            ]);

            $this->sendIndex++;

            return 'sent';
        } catch (\Throwable $e) {
            Log::error('Failed to send membership expiry notification', [
                'user_id' => $user->id,
                'membership_id' => $membership->id,
                'kind' => $kind,
                'error' => $e->getMessage(),
            ]);
            $this->error("    Failed: {$e->getMessage()}");

            return 'skipped';
        }
    }

    /**
     * Pick the relevant reminder bucket for a membership today, or null if none applies.
     */
    protected function bucketFor(Membership $membership, CarbonInterface $today): ?string
    {
        $expiresAt = $membership->expires_at?->copy()?->startOfDay();
        if (! $expiresAt) {
            return null;
        }

        $days = (int) $today->diffInDays($expiresAt, false);

        if ($days < 0) {
            // Already past expiry — only fire 'expired' once and only inside grace window.
            $graceDays = Membership::renewalGracePeriodDays();
            if (abs($days) > $graceDays) {
                return null;
            }

            return MembershipRenewalReminder::KIND_EXPIRED;
        }

        if ($days <= 7) {
            return MembershipRenewalReminder::KIND_SEVEN_DAYS;
        }

        if ($days <= 30) {
            return MembershipRenewalReminder::KIND_THIRTY_DAYS;
        }

        return null;
    }
}
