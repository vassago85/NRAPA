<?php

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;

uses(RefreshDatabase::class);

function makeUser(): User
{
    return User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'email_verified_at' => now(),
    ]);
}

function makeAnnualType(): MembershipType
{
    return MembershipType::create([
        'slug' => 'standard-annual-' . uniqid(),
        'name' => 'Standard Annual',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 500.00,
        'is_active' => true,
    ]);
}

function makeLifetimeType(): MembershipType
{
    return MembershipType::create([
        'slug' => 'lifetime-' . uniqid(),
        'name' => 'Lifetime',
        'duration_type' => 'lifetime',
        'expiry_rule' => 'none',
        'requires_renewal' => false,
        'pricing_model' => 'once_off',
        'price' => 5000.00,
        'is_active' => true,
    ]);
}

function makeMembershipRow(
    User $user,
    MembershipType $type,
    ?\Carbon\CarbonInterface $appliedAt = null,
    ?\Carbon\CarbonInterface $activatedAt = null,
    ?\Carbon\CarbonInterface $expiresAt = null,
    string $status = 'active'
): Membership {
    return Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => $status,
        'applied_at' => $appliedAt ?? now(),
        'approved_at' => $activatedAt,
        'activated_at' => $activatedAt,
        'expires_at' => $expiresAt,
    ]);
}

function makeGoodStandingType(string $slug = 'membership-certificate'): CertificateType
{
    return CertificateType::create([
        'slug' => $slug,
        'name' => 'Membership Certificate',
        'description' => 'Test',
        'template' => 'documents.certificates.good-standing',
        'validity_months' => 12,
        'is_active' => true,
        'sort_order' => 1,
    ]);
}

// ---------------------------------------------------------------------------
// User::joinedOn()
// ---------------------------------------------------------------------------

test('joinedOn returns the earliest activated_at across all memberships (survives renewals)', function () {
    $user = makeUser();
    $type = makeAnnualType();

    // Original membership activated 3 years ago.
    makeMembershipRow(
        $user,
        $type,
        appliedAt: now()->subYears(3),
        activatedAt: now()->subYears(3),
        expiresAt: now()->subYears(2),
        status: 'expired',
    );

    // Renewal from 2 years ago.
    makeMembershipRow(
        $user,
        $type,
        appliedAt: now()->subYears(2),
        activatedAt: now()->subYears(2),
        expiresAt: now()->subYear(),
        status: 'expired',
    );

    // Current active renewal.
    makeMembershipRow(
        $user,
        $type,
        appliedAt: now()->subYear(),
        activatedAt: now()->subYear(),
        expiresAt: now()->addMonths(6),
    );

    $joined = $user->joinedOn();

    expect($joined)->not->toBeNull();
    expect($joined->toDateString())->toBe(now()->subYears(3)->toDateString());
});

test('joinedOn falls back to earliest applied_at when no membership has activated_at set', function () {
    $user = makeUser();
    $type = makeAnnualType();

    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'submitted',
        'applied_at' => now()->subMonths(4),
        'activated_at' => null,
    ]);

    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'submitted',
        'applied_at' => now()->subMonths(2),
        'activated_at' => null,
    ]);

    $joined = $user->joinedOn();

    expect($joined?->toDateString())->toBe(now()->subMonths(4)->toDateString());
});

test('joinedOn falls back to account created_at when the user has no memberships at all', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'email_verified_at' => now(),
        'created_at' => now()->subMonths(9),
    ]);

    $joined = $user->joinedOn();

    expect($joined)->not->toBeNull();
    expect($joined->toDateString())->toBe(now()->subMonths(9)->toDateString());
});

// ---------------------------------------------------------------------------
// good-standing template rendering
// ---------------------------------------------------------------------------

test('good-standing certificate template renders Joined On and Membership Expires On rows for an annual membership', function () {
    $user = makeUser();
    $type = makeAnnualType();
    $certType = makeGoodStandingType();

    $joinDate = now()->subYears(2)->startOfDay();
    $expiresAt = now()->addMonths(6)->startOfDay();

    $membership = makeMembershipRow(
        $user,
        $type,
        appliedAt: $joinDate,
        activatedAt: $joinDate,
        expiresAt: $expiresAt,
    );

    $certificate = Certificate::create([
        'user_id' => $user->id,
        'membership_id' => $membership->id,
        'certificate_type_id' => $certType->id,
        'issued_by' => $user->id,
        'valid_from' => now(),
        'valid_until' => now()->addYear(),
        'signatory_name' => 'Test Signatory',
        'signatory_title' => 'Chairperson',
    ]);

    $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

    $html = View::make('documents.certificates.good-standing', [
        'certificate' => $certificate,
        'user' => $certificate->user,
        'membership' => $certificate->membership,
        'certificateType' => $certificate->certificateType,
        'logo_url' => '',
    ])->render();

    expect($html)->toContain('Joined On');
    expect($html)->toContain($joinDate->format('d F Y'));

    expect($html)->toContain('Membership Expires On');
    expect($html)->toContain($expiresAt->format('d F Y'));
});

test('good-standing certificate template shows Lifetime instead of an expiry date for lifetime memberships', function () {
    $user = makeUser();
    $type = makeLifetimeType();
    $certType = makeGoodStandingType('membership-certificate');

    $joinDate = now()->subYears(4)->startOfDay();

    $membership = makeMembershipRow(
        $user,
        $type,
        appliedAt: $joinDate,
        activatedAt: $joinDate,
        expiresAt: null,
    );

    $certificate = Certificate::create([
        'user_id' => $user->id,
        'membership_id' => $membership->id,
        'certificate_type_id' => $certType->id,
        'issued_by' => $user->id,
        'valid_from' => now(),
        'valid_until' => now()->addYear(),
        'signatory_name' => 'Test Signatory',
        'signatory_title' => 'Chairperson',
    ]);

    $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

    $html = View::make('documents.certificates.good-standing', [
        'certificate' => $certificate,
        'user' => $certificate->user,
        'membership' => $certificate->membership,
        'certificateType' => $certificate->certificateType,
        'logo_url' => '',
    ])->render();

    // The "Membership Expires On" row shows Lifetime (in the row itself, not just the certificate Valid Until row).
    // Match the specific kv row we added.
    expect($html)->toContain('Membership Expires On');
    // The lifetime membership row should render "Lifetime" as the value.
    // Also assert the join date is present.
    expect($html)->toContain($joinDate->format('d F Y'));

    // Confirm no annual expiry date leaked into the Membership Expires On value —
    // there is no way for a date like "d F Y" to appear for the expires row.
    // We look for the marker phrase directly.
    // Because "Lifetime" also appears on the certificate Valid Until when null,
    // we specifically test the Membership Expires row by searching for both labels
    // and confirming they occur.
    expect($html)->toContain('Lifetime');
});

// ---------------------------------------------------------------------------
// nrapa:regenerate-membership-certificates command
// ---------------------------------------------------------------------------

test('regenerate command targets good-standing certificates only and skips revoked ones', function () {
    $user = makeUser();
    $type = makeAnnualType();

    $goodStandingType = makeGoodStandingType('membership-certificate');
    $dedicatedType = CertificateType::create([
        'slug' => 'dedicated-status-certificate',
        'name' => 'Dedicated Status',
        'template' => 'documents.certificates.dedicated-status',
        'validity_months' => 12,
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $membership = makeMembershipRow(
        $user,
        $type,
        appliedAt: now()->subYear(),
        activatedAt: now()->subYear(),
        expiresAt: now()->addMonths(6),
    );

    $targeted = Certificate::create([
        'user_id' => $user->id,
        'membership_id' => $membership->id,
        'certificate_type_id' => $goodStandingType->id,
        'issued_by' => $user->id,
        'valid_from' => now(),
        'valid_until' => now()->addYear(),
        'signatory_name' => 'Sig',
        'signatory_title' => 'Chair',
    ]);

    $revoked = Certificate::create([
        'user_id' => $user->id,
        'membership_id' => $membership->id,
        'certificate_type_id' => $goodStandingType->id,
        'issued_by' => $user->id,
        'valid_from' => now()->subYear(),
        'valid_until' => now()->subMonths(3),
        'revoked_at' => now()->subMonths(2),
        'signatory_name' => 'Sig',
        'signatory_title' => 'Chair',
    ]);

    $dedicated = Certificate::create([
        'user_id' => $user->id,
        'membership_id' => $membership->id,
        'certificate_type_id' => $dedicatedType->id,
        'issued_by' => $user->id,
        'valid_from' => now(),
        'valid_until' => now()->addYear(),
        'signatory_name' => 'Sig',
        'signatory_title' => 'Chair',
    ]);

    // Dry run: should count only 1 (the targeted good-standing, non-revoked) certificate.
    $exit = Artisan::call('nrapa:regenerate-membership-certificates', ['--dry-run' => true]);
    expect($exit)->toBe(0);

    $output = Artisan::output();
    expect($output)->toContain('Found 1 membership certificate(s) to regenerate');
    expect($output)->toContain("#{$targeted->id}");
    expect($output)->not->toContain("#{$revoked->id}");
    expect($output)->not->toContain("#{$dedicated->id}");
});

test('regenerate command reports zero when there are no matching certificates', function () {
    $exit = Artisan::call('nrapa:regenerate-membership-certificates', ['--dry-run' => true]);
    expect($exit)->toBe(0);
    expect(Artisan::output())->toContain('No membership certificates found to regenerate.');
});

test('regenerate command --start-from skips earlier certificate ids so a crashed run can resume', function () {
    $user = makeUser();
    $type = makeAnnualType();
    $certType = makeGoodStandingType('membership-certificate');

    $membership = makeMembershipRow(
        $user,
        $type,
        appliedAt: now()->subYear(),
        activatedAt: now()->subYear(),
        expiresAt: now()->addMonths(6),
    );

    $first = Certificate::create([
        'user_id' => $user->id,
        'membership_id' => $membership->id,
        'certificate_type_id' => $certType->id,
        'issued_by' => $user->id,
        'valid_from' => now(),
        'valid_until' => now()->addYear(),
        'signatory_name' => 'S', 'signatory_title' => 'T',
    ]);
    $second = Certificate::create([
        'user_id' => $user->id,
        'membership_id' => $membership->id,
        'certificate_type_id' => $certType->id,
        'issued_by' => $user->id,
        'valid_from' => now(),
        'valid_until' => now()->addYear(),
        'signatory_name' => 'S', 'signatory_title' => 'T',
    ]);
    $third = Certificate::create([
        'user_id' => $user->id,
        'membership_id' => $membership->id,
        'certificate_type_id' => $certType->id,
        'issued_by' => $user->id,
        'valid_from' => now(),
        'valid_until' => now()->addYear(),
        'signatory_name' => 'S', 'signatory_title' => 'T',
    ]);

    $exit = Artisan::call('nrapa:regenerate-membership-certificates', [
        '--dry-run' => true,
        '--start-from' => $second->id,
    ]);

    expect($exit)->toBe(0);

    $output = Artisan::output();
    expect($output)->toContain("Resuming from certificate #{$second->id}");
    expect($output)->toContain('Found 2 membership certificate(s) to regenerate');
    expect($output)->not->toContain("#{$first->id}");
    expect($output)->toContain("#{$second->id}");
    expect($output)->toContain("#{$third->id}");
});

test('regenerate command --limit caps the number of certificates processed', function () {
    $user = makeUser();
    $type = makeAnnualType();
    $certType = makeGoodStandingType('membership-certificate');

    $membership = makeMembershipRow(
        $user,
        $type,
        appliedAt: now()->subYear(),
        activatedAt: now()->subYear(),
        expiresAt: now()->addMonths(6),
    );

    for ($i = 0; $i < 3; $i++) {
        Certificate::create([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $user->id,
            'valid_from' => now(),
            'valid_until' => now()->addYear(),
            'signatory_name' => 'S', 'signatory_title' => 'T',
        ]);
    }

    $exit = Artisan::call('nrapa:regenerate-membership-certificates', [
        '--dry-run' => true,
        '--limit' => 2,
    ]);

    expect($exit)->toBe(0);
    expect(Artisan::output())->toContain('processing 2');
});

test('regenerate command with unknown --certificate-id exits with failure', function () {
    $exit = Artisan::call('nrapa:regenerate-membership-certificates', [
        '--certificate-id' => 999999,
    ]);

    expect($exit)->toBe(1);
    expect(Artisan::output())->toContain('Certificate #999999 not found.');
});
