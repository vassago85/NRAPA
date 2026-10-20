<?php

use App\Mail\ActivityReminderMail;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

/*
 * User-model helpers
 */

it('treats a user with no opt-out as able to receive non-essential email', function () {
    $user = User::factory()->create();

    expect($user->canReceiveNonEssentialEmail())->toBeTrue()
        ->and($user->isUnsubscribedFromNonEssentialEmail())->toBeFalse();
});

it('suppresses non-essential email after optOut', function () {
    $user = User::factory()->create();

    $changed = $user->optOutOfNonEssentialEmail('footer_link');

    expect($changed)->toBeTrue()
        ->and($user->fresh()->canReceiveNonEssentialEmail())->toBeFalse()
        ->and($user->fresh()->isUnsubscribedFromNonEssentialEmail())->toBeTrue()
        ->and($user->fresh()->email_opt_out_reason)->toBe('footer_link');
});

it('does not alter the timestamp when opting out a second time', function () {
    $user = User::factory()->create();
    $user->optOutOfNonEssentialEmail('footer_link');
    $firstStamp = $user->fresh()->email_opt_out_at;

    sleep(1);
    $changed = $user->fresh()->optOutOfNonEssentialEmail('later_source');

    expect($changed)->toBeFalse()
        ->and($user->fresh()->email_opt_out_at->timestamp)->toBe($firstStamp->timestamp)
        ->and($user->fresh()->email_opt_out_reason)->toBe('footer_link');
});

it('allows re-subscribe to restore non-essential email', function () {
    $user = User::factory()->create();
    $user->optOutOfNonEssentialEmail('footer_link');

    expect($user->fresh()->canReceiveNonEssentialEmail())->toBeFalse();

    $user->fresh()->optInToNonEssentialEmail();

    expect($user->fresh()->canReceiveNonEssentialEmail())->toBeTrue()
        ->and($user->fresh()->email_opt_out_at)->toBeNull()
        ->and($user->fresh()->email_opt_out_reason)->toBeNull();
});

it('keeps essential email blocked when the address is hard-bounced', function () {
    $user = User::factory()->create(['email_status' => 'bounced']);

    expect($user->canReceiveEmail())->toBeFalse()
        ->and($user->canReceiveNonEssentialEmail())->toBeFalse();
});

/*
 * Signed one-click unsubscribe
 */

it('one-click unsubscribe sets the opt-out timestamp and writes an audit log', function () {
    $user = User::factory()->create();

    $url = URL::signedRoute('email.unsubscribe', ['uuid' => $user->uuid]);

    $this->get($url)
        ->assertOk()
        ->assertSee('You\'ve been unsubscribed', false);

    expect($user->fresh()->isUnsubscribedFromNonEssentialEmail())->toBeTrue()
        ->and(AuditLog::where('event', 'email.opt_out')->where('auditable_id', $user->id)->exists())->toBeTrue();
});

it('rejects an unsigned unsubscribe request', function () {
    $user = User::factory()->create();

    $this->get('/email/unsubscribe/' . $user->uuid)
        ->assertStatus(403);

    expect($user->fresh()->isUnsubscribedFromNonEssentialEmail())->toBeFalse();
});

it('one-click resubscribe clears the opt-out', function () {
    $user = User::factory()->create();
    $user->optOutOfNonEssentialEmail('footer_link');

    $url = URL::signedRoute('email.resubscribe', ['uuid' => $user->uuid]);

    $this->get($url)
        ->assertOk()
        ->assertSee('Welcome back');

    expect($user->fresh()->isUnsubscribedFromNonEssentialEmail())->toBeFalse()
        ->and(AuditLog::where('event', 'email.opt_in')->where('auditable_id', $user->id)->exists())->toBeTrue();
});

it('shows the already-unsubscribed message when a second signed click arrives', function () {
    $user = User::factory()->create();
    $user->optOutOfNonEssentialEmail('footer_link');

    $url = URL::signedRoute('email.unsubscribe', ['uuid' => $user->uuid]);

    $this->get($url)
        ->assertOk()
        ->assertSee('already unsubscribed');
});

it('RFC 8058 list-unsubscribe POST opts the user out and returns 200', function () {
    $user = User::factory()->create();

    $url = URL::signedRoute('email.list_unsubscribe', ['uuid' => $user->uuid]);

    $this->postJson($url, ['List-Unsubscribe' => 'One-Click'])
        ->assertOk();

    expect($user->fresh()->isUnsubscribedFromNonEssentialEmail())->toBeTrue()
        ->and($user->fresh()->email_opt_out_reason)->toBe('list_unsubscribe_header');
});

/*
 * Email layout rendering
 */

it('non-essential mailable renders an unsubscribe link in the rendered HTML', function () {
    $user = User::factory()->create();

    $mail = new ActivityReminderMail($user, 2026, 2, 0);
    $html = $mail->render();

    expect($html)->toContain('/email/unsubscribe/' . $user->uuid)
        ->and($html)->toContain('Unsubscribe')
        ->and($html)->toContain('Manage preferences');
});

it('non-essential mailable attaches RFC 8058 List-Unsubscribe headers', function () {
    $user = User::factory()->create();

    $headers = (new ActivityReminderMail($user, 2026, 2, 0))->headers();

    expect($headers->text)->toHaveKey('List-Unsubscribe')
        ->and($headers->text)->toHaveKey('List-Unsubscribe-Post')
        ->and($headers->text['List-Unsubscribe-Post'])->toBe('List-Unsubscribe=One-Click')
        ->and($headers->text['List-Unsubscribe'])->toContain($user->uuid);
});

it('transactional email layout renders the "cannot be unsubscribed" note and no unsubscribe link', function () {
    // Render the shared email layout directly with no $unsubscribeUrl set —
    // this is the exact code path for every transactional mailable. If this
    // asserts, all 25 essential emails (decisions, payment confirmations,
    // password resets, etc.) carry the explanatory note.
    $html = view('emails.layout')->render();

    expect($html)->toContain('cannot be unsubscribed from')
        ->and($html)->not->toContain('/email/unsubscribe/');
});

it('every transactional mailable in app/Mail avoids the NonEssentialMail trait', function () {
    // Guard rail: adding a new mailable with the NonEssentialMail trait is
    // deliberate. If this list grows, make sure the new entry really is
    // non-essential (reminders, chases, bulk admin mail, newsletters), and
    // that its send-site gates on canReceiveNonEssentialEmail().
    $nonEssential = [
        \App\Mail\ActivityReminderMail::class,
        \App\Mail\ApprovalChaseMail::class,
        \App\Mail\MemberMessageMail::class,
        \App\Mail\PopFollowupReminder::class,
    ];

    $actual = collect(\Illuminate\Support\Facades\File::files(app_path('Mail')))
        ->map(fn ($file) => 'App\\Mail\\' . $file->getFilenameWithoutExtension())
        ->filter(fn ($fqcn) => class_exists($fqcn)
            && in_array(\App\Mail\Concerns\NonEssentialMail::class, class_uses_recursive($fqcn), true))
        ->values()
        ->all();

    expect($actual)->toEqualCanonicalizing($nonEssential);
});
