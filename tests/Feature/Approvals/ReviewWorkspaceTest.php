<?php

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalState;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Services\Approvals\ApprovalService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Support\ApprovalFixtures;

beforeEach(function () {
    Mail::fake();
    ApprovalFixtures::stubDocumentRendering();
    $this->admin = ApprovalFixtures::admin(['name' => 'Alice Admin']);
    $this->otherAdmin = ApprovalFixtures::admin(['name' => 'Bob Admin']);
});

/**
 * An activity whose member has a current membership, so every check passes.
 */
function cleanActivityApproval(): Approval
{
    $member = ApprovalFixtures::member();
    ApprovalFixtures::activeMembership($member);

    return ApprovalFixtures::activityApproval($member);
}

test('approve and next walks through a seeded queue in order', function () {
    $seeded = [];
    foreach ([60 => fn () => ApprovalFixtures::documentApproval(), 40 => fn () => ApprovalFixtures::calibreApproval(), 20 => fn () => cleanActivityApproval()] as $hours => $build) {
        Carbon::setTestNow(now()->subHours($hours));
        $seeded[] = $build()->id;
        Carbon::setTestNow();
    }

    $page = Livewire::actingAs($this->admin)->test('pages::admin.review.index')
        ->assertSet('currentId', $seeded[0])
        ->assertSee('Before approving')
        ->call('approveAndNext')
        ->assertSet('currentId', $seeded[1])
        ->call('approveAndNext')
        ->assertSet('currentId', $seeded[2])
        ->call('approveAndNext')
        ->assertSee('Nothing else is waiting on you.');

    foreach ($seeded as $id) {
        expect(Approval::find($id)->state)->toBe(ApprovalState::Approved);
    }

    expect(AuditLog::where('event', 'approval.approved')->count())->toBe(3)
        // Decided rows stay in the list.
        ->and($page->get('listIds'))->toBe($seeded);
});

test('an item that fails a preflight check exposes no inline approve button', function () {
    $failing = ApprovalFixtures::activityApproval(); // member has no membership
    $passing = cleanActivityApproval();

    expect($failing->approvable->isInlineApprovable())->toBeFalse()
        ->and($passing->approvable->isInlineApprovable())->toBeTrue();

    $this->actingAs($this->admin)->get(route('admin.approvals.index'))
        ->assertOk()
        ->assertDontSee('data-inline-approve="'.$failing->id.'"', false)
        ->assertSee('data-inline-approve="'.$passing->id.'"', false);

    expect(fn () => app(ApprovalService::class)->approveInline($failing, $this->admin))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('approving past a failed check in the workspace needs a note', function () {
    $failing = ApprovalFixtures::activityApproval();

    Livewire::actingAs($this->admin)->test('pages::admin.review.index', ['approval' => $failing])
        ->assertSee('data-passed="0"', false)
        ->call('approve')
        ->assertHasErrors('note');

    expect($failing->refresh()->state)->toBe(ApprovalState::WaitingOnAdmin);

    Livewire::actingAs($this->admin)->test('pages::admin.review.index', ['approval' => $failing])
        ->set('note', 'Membership confirmed by phone')
        ->call('approve')
        ->assertHasNoErrors();

    expect($failing->refresh()->state)->toBe(ApprovalState::Approved)
        ->and(AuditLog::where('event', 'approval.approved')->latest('id')->first()->meta['note'])->toBe('Membership confirmed by phone');
});

test('a concurrent second decision is rejected and reports the existing decision', function () {
    $approval = ApprovalFixtures::documentApproval();

    $page = Livewire::actingAs($this->admin)->test('pages::admin.review.index', ['approval' => $approval])
        ->assertSet('currentId', $approval->id);

    // Bob decides while Alice's page is open.
    app(ApprovalService::class)->decide($approval->fresh(), ApprovalDecision::Decline, $this->otherAdmin, 'Blurry scan');

    $page->call('approve')
        ->assertSee('Already decided by Bob Admin');

    $approval->refresh();
    expect($approval->state)->toBe(ApprovalState::Declined)
        ->and($approval->decided_by)->toBe($this->otherAdmin->id)
        ->and(AuditLog::where('event', 'like', 'approval.%')->count())->toBe(1);
});

test('an endorsement is approved by one admin like every other type', function () {
    $approval = ApprovalFixtures::endorsementApproval();

    expect($approval->requires_second_approval)->toBeFalse();

    Livewire::actingAs($this->admin)->test('pages::admin.review.index', ['approval' => $approval])
        ->set('note', 'Checked the firearm details')
        ->call('approve')
        ->assertSee('Approved:');

    expect($approval->refresh()->state)->toBe(ApprovalState::Approved)
        ->and($approval->decided_by)->toBe($this->admin->id)
        ->and($approval->first_approved_by)->toBeNull();
});

test('decline and request changes need a note and go through the service', function () {
    $declined = ApprovalFixtures::calibreApproval();
    $sentBack = ApprovalFixtures::documentApproval();

    Livewire::actingAs($this->admin)->test('pages::admin.review.index', ['approval' => $declined])
        ->call('decline')
        ->assertHasErrors('note')
        ->set('note', 'Already on the list under another name')
        ->call('decline')
        ->assertHasNoErrors();

    Livewire::actingAs($this->admin)->test('pages::admin.review.index', ['approval' => $sentBack])
        ->set('note', "Upload a clearer scan\nInclude the second page")
        ->call('requestChanges')
        ->assertHasNoErrors();

    expect($declined->refresh()->state)->toBe(ApprovalState::Declined)
        ->and($sentBack->refresh()->state)->toBe(ApprovalState::WaitingOnMember)
        ->and($sentBack->metaValue('changes_requested'))->toBe(['Upload a clearer scan', 'Include the second page']);
});

test('the workspace renders the evidence for every type', function () {
    foreach (ApprovalFixtures::builders() as $type => $build) {
        $approval = $build(null);

        $this->actingAs($this->admin)
            ->get(route('admin.review.show', $approval))
            ->assertOk()
            ->assertSee($approval->approvable->summaryLine())
            ->assertSee('Before approving')
            ->assertSee('History');
    }

    $this->actingAs($this->admin)->get(route('admin.review.index', ['type' => 'calibre']))->assertOk();
});

test('a disciplinary hold from the member page fails the check until lifted', function () {
    $approval = cleanActivityApproval();
    $member = $approval->user;
    expect($approval->approvable->isInlineApprovable())->toBeTrue();

    $page = Livewire::actingAs($this->admin)->test('pages::admin.members.show', ['user' => $member])
        ->set('showHoldForm', true)
        ->set('holdReason', 'Club hearing pending')
        ->call('placeDisciplinaryHold')
        ->assertHasNoErrors();

    $approval->refresh()->load('approvable.user');
    expect($member->refresh()->disciplinary_hold_by)->toBe($this->admin->id)
        ->and($approval->approvable->isInlineApprovable())->toBeFalse()
        ->and(AuditLog::where('event', 'member.disciplinary_hold_placed')->exists())->toBeTrue();

    $page->call('liftDisciplinaryHold');

    $approval->refresh()->load('approvable.user');
    expect($member->refresh()->disciplinary_hold_at)->toBeNull()
        ->and($approval->approvable->isInlineApprovable())->toBeTrue()
        ->and(AuditLog::where('event', 'member.disciplinary_hold_lifted')->exists())->toBeTrue();
});

test('members cannot open the workspace', function () {
    $this->actingAs(ApprovalFixtures::member())->get(route('admin.review.index'))->assertForbidden();
});
