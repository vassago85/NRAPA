<?php

namespace App\Mail\Concerns;

use App\Http\Controllers\EmailPreferencesController;
use App\Models\User;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\URL;

/**
 * Marks a mailable as "non-essential" (reminders, chases, bulk messages,
 * newsletters) so the shared email layout renders the one-click unsubscribe
 * link in the footer, and so Gmail/Outlook show their native unsubscribe
 * button via the RFC 8058 List-Unsubscribe-Post handshake.
 *
 * Opt-out enforcement happens at the SEND site (BulkMemberAction, ChaseRunner,
 * command handlers, etc.) — a non-essential mailable constructed in isolation
 * will still render, so the mailable can be previewed.
 *
 * Contract: the host mailable must implement `recipient(): User` returning
 * the member who should get the signed URL. We use the member's uuid, never
 * their primary-key id.
 */
trait NonEssentialMail
{
    abstract public function recipient(): User;

    /**
     * View data to merge into any mailable using this trait. The host
     * mailable's `content()` method should spread this into its `with:` array:
     *
     *     return new Content(view: 'emails.foo', with: array_merge([
     *         'user' => $this->user,
     *         ...
     *     ], $this->nonEssentialMailViewData()));
     */
    public function nonEssentialMailViewData(): array
    {
        if (! $this->shouldAttachUnsubscribe()) {
            return [];
        }

        return [
            'unsubscribeUrl' => EmailPreferencesController::signedUnsubscribeUrl(
                $this->recipient(),
                $this->unsubscribeReason()
            ),
        ];
    }

    /**
     * RFC 8058-compliant List-Unsubscribe + List-Unsubscribe-Post headers so
     * Gmail / Outlook / Fastmail can render a native "Unsubscribe" button.
     *
     * The host mailable's `headers()` method should return this directly:
     *
     *     public function headers(): Headers
     *     {
     *         return $this->nonEssentialMailHeaders();
     *     }
     */
    public function nonEssentialMailHeaders(): Headers
    {
        if (! $this->shouldAttachUnsubscribe()) {
            return new Headers();
        }

        $recipient = $this->recipient();

        $httpUnsubscribe = URL::signedRoute('email.list_unsubscribe', [
            'uuid' => $recipient->uuid,
        ]);
        $browserUnsubscribe = EmailPreferencesController::signedUnsubscribeUrl(
            $recipient,
            'list_unsub_mailto_fallback'
        );
        $mailtoUnsubscribe = sprintf(
            'mailto:unsubscribe@%s?subject=unsubscribe:%s',
            parse_url(config('app.url'), PHP_URL_HOST) ?: 'nrapa.co.za',
            urlencode($recipient->uuid)
        );

        return new Headers(
            text: [
                // RFC 2369 + RFC 8058. Order matters: mailto first for strict
                // clients, HTTPS second for one-click, browser URL third as a
                // visible fallback for clients that render the raw header.
                'List-Unsubscribe' => sprintf('<%s>, <%s>, <%s>', $mailtoUnsubscribe, $httpUnsubscribe, $browserUnsubscribe),
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    /**
     * Override in the host mailable to tag where this specific email's
     * unsubscribe came from (e.g. 'activity_reminder', 'approval_chase').
     * Shows up in the audit log so admins can see which campaign triggered
     * the opt-out.
     */
    protected function unsubscribeReason(): string
    {
        return 'footer_' . class_basename(static::class);
    }

    /**
     * Hook for mailables where the "non-essential" flag depends on content.
     * Default: always non-essential. Override to short-circuit — the view
     * data and headers both no-op when this returns false, so the email
     * renders without an unsubscribe link.
     *
     * Example: MemberMessageMail is non-essential when an admin broadcasts
     * to a member, but transactional when the member replies back to the
     * NRAPA admin inbox.
     */
    protected function shouldAttachUnsubscribe(): bool
    {
        return true;
    }
}
