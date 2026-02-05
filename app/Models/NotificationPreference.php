<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'ntfy_topic',
        'ntfy_enabled',
        'working_hours_start',
        'working_hours_end',
        'working_days',
        'respect_working_hours',
        'notify_new_member',
        'notify_payment_received',
        'notify_document_uploaded',
        'notify_document_rejected',
        'notify_membership_expiring',
        'notify_activity_submitted',
        'notify_knowledge_test_completed',
        'notify_endorsement_request',
        'notify_system_errors',
        'notify_license_expiry',
        'license_expiry_intervals',
    ];

    protected function casts(): array
    {
        return [
            'working_days' => 'array',
            'ntfy_enabled' => 'boolean',
            'respect_working_hours' => 'boolean',
            'notify_new_member' => 'boolean',
            'notify_payment_received' => 'boolean',
            'notify_document_uploaded' => 'boolean',
            'notify_document_rejected' => 'boolean',
            'notify_membership_expiring' => 'boolean',
            'notify_activity_submitted' => 'boolean',
            'notify_knowledge_test_completed' => 'boolean',
            'notify_system_errors' => 'boolean',
            'notify_license_expiry' => 'boolean',
            'license_expiry_intervals' => 'array',
        ];
    }

    /**
     * Get working_days with default value if null.
     */
    public function getWorkingDaysAttribute($value): array
    {
        return $value ?? [1, 2, 3, 4, 5]; // Mon-Fri default
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if currently within working hours.
     */
    public function isWithinWorkingHours(): bool
    {
        if (!$this->respect_working_hours) {
            return true;
        }

        $now = Carbon::now();
        $currentDay = $now->dayOfWeekIso; // 1 = Monday, 7 = Sunday

        // Check if today is a working day
        if (!in_array($currentDay, $this->working_days ?? [1, 2, 3, 4, 5])) {
            return false;
        }

        // Check time
        $currentTime = $now->format('H:i');
        $start = $this->working_hours_start ?? '08:00';
        $end = $this->working_hours_end ?? '17:00';

        return $currentTime >= $start && $currentTime <= $end;
    }

    /**
     * Get the next working hours start time.
     */
    public function getNextWorkingHoursStart(): Carbon
    {
        $now = Carbon::now();
        $workingDays = $this->working_days ?? [1, 2, 3, 4, 5];
        $startTime = $this->working_hours_start ?? '08:00';

        // Start from today
        $checkDate = $now->copy();

        // If we're before start time today and today is a working day, return today's start
        if (in_array($checkDate->dayOfWeekIso, $workingDays)) {
            $todayStart = $checkDate->copy()->setTimeFromTimeString($startTime);
            if ($now->lt($todayStart)) {
                return $todayStart;
            }
        }

        // Find next working day
        for ($i = 1; $i <= 7; $i++) {
            $checkDate->addDay();
            if (in_array($checkDate->dayOfWeekIso, $workingDays)) {
                return $checkDate->setTimeFromTimeString($startTime);
            }
        }

        // Fallback (shouldn't happen)
        return $now->addDay()->setTimeFromTimeString($startTime);
    }

    /**
     * Check if user wants a specific notification type.
     */
    public function wantsNotification(string $type): bool
    {
        $attribute = 'notify_' . $type;
        return $this->{$attribute} ?? false;
    }

    /**
     * Get notification types as array for display.
     */
    public static function getNotificationTypes(): array
    {
        return [
            'new_member' => 'New Member Registration',
            'payment_received' => 'Payment Received',
            'document_uploaded' => 'Document Uploaded',
            'document_rejected' => 'Document Rejected',
            'membership_expiring' => 'Membership Expiring',
            'activity_submitted' => 'Activity Submitted',
            'knowledge_test_completed' => 'Knowledge Test Completed',
            'endorsement_request' => 'Endorsement Request Submitted',
            'system_errors' => 'System Errors (Developer Only)',
        ];
    }
}
