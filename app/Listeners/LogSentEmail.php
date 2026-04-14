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

        $body = $message->getHtmlBody() ?: $message->getTextBody();

        EmailLog::create([
            'user_id' => $user?->id,
            'to_email' => $toEmail,
            'to_name' => $toName,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'subject' => $message->getSubject(),
            'body' => $body,
            'mailable_class' => is_object($mailableClass) ? get_class($mailableClass) : $mailableClass,
            'status' => 'sent',
            'sent_at' => now(),
            'metadata' => $event->data ?? [],
        ]);
    }
}
