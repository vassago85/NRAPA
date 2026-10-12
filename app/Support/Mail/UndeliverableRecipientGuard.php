<?php

namespace App\Support\Mail;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;

/**
 * Safety net behind every Mail::to() call: strips recipients whose address has
 * hard-bounced or complained, and cancels the send when nobody is left.
 */
class UndeliverableRecipientGuard
{
    public const BLOCKED_MESSAGE = 'Not sent: the address has hard-bounced or complained.';

    public function allows(MessageSending $event): bool
    {
        $message = $event->message;
        $fields = ['to' => $message->getTo(), 'cc' => $message->getCc(), 'bcc' => $message->getBcc()];

        $addresses = collect($fields)->flatten()
            ->map(fn (Address $a) => $a->getAddress())
            ->filter()
            ->unique()
            ->values();

        if ($addresses->isEmpty()) {
            return true;
        }

        $blocked = User::query()
            ->whereIn('email', $addresses->merge($addresses->map(fn ($a) => strtolower($a)))->unique()->all())
            ->whereIn('email_status', User::UNDELIVERABLE_EMAIL_STATUSES)
            ->pluck('email')
            ->map(fn ($e) => strtolower($e))
            ->all();

        if ($blocked === []) {
            return true;
        }

        $keep = fn (array $list) => array_values(array_filter(
            $list,
            fn (Address $a) => ! in_array(strtolower($a->getAddress()), $blocked, true)
        ));

        $remainingTo = $keep($fields['to']);

        Log::warning('[MAIL_BLOCKED] '.implode(',', $blocked).' subject="'.$message->getSubject().'"');

        if ($remainingTo === []) {
            $this->recordBlocked($blocked[0], (string) $message->getSubject());

            return false;
        }

        $message->to(...$remainingTo);

        foreach (['cc' => 'Cc', 'bcc' => 'Bcc'] as $field => $header) {
            $remaining = $keep($fields[$field]);

            if ($remaining === []) {
                $message->getHeaders()->remove($header);
            } else {
                $message->{$field}(...$remaining);
            }
        }

        return true;
    }

    private function recordBlocked(string $email, string $subject): void
    {
        try {
            $queued = EmailLog::where('to_email', $email)
                ->where('subject', $subject)
                ->where('status', 'queued')
                ->orderBy('created_at')
                ->first();

            if ($queued) {
                $queued->update(['status' => 'failed', 'error_message' => self::BLOCKED_MESSAGE]);

                return;
            }

            $user = User::where('email', $email)->first();

            EmailLog::log(
                toEmail: $email,
                subject: $subject !== '' ? $subject : '(no subject)',
                mailableClass: 'Blocked',
                toName: $user?->name,
                userId: $user?->id,
                status: 'failed',
                errorMessage: self::BLOCKED_MESSAGE,
            );
        } catch (\Throwable $e) {
            Log::warning('[MAIL_BLOCKED] audit row failed: '.$e->getMessage());
        }
    }
}
