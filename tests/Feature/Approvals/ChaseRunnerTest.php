<?php

use App\Enums\ApprovalState;
use App\Enums\ApprovalType;
use App\Enums\ChaseOutcome;
use App\Mail\ApprovalChaseMail;
use App\Models\Approval;
use App\Models\ApprovalChase;
use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Services\Approvals\ChaseRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ApprovalFixtures;

uses(RefreshDatabase::class);

/**
 * Marks an approval as waiting-on-member for $days with a chase cycle that
 * started $days ago. Payments get a bonus clear of the manual-reminder stamp
 * so the auto path doesn't skip them.
 */
function makeWaitingApproval(int $days = 10, string $type = 'document'): Approval
{
    $approval = ApprovalFixtures::builders()[$type]();
    $since = now()->subDays($days);

    $approval->forceFill([
        'state' => ApprovalState::WaitingOnMember,
        'updated_at' => $since,
        'meta' => array_merge($approval->meta ?? [], ['waiting_since' => $since->toIso8601String()]),
    ])->saveQuietly();

    if ($approval->type === ApprovalType::Payment && ($payment = $approval->approvable)) {
        $payment->membership?->forceFill(['pop_reminder_sent_at' => null])->saveQuietly();
    }

    return $approval->refresh();
}

it('sends a manual chase, logs the email, writes an audit entry, and bumps the cycle row', function () {
    Mail::fake();
    $actor = ApprovalFixtures::admin();
    $approval = makeWaitingApproval(days: 3);

    $outcome = app(ChaseRunner::class)->chase($approval, $actor, source: 'manual');

    expect($outcome)->toBe(ChaseOutcome::Sent);
    Mail::assertSent(ApprovalChaseMail::class, fn ($mail) => $mail->hasTo($approval->user->email));
    expect(EmailLog::query()->where('mailable_class', ApprovalChaseMail::class)->count())->toBe(1);
    expect(AuditLog::query()->where('event', 'approval_chased')->where('user_id', $actor->id)->count())->toBe(1);
    expect(ApprovalChase::query()->where('approval_id', $approval->id)->count())->toBe(1);
    // Day 3 is below the 7-day threshold so the manual chase claims the 7-day slot.
    expect(ApprovalChase::query()->where('approval_id', $approval->id)->first()->stage)->toBe(ChaseRunner::DEFAULT_MANUAL_STAGE);
});

it('refuses a second manual chase inside the throttle window and sends after it clears', function () {
    Mail::fake();
    $actor = ApprovalFixtures::admin();
    $approval = makeWaitingApproval();

    expect(app(ChaseRunner::class)->chase($approval, $actor, source: 'manual'))->toBe(ChaseOutcome::Sent);
    expect(app(ChaseRunner::class)->chase($approval, $actor, source: 'manual'))->toBe(ChaseOutcome::AlreadyChasedRecently);
    Mail::assertSent(ApprovalChaseMail::class, 1);

    // After the throttle window a fresh chase goes out and the audit trail grows.
    $this->travel(ChaseRunner::THROTTLE_HOURS + 1)->hours();
    expect(app(ChaseRunner::class)->chase($approval, $actor, source: 'manual'))->toBe(ChaseOutcome::Sent);
    Mail::assertSent(ApprovalChaseMail::class, 2);
    expect(AuditLog::query()->where('event', 'approval_chased')->count())->toBe(2);
});

it('refuses to mail a member whose email has hard-bounced', function () {
    Mail::fake();
    $actor = ApprovalFixtures::admin();
    $approval = makeWaitingApproval();
    $approval->user->forceFill(['email_status' => 'bounced'])->save();

    $outcome = app(ChaseRunner::class)->chase($approval->refresh(), $actor, source: 'manual');

    expect($outcome)->toBe(ChaseOutcome::MemberCannotReceiveEmail);
    Mail::assertNothingSent();
    expect(AuditLog::query()->where('event', 'approval_chased')->count())->toBe(0);
});

it('refuses to chase an item that is no longer waiting on the member', function () {
    Mail::fake();
    $approval = makeWaitingApproval();
    $approval->forceFill(['state' => ApprovalState::WaitingOnAdmin])->saveQuietly();

    $outcome = app(ChaseRunner::class)->chase($approval, ApprovalFixtures::admin(), source: 'manual');

    expect($outcome)->toBe(ChaseOutcome::NotWaitingOnMember);
    Mail::assertNothingSent();
});

it('runs the auto chase through the same path and stops when the stage is already recorded', function () {
    Mail::fake();
    $approval = makeWaitingApproval(days: 7);

    $first = app(ChaseRunner::class)->chase($approval, actor: null, source: 'auto', stage: 7);
    $second = app(ChaseRunner::class)->chase($approval->refresh(), actor: null, source: 'auto', stage: 7);

    expect($first)->toBe(ChaseOutcome::Sent)
        ->and($second)->toBe(ChaseOutcome::AlreadyChasedForStage);
    Mail::assertSent(ApprovalChaseMail::class, 1);
    // Auto-triggered chases record a system actor (user_id = null).
    expect(AuditLog::query()->where('event', 'approval_chased')->whereNull('user_id')->count())->toBe(1);
});

it('bumps the payment membership pop_reminder stamp so the admin page shows the chase', function () {
    Mail::fake();
    $approval = makeWaitingApproval(type: 'payment', days: 2);
    $payment = $approval->approvable;

    expect($payment->membership->pop_reminder_count)->toBe(0);
    app(ChaseRunner::class)->chase($approval, ApprovalFixtures::admin(), source: 'manual');

    $payment->membership->refresh();
    expect($payment->membership->pop_reminder_count)->toBe(1)
        ->and($payment->membership->pop_reminder_sent_at)->not->toBeNull();
});
