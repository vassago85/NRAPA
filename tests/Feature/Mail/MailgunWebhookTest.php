<?php

use App\Models\EmailLog;
use App\Models\MailgunEvent;
use App\Models\User;
use App\Support\Mail\UndeliverableRecipientGuard;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

const MAILGUN_TEST_KEY = 'test-webhook-signing-key';

beforeEach(function () {
    config([
        'services.mailgun.webhook_signing_key' => MAILGUN_TEST_KEY,
        'mail.min_gap_seconds' => 0,
    ]);

    $this->member = User::factory()->create(['role' => User::ROLE_MEMBER, 'email' => 'member@example.test']);
    $this->log = EmailLog::create([
        'user_id' => $this->member->id,
        'to_email' => $this->member->email,
        'subject' => 'Your renewal',
        'status' => 'sent',
        'sent_at' => now(),
        'provider_message_id' => '20261012.abc123@mg.nrapa.co.za',
        'delivery_status' => 'sent',
    ]);
});

function mailgunPayload(string $event, array $extra = [], ?int $timestamp = null, ?string $key = MAILGUN_TEST_KEY): array
{
    $timestamp ??= now()->getTimestamp();
    $token = Str::random(50);

    return [
        'signature' => [
            'timestamp' => (string) $timestamp,
            'token' => $token,
            'signature' => hash_hmac('sha256', $timestamp.$token, (string) $key),
        ],
        'event-data' => array_merge([
            'id' => Str::random(22),
            'event' => $event,
            'timestamp' => $timestamp,
            'recipient' => 'member@example.test',
            'message' => ['headers' => ['message-id' => '20261012.abc123@mg.nrapa.co.za']],
        ], $extra),
    ];
}

function hardBounce(): array
{
    return [
        'severity' => 'permanent',
        'reason' => 'bounce',
        'delivery-status' => ['code' => 550, 'description' => 'Mailbox does not exist'],
    ];
}

it('moves a message forward through delivered, opened and clicked', function () {
    $this->postJson(route('webhooks.mailgun'), mailgunPayload('delivered'))->assertOk();
    expect($this->log->fresh())
        ->delivery_status->toBe('delivered')
        ->delivered_at->not->toBeNull();

    $this->postJson(route('webhooks.mailgun'), mailgunPayload('opened'))->assertOk();
    expect($this->log->fresh()->delivery_status)->toBe('opened');

    $this->postJson(route('webhooks.mailgun'), mailgunPayload('clicked'))->assertOk();

    $log = $this->log->fresh();
    expect($log->delivery_status)->toBe('clicked')
        ->and($log->opened_at)->not->toBeNull()
        ->and($log->clicked_at)->not->toBeNull()
        ->and($log->events)->toHaveCount(3)
        ->and($this->member->fresh()->email_status)->toBe('delivered')
        ->and(MailgunEvent::whereNull('processed_at')->exists())->toBeFalse();
});

it('records a hard bounce on the message and the member', function () {
    $this->postJson(route('webhooks.mailgun'), mailgunPayload('failed', hardBounce()))->assertOk();

    $log = $this->log->fresh();
    $member = $this->member->fresh();

    expect($log->delivery_status)->toBe('bounced')
        ->and($log->bounce_code)->toBe('550')
        ->and($log->bounce_reason)->toBe('Mailbox does not exist')
        ->and($member->email_status)->toBe('bounced')
        ->and($member->canReceiveEmail())->toBeFalse()
        ->and(User::emailable()->whereKey($member->id)->exists())->toBeFalse();
});

it('marks complaints as undeliverable and leaves temporary failures alone', function () {
    $this->postJson(route('webhooks.mailgun'), mailgunPayload('failed', ['severity' => 'temporary', 'reason' => 'generic']))->assertOk();
    expect($this->log->fresh()->delivery_status)->toBe('sent')
        ->and($this->member->fresh()->canReceiveEmail())->toBeTrue();

    $this->postJson(route('webhooks.mailgun'), mailgunPayload('complained'))->assertOk();
    expect($this->log->fresh()->delivery_status)->toBe('complained')
        ->and($this->member->fresh()->email_status)->toBe('complained');
});

it('never moves backwards, and a late delivered never overwrites a bounce', function () {
    $this->postJson(route('webhooks.mailgun'), mailgunPayload('opened'))->assertOk();
    $this->postJson(route('webhooks.mailgun'), mailgunPayload('delivered'))->assertOk();
    expect($this->log->fresh()->delivery_status)->toBe('opened');

    $this->postJson(route('webhooks.mailgun'), mailgunPayload('failed', hardBounce()))->assertOk();
    $this->postJson(route('webhooks.mailgun'), mailgunPayload('delivered'))->assertOk();
    $this->postJson(route('webhooks.mailgun'), mailgunPayload('clicked'))->assertOk();

    expect($this->log->fresh()->delivery_status)->toBe('bounced')
        ->and($this->member->fresh()->email_status)->toBe('bounced');
});

it('rejects unsigned, mis-signed and stale payloads without changing anything', function () {
    $unsigned = mailgunPayload('delivered');
    unset($unsigned['signature']);

    $this->postJson(route('webhooks.mailgun'), $unsigned)->assertForbidden();
    $this->postJson(route('webhooks.mailgun'), mailgunPayload('delivered', key: 'wrong-key'))->assertForbidden();
    $this->postJson(route('webhooks.mailgun'), mailgunPayload('delivered', timestamp: now()->subMinutes(16)->getTimestamp()))->assertForbidden();

    config(['services.mailgun.webhook_signing_key' => null]);
    $this->postJson(route('webhooks.mailgun'), mailgunPayload('delivered'))->assertForbidden();

    expect(MailgunEvent::count())->toBe(0)
        ->and($this->log->fresh()->delivery_status)->toBe('sent');
});

it('processes a repeated event id only once', function () {
    $payload = mailgunPayload('opened');

    $this->postJson(route('webhooks.mailgun'), $payload)->assertOk();
    $this->postJson(route('webhooks.mailgun'), $payload)->assertOk();

    expect(MailgunEvent::count())->toBe(1)
        ->and($this->log->fresh()->events)->toHaveCount(1);
});

it('blocks mail to a hard-bounced address and records why', function () {
    $this->member->forceFill(['email_status' => 'bounced'])->saveQuietly();
    $healthy = User::factory()->create(['email' => 'healthy@example.test']);

    Mail::raw('Only the healthy address should get this.', function ($message) use ($healthy) {
        $message->to([$this->member->email, $healthy->email])->subject('Mixed recipients');
    });

    Mail::raw('Nobody should get this.', function ($message) {
        $message->to($this->member->email)->subject('Blocked entirely');
    });

    $sent = app('mailer')->getSymfonyTransport()->messages();

    expect($sent)->toHaveCount(1)
        ->and(collect($sent->first()->getEnvelope()->getRecipients())->map->getAddress()->all())->toBe(['healthy@example.test'])
        ->and(EmailLog::where('subject', 'Blocked entirely')->first())
        ->status->toBe('failed')
        ->error_message->toBe(UndeliverableRecipientGuard::BLOCKED_MESSAGE);
});

it('captures the provider message id when mail is sent', function () {
    Mail::raw('Hello', fn ($message) => $message->to('healthy@example.test')->subject('Message id capture'));

    $log = EmailLog::where('subject', 'Message id capture')->first();

    expect($log->provider_message_id)->not->toBeNull()
        ->and($log->provider_message_id)->not->toContain('<')
        ->and($log->delivery_status)->toBe('sent');
});

it('clears the bounce flag when the member changes their address', function () {
    $this->member->forceFill(['email_status' => 'bounced', 'email_bounce_reason' => 'Mailbox does not exist'])->saveQuietly();

    $this->member->update(['email' => 'new-address@example.test']);

    expect($this->member->fresh())
        ->email_status->toBeNull()
        ->email_bounce_reason->toBeNull();
});

it('stamps last_signed_in_at on login', function () {
    expect($this->member->last_signed_in_at)->toBeNull();

    event(new Login('web', $this->member, false));

    expect($this->member->fresh()->last_signed_in_at)->not->toBeNull();
});
