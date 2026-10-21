<?php

declare(strict_types=1);

use App\Jobs\BulkMemberAction;
use App\Mail\MemberMessageMail;
use App\Models\MemberMessage;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Support\Members\MemberLetter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * The formal member letter feature. Each member gets their own copy with
 * tokens replaced from their membership — first name, membership type,
 * status, and expiry. Covered here:
 *
 *   - MemberLetter::render fills curly-brace tokens from an active membership.
 *   - Lifetime memberships get "does not expire" rather than a blank.
 *   - Unknown tokens are left intact so admins can quote policy references.
 *   - BulkMemberAction renders the subject and body per recipient so the
 *     inbox copy stored for each member already has their own values.
 */

function letterSeed(): array
{
    $annual = MembershipType::create([
        'slug' => 'test-letter-annual',
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

    $lifetime = MembershipType::create([
        'slug' => 'test-letter-lifetime',
        'name' => 'Lifetime',
        'duration_type' => 'lifetime',
        'requires_renewal' => false,
        'pricing_model' => 'once_off',
        'initial_price' => 15000,
        'allows_dedicated_status' => true,
        'is_active' => true,
    ]);

    $basic = MembershipType::create([
        'slug' => 'test-letter-basic',
        'name' => 'Basic',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 300,
        'renewal_price' => 300,
        'allows_dedicated_status' => false,
        'is_active' => true,
    ]);

    return compact('annual', 'lifetime', 'basic');
}

function letterMember(MembershipType $type, array $overrides = [], ?\DateTimeInterface $expiresAt = null): User
{
    $user = User::factory()->create($overrides + ['role' => User::ROLE_MEMBER]);

    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => $type->isLifetime() ? null : ($expiresAt ?? now()->addMonths(6)),
    ]);

    return $user;
}

it('replaces every advertised token with the member value', function () {
    $types = letterSeed();
    $user = letterMember($types['annual'], [
        'name' => 'Jane Mary Smith',
        'member_number' => 42,
    ], expiresAt: \Carbon\Carbon::create(2027, 3, 15));

    $template = 'Hi {first_name}, your {membership_type} membership ({membership_number}) is {status} and valid until {expiry}. — {full_name}';
    $rendered = MemberLetter::render($template, $user->fresh());

    expect($rendered)->toContain('Hi Jane,')
        ->and($rendered)->toContain('your Annual Dedicated membership')
        ->and($rendered)->toContain('NRAPA-00042')
        ->and($rendered)->toContain('is Active')
        ->and($rendered)->toContain('valid until 15 March 2027')
        ->and($rendered)->toContain('— Jane Mary Smith');
});

it('writes "does not expire" instead of a blank for lifetime memberships', function () {
    $types = letterSeed();
    $user = letterMember($types['lifetime'], ['name' => 'Lily Lifer']);

    $template = 'Your membership expires: {expiry}. ({expiry_date})';
    $rendered = MemberLetter::render($template, $user->fresh());

    expect($rendered)->toBe('Your membership expires: does not expire. (does not expire)');
});

it('leaves unknown tokens intact so admins can quote policy references', function () {
    $types = letterSeed();
    $user = letterMember($types['basic'], ['name' => 'Basil Basic']);

    $template = 'Per rule {rule_7}, {first_name}, your {membership_type} membership stands.';
    $rendered = MemberLetter::render($template, $user->fresh());

    expect($rendered)->toBe('Per rule {rule_7}, Basil, your Basic membership stands.');
});

it('writes a per-member inbox copy when BulkMemberAction sends a letter', function () {
    Mail::fake();

    $types = letterSeed();
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $jane = letterMember($types['annual'], ['name' => 'Jane Doe'], expiresAt: \Carbon\Carbon::create(2027, 1, 31));
    $basil = letterMember($types['basic'], ['name' => 'Basil Basic']);
    $lily = letterMember($types['lifetime'], ['name' => 'Lily Lifer']);

    $job = new BulkMemberAction(
        actorId: $admin->id,
        action: BulkMemberAction::ACTION_MESSAGE,
        payload: [
            'subject' => 'Hi {first_name}, about your {membership_type} membership',
            'body' => "Dear {first_name},\n\nYour membership is {membership_type}. It is valid until {expiry}.\n\nKind regards\nNRAPA",
        ],
        memberIds: [$jane->id, $basil->id, $lily->id],
    );
    $job->handle();

    expect(MemberMessage::count())->toBe(3);

    $janeMsg = MemberMessage::where('user_id', $jane->id)->firstOrFail();
    expect($janeMsg->subject)->toBe('Hi Jane, about your Annual Dedicated membership')
        ->and($janeMsg->body)->toContain('Dear Jane,')
        ->and($janeMsg->body)->toContain('Your membership is Annual Dedicated.')
        ->and($janeMsg->body)->toContain('valid until 31 January 2027');

    $basilMsg = MemberMessage::where('user_id', $basil->id)->firstOrFail();
    expect($basilMsg->subject)->toBe('Hi Basil, about your Basic membership')
        ->and($basilMsg->body)->toContain('Dear Basil,')
        ->and($basilMsg->body)->toContain('Your membership is Basic.');

    $lilyMsg = MemberMessage::where('user_id', $lily->id)->firstOrFail();
    expect($lilyMsg->subject)->toBe('Hi Lily, about your Lifetime membership')
        ->and($lilyMsg->body)->toContain('Dear Lily,')
        ->and($lilyMsg->body)->toContain('valid until does not expire');

    // Each member gets their own email, with their own values — three sent,
    // not one shared template dispatched three times.
    Mail::assertSent(MemberMessageMail::class, 3);
    Mail::assertSent(MemberMessageMail::class, fn ($mail) => $mail->message->user_id === $jane->id
        && str_contains($mail->message->subject, 'Jane'));
    Mail::assertSent(MemberMessageMail::class, fn ($mail) => $mail->message->user_id === $basil->id
        && str_contains($mail->message->body, 'Basic'));
});
