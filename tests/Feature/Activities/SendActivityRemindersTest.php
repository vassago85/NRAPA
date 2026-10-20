<?php

declare(strict_types=1);

use App\Console\Commands\SendActivityReminders;
use App\Mail\ActivityReminderMail;
use App\Models\ActivityReminder;
use App\Models\MemberMessage;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Support\Members\ActivityReminderRecipients;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function remSeedType(): MembershipType
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

function remMakeMember(MembershipType $type, array $overrides = []): User
{
    $user = User::factory()->create($overrides + ['role' => User::ROLE_MEMBER]);
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
});

it('writes an inbox message, an email, and an idempotency row when the member is reachable', function () {
    $type = remSeedType();
    $user = remMakeMember($type);
    $year = ActivityReminderRecipients::defaultYear();
    $required = ActivityReminderRecipients::requiredCount();

    $result = SendActivityReminders::deliverFor($user, $year, $required, 0, $this->admin);

    expect($result['emailed'])->toBeTrue();

    Mail::assertSent(ActivityReminderMail::class, function (ActivityReminderMail $mail) use ($user, $year) {
        return $mail->hasTo($user->email)
            && $mail->user->is($user)
            && $mail->year === $year;
    });

    $message = MemberMessage::where('user_id', $user->id)->firstOrFail();
    expect($message->direction)->toBe(MemberMessage::DIRECTION_ADMIN_TO_MEMBER)
        ->and($message->subject)->toContain((string) $year)
        ->and($message->email_sent_at)->not->toBeNull();

    $reminder = ActivityReminder::where('user_id', $user->id)->firstOrFail();
    expect($reminder->year)->toBe($year)
        ->and((bool) $reminder->emailed)->toBeTrue();
});

it('still writes an inbox message when the member cannot be emailed', function () {
    $type = remSeedType();
    $user = remMakeMember($type, ['email_status' => 'bounced']);
    $year = ActivityReminderRecipients::defaultYear();
    $required = ActivityReminderRecipients::requiredCount();

    $result = SendActivityReminders::deliverFor($user, $year, $required, 0, $this->admin);

    expect($result['emailed'])->toBeFalse();

    Mail::assertNotSent(ActivityReminderMail::class);

    expect(MemberMessage::where('user_id', $user->id)->exists())->toBeTrue();
    expect(ActivityReminder::where('user_id', $user->id)->where('emailed', false)->exists())->toBeTrue();
});

it('still writes an inbox message when the member has unsubscribed from non-essential email', function () {
    $type = remSeedType();
    $user = remMakeMember($type);
    $user->optOutOfNonEssentialEmail('footer_link');
    $year = ActivityReminderRecipients::defaultYear();
    $required = ActivityReminderRecipients::requiredCount();

    $result = SendActivityReminders::deliverFor($user->fresh(), $year, $required, 0, $this->admin);

    expect($result['emailed'])->toBeFalse();

    Mail::assertNotSent(ActivityReminderMail::class);

    expect(MemberMessage::where('user_id', $user->id)->exists())->toBeTrue();
    expect(ActivityReminder::where('user_id', $user->id)->where('emailed', false)->exists())->toBeTrue();
});

it('refuses to send a second reminder in the same calendar year', function () {
    $type = remSeedType();
    $user = remMakeMember($type);

    $exit = \Illuminate\Support\Facades\Artisan::call('nrapa:send-activity-reminders', [
        '--throttle' => 0,
        '--actor-id' => $this->admin->id,
    ]);
    expect($exit)->toBe(0);
    Mail::assertSent(ActivityReminderMail::class, 1);

    // Second run on the same year — the existing activity_reminders row
    // short-circuits the recipient so nothing is sent again.
    \Illuminate\Support\Facades\Artisan::call('nrapa:send-activity-reminders', [
        '--throttle' => 0,
        '--actor-id' => $this->admin->id,
    ]);
    Mail::assertSent(ActivityReminderMail::class, 1); // still one
    expect(ActivityReminder::where('user_id', $user->id)->count())->toBe(1);
});

it('--dry-run reports recipients but sends nothing', function () {
    $type = remSeedType();
    $user = remMakeMember($type);

    \Illuminate\Support\Facades\Artisan::call('nrapa:send-activity-reminders', [
        '--dry-run' => true,
        '--throttle' => 0,
        '--actor-id' => $this->admin->id,
    ]);

    Mail::assertNothingSent();
    expect(ActivityReminder::count())->toBe(0);
    expect(MemberMessage::count())->toBe(0);
});

it('--force resends to a member who already received this year', function () {
    $type = remSeedType();
    $user = remMakeMember($type);
    $year = ActivityReminderRecipients::defaultYear();

    ActivityReminder::create([
        'user_id' => $user->id,
        'year' => $year,
        'emailed' => true,
        'sent_at' => now()->subDay(),
    ]);

    \Illuminate\Support\Facades\Artisan::call('nrapa:send-activity-reminders', [
        '--force' => true,
        '--throttle' => 0,
        '--actor-id' => $this->admin->id,
    ]);

    Mail::assertSent(ActivityReminderMail::class, 1);
});
