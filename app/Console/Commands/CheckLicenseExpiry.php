<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserFirearm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckLicenseExpiry extends Command
{
    protected $signature = 'nrapa:send-license-expiry-notifications';

    protected $description = 'Check for expiring firearm licenses and send notifications';

    // Default intervals in months
    protected array $defaultIntervals = [18, 12, 6];

    public function handle(): int
    {
        $this->info('Checking for expiring firearm licenses...');

        $notificationsSent = 0;

        // Get all users with active memberships who have firearms
        $users = User::whereHas('activeMembership')
            ->whereHas('firearms', function ($query) {
                $query->active()
                    ->whereNotNull('license_expiry_date')
                    ->where('license_expiry_date', '>', now()); // Not already expired
            })
            ->with(['notificationPreferences', 'firearms' => function ($query) {
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
                        $this->sendNotification($user, $firearm, $months);
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
        $prefs = $user->notificationPreferences;

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
    protected function sendNotification(User $user, UserFirearm $firearm, int $months): void
    {
        $this->line("  - Sending {$months}-month expiry notice to {$user->email} for {$firearm->display_name}");

        try {
            // Send email notification
            Mail::send('emails.license-expiry', [
                'user' => $user,
                'firearm' => $firearm,
                'months' => $months,
                'expiryDate' => $firearm->license_expiry_date->format('d F Y'),
            ], function ($message) use ($user, $firearm, $months) {
                $message->to($user->email)
                    ->subject("License Expiry Notice - {$firearm->display_name} ({$months} months)");
            });

            // Log the notification
            Log::info('License expiry notification sent', [
                'user_id' => $user->id,
                'firearm_id' => $firearm->id,
                'months_until_expiry' => $months,
                'expiry_date' => $firearm->license_expiry_date->toDateString(),
            ]);
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
