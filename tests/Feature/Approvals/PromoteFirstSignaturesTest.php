<?php

use App\Enums\ApprovalState;
use App\Models\Approval;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\ApprovalFixtures;

uses(RefreshDatabase::class);

/**
 * Runs the artisan command and returns its captured output. We avoid
 * $this->artisan(...)->expectsOutputToContain(...) for the branches whose
 * log line is written from inside a chunkById closure, because that path
 * doesn't always reach the PendingCommand output buffer in time for the
 * assertion — Artisan::call() + Artisan::output() is the source of truth.
 */
function runPromote(array $options = []): string
{
    Artisan::call('nrapa:promote-first-signatures', $options);

    return Artisan::output();
}

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

it('respects the --type filter so only that type is promoted', function () {
    $signer = ApprovalFixtures::admin();
    $endorsement = orphanedEndorsement($signer);

    // Orphan a document approval the same way so both qualify under the base query.
    $document = ApprovalFixtures::documentApproval();
    $document->forceFill([
        'requires_second_approval' => false,
        'first_approved_by' => $signer->id,
        'first_approved_at' => now()->subMinutes(5),
    ])->saveQuietly();

    $this->artisan('nrapa:promote-first-signatures', ['--type' => 'endorsement'])
        ->expectsOutputToContain('Found 1 open approval')
        ->assertSuccessful();

    expect($endorsement->refresh()->state)->toBe(ApprovalState::Approved)
        ->and($document->refresh()->state)->toBe(ApprovalState::WaitingOnAdmin);
});

it('skips an approval whose first signer is no longer an admin', function () {
    $signer = ApprovalFixtures::admin();
    $approval = orphanedEndorsement($signer);

    // The admin was demoted to a member after signing but before deploy.
    $signer->forceFill([
        'role' => \App\Models\User::ROLE_MEMBER,
        'is_admin' => false,
        'admin_type' => null,
    ])->saveQuietly();

    $output = runPromote();

    expect($output)->toContain('skip approval', 'no longer an admin', 'Promoted 0, skipped 1')
        ->and($approval->refresh()->state)->toBe(ApprovalState::WaitingOnAdmin);
});

it('skips an approval whose underlying record has been removed', function () {
    $signer = ApprovalFixtures::admin();
    $approval = orphanedEndorsement($signer);

    // Point the approvable at a row that no longer exists; this models the
    // "the underlying endorsement was hard-deleted" edge case without having
    // to pierce any cascade rules on the approvals table.
    $approval->forceFill(['approvable_id' => 999999])->saveQuietly();

    // Sanity: eager-loaded approvable should be null for the missing row.
    expect(Approval::query()->where('id', $approval->id)->with('approvable')->first()->approvable)->toBeNull();

    $output = runPromote();

    expect($output)->toContain('skip approval', 'underlying record is gone', 'Promoted 0, skipped 1')
        ->and($approval->refresh()->state)->toBe(ApprovalState::WaitingOnAdmin);
});
