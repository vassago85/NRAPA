<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailPreferencesController extends Controller
{
    /**
     * One-click opt-out from non-essential email, triggered by the signed URL
     * embedded in the footer of every non-essential email. No login required
     * — the signed URL IS the authentication.
     *
     * Transactional / account mail is unaffected: this only suppresses
     * reminders, chases, bulk admin messages, and newsletters.
     */
    public function unsubscribe(Request $request, string $uuid)
    {
        $user = User::where('uuid', $uuid)->first();

        // Return a soft "already handled" page rather than 404 so a stale link
        // from an old email doesn't look broken.
        if (! $user) {
            return response()->view('email-preferences.unsubscribed', [
                'user' => null,
                'alreadyUnsubscribed' => false,
                'resubscribeUrl' => null,
            ]);
        }

        $changed = $user->optOutOfNonEssentialEmail(
            $this->resolveReason($request, 'footer_link')
        );

        if ($changed) {
            AuditLog::log(
                event: 'email.opt_out',
                auditable: $user,
                newValues: ['email_opt_out_at' => $user->email_opt_out_at?->toIso8601String()],
                user: $user,
                meta: ['source' => 'footer_link', 'reason' => $user->email_opt_out_reason]
            );
        }

        return response()->view('email-preferences.unsubscribed', [
            'user' => $user,
            'alreadyUnsubscribed' => ! $changed,
            'resubscribeUrl' => $this->signedResubscribeUrl($user),
        ]);
    }

    /**
     * One-click re-subscribe, used by the "undo" button on the confirmation
     * page and by Mailgun's List-Unsubscribe-Post handler if we ever want to
     * reverse it.
     */
    public function resubscribe(Request $request, string $uuid)
    {
        $user = User::where('uuid', $uuid)->firstOrFail();

        $changed = $user->optInToNonEssentialEmail();

        if ($changed) {
            AuditLog::log(
                event: 'email.opt_in',
                auditable: $user,
                newValues: ['email_opt_out_at' => null],
                user: $user,
                meta: ['source' => 'signed_link']
            );
        }

        return response()->view('email-preferences.resubscribed', [
            'user' => $user,
        ]);
    }

    /**
     * RFC 8058 List-Unsubscribe-Post handler. Email clients (Gmail / Outlook)
     * POST here when the user clicks their native "Unsubscribe" button. The
     * body is "List-Unsubscribe=One-Click" per the spec.
     */
    public function listUnsubscribePost(Request $request, string $uuid)
    {
        $user = User::where('uuid', $uuid)->first();

        if ($user) {
            $changed = $user->optOutOfNonEssentialEmail('list_unsubscribe_header');
            if ($changed) {
                AuditLog::log(
                    event: 'email.opt_out',
                    auditable: $user,
                    newValues: ['email_opt_out_at' => $user->email_opt_out_at?->toIso8601String()],
                    user: $user,
                    meta: ['source' => 'rfc8058_list_unsubscribe']
                );
            }
        }

        // RFC 8058 just wants a 200.
        return response('', 200);
    }

    /**
     * Build a signed one-click unsubscribe URL for the given user. Public so
     * mailables can call it when composing their view data.
     */
    public static function signedUnsubscribeUrl(User $user, ?string $reason = null): string
    {
        return URL::signedRoute('email.unsubscribe', array_filter([
            'uuid' => $user->uuid,
            'reason' => $reason,
        ]));
    }

    protected function signedResubscribeUrl(User $user): string
    {
        return URL::signedRoute('email.resubscribe', ['uuid' => $user->uuid]);
    }

    protected function resolveReason(Request $request, string $default): string
    {
        $raw = $request->query('reason', $default);

        // Keep the reason tag short and safe for the varchar(64) column.
        return substr(preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $raw), 0, 64) ?: $default;
    }
}
