<?php

use App\Enums\ApprovalState;
use App\Models\Approval;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ApprovalFixtures;

uses(RefreshDatabase::class);

/**
 * The two-admin rule for endorsements was dropped; the artisan command
 * `nrapa:promote-first-signatures` walks every approval that was waiting at
 * "1 of 2" and finishes it on behalf of the admin who signed first, so the
 * downstream work (letters, member emails, audit entries) still runs.
 */

/**
 * Build an endorsement that was approved by $signer under the old two-admin
 * rule and is now orphaned at state=waiting_on_admin with first_approved_by set.
 */
function orphanedEndorsement(\App\Models\User $signer): Approval
{
    ApprovalFixtures::stubDocumentRendering();
    $approval = ApprovalFixtures::endorsementApproval();

    $approval->forceFill([
        'requires_second_approval' => false,
        'first_approved_by' => $signer->id,
        'first_approved_at' => now()->subMinutes(5),
    ])->saveQuietly();

    return $approval->refresh();
}

it('promotes orphaned first-signature endorsements on behalf of the signer', function () {
    $signer = ApprovalFixtures::admin();
    $approval = orphanedEndorsement($signer);

    $this->artisan('nrapa:promote-first-signatures')
        ->expectsOutputToContain('Found 1 open approval(s)')
        ->expectsOutputToContain('Promoted 1')
        ->assertSuccessful();

    $approval->refresh();
    expect($approval->state)->toBe(ApprovalState::Approved)
        ->and((int) $approval->decided_by)->toBe((int) $signer->id)
        ->and(AuditLog::query()->where('event', 'approval.approved')->where('user_id', $signer->id)->count())->toBe(1);
});

it('dry-run reports what would happen without touching the approval', function () {
    $signer = ApprovalFixtures::admin();
    $approval = orphanedEndorsement($signer);

    $this->artisan('nrapa:promote-first-signatures', ['--dry-run' => true])
        ->expectsOutputToContain('[dry run]')
        ->expectsOutputToContain('would promote approval')
        ->assertSuccessful();

    expect($approval->refresh()->state)->toBe(ApprovalState::WaitingOnAdmin)
        ->and(AuditLog::query()->where('event', 'approval.approved')->count())->toBe(0);
});

it('says nothing to do when no approval has a pending first signature', function () {
    ApprovalFixtures::endorsementApproval();

    $this->artisan('nrapa:promote-first-signatures')
        ->expectsOutputToContain('Nothing to promote')
        ->assertSuccessful();
});

it('leaves other open approvals alone', function () {
    $signer = ApprovalFixtures::admin();
    $endorsement = orphanedEndorsement($signer);
    $otherApproval = ApprovalFixtures::documentApproval();

    $this->artisan('nrapa:promote-first-signatures')->assertSuccessful();

    expect($endorsement->refresh()->state)->toBe(ApprovalState::Approved)
        ->and($otherApproval->refresh()->state)->toBe(ApprovalState::WaitingOnAdmin);
});
