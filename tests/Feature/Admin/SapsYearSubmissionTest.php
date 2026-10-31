<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\SapsYearSubmission;
use App\Models\ShootingActivity;
use App\Models\User;
use App\Support\Members\ActivityComplianceCsvExport;
use App\Support\Members\MemberCompliance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

/**
 * SAPS year-submission lock behaviour.
 *
 * Verifies: locking writes an audit row, double-locking is blocked,
 * super-admin can unlock, and compliance/CSV only count activities
 * verified on or before the lock timestamp once a year is locked.
 */

function sapsMembershipType(): MembershipType
{
    return MembershipType::firstOrCreate(
        ['slug' => 'saps-test-annual'],
        [
            'name' => 'SAPS Test Annual',
            'duration_type' => 'annual',
            'duration_months' => 12,
            'requires_renewal' => true,
            'pricing_model' => 'annual',
            'initial_price' => 500,
            'renewal_price' => 400,
            'allows_dedicated_status' => true,
            'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
            'is_active' => true,
        ],
    );
}

function sapsMember(array $overrides = []): User
{
    $user = User::factory()->create($overrides + ['role' => User::ROLE_MEMBER]);
    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => sapsMembershipType()->id,
        'status' => 'active',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => now()->addMonths(6),
    ]);
    return $user;
}

/**
 * Create an approved activity with explicit activity_date and verified_at
 * so the lock-aware queries can filter correctly. Bypasses the Approvable
 * state machine on purpose — we are exercising the counting rule here.
 */
function sapsApprovedActivity(User $user, Carbon $activityDate, ?Carbon $verifiedAt = null): ShootingActivity
{
    $activity = ShootingActivity::create([
        'user_id' => $user->id,
        'activity_date' => $activityDate->toDateString(),
        'location' => 'SAPS Test Range',
        'status' => 'approved',
    ]);
    // verified_at is not in $fillable — set it directly and save.
    $activity->verified_at = $verifiedAt ?? now();
    $activity->save();
    return $activity;
}

beforeEach(function () {
    SapsYearSubmission::clearCache();
    MemberCompliance::flush();
    Cache::flush();

    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'admin_type' => User::ADMIN_TYPE_STANDARD,
        'is_admin' => true,
    ]);

    $this->superAdmin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'admin_type' => User::ADMIN_TYPE_SUPER,
        'is_admin' => true,
    ]);
});

it('admin can lock a year and the lock is written to the audit log', function () {
    Livewire::actingAs($this->admin)
        ->test('pages::admin.reports.index')
        ->call('submitSapsYear');

    $lock = SapsYearSubmission::activeLockFor((int) now()->year);

    expect($lock)->not->toBeNull()
        ->and($lock->year)->toBe((int) now()->year)
        ->and($lock->submitted_by)->toBe($this->admin->id)
        ->and($lock->locked_count)->toBe(0);

    $audit = AuditLog::where('event', 'admin.saps.year_submitted')
        ->where('auditable_type', SapsYearSubmission::class)
        ->where('auditable_id', $lock->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->user_id)->toBe($this->admin->id);
});

it('cannot double-lock the same year', function () {
    $year = (int) now()->year;

    SapsYearSubmission::create([
        'year' => $year,
        'submitted_at' => now(),
        'submitted_by' => $this->admin->id,
        'locked_count' => 0,
    ]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.reports.index')
        ->call('submitSapsYear')
        ->assertHasNoErrors();

    // Second lock blocked — row count stays at exactly 1 and no new audit
    // "year_submitted" row was written.
    expect(SapsYearSubmission::query()->where('year', $year)->count())->toBe(1)
        ->and(AuditLog::where('event', 'admin.saps.year_submitted')->count())->toBe(0);
});

it('super-admin can unlock a locked year via soft-delete', function () {
    $year = (int) now()->year;

    $lock = SapsYearSubmission::create([
        'year' => $year,
        'submitted_at' => now()->subHour(),
        'submitted_by' => $this->admin->id,
        'locked_count' => 3,
    ]);

    Livewire::actingAs($this->superAdmin)
        ->test('pages::admin.reports.index')
        ->call('unlockSapsYear');

    SapsYearSubmission::clearCache();

    expect(SapsYearSubmission::activeLockFor($year))->toBeNull()
        ->and(SapsYearSubmission::withTrashed()->find($lock->id)?->trashed())->toBeTrue();

    $audit = AuditLog::where('event', 'admin.saps.year_unlocked')->first();
    expect($audit)->not->toBeNull()
        ->and($audit->user_id)->toBe($this->superAdmin->id);
});

it('standard admin cannot unlock a locked year', function () {
    $year = (int) now()->year;

    SapsYearSubmission::create([
        'year' => $year,
        'submitted_at' => now()->subHour(),
        'submitted_by' => $this->admin->id,
        'locked_count' => 0,
    ]);

    // The Livewire action aborts with 403 when a non-super-admin tries it.
    Livewire::actingAs($this->admin)
        ->test('pages::admin.reports.index')
        ->call('unlockSapsYear')
        ->assertForbidden();

    SapsYearSubmission::clearCache();
    expect(SapsYearSubmission::activeLockFor($year))->not->toBeNull();
});

it('activities verified before the lock still count toward compliance', function () {
    $year = (int) now()->year;
    $user = sapsMember();

    // Approve two activities a couple of weeks ago.
    sapsApprovedActivity($user, Carbon::create($year, 5, 1), Carbon::create($year, 5, 2));
    sapsApprovedActivity($user, Carbon::create($year, 5, 10), Carbon::create($year, 5, 11));

    // Lock the year AFTER the approvals.
    SapsYearSubmission::create([
        'year' => $year,
        'submitted_at' => Carbon::create($year, 6, 1),
        'submitted_by' => $this->admin->id,
        'locked_count' => 2,
    ]);
    MemberCompliance::flush();

    $compliance = MemberCompliance::for($user);
    $activities = $compliance->requirement('activities');

    expect($activities?->meta['current_year_count'] ?? null)->toBe(2);
});

it('activities verified after the lock do not count toward compliance', function () {
    $year = (int) now()->year;
    $user = sapsMember();

    // One pre-lock activity so we can see the before/after difference.
    sapsApprovedActivity($user, Carbon::create($year, 5, 1), Carbon::create($year, 5, 2));

    // Lock the year.
    SapsYearSubmission::create([
        'year' => $year,
        'submitted_at' => Carbon::create($year, 6, 1),
        'submitted_by' => $this->admin->id,
        'locked_count' => 1,
    ]);
    SapsYearSubmission::clearCache();

    // Admin approves a late submission AFTER the lock.
    sapsApprovedActivity($user, Carbon::create($year, 5, 15), Carbon::create($year, 7, 1));
    MemberCompliance::flush();

    $compliance = MemberCompliance::for($user);
    $activities = $compliance->requirement('activities');

    // Only the pre-lock activity counts, even though two exist in the window.
    expect($activities?->meta['current_year_count'] ?? null)->toBe(1)
        ->and(ShootingActivity::where('user_id', $user->id)->where('status', 'approved')->count())->toBe(2);
});

it('CSV export counts match MemberCompliance after a lock', function () {
    $year = (int) now()->year;
    $user = sapsMember(['name' => 'Lock Tester']);

    sapsApprovedActivity($user, Carbon::create($year, 5, 1), Carbon::create($year, 5, 2));

    SapsYearSubmission::create([
        'year' => $year,
        'submitted_at' => Carbon::create($year, 6, 1),
        'submitted_by' => $this->admin->id,
        'locked_count' => 1,
    ]);
    SapsYearSubmission::clearCache();

    // Late approval that should NOT count.
    sapsApprovedActivity($user, Carbon::create($year, 5, 20), Carbon::create($year, 7, 10));

    $export = new ActivityComplianceCsvExport($this->admin, $year);
    $out = fopen('php://temp', 'r+');
    $export->write($out);
    rewind($out);
    $contents = stream_get_contents($out);
    fclose($out);

    // Two activities exist in Jan–Oct, only one is pre-lock. With the min
    // activity count set to 2 by SystemSetting default, this member reads
    // as "No" (one counted activity, two required). CSV splits "Lock Tester"
    // into first + last columns, so the row begins "Lock,Tester,".
    expect($contents)->toContain('Lock,Tester,')
        ->and($contents)->toContain(',No');
});

it('member submit page shows the late submission warning after 31 October', function () {
    // Simulate "now" past the Oct 31 deadline of the current test year.
    Carbon::setTestNow(Carbon::create((int) now()->year, 11, 15, 10));

    $user = sapsMember();

    Livewire::actingAs($user)
        ->test('pages::member.activities.submit')
        ->assertSeeText('The 31 October deadline has passed');

    Carbon::setTestNow();
});

it('member submit page shows the locked-year warning when the chosen date year is locked', function () {
    $year = (int) now()->year;
    SapsYearSubmission::create([
        'year' => $year,
        'submitted_at' => now()->subDay(),
        'submitted_by' => $this->admin->id,
        'locked_count' => 0,
    ]);
    SapsYearSubmission::clearCache();

    $user = sapsMember();

    Livewire::actingAs($user)
        ->test('pages::member.activities.submit')
        ->set('activity_date', Carbon::create($year, 5, 1)->toDateString())
        ->assertSeeText('has been submitted to SAPS');
});
