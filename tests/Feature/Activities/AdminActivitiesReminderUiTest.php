<?php

declare(strict_types=1);

use App\Mail\ActivityReminderMail;
use App\Models\ActivityReminder;
use App\Models\MemberMessage;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Smoke-level coverage for the admin activities page's year-end reminder
 * button. Renders the page, opens the modal, and fires `sendReminder` — the
 * route the admin actually uses from the UI.
 */

function uiSeedType(): MembershipType
{
    return MembershipType::create([
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
        'is_active' => true,
    ]);
}

function uiMember(MembershipType $type): User
{
    $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => now()->addMonths(6),
    ]);
    return $user;
}

beforeEach(function () {
    Mail::fake();
    $this->admin = User::factory()->create(['role' => User::ROLE_OWNER]);
    $this->actingAs($this->admin);
});

it('renders the admin activities page with the reminder modal state ready', function () {
    uiMember(uiSeedType());

    Livewire::test('pages::admin.activities.index')
        ->assertSuccessful()
        ->assertSet('showReminderModal', false)
        ->call('openReminderModal')
        ->assertSet('showReminderModal', true)
        // Modal body shows the recipient count and the "Send to X member(s)" button copy.
        ->assertSee('Send to 1 member');
});

it('opens the modal, fires sendReminder, and writes mail + inbox + idempotency', function () {
    $type = uiSeedType();
    $member = uiMember($type);

    Livewire::test('pages::admin.activities.index')
        ->call('openReminderModal')
        ->assertSet('showReminderModal', true)
        ->call('sendReminder')
        ->assertSet('showReminderModal', false);

    Mail::assertSent(ActivityReminderMail::class, 1);
    expect(MemberMessage::where('user_id', $member->id)->exists())->toBeTrue();
    expect(ActivityReminder::where('user_id', $member->id)->where('year', now()->year)->exists())->toBeTrue();
});

it('skips a member who already received this year when clicked a second time', function () {
    $type = uiSeedType();
    $member = uiMember($type);

    Livewire::test('pages::admin.activities.index')->call('sendReminder');
    Mail::assertSent(ActivityReminderMail::class, 1);

    Livewire::test('pages::admin.activities.index')->call('sendReminder');
    Mail::assertSent(ActivityReminderMail::class, 1); // still one
});

it('renders the admin reports page and streams the activity-compliance CSV', function () {
    $type = uiSeedType();
    $member = uiMember($type);
    \App\Models\ShootingActivity::create([
        'user_id' => $member->id,
        'activity_date' => \Carbon\Carbon::create(now()->year, 5, 1),
        'activity_type' => 'range_session',
        'track' => 'sport',
        'status' => 'approved',
        'approved_at' => now(),
        'location' => 'Test range',
        'description' => 'test',
    ]);
    \App\Models\ShootingActivity::create([
        'user_id' => $member->id,
        'activity_date' => \Carbon\Carbon::create(now()->year, 5, 2),
        'activity_type' => 'range_session',
        'track' => 'sport',
        'status' => 'approved',
        'approved_at' => now(),
        'location' => 'Test range',
        'description' => 'test',
    ]);

    $response = Livewire::test('pages::admin.reports.index')
        ->call('exportActivityComplianceCsv');

    $response->assertFileDownloaded(
        'activity-compliance-' . now()->year . '.csv'
    );
});
