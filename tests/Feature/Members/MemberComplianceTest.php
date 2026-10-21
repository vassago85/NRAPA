<?php

declare(strict_types=1);

use App\Models\DocumentType;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ShootingActivity;
use App\Models\User;
use App\Support\Members\MemberCompliance;
use App\Support\Members\RequirementState;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Phase 1 fixtures. The Phase 1 brief enumerates the five scenarios that the
 * resolver must give the same answer for across the dashboard, /endorsements
 * and the dedicated-status card:
 *
 *   1. an annual member who is compliant,
 *   2. an annual member short on activities,
 *   3. a lifetime member,
 *   4. an exempt member (honorary in our case; Phase 1 Q6 answer),
 *   5. a member with an expired ID document.
 *
 * The acceptance assertion the brief calls out explicitly:
 *   "no fixture renders '0/0' or a tick against a not_applicable requirement."
 *
 * These tests also assert that `MemberCompliance::for()` is cached per request,
 * so three Blade computed properties reading the same requirement produce one
 * query path, not three.
 */

/**
 * Seed the common configuration used across the fixtures. Pest tests may call
 * this at the top of each `it(...)` so the DB is primed consistently.
 */
function phase1Seed(): array
{
    DocumentType::create([
        'slug' => 'identity-document',
        'name' => 'ID',
        'description' => 'Identity document',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $annualDedicated = MembershipType::create([
        'slug' => 'test-annual-dedicated',
        'name' => 'Annual Dedicated',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 900,
        'renewal_price' => 800,
        'allows_dedicated_status' => true,
        'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
        'requires_knowledge_test' => true,
        'is_active' => true,
    ]);

    $lifetime = MembershipType::create([
        'slug' => 'test-lifetime',
        'name' => 'Lifetime',
        'duration_type' => 'lifetime',
        'requires_renewal' => false,
        'expiry_rule' => 'none',
        'pricing_model' => 'once_off',
        'initial_price' => 15000,
        'allows_dedicated_status' => true,
        'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
        'requires_knowledge_test' => true,
        'is_active' => true,
    ]);

    $honorary = MembershipType::create([
        'slug' => 'test-honorary',
        'name' => 'Honorary',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => false,
        'pricing_model' => 'none',
        'initial_price' => 0,
        'allows_dedicated_status' => true,
        'is_honorary' => true,
        'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
        'requires_knowledge_test' => false,
        'is_active' => true,
    ]);

    return [
        'annualDedicated' => $annualDedicated,
        'lifetime' => $lifetime,
        'honorary' => $honorary,
    ];
}

/**
 * Build a member with the supplied type, backdated so they are NOT in their
 * join-year (which would otherwise flip the activities requirement to exempt).
 */
function memberOnType(MembershipType $type, array $overrides = []): User
{
    $user = User::factory()->create($overrides + ['role' => 'member']);

    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => $type->isLifetime() ? null : now()->addMonths(6),
    ]);

    return $user;
}

/**
 * Attach a verified identity-document to the user, with an optional expiry.
 */
function uploadIdFor(User $user, ?\DateTimeInterface $expiresAt = null, string $status = 'verified'): MemberDocument
{
    $type = DocumentType::where('slug', 'identity-document')->firstOrFail();
    return MemberDocument::create([
        'user_id' => $user->id,
        'document_type_id' => $type->id,
        'file_path' => 'docs/'.$user->id.'.pdf',
        'original_filename' => 'id.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'status' => $status,
        'uploaded_at' => now()->subDays(10),
        'verified_at' => $status === 'verified' ? now()->subDays(5) : null,
        'expires_at' => $expiresAt,
    ]);
}

/**
 * Approve N shooting activities in a specified year's Jan–Oct window.
 */
function approvedActivitiesIn(User $user, int $year, int $count): void
{
    $date = \Carbon\Carbon::create($year, 6, 15);
    for ($i = 0; $i < $count; $i++) {
        ShootingActivity::create([
            'user_id' => $user->id,
            'activity_date' => $date->copy()->addDays($i),
            'activity_type' => 'range_session',
            'track' => 'sport',
            'status' => 'approved',
            'approved_at' => now(),
            'location' => 'Test range',
            'description' => 'test',
        ]);
    }
}

/**
 * Create a knowledge test and record a passed attempt against the given user.
 * Keeps the fixtures focused — the test's own content is not under scrutiny.
 */
function passKnowledgeTestFor(User $user): void
{
    $test = KnowledgeTest::create([
        'slug' => 'test-'.strtolower(\Illuminate\Support\Str::random(6)),
        'name' => 'Test Knowledge Test',
        'description' => 'Test',
        'pass_mark' => 70,
        'duration_minutes' => 30,
        'is_active' => true,
    ]);
    KnowledgeTestAttempt::create([
        'user_id' => $user->id,
        'knowledge_test_id' => $test->id,
        'started_at' => now()->subMinutes(30),
        'completed_at' => now(),
        'score' => 90,
        'passed' => true,
    ]);
}

beforeEach(function () {
    // The resolver caches per request. Tests build fresh state in each `it(...)`
    // so flush between tests to avoid cross-contamination.
    MemberCompliance::flush();
});

/* ------------------------------------------------------------------
 | Fixture 1 — compliant annual dedicated member
 | ------------------------------------------------------------------ */

it('resolves a fully compliant annual dedicated member as compliant', function () {
    $types = phase1Seed();
    $user = memberOnType($types['annualDedicated']);
    uploadIdFor($user);
    passKnowledgeTestFor($user);
    approvedActivitiesIn($user, now()->year - 1, 2); // previous year window qualifies this year

    $result = MemberCompliance::for($user);

    expect($result->isCompliant())->toBeTrue()
        ->and($result->overall())->toBe('compliant')
        ->and($result->isDedicatedEligible)->toBeTrue()
        ->and($result->showsDedicatedSurfaces)->toBeTrue();

    expect($result->requirement('membership')->state)->toBe(RequirementState::Met)
        ->and($result->requirement('profile_phone')->state)->toBe(RequirementState::Met)
        ->and($result->requirement('id_document')->state)->toBe(RequirementState::Met)
        ->and($result->requirement('knowledge_test')->state)->toBe(RequirementState::Met)
        ->and($result->requirement('activities')->state)->toBe(RequirementState::Met);
});

/* ------------------------------------------------------------------
 | Fixture 2 — annual dedicated member short on activities
 | ------------------------------------------------------------------ */

it('resolves an annual dedicated member short on activities as incomplete', function () {
    $types = phase1Seed();
    $user = memberOnType($types['annualDedicated']);
    uploadIdFor($user);
    passKnowledgeTestFor($user);
    // zero approved activities

    $result = MemberCompliance::for($user);

    expect($result->isCompliant())->toBeFalse()
        ->and($result->overall())->toBe('incomplete')
        ->and($result->hasBlocking())->toBeTrue();

    $activities = $result->requirement('activities');
    expect($activities->state)->toBe(RequirementState::NotMet)
        ->and($activities->meta['required'])->toBe(2)
        ->and($activities->meta['approved_count'])->toBe(0);
});

/* ------------------------------------------------------------------
 | Fixture 3 — lifetime member (Phase 1 Q1 — "always compliant", no ratio)
 | ------------------------------------------------------------------ */

it('resolves a lifetime dedicated member as compliant with activities not_applicable', function () {
    $types = phase1Seed();
    $user = memberOnType($types['lifetime']);
    uploadIdFor($user);
    passKnowledgeTestFor($user);

    $result = MemberCompliance::for($user);
    $activities = $result->requirement('activities');

    expect($result->isLifetime)->toBeTrue()
        ->and($result->isCompliant())->toBeTrue()
        ->and($activities->state)->toBe(RequirementState::NotApplicable)
        ->and($activities->reason)->toBe('Lifetime members are always compliant.')
        ->and($activities->meta['is_lifetime'])->toBeTrue();

    // Phase 1 brief: "not_applicable and exempt are never displayed as 'complete', '0/0' or a tick."
    expect($activities->state->allowsRatioOrTick())->toBeFalse();
});

/* ------------------------------------------------------------------
 | Fixture 4 — honorary member (Phase 1 Q6 — new is_honorary flag)
 | ------------------------------------------------------------------ */

it('resolves an honorary member as compliant via is_honorary attribute', function () {
    $types = phase1Seed();
    $user = memberOnType($types['honorary']);
    uploadIdFor($user);
    // Honorary type does not require the knowledge test.

    $result = MemberCompliance::for($user);
    $activities = $result->requirement('activities');

    expect($result->isHonorary)->toBeTrue()
        ->and($result->isLifetime)->toBeFalse()
        ->and($result->isCompliant())->toBeTrue()
        ->and($activities->state)->toBe(RequirementState::NotApplicable)
        ->and($activities->reason)->toBe('Honorary members are always compliant.')
        ->and($activities->meta['is_honorary'])->toBeTrue();

    expect($result->requirement('knowledge_test')->state)->toBe(RequirementState::NotApplicable);
});

/* ------------------------------------------------------------------
 | Fixture 4b — non-dedicated type (Basic etc.) — activity requirement
 | does not apply. Compliance treats activities as NotApplicable, same as
 | lifetime/honorary, so the clarification-letter audience resolves cleanly.
 | ------------------------------------------------------------------ */

it('resolves a non-dedicated-status member as compliant with activities not_applicable', function () {
    phase1Seed();
    $basic = MembershipType::create([
        'slug' => 'test-basic',
        'name' => 'Basic',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 300,
        'renewal_price' => 300,
        'allows_dedicated_status' => false,
        'dedicated_type' => null,
        'is_active' => true,
    ]);

    $user = memberOnType($basic);
    uploadIdFor($user);

    $result = MemberCompliance::for($user);
    $activities = $result->requirement('activities');

    expect($result->isLifetime)->toBeFalse()
        ->and($result->isHonorary)->toBeFalse()
        ->and($result->isDedicatedEligible)->toBeFalse()
        ->and($activities->state)->toBe(RequirementState::NotApplicable)
        ->and($activities->reason)->toContain('does not include the activity requirement')
        ->and($activities->meta['allows_dedicated_status'])->toBeFalse();

    expect($activities->state->allowsRatioOrTick())->toBeFalse();
});

/* ------------------------------------------------------------------
 | Fixture 5 — member with an expired ID document
 | ------------------------------------------------------------------ */

it('resolves a member with an expired ID document as not_met on id_document', function () {
    $types = phase1Seed();
    $user = memberOnType($types['annualDedicated']);
    uploadIdFor($user, expiresAt: now()->subDays(10));
    passKnowledgeTestFor($user);
    approvedActivitiesIn($user, now()->year - 1, 2);

    $result = MemberCompliance::for($user);
    $idDoc = $result->requirement('id_document');

    expect($idDoc->state)->toBe(RequirementState::NotMet)
        ->and($idDoc->meta['status'])->toBe('expired')
        ->and($result->isCompliant())->toBeFalse()
        ->and($result->hasBlocking())->toBeTrue();
});

/* ------------------------------------------------------------------
 | Cross-cutting invariants from the Phase 1 brief
 | ------------------------------------------------------------------ */

it('never allows a ratio or tick for not_applicable or exempt requirements', function () {
    foreach ([RequirementState::NotApplicable, RequirementState::Exempt] as $state) {
        expect($state->allowsRatioOrTick())
            ->toBeFalse("state {$state->value} must not render a ratio or tick");
    }
});

it('caches the compliance result per request for the same user', function () {
    $types = phase1Seed();
    $user = memberOnType($types['annualDedicated']);
    uploadIdFor($user);

    $a = MemberCompliance::for($user);
    $b = MemberCompliance::for($user);

    // Identity, not just equality — proves the per-request cache is in play.
    expect($a)->toBe($b);
});

it('flush() invalidates the cache so a subsequent read sees changes', function () {
    $types = phase1Seed();
    $user = memberOnType($types['annualDedicated']);

    $before = MemberCompliance::for($user);
    expect($before->requirement('id_document')->state)->toBe(RequirementState::NotMet);

    uploadIdFor($user);
    MemberCompliance::flush($user->id);

    $after = MemberCompliance::for($user);
    expect($after->requirement('id_document')->state)->toBe(RequirementState::Met);
});

/* ------------------------------------------------------------------
 | Q4b hybrid: `transfer`-like type keeps the Dedicated Status surfaces
 | ------------------------------------------------------------------ */

it('shows dedicated surfaces for a type where allows=true but dedicated_type is null', function () {
    phase1Seed();
    $transfer = MembershipType::create([
        'slug' => 'test-transfer',
        'name' => 'Transfer',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 100,
        'renewal_price' => 100,
        'allows_dedicated_status' => true,
        'dedicated_type' => null,
        'is_active' => true,
    ]);

    $user = memberOnType($transfer);
    $result = MemberCompliance::for($user);

    // Q4b: OR for display, AND for actionable eligibility.
    expect($result->showsDedicatedSurfaces)->toBeTrue()
        ->and($result->isDedicatedEligible)->toBeFalse();
});

/* ------------------------------------------------------------------
 | Legacy shims deliver the same compliance decision as the resolver
 | ------------------------------------------------------------------ */

it('delegates ShootingActivity::complianceSummary through the resolver for lifetime members', function () {
    $types = phase1Seed();
    $user = memberOnType($types['lifetime']);

    $summary = ShootingActivity::complianceSummary($user);

    // Pre-Phase-1 this returned is_compliant_now=false for a lifetime member
    // with 0 activities. Now it mirrors the resolver — "always compliant".
    expect($summary['is_compliant_now'])->toBeTrue()
        ->and($summary['explainer'])->toContain('Lifetime');
});

it('delegates EndorsementRequest::checkActivityRequirements through the resolver for lifetime members', function () {
    $types = phase1Seed();
    $user = memberOnType($types['lifetime']);

    $check = \App\Models\EndorsementRequest::checkActivityRequirements($user);

    expect($check['met'])->toBeTrue()
        ->and($check['period'])->toBe('Lifetime');
});

it('includes id-copy in the missing-documents check (Phase 1 Q8)', function () {
    $types = phase1Seed();

    // Seed id-copy as an alternate slug — the old helper hardcoded only
    // ['identity-document', 'id-document'] so a user whose only ID was
    // uploaded as id-copy was reported missing. The resolver uses the full
    // ID_DOCUMENT_SLUGS list.
    $idCopyType = DocumentType::create([
        'slug' => 'id-copy',
        'name' => 'ID Copy',
        'description' => 'Alternate ID slug',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $user = memberOnType($types['annualDedicated']);
    MemberDocument::create([
        'user_id' => $user->id,
        'document_type_id' => $idCopyType->id,
        'file_path' => 'docs/id-copy.pdf',
        'original_filename' => 'id-copy.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'status' => 'verified',
        'uploaded_at' => now()->subDays(5),
        'verified_at' => now()->subDays(2),
    ]);

    $result = MemberCompliance::for($user);
    expect($result->requirement('id_document')->state)->toBe(RequirementState::Met)
        ->and(\App\Models\EndorsementRequest::getMissingRequiredDocuments($user))->toBeEmpty();
});
