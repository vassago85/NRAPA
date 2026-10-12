<?php

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalState;
use App\Exceptions\Approvals\ApprovalNotAllowed;
use App\Mail\ApprovalChaseMail;
use App\Mail\ApprovalDecisionMail;
use App\Models\Approval;
use App\Models\ApprovalChase;
use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\Payment;
use App\Services\Approvals\ApprovalService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ApprovalFixtures;

beforeEach(function () {
    Mail::fake();
    ApprovalFixtures::stubDocumentRendering();
    $this->admin = ApprovalFixtures::admin();
    $this->service = app(ApprovalService::class);
});

afterEach(fn () => Carbon::setTestNow());

test('one decision writes exactly one audit entry with the preflight snapshot', function () {
    $approval = ApprovalFixtures::calibreApproval();

    $this->service->decide($approval, ApprovalDecision::Approve, $this->admin, 'Standard calibre');

    $entries = AuditLog::where('event', 'like', 'approval.%')->get();
    expect($entries)->toHaveCount(1);

    $entry = $entries->first();
    expect($entry->event)->toBe('approval.approved')
        ->and($entry->user_id)->toBe($this->admin->id)
        ->and($entry->meta['approval_id'])->toBe($approval->id)
        ->and($entry->meta['type'])->toBe('calibre')
        ->and($entry->meta['note'])->toBe('Standard calibre')
        ->and(collect($entry->meta['preflight'])->pluck('key')->all())->toBe(['calibre_named', 'calibre_not_duplicate'])
        ->and(collect($entry->meta['preflight'])->every(fn ($c) => array_key_exists('passed', $c) && array_key_exists('reason', $c)))->toBeTrue();
});

test('a renewal activated by its payment folds the membership closure into the one entry', function () {
    $member = ApprovalFixtures::member();
    $previous = ApprovalFixtures::activeMembership($member);
    $renewal = Membership::create([
        'user_id' => $member->id,
        'membership_type_id' => $previous->membership_type_id,
        'previous_membership_id' => $previous->id,
        'status' => 'applied',
        'source' => 'web',
        'applied_at' => now(),
        'payment_reference' => 'NRAPA-RENEW-1234',
        'proof_of_payment_path' => 'proof-of-payment/renewal.pdf',
    ]);
    $payment = Approval::forApprovable(Payment::where('membership_id', $renewal->id)->firstOrFail())->firstOrFail();
    $membershipApproval = Approval::forApprovable($renewal)->firstOrFail();

    $this->service->decide($payment, ApprovalDecision::Approve, $this->admin);

    expect($renewal->refresh()->status)->toBe('active')
        ->and($membershipApproval->refresh()->state)->toBe(ApprovalState::Approved)
        ->and(AuditLog::where('event', 'like', 'approval.%')->count())->toBe(1)
        ->and(AuditLog::where('event', 'approval.approved')->first()->meta['cascaded'][0]['approval_id'])->toBe($membershipApproval->id);
});

test('a self-submitted item is refused', function () {
    $approval = ApprovalFixtures::calibreApproval($this->admin);

    expect(fn () => $this->service->decide($approval, ApprovalDecision::Approve, $this->admin))
        ->toThrow(ApprovalNotAllowed::class);

    expect($approval->refresh()->state)->toBe(ApprovalState::WaitingOnAdmin)
        ->and(AuditLog::where('event', 'like', 'approval.%')->count())->toBe(0);
});

test('members are refused even for other members items', function () {
    $approval = ApprovalFixtures::calibreApproval();

    expect(fn () => $this->service->decide($approval, ApprovalDecision::Approve, ApprovalFixtures::member()))
        ->toThrow(ApprovalNotAllowed::class);
});

test('the member is emailed once per outcome', function () {
    $calibre = ApprovalFixtures::calibreApproval();
    $this->service->decide($calibre, ApprovalDecision::Decline, $this->admin, 'Duplicate of .308 Win');

    Mail::assertQueued(ApprovalDecisionMail::class, fn ($mail) => $mail->approval->is($calibre)
        && $mail->decision === ApprovalDecision::Decline
        && $mail->note === 'Duplicate of .308 Win');

    // Documents send their own rejection mail; the queue must not add a second.
    $document = ApprovalFixtures::documentApproval();
    $this->service->decide($document, ApprovalDecision::RequestChanges, $this->admin, null, ['Upload a clearer scan']);

    Mail::assertNotQueued(ApprovalDecisionMail::class, fn ($mail) => $mail->approval->is($document));
});

test('changes requested on a document reopen when the member uploads a replacement', function () {
    $old = ApprovalFixtures::documentApproval();
    $this->service->decide($old, ApprovalDecision::RequestChanges, $this->admin, null, ['Upload a clearer scan']);

    expect($old->refresh()->state)->toBe(ApprovalState::WaitingOnMember)
        ->and($old->metaValue('awaiting_resubmission'))->toBeTrue();

    $replacement = MemberDocument::create([
        'user_id' => $old->user_id,
        'document_type_id' => $old->approvable->document_type_id,
        'file_path' => 'member-documents/poa-2.pdf',
        'original_filename' => 'poa-2.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 10,
        'status' => 'pending',
        'uploaded_at' => now(),
    ]);

    $new = Approval::forApprovable($replacement)->firstOrFail();
    expect($new->state)->toBe(ApprovalState::WaitingOnAdmin)
        ->and($old->refresh()->state)->toBe(ApprovalState::Withdrawn)
        ->and($old->metaValue('superseded_by'))->toBe($new->id);
});

test('changes requested on a payment reopen with a fresh submitted_at when new proof arrives', function () {
    $approval = ApprovalFixtures::paymentApproval();
    $this->service->decide($approval, ApprovalDecision::RequestChanges, $this->admin, 'Proof is for the wrong amount');

    expect($approval->refresh()->state)->toBe(ApprovalState::WaitingOnMember);

    Carbon::setTestNow(now()->addDays(2));
    $approval->approvable->membership->update(['proof_of_payment_path' => 'proof-of-payment/second.pdf']);

    $approval->refresh();
    expect($approval->state)->toBe(ApprovalState::WaitingOnAdmin)
        ->and($approval->submitted_at->isSameDay(now()))->toBeTrue()
        ->and($approval->decided_by)->toBeNull();
});

test('changes requested on an application reopen when the member uploads a document', function () {
    $approval = ApprovalFixtures::membershipApproval();
    $this->service->decide($approval, ApprovalDecision::RequestChanges, $this->admin, 'Please upload your ID');

    expect($approval->refresh()->state)->toBe(ApprovalState::WaitingOnMember);

    ApprovalFixtures::documentApproval($approval->user);

    expect($approval->refresh()->state)->toBe(ApprovalState::WaitingOnAdmin);
});

test('the chase fires once per stage and running it again sends nothing', function () {
    $approval = ApprovalFixtures::paymentApproval();
    $this->service->decide($approval, ApprovalDecision::RequestChanges, $this->admin, 'Proof is unreadable');
    $start = now();

    Carbon::setTestNow($start->copy()->addDays(6));
    $this->artisan('nrapa:chase-waiting-on-member')->assertSuccessful();
    expect(ApprovalChase::count())->toBe(0);

    Carbon::setTestNow($start->copy()->addDays(7)->addHour());
    $this->artisan('nrapa:chase-waiting-on-member')->assertSuccessful();
    $this->artisan('nrapa:chase-waiting-on-member')->assertSuccessful();

    expect(ApprovalChase::pluck('stage')->all())->toBe([7]);
    Mail::assertSent(ApprovalChaseMail::class, 1);
    expect(ApprovalChase::first()->email_log_id)->not->toBeNull()
        ->and(EmailLog::find(ApprovalChase::first()->email_log_id)->subject)->toBe(ApprovalChaseMail::subjectFor($approval));

    Carbon::setTestNow($start->copy()->addDays(15));
    $this->artisan('nrapa:chase-waiting-on-member')->assertSuccessful();
    Carbon::setTestNow($start->copy()->addDays(22));
    $this->artisan('nrapa:chase-waiting-on-member')->assertSuccessful();
    $this->artisan('nrapa:chase-waiting-on-member')->assertSuccessful();

    expect(ApprovalChase::orderBy('stage')->pluck('stage')->all())->toBe([7, 14, 21]);
    Mail::assertSent(ApprovalChaseMail::class, 3);

    $summary = app(\App\Queries\ApprovalQueue::class)
        ->withChaseSummary(Approval::whereKey($approval->id))->first()->chaseSummary();
    expect($summary)->toStartWith('3 emails, last chased');
});

test('a member reached late gets only the latest due stage', function () {
    $approval = ApprovalFixtures::paymentApproval();
    $this->service->decide($approval, ApprovalDecision::RequestChanges, $this->admin, 'Unreadable');

    Carbon::setTestNow(now()->addDays(16));
    $this->artisan('nrapa:chase-waiting-on-member')->assertSuccessful();

    expect(ApprovalChase::pluck('stage')->all())->toBe([14]);
    Mail::assertSent(ApprovalChaseMail::class, 1);
});

test('the chase skips bounced members and memberships blocked on payment', function () {
    // Web application without proof: payment and membership both wait on the member.
    $member = ApprovalFixtures::member();
    $membership = Membership::create([
        'user_id' => $member->id,
        'membership_type_id' => ApprovalFixtures::membershipType()->id,
        'status' => 'applied',
        'source' => 'web',
        'applied_at' => now(),
        'payment_reference' => 'NRAPA-CHASE-1234',
    ]);
    $membershipApproval = Approval::forApprovable($membership)->firstOrFail();
    expect($membershipApproval->metaValue('blocked_by_payment'))->toBeTrue();

    $bounced = ApprovalFixtures::member(['email_status' => 'bounced']);
    $bouncedApproval = ApprovalFixtures::paymentApproval($bounced);
    $this->service->decide($bouncedApproval, ApprovalDecision::RequestChanges, $this->admin, 'Unreadable');

    Carbon::setTestNow(now()->addDays(8));
    $this->artisan('nrapa:chase-waiting-on-member')->assertSuccessful();

    expect(ApprovalChase::pluck('approval_id')->all())->toBe([
        Approval::forApprovable(Payment::where('membership_id', $membership->id)->firstOrFail())->value('id'),
    ]);
    Mail::assertNotSent(ApprovalChaseMail::class, fn ($mail) => $mail->hasTo($bounced->email));
});
