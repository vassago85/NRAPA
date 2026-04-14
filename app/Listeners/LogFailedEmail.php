<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSending;

class LogFailedEmail
{
    /**
     * Handle failed email delivery by listening to MessageSending
     * and wrapping the send in error tracking.
     *
     * Note: Laravel does not have a built-in "MessageFailed" event.
     * Failed emails are logged from catch blocks across the app instead.
     * This listener exists as a placeholder for future use.
     */
    public function handle(MessageSending $event): void
    {
        // MessageSending fires before send — we can't detect failure here.
        // Failure logging is handled by the static helper below.
    }

    /**
     * Log a failed email attempt. Call this from catch blocks.
     */
    public static function logFailure(
        string $toEmail,
        string $subject,
        string $mailableClass,
        string $errorMessage,
        ?int $userId = null,
    ): void {
        if (! $userId) {
            $user = User::where('email', $toEmail)
                ->orWhere('phone', $toEmail)
                ->first();
            $userId = $user?->id;
        }

        EmailLog::create([
            'user_id' => $userId,
            'to_email' => $toEmail,
            'from_email' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'subject' => $subject,
            'mailable_class' => $mailableClass,
            'status' => 'failed',
            'error_message' => $errorMessage,
            'sent_at' => now(),
        ]);
    }
}
