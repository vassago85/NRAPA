<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;

class LogSentEmail
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        // Get recipients
        $to = $message->getTo();
        $toEmail = '';
        $toName = null;

        foreach ($to as $address) {
            $toEmail = $address->getAddress();
            $toName = $address->getName();
            break; // Just take the first recipient
        }

        // Get from
        $from = $message->getFrom();
        $fromEmail = '';
        $fromName = null;

        foreach ($from as $address) {
            $fromEmail = $address->getAddress();
            $fromName = $address->getName();
            break;
        }

        $user = User::where('email', $toEmail)->first()
            ?? User::where('phone', $toEmail)->first();

        // Get the mailable class from the data if available
        $mailableClass = $event->data['__laravel_notification'] ??
                         $event->data['mailable'] ??
                         'Unknown';
        $mailableClass = is_object($mailableClass) ? get_class($mailableClass) : $mailableClass;

        $body = $message->getHtmlBody() ?: $message->getTextBody();
        $subject = $message->getSubject();

        // Bulk-send commands (e.g. SendMembershipExpiryNotifications) write a "queued"
        // audit row at dispatch time. When the mail actually leaves the building we
        // want to PROMOTE that row to "sent" instead of creating a parallel "Unknown"
        // row, so the admin email-logs page shows a single status per dispatch.
        //
        // Match on (to_email, subject, status='queued') within the last hour. Subject
        // disambiguates same-recipient queued mails (e.g. a member with two memberships
        // both renewing). orderBy created_at asc so the OLDEST queued row gets
        // promoted first — matches first-in-first-out delivery order.
        $queuedRow = EmailLog::where('to_email', $toEmail)
            ->where('subject', $subject)
            ->where('status', 'queued')
            ->where('created_at', '>=', now()->subHour())
            ->orderBy('created_at')
            ->first();

        if ($queuedRow) {
            $queuedRow->update([
                'status' => 'sent',
                'sent_at' => now(),
                'body' => $queuedRow->body ?: $body,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
                'metadata' => array_merge((array) ($queuedRow->metadata ?? []), $event->data ?? []),
            ]);

            return;
        }

        EmailLog::create([
            'user_id' => $user?->id,
            'to_email' => $toEmail,
            'to_name' => $toName,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'subject' => $subject,
            'body' => $body,
            'mailable_class' => $mailableClass,
            'status' => 'sent',
            'sent_at' => now(),
            'metadata' => $event->data ?? [],
        ]);
    }
}
