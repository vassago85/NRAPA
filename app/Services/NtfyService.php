<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\QueuedNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NtfyService
{
    protected string $baseUrl;
    protected ?string $developerTopic;

    public function __construct()
    {
        $this->baseUrl = config('services.ntfy.url', 'https://ntfy.sh');
        $this->developerTopic = config('services.ntfy.developer_topic');
    }

    /**
     * Send notification to a specific topic.
     */
    public function send(string $topic, string $title, string $message, string $priority = 'default', array $tags = []): bool
    {
        try {
            $response = Http::withHeaders([
                'Title' => $title,
                'Priority' => $priority,
                'Tags' => implode(',', $tags),
            ])->post("{$this->baseUrl}/{$topic}", $message);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('NTFY send failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send notification to developer (site errors).
     */
    public function notifyDeveloper(string $title, string $message, string $priority = 'high'): bool
    {
        if (!$this->developerTopic) {
            return false;
        }

        return $this->send($this->developerTopic, $title, $message, $priority, ['warning', 'computer']);
    }

    /**
     * Notify user based on their preferences.
     */
    public function notifyUser(User $user, string $type, string $title, string $message, string $priority = 'default', array $data = []): bool
    {
        $prefs = $user->notificationPreference;

        // If no preferences or NTFY not enabled, skip
        if (!$prefs || !$prefs->ntfy_enabled || !$prefs->ntfy_topic) {
            return false;
        }

        // Check if user wants this notification type
        if (!$prefs->wantsNotification($type)) {
            return false;
        }

        // Check working hours
        if ($prefs->respect_working_hours && !$prefs->isWithinWorkingHours()) {
            // Queue for later
            $this->queueNotification($user, $type, $title, $message, $priority, $data, $prefs->getNextWorkingHoursStart());
            return true; // Queued successfully
        }

        // Send immediately
        $success = $this->send($prefs->ntfy_topic, $title, $message, $priority, $this->getTagsForType($type));

        if (!$success) {
            // Queue for retry
            $this->queueNotification($user, $type, $title, $message, $priority, $data);
        }

        return $success;
    }

    /**
     * Notify all admins/owners about an event.
     */
    public function notifyAdmins(string $type, string $title, string $message, string $priority = 'default', array $data = []): void
    {
        $users = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OWNER, User::ROLE_DEVELOPER])
            ->whereHas('notificationPreference', function ($q) use ($type) {
                $q->where('ntfy_enabled', true)
                    ->whereNotNull('ntfy_topic')
                    ->where('notify_' . $type, true);
            })
            ->with('notificationPreference')
            ->get();

        foreach ($users as $user) {
            $this->notifyUser($user, $type, $title, $message, $priority, $data);
        }
    }

    /**
     * Queue a notification for later delivery.
     */
    protected function queueNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        string $priority,
        array $data,
        ?\Carbon\Carbon $scheduledFor = null
    ): QueuedNotification {
        return QueuedNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'data' => $data,
            'scheduled_for' => $scheduledFor,
        ]);
    }

    /**
     * Process queued notifications that are ready to send.
     */
    public function processQueue(): int
    {
        $sent = 0;

        $notifications = QueuedNotification::readyToSend()
            ->with('user.notificationPreference')
            ->get();

        foreach ($notifications as $notification) {
            $prefs = $notification->user->notificationPreference;

            // If still not in working hours (and they care), reschedule
            if ($prefs && $prefs->respect_working_hours && !$prefs->isWithinWorkingHours()) {
                $notification->update([
                    'scheduled_for' => $prefs->getNextWorkingHoursStart(),
                ]);
                continue;
            }

            // Try to send
            $topic = $prefs?->ntfy_topic;
            if (!$topic) {
                $notification->markAsFailed('No NTFY topic configured');
                continue;
            }

            $success = $this->send(
                $topic,
                $notification->title,
                $notification->message,
                $notification->priority,
                $this->getTagsForType($notification->type)
            );

            if ($success) {
                $notification->markAsSent();
                $sent++;
            } else {
                $notification->markAsFailed('Failed to send to NTFY');
            }
        }

        return $sent;
    }

    /**
     * Get NTFY tags for notification type.
     */
    protected function getTagsForType(string $type): array
    {
        return match ($type) {
            'new_member' => ['bust_in_silhouette', 'new'],
            'payment_received' => ['money_with_wings', 'bank'],
            'document_uploaded' => ['page_facing_up', 'inbox_tray'],
            'membership_expiring' => ['hourglass', 'warning'],
            'activity_submitted' => ['clipboard', 'gun'],
            'knowledge_test_completed' => ['mortar_board', 'memo'],
            'system_errors' => ['rotating_light', 'computer'],
            default => ['bell'],
        };
    }
}
