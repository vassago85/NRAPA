<?php

namespace App\Console\Commands;

use App\Mail\LicenseExpiry;
use App\Models\User;
use App\Models\UserFirearm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckLicenseExpiry extends Command
{
    protected $signature = 'nrapa:send-license-expiry-notifications
                            {--throttle=2 : Seconds to stagger between queued sends, to keep Mailgun happy on bulk runs}';

    protected $description = 'Check for expiring firearm licenses and send notifications';

    // Default intervals in months
    protected array $defaultIntervals = [18, 12, 6];

    /**
     * Counter used to compute per-message delay so successive queued mails are
     * staggered (avoids hammering Mailgun on first run).
     */
    protected int $sendIndex = 0;

    public function handle(): int
    {
        $this->info('Checking for expiring firearm licenses...');

        $throttleSeconds = max(0, (int) $this->option('throttle'));

        if ($throttleSeconds > 0) {
            $this->line("Throttle: {$throttleSeconds}s between sends.");
        }

        $notificationsSent = 0;

        // Get all users with active memberships who have firearms
        $users = User::whereHas('activeMembership')
            ->whereHas('firearms', function ($query) {
                $query->active()
                    ->whereNotNull('license_expiry_date')
                    ->where('license_expiry_date', '>', now()); // Not already expired
            })
            ->with(['notificationPreference', 'firearms' => function ($query) {
                $query->active()
                    ->whereNotNull('license_expiry_date')
                    ->where('license_expiry_date', '>', now());
            }])
            ->get();

        foreach ($users as $user) {
            $intervals = $this->getUserIntervals($user);

            if (empty($intervals)) {
                continue; // User has disabled license expiry notifications
            }

            foreach ($user->firearms as $firearm) {
                foreach ($intervals as $months) {
                    if ($firearm->shouldSendNotification($months)) {
                        $this->sendNotification($user, $firearm, $months, $throttleSeconds);
                        $firearm->markNotificationSent($months);
                        $notificationsSent++;
                    }
                }
            }
        }

        $this->info("License expiry check complete. {$notificationsSent} notifications sent.");

        return Command::SUCCESS;
    }

    /**
     * Get the notification intervals for a user (from preferences or defaults).
     */
    protected function getUserIntervals(User $user): array
    {
        $prefs = $user->notificationPreference;

        // Check if user has disabled license expiry notifications
        if ($prefs && ! $prefs->notify_license_expiry) {
            return [];
        }

        // Get custom intervals or use defaults
        if ($prefs && $prefs->license_expiry_intervals) {
            return $prefs->license_expiry_intervals;
        }

        return $this->defaultIntervals;
    }

    /**
     * Send a license expiry notification.
     */
    protected function sendNotification(User $user, UserFirearm $firearm, int $months, int $throttleSeconds = 0): void
    {
        if ($user->hasPlaceholderEmail()) {
            $this->line("  - Skipping {$months}-month notice for {$firearm->display_name}: user has placeholder email.");

            return;
        }

        $delaySeconds = $throttleSeconds * $this->sendIndex;
        $delayLabel = $delaySeconds > 0 ? sprintf(' (+%ds)', $delaySeconds) : '';

        $this->line("  - Queueing {$months}-month expiry notice to {$user->email} for {$firearm->display_name}{$delayLabel}");

        try {
            $daysUntilExpiry = max(0, (int) now()->startOfDay()->diffInDays($firearm->license_expiry_date, false));

            $mail = new LicenseExpiry($user, $firearm, $daysUntilExpiry);

            if ($delaySeconds > 0) {
                Mail::to($user->email)->later(now()->addSeconds($delaySeconds), $mail);
            } else {
                Mail::to($user->email)->send($mail);
            }

            Log::info('License expiry notification queued', [
                'user_id' => $user->id,
                'firearm_id' => $firearm->id,
                'months_until_expiry' => $months,
                'expiry_date' => $firearm->license_expiry_date->toDateString(),
                'delay_seconds' => $delaySeconds,
            ]);

            $this->sendIndex++;
        } catch (\Exception $e) {
            Log::error('Failed to send license expiry notification', [
                'user_id' => $user->id,
                'firearm_id' => $firearm->id,
                'error' => $e->getMessage(),
            ]);
            $this->error("  Failed to send notification: {$e->getMessage()}");
        }
    }
}
