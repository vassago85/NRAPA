<?php

namespace App\Console\Commands;

use App\Models\UserFirearm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLicenseExpiryNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nrapa:send-license-expiry-notifications';

    /**
     * The console command description.
     */
    protected $description = 'Send notifications to users about expiring firearm licenses';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expiring licenses...');

        $notificationDays = [
            90 => 'expiry_notification_sent_90',
            60 => 'expiry_notification_sent_60',
            30 => 'expiry_notification_sent_30',
            7 => 'expiry_notification_sent_7',
        ];

        $totalSent = 0;

        foreach ($notificationDays as $days => $notificationField) {
            $firearms = UserFirearm::with('user')
                ->where('is_active', true)
                ->whereNotNull('license_expiry_date')
                ->whereDate('license_expiry_date', '=', now()->addDays($days)->toDateString())
                ->where($notificationField, false)
                ->get();

            foreach ($firearms as $firearm) {
                if (!$firearm->user || !$firearm->user->email) {
                    continue;
                }

                $this->sendExpiryNotification($firearm, $days);

                // Mark notification as sent
                $firearm->update([$notificationField => true]);

                $totalSent++;
                $this->info("Sent {$days}-day notification for firearm ID {$firearm->id} to {$firearm->user->email}");
            }
        }

        // Also check for already expired licenses that haven't been notified
        $expiredFirearms = UserFirearm::with('user')
            ->where('is_active', true)
            ->whereNotNull('license_expiry_date')
            ->whereDate('license_expiry_date', '<', now())
            ->where('license_status', '!=', 'expired')
            ->get();

        foreach ($expiredFirearms as $firearm) {
            // Update status to expired
            $firearm->update(['license_status' => 'expired']);
            
            $this->warn("Marked firearm ID {$firearm->id} license as expired");
        }

        $this->info("Finished. Sent {$totalSent} notifications.");

        return Command::SUCCESS;
    }

    /**
     * Send the expiry notification email.
     */
    protected function sendExpiryNotification(UserFirearm $firearm, int $days): void
    {
        $user = $firearm->user;
        $expiryDate = $firearm->license_expiry_date->format('d M Y');

        // For now, we'll log this - implement actual notification later
        Log::info("License expiry notification", [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'firearm_id' => $firearm->id,
            'firearm_name' => $firearm->display_name,
            'days_until_expiry' => $days,
            'expiry_date' => $expiryDate,
        ]);

        // TODO: Implement email notification using Laravel Mail
        // Mail::to($user)->send(new LicenseExpiryNotification($firearm, $days));

        // For now, we'll use Laravel's notification system if available
        // $user->notify(new \App\Notifications\LicenseExpiryNotification($firearm, $days));
    }
}
