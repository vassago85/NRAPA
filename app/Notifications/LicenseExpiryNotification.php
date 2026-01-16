<?php

namespace App\Notifications;

use App\Models\UserFirearm;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public UserFirearm $firearm,
        public int $daysUntilExpiry
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->getUrgencyLevel();
        $subject = $this->getSubject();

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},");

        if ($this->daysUntilExpiry <= 7) {
            $message->line("⚠️ **URGENT:** Your firearm license is expiring very soon!");
        } elseif ($this->daysUntilExpiry <= 30) {
            $message->line("⏰ Your firearm license is expiring soon.");
        } else {
            $message->line("This is a reminder about your upcoming firearm license expiry.");
        }

        $message
            ->line("**Firearm:** {$this->firearm->display_name}")
            ->line("**Make/Model:** {$this->firearm->make} {$this->firearm->model}")
            ->line("**License Number:** " . ($this->firearm->license_number ?? 'Not recorded'))
            ->line("**Expiry Date:** {$this->firearm->license_expiry_date->format('d M Y')}")
            ->line("**Days Remaining:** {$this->daysUntilExpiry} days");

        if ($this->daysUntilExpiry <= 30) {
            $message->line("")
                ->line("**Action Required:**")
                ->line("Please start your license renewal process immediately to avoid any legal issues or interruptions to your shooting activities.");
        }

        $message
            ->action('View in My Armoury', route('armoury.show', $this->firearm))
            ->line("You can update your license details and upload your renewed license document in your armoury.");

        if ($this->daysUntilExpiry <= 7) {
            $message->line("")
                ->line("**Important:** An expired firearm license may result in legal consequences. Please prioritize this renewal.");
        }

        $message->line("Thank you for being a responsible firearm owner and NRAPA member.");

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'license_expiry',
            'firearm_id' => $this->firearm->id,
            'firearm_uuid' => $this->firearm->uuid,
            'firearm_name' => $this->firearm->display_name,
            'license_number' => $this->firearm->license_number,
            'expiry_date' => $this->firearm->license_expiry_date->toISOString(),
            'days_until_expiry' => $this->daysUntilExpiry,
            'urgency' => $this->getUrgencyLevel(),
        ];
    }

    /**
     * Get the urgency level based on days until expiry.
     */
    protected function getUrgencyLevel(): string
    {
        return match (true) {
            $this->daysUntilExpiry <= 7 => 'critical',
            $this->daysUntilExpiry <= 30 => 'high',
            $this->daysUntilExpiry <= 60 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get the notification subject.
     */
    protected function getSubject(): string
    {
        return match (true) {
            $this->daysUntilExpiry <= 7 => "⚠️ URGENT: Firearm License Expires in {$this->daysUntilExpiry} Days",
            $this->daysUntilExpiry <= 30 => "⏰ Firearm License Expiring Soon - {$this->daysUntilExpiry} Days",
            default => "Reminder: Firearm License Expiring in {$this->daysUntilExpiry} Days",
        };
    }
}
