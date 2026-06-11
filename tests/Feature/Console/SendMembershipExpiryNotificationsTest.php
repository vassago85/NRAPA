<?php

use App\Mail\MembershipExpiry;
use App\Models\Membership;
use App\Models\MembershipRenewalReminder;
use App\Models\MembershipType;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    $this->annualType = MembershipType::create([
        'slug' => 'standard',
        'name' => 'Standard Membership',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 500.00,
        'is_active' => true,
    ]);

    $this->lifetimeType = MembershipType::create([
        'slug' => 'lifetime',
        'name' => 'Lifetime Membership',
        'duration_type' => 'lifetime',
        'expiry_rule' => 'none',
        'requires_renewal' => false,
        'pricing_model' => 'once_off',
        'price' => 5000.00,
        'is_active' => true,
    ]);
});

function makeMember(?string $email = null): User
{
    return User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'email_verified_at' => now(),
        'email' => $email ?? fake()->unique()->safeEmail(),
    ]);
}

function makeMembership(User $user, MembershipType $type, ?\Carbon\CarbonInterface $expiresAt, string $status = 'active'): Membership
{
    return Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => $status,
        'applied_at' => now()->subMonths(11),
        'approved_at' => now()->subMonths(11),
        'activated_at' => now()->subMonths(11),
        'expires_at' => $expiresAt,
    ]);
}

test('sends thirty_days reminder when membership expires in 30 days', function () {
    $user = makeMember();
    $membership = makeMembership($user, $this->annualType, now()->addDays(30));

    Artisan::call('nrapa:send-membership-expiry-notifications');

    Mail::assertQueued(MembershipExpiry::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email)
            && $mail->kind === MembershipRenewalReminder::KIND_THIRTY_DAYS;
    });

    expect(MembershipRenewalReminder::where('membership_id', $membership->id)->count())->toBe(1);
});

test('sends seven_days reminder when membership expires in 7 days', function () {
    $user = makeMember();
    $membership = makeMembership($user, $this->annualType, now()->addDays(7));

    Artisan::call('nrapa:send-membership-expiry-notifications');

    Mail::assertQueued(MembershipExpiry::class, function ($mail) {
        return $mail->kind === MembershipRenewalReminder::KIND_SEVEN_DAYS;
    });

    expect(MembershipRenewalReminder::where('membership_id', $membership->id)->where('kind', 'seven_days')->exists())->toBeTrue();
});

test('compresses on first run: imported member already at 5 days only gets the seven_days notice, not thirty_days', function () {
    $user = makeMember();
    $membership = makeMembership($user, $this->annualType, now()->addDays(5));

    Artisan::call('nrapa:send-membership-expiry-notifications');

    expect(MembershipRenewalReminder::where('membership_id', $membership->id)->pluck('kind')->all())
        ->toBe(['seven_days']);

    Mail::assertQueuedCount(1);
});

test('sends expired reminder for newly expired membership inside grace period', function () {
    $user = makeMember();
    $membership = makeMembership($user, $this->annualType, now()->subDays(3));

    Artisan::call('nrapa:send-membership-expiry-notifications');

    Mail::assertQueued(MembershipExpiry::class, function ($mail) {
        return $mail->kind === MembershipRenewalReminder::KIND_EXPIRED;
    });
});

test('does not re-send the same bucket on a second run the same day', function () {
    $user = makeMember();
    $membership = makeMembership($user, $this->annualType, now()->addDays(7));

    Artisan::call('nrapa:send-membership-expiry-notifications');
    Artisan::call('nrapa:send-membership-expiry-notifications');

    Mail::assertQueuedCount(1);
    expect(MembershipRenewalReminder::where('membership_id', $membership->id)->count())->toBe(1);
});

test('skips lifetime memberships entirely', function () {
    $user = makeMember();
    makeMembership($user, $this->lifetimeType, null);

    Artisan::call('nrapa:send-membership-expiry-notifications');

    Mail::assertNothingQueued();
});

test('skips members with placeholder phone-fallback email', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'email' => '0827654321@phone.nrapa.co.za',
        'email_verified_at' => now(),
    ]);
    makeMembership($user, $this->annualType, now()->addDays(7));

    Artisan::call('nrapa:send-membership-expiry-notifications');

    Mail::assertNothingQueued();
});

test('skips members who have opted out via notify_membership_expiry = false', function () {
    $user = makeMember();
    makeMembership($user, $this->annualType, now()->addDays(7));

    NotificationPreference::create([
        'user_id' => $user->id,
        'notify_membership_expiry' => false,
    ]);

    Artisan::call('nrapa:send-membership-expiry-notifications');

    Mail::assertNothingQueued();
});

test('skips memberships expired beyond the grace period', function () {
    $user = makeMember();
    $graceDays = Membership::renewalGracePeriodDays();
    makeMembership($user, $this->annualType, now()->subDays($graceDays + 5), 'expired');

    Artisan::call('nrapa:send-membership-expiry-notifications');

    Mail::assertNothingQueued();
});

test('dry-run does not queue mail or write reminder rows', function () {
    $user = makeMember();
    $membership = makeMembership($user, $this->annualType, now()->addDays(7));

    Artisan::call('nrapa:send-membership-expiry-notifications', ['--dry-run' => true]);

    Mail::assertNothingQueued();
    expect(MembershipRenewalReminder::where('membership_id', $membership->id)->count())->toBe(0);
});

test('staggers queued sends so we do not burst Mailgun', function () {
    // Three members, all hitting the seven_days bucket today.
    $u1 = makeMember();
    $u2 = makeMember();
    $u3 = makeMember();
    makeMembership($u1, $this->annualType, now()->addDays(7));
    makeMembership($u2, $this->annualType, now()->addDays(7));
    makeMembership($u3, $this->annualType, now()->addDays(7));

    Artisan::call('nrapa:send-membership-expiry-notifications', ['--throttle' => 5]);

    // First mail goes out immediately (delay = null), the next two are delayed
    // by 5 and 10 seconds respectively.
    Mail::assertQueuedCount(3);

    $delays = collect();
    Mail::assertQueued(MembershipExpiry::class, function ($mail) use ($delays) {
        $delays->push($mail->delay);
        return true;
    });

    $secs = $delays
        ->map(fn ($d) => $d instanceof \DateTimeInterface
            ? max(0, $d->getTimestamp() - now()->getTimestamp())
            : (int) ($d ?? 0))
        ->sort()
        ->values()
        ->all();

    expect($secs[0])->toBe(0);
    expect($secs[1])->toBeGreaterThanOrEqual(4)->toBeLessThanOrEqual(6);
    expect($secs[2])->toBeGreaterThanOrEqual(9)->toBeLessThanOrEqual(11);
});

test('throttle=0 disables staggering', function () {
    $u1 = makeMember();
    $u2 = makeMember();
    makeMembership($u1, $this->annualType, now()->addDays(7));
    makeMembership($u2, $this->annualType, now()->addDays(7));

    Artisan::call('nrapa:send-membership-expiry-notifications', ['--throttle' => 0]);

    Mail::assertQueuedCount(2);
    Mail::assertQueued(MembershipExpiry::class, function ($mail) {
        return $mail->delay === null || $mail->delay === 0;
    });
});
