<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\ApprovalFixtures;

uses(RefreshDatabase::class);

/**
 * An admin should be able to correct a member's identity details (name, ID
 * number, DOB, contact, address) inline from the review workspace, so a
 * typo doesn't force them to leave the queue to go hunt for the record.
 * Every change writes an audit entry recording the exact field diff.
 */

function reviewWithApproval(User $actor, \App\Models\Approval $approval)
{
    return Livewire::actingAs($actor)
        ->test('pages::admin.review.index', ['approval' => $approval]);
}

it('an admin inline-corrects a members ID number and name while reviewing', function () {
    $admin = ApprovalFixtures::admin();
    $member = ApprovalFixtures::member([
        'name' => 'Jon Smiht',
        'id_number' => '8001015009080',
    ]);
    $approval = ApprovalFixtures::documentApproval($member);

    reviewWithApproval($admin, $approval)
        ->call('startEditingMember')
        ->assertSet('editingMember', true)
        ->assertSet('memberDraft.name', 'Jon Smiht')
        ->assertSet('memberDraft.id_number', '8001015009080')
        ->set('memberDraft.name', 'John Smith')
        ->set('memberDraft.id_number', '8001015009087')
        ->call('saveMemberDraft')
        ->assertSet('editingMember', false)
        ->assertHasNoErrors();

    $member->refresh();
    expect($member->name)->toBe('John Smith')
        ->and($member->id_number)->toBe('8001015009087');

    $audit = AuditLog::where('event', 'member.details_corrected')->firstOrFail();
    expect((int) $audit->user_id)->toBe((int) $admin->id)
        ->and((int) $audit->auditable_id)->toBe((int) $member->id)
        ->and($audit->old_values)->toMatchArray(['name' => 'Jon Smiht', 'id_number' => '8001015009080'])
        ->and($audit->new_values)->toMatchArray(['name' => 'John Smith', 'id_number' => '8001015009087'])
        ->and($audit->meta['source'])->toBe('admin_review_workspace')
        ->and((int) $audit->meta['approval_id'])->toBe((int) $approval->id);
});

it('an admin cancelling closes the form without touching the member', function () {
    $admin = ApprovalFixtures::admin();
    $member = ApprovalFixtures::member(['name' => 'Correct Name', 'id_number' => '8001015009087']);
    $approval = ApprovalFixtures::documentApproval($member);

    reviewWithApproval($admin, $approval)
        ->call('startEditingMember')
        ->set('memberDraft.name', 'Typed Garbage')
        ->set('memberDraft.id_number', '0')
        ->call('cancelEditingMember')
        ->assertSet('editingMember', false);

    $member->refresh();
    expect($member->name)->toBe('Correct Name')
        ->and($member->id_number)->toBe('8001015009087')
        ->and(AuditLog::where('event', 'member.details_corrected')->count())->toBe(0);
});

it('validation rejects an email that is already in use by another member', function () {
    $admin = ApprovalFixtures::admin();
    $existing = ApprovalFixtures::member(['email' => 'taken@example.test']);
    $member = ApprovalFixtures::member(['email' => 'mine@example.test']);
    $approval = ApprovalFixtures::documentApproval($member);

    reviewWithApproval($admin, $approval)
        ->call('startEditingMember')
        ->set('memberDraft.email', 'taken@example.test')
        ->call('saveMemberDraft')
        ->assertHasErrors(['memberDraft.email'])
        ->assertSet('editingMember', true);

    expect($member->fresh()->email)->toBe('mine@example.test')
        ->and(AuditLog::where('event', 'member.details_corrected')->count())->toBe(0);
});

it('saving with no changes closes the form without writing an audit entry', function () {
    $admin = ApprovalFixtures::admin();
    $member = ApprovalFixtures::member(['name' => 'No Change', 'email' => 'nc@example.test']);
    $approval = ApprovalFixtures::documentApproval($member);

    reviewWithApproval($admin, $approval)
        ->call('startEditingMember')
        ->call('saveMemberDraft')
        ->assertSet('editingMember', false)
        ->assertSet('flash.message', 'No changes to save.');

    expect(AuditLog::where('event', 'member.details_corrected')->count())->toBe(0);
});

it('an admin editing one member does not affect another members data', function () {
    $admin = ApprovalFixtures::admin();
    $alice = ApprovalFixtures::member(['name' => 'Alice Original']);
    $bob = ApprovalFixtures::member(['name' => 'Bob Unchanged']);
    $approval = ApprovalFixtures::documentApproval($alice);

    reviewWithApproval($admin, $approval)
        ->call('startEditingMember')
        ->set('memberDraft.name', 'Alice Corrected')
        ->call('saveMemberDraft')
        ->assertSet('editingMember', false);

    expect($alice->fresh()->name)->toBe('Alice Corrected')
        ->and($bob->fresh()->name)->toBe('Bob Unchanged');
});
