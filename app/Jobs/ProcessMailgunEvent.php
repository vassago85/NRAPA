<?php

namespace App\Jobs;

use App\Models\EmailLog;
use App\Models\MailgunEvent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Applies one stored Mailgun webhook event to the matching email log and
 * recipient. Statuses only move forward; bounces and complaints are final.
 */
class ProcessMailgunEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const MAX_EVENTS_KEPT = 50;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $mailgunEventId) {}

    public function handle(): void
    {
        $event = MailgunEvent::find($this->mailgunEventId);

        if (! $event || $event->processed_at) {
            return;
        }

        $data = (array) $event->payload;
        $status = self::statusFor($event->event, $data);
        $at = $event->occurred_at ?? now();
        $code = data_get($data, 'delivery-status.code');
        $reason = self::reasonFor($data);

        DB::transaction(function () use ($event, $data, $status, $at, $code, $reason) {
            $log = $event->provider_message_id
                ? EmailLog::where('provider_message_id', $event->provider_message_id)->lockForUpdate()->first()
                : null;

            if ($log) {
                $this->applyToLog($log, $event, $data, $status, $at, $code, $reason);
            }

            $this->applyToRecipient($log, $event->recipient, $status, $at, $reason);

            $event->update(['processed_at' => now()]);
        });
    }

    /**
     * The delivery status an event moves a message to, or null when it only
     * gets recorded (temporary failures, which Mailgun retries).
     */
    public static function statusFor(string $event, array $data): ?string
    {
        return match ($event) {
            'accepted' => 'sent',
            'delivered' => 'delivered',
            'opened' => 'opened',
            'clicked' => 'clicked',
            'complained' => 'complained',
            'unsubscribed' => 'unsubscribed',
            'failed', 'permanent_fail' => self::failureStatus($event, $data),
            default => null,
        };
    }

    private static function failureStatus(string $event, array $data): ?string
    {
        $severity = $event === 'permanent_fail' ? 'permanent' : ($data['severity'] ?? null);

        if ($severity !== 'permanent') {
            return null;
        }

        $reason = (string) ($data['reason'] ?? '');
        $code = (int) data_get($data, 'delivery-status.code', 0);

        return in_array($reason, ['bounce', 'suppress-bounce'], true) || $code >= 500 ? 'bounced' : 'failed';
    }

    private static function reasonFor(array $data): ?string
    {
        $reason = data_get($data, 'delivery-status.description')
            ?: data_get($data, 'delivery-status.message')
            ?: ($data['reason'] ?? null);

        return $reason ? mb_substr((string) $reason, 0, 500) : null;
    }

    private function applyToLog(EmailLog $log, MailgunEvent $event, array $data, ?string $status, $at, $code, ?string $reason): void
    {
        $events = (array) ($log->events ?? []);
        $events[] = array_filter([
            'event' => $event->event,
            'at' => $at->toIso8601String(),
            'severity' => $data['severity'] ?? null,
            'reason' => $reason,
        ]);

        $updates = ['events' => array_slice($events, -self::MAX_EVENTS_KEPT)];

        if ($status && EmailLog::canAdvanceDelivery($log->delivery_status, $status)) {
            $updates['delivery_status'] = $status;
        }

        $firstSeenColumn = ['delivered' => 'delivered_at', 'opened' => 'opened_at', 'clicked' => 'clicked_at'][$status] ?? null;

        if ($firstSeenColumn) {
            $updates[$firstSeenColumn] = $log->{$firstSeenColumn} ?? $at;
        }

        if (in_array($status, ['bounced', 'failed'], true)) {
            $updates['bounced_at'] = $log->bounced_at ?? $at;
            $updates['bounce_code'] = $code !== null ? mb_substr((string) $code, 0, 20) : $log->bounce_code;
            $updates['bounce_reason'] = $reason ?? $log->bounce_reason;
        }

        $log->update($updates);
    }

    private function applyToRecipient(?EmailLog $log, ?string $recipient, ?string $status, $at, ?string $reason): void
    {
        if (! $recipient) {
            return;
        }

        $user = $log?->user && strcasecmp((string) $log->user->email, $recipient) === 0
            ? $log->user
            : User::whereRaw('lower(email) = ?', [strtolower($recipient)])->first();

        if (! $user) {
            return;
        }

        $updates = ['email_last_event_at' => $at];
        $current = $user->email_status;
        $blocked = in_array($current, User::UNDELIVERABLE_EMAIL_STATUSES, true);

        if (in_array($status, ['bounced', 'complained'], true)) {
            $updates['email_status'] = $status;
            $updates['email_bounce_reason'] = $reason ?? $user->email_bounce_reason;
        } elseif (! $blocked && $status === 'unsubscribed') {
            $updates['email_status'] = 'unsubscribed';
        } elseif (! $blocked && $current !== 'unsubscribed' && in_array($status, ['delivered', 'opened', 'clicked'], true)) {
            $updates['email_status'] = 'delivered';
        }

        $user->forceFill($updates)->saveQuietly();
    }
}
