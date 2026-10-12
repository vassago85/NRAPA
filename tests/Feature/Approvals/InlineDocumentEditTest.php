<?php

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\DocumentType;
use App\Models\MemberDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\ApprovalFixtures;

uses(RefreshDatabase::class);

/**
 * An admin reviewing a Document approval should be able to correct the
 * document's type (member tagged a passport as "ID") or expiry (member
 * typed a wrong date, or left it as "does not expire" when it actually
 * does) inline without leaving the queue. Every change writes an audit
 * entry recording the exact field diff.
 */

function openReview(\App\Models\User $actor, Approval $approval)
{
    return Livewire::actingAs($actor)
        ->test('pages::admin.review.index', ['approval' => $approval]);
}

/**
 * Pre-seed a second document type the admin can switch to.
 */
function passportDocumentType(): DocumentType
{
    return DocumentType::firstOrCreate(
        ['slug' => 'passport'],
        ['name' => 'Passport', 'is_active' => true, 'sort_order' => 3],
    );
}

it('an admin corrects a documents type and expiry inline', function () {
    $admin = ApprovalFixtures::admin();
    $approval = ApprovalFixtures::documentApproval();
    $doc = $approval->approvable;
    $passport = passportDocumentType();

    openReview($admin, $approval)
        ->call('startEditingDocument')
        ->assertSet('editingDocument', true)
        ->assertSet('documentDraft.document_type_id', $doc->document_type_id)
        ->assertSet('documentDraft.is_permanent', true) // doc has null expiry
        ->set('documentDraft.document_type_id', $passport->id)
        ->set('documentDraft.is_permanent', false)
        ->set('documentDraft.expires_at', '2030-12-31')
        ->call('saveDocumentDraft')
        ->assertHasNoErrors()
        ->assertSet('editingDocument', false);

    $doc->refresh()->loadMissing('documentType');
    expect((int) $doc->document_type_id)->toBe((int) $passport->id)
        ->and($doc->documentType?->name)->toBe('Passport')
        ->and($doc->expires_at?->format('Y-m-d'))->toBe('2030-12-31');

    $audit = AuditLog::where('event', 'document.details_corrected')->firstOrFail();
    expect((int) $audit->user_id)->toBe((int) $admin->id)
        ->and((int) $audit->auditable_id)->toBe((int) $doc->id)
        ->and($audit->auditable_type)->toBe(MemberDocument::class)
        ->and($audit->old_values)->toMatchArray(['expires_at' => null])
        ->and($audit->new_values)->toMatchArray(['expires_at' => '2030-12-31', 'document_type_name' => 'Passport'])
        ->and($audit->meta['source'])->toBe('admin_review_workspace')
        ->and((int) $audit->meta['approval_id'])->toBe((int) $approval->id);
});

it('an admin can switch an expiring doc back to permanent (does not expire)', function () {
    $admin = ApprovalFixtures::admin();
    $approval = ApprovalFixtures::documentApproval();
    $doc = $approval->approvable;
    $doc->update(['expires_at' => '2027-06-30']);

    openReview($admin, $approval)
        ->call('startEditingDocument')
        ->assertSet('documentDraft.expires_at', '2027-06-30')
        ->assertSet('documentDraft.is_permanent', false)
        ->set('documentDraft.is_permanent', true)
        ->call('saveDocumentDraft')
        ->assertHasNoErrors()
        ->assertSet('editingDocument', false);

    $audit = AuditLog::where('event', 'document.details_corrected')->firstOrFail();
    expect($doc->refresh()->expires_at)->toBeNull()
        ->and($audit->new_values)->toHaveKey('expires_at')
        ->and($audit->new_values['expires_at'])->toBeNull()
        ->and($audit->old_values['expires_at'])->toBe('2027-06-30');
});

it('cancel leaves the document untouched', function () {
    $admin = ApprovalFixtures::admin();
    $approval = ApprovalFixtures::documentApproval();
    $doc = $approval->approvable;
    $originalType = $doc->document_type_id;
    $passport = passportDocumentType();

    openReview($admin, $approval)
        ->call('startEditingDocument')
        ->set('documentDraft.document_type_id', $passport->id)
        ->set('documentDraft.is_permanent', false)
        ->set('documentDraft.expires_at', '2099-01-01')
        ->call('cancelEditingDocument')
        ->assertSet('editingDocument', false);

    expect((int) $doc->refresh()->document_type_id)->toBe((int) $originalType)
        ->and(AuditLog::where('event', 'document.details_corrected')->count())->toBe(0);
});

it('validation rejects an unknown document type', function () {
    $admin = ApprovalFixtures::admin();
    $approval = ApprovalFixtures::documentApproval();

    openReview($admin, $approval)
        ->call('startEditingDocument')
        ->set('documentDraft.document_type_id', 99999)
        ->call('saveDocumentDraft')
        ->assertHasErrors(['documentDraft.document_type_id'])
        ->assertSet('editingDocument', true);

    expect(AuditLog::where('event', 'document.details_corrected')->count())->toBe(0);
});

it('validation requires an expiry date when not marked permanent', function () {
    $admin = ApprovalFixtures::admin();
    $approval = ApprovalFixtures::documentApproval();

    openReview($admin, $approval)
        ->call('startEditingDocument')
        ->set('documentDraft.is_permanent', false)
        ->set('documentDraft.expires_at', '')
        ->call('saveDocumentDraft')
        ->assertHasErrors(['documentDraft.expires_at'])
        ->assertSet('editingDocument', true);
});

it('the edit button is not exposed for a non-document approval', function () {
    $admin = ApprovalFixtures::admin();
    $approval = ApprovalFixtures::calibreApproval();

    openReview($admin, $approval)
        ->assertDontSeeHtml('data-edit-document')
        ->assertSet('editingDocument', false);
});

it('navigating to a different approval closes an open document edit form', function () {
    $admin = ApprovalFixtures::admin();
    $docApproval = ApprovalFixtures::documentApproval();
    $otherApproval = ApprovalFixtures::calibreApproval();

    openReview($admin, $docApproval)
        ->call('startEditingDocument')
        ->assertSet('editingDocument', true)
        ->call('select', $otherApproval->id)
        ->assertSet('editingDocument', false);
});
