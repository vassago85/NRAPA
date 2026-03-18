<?php

namespace App\Notifications;

use App\Mail\LicenseExpiry;
use App\Models\UserFirearm;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
    public function toMail(object $notifiable): LicenseExpiry
    {
        return (new LicenseExpiry(
            user: $notifiable,
            firearm: $this->firearm,
            daysUntilExpiry: $this->daysUntilExpiry,
        ))->to($notifiable->email);
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
}
