<?php

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalState;
use App\Enums\ApprovalType;
use App\Models\Approval;
use App\Queries\ApprovalQueue;
use App\Services\Approvals\ApprovalService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ApprovalFixtures;

beforeEach(function () {
    Mail::fake();
    ApprovalFixtures::stubDocumentRendering();
    $this->admin = ApprovalFixtures::admin();
    $this->secondAdmin = ApprovalFixtures::admin();
});

test('one approval of each type lands in a single queue ordered by submitted_at', function () {
    $expected = [];
    $hoursAgo = 60;

    foreach (ApprovalFixtures::builders() as $type => $build) {
        Carbon::setTestNow(now()->subHours($hoursAgo));
        $expected[] = $build(null)->id;
        Carbon::setTestNow();
        $hoursAgo -= 10;
    }

    $queue = app(ApprovalQueue::class);

    expect($queue->waitingOnAdmin()->pluck('id')->all())->toBe($expected)
        ->and($queue->waitingOnAdmin()->pluck('type')->map->value->all())
        ->toBe(['payment', 'document', 'membership', 'activity', 'endorsement', 'calibre'])
        ->and($queue->countsByType())->toBe(array_fill_keys(array_column(ApprovalType::cases(), 'value'), 1))
        ->and($queue->oldest()->id)->toBe($expected[0])
        ->and($queue->breachCount(48))->toBe(2);
});

test('approving through the queue moves each record to its approved status', function () {
    $service = app(ApprovalService::class);
    $approvals = collect(ApprovalFixtures::builders())->map(fn ($build) => $build(null));

    foreach ($approvals as $approval) {
        $service->decide($approval, ApprovalDecision::Approve, $this->admin);
    }

    // Endorsements need a second, different admin.
    expect($approvals['endorsement']->refresh()->state)->toBe(ApprovalState::WaitingOnAdmin)
        ->and($approvals['endorsement']->first_approved_by)->toBe($this->admin->id);
    $service->decide($approvals['endorsement'], ApprovalDecision::Approve, $this->secondAdmin);

    foreach ($approvals as $approval) {
        expect($approval->refresh()->state)->toBe(ApprovalState::Approved);
    }

    expect($approvals['payment']->approvable->status)->toBe('completed')
        ->and($approvals['payment']->approvable->membership->payment_confirmed_at)->not->toBeNull()
        ->and($approvals['document']->approvable->status)->toBe('verified')
        ->and($approvals['membership']->approvable->status)->toBe('active')
        ->and($approvals['activity']->approvable->status)->toBe('approved')
        ->and($approvals['endorsement']->approvable->status)->toBeIn(['approved', 'issued'])
        ->and($approvals['calibre']->approvable->status)->toBe('approved')
        ->and($approvals['calibre']->approvable->calibre_id)->not->toBeNull();

    // Confirming a new application's payment hands the membership itself to the queue.
    $membershipApproval = Approval::forApprovable($approvals['payment']->approvable->membership)->first();
    expect($membershipApproval->state)->toBe(ApprovalState::WaitingOnAdmin);
});

test('waiting on member rows never appear in the admin count', function () {
    // Web application with no proof of payment: payment waits on the member,
    // the membership is blocked behind it.
    $membership = \App\Models\Membership::create([
        'user_id' => ApprovalFixtures::member()->id,
        'membership_type_id' => ApprovalFixtures::membershipType()->id,
        'status' => 'applied',
        'source' => 'web',
        'applied_at' => now(),
    ]);
    ApprovalFixtures::activityApproval();

    $queue = app(ApprovalQueue::class);

    expect(Approval::waitingOnMember()->count())->toBe(2)
        ->and($queue->totalWaitingOnAdmin())->toBe(1)
        ->and($queue->sidebarTotal())->toBe(1)
        ->and($queue->waitingOnMember()->count())->toBe(1)
        ->and($queue->waitingOnMember()->first()->type)->toBe(ApprovalType::Payment);

    $membership->update(['proof_of_payment_path' => 'proof-of-payment/late.pdf']);

    expect($queue->totalWaitingOnAdmin())->toBe(2)
        ->and($queue->countsByType()['payment'])->toBe(1)
        ->and($queue->countsByType()['membership'])->toBe(0);
});

test('records decided on the old pages close their approval with an audit entry', function () {
    $approval = ApprovalFixtures::activityApproval();
    $this->actingAs($this->admin);

    $approval->approvable->approve($this->admin);

    expect($approval->refresh()->state)->toBe(ApprovalState::Approved)
        ->and(\App\Models\AuditLog::where('event', 'approval.approved')->count())->toBe(1);
});

test('the approvals page lists queue rows and bulk approval goes through the service', function () {
    ApprovalFixtures::activityApproval();
    ApprovalFixtures::calibreApproval();

    \Livewire\Livewire::actingAs($this->admin)
        ->test('pages::admin.approvals.index')
        ->assertSee('Queue Range')
        ->call('clearAllApprovals');

    expect(app(ApprovalQueue::class)->totalWaitingOnAdmin())->toBe(0)
        ->and(\App\Models\AuditLog::where('event', 'approval.approved')->count())->toBe(2);
});

test('the approvals page renders with a pending calibre request', function () {
    ApprovalFixtures::calibreApproval();

    $this->actingAs($this->admin)->get(route('admin.approvals.index'))->assertOk();
});
