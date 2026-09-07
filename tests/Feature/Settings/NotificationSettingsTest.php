<?php

use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NtfyService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('admin notification settings omit document rejected and membership expiring', function () {
    $developer = User::factory()->create([
        'role' => User::ROLE_DEVELOPER,
        'is_admin' => true,
    ]);

    $this->actingAs($developer);

    Livewire::test('pages::settings.notifications')
        ->assertSee('New Member Registration')
        ->assertSee('Payment Received')
        ->assertSee('Document Uploaded')
        ->assertSee('Activity Submitted')
        ->assertSee('Knowledge Test Completed')
        ->assertSee('Endorsement Request')
        ->assertSee('System Errors')
        ->assertDontSee('Document Rejected')
        ->assertDontSee('Membership Expiring');
});

test('mysql time-with-seconds values do not block saving notification toggles', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_admin' => true,
    ]);

    NotificationPreference::create([
        'user_id' => $admin->id,
        'ntfy_enabled' => true,
        'ntfy_topic' => 'nrapa-developer',
        'working_hours_start' => '10:02:00',
        'working_hours_end' => '17:00:00',
        'respect_working_hours' => false,
        'notify_license_expiry' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::settings.notifications')
        ->set('ntfy_enabled', false)
        ->set('notify_license_expiry', false)
        ->call('save')
        ->assertHasNoErrors();

    $prefs = $admin->notificationPreference()->first();

    expect($prefs->ntfy_enabled)->toBeFalse()
        ->and($prefs->notify_license_expiry)->toBeFalse();
});

test('queued ntfy is not sent after notifications are disabled', function () {
    Http::fake([
        'https://ntfy.sh/*' => Http::response('ok', 200),
    ]);

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_admin' => true,
    ]);

    NotificationPreference::create([
        'user_id' => $admin->id,
        'ntfy_enabled' => false,
        'ntfy_topic' => 'nrapa-developer',
        'respect_working_hours' => false,
        'notify_document_uploaded' => true,
    ]);

    \App\Models\QueuedNotification::create([
        'user_id' => $admin->id,
        'type' => 'document_uploaded',
        'title' => 'Document Uploaded',
        'message' => 'A document was uploaded.',
        'priority' => 'default',
        'status' => 'pending',
    ]);

    app(NtfyService::class)->processQueue();

    Http::assertNothingSent();
});

test('turning a notification type off stops ntfy for that type', function () {
    Http::fake([
        'https://ntfy.sh/*' => Http::response('ok', 200),
    ]);

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_admin' => true,
    ]);

    NotificationPreference::create([
        'user_id' => $admin->id,
        'ntfy_enabled' => true,
        'ntfy_topic' => 'nrapa-admin-topic',
        'notify_new_member' => false,
        'notify_document_uploaded' => true,
        'respect_working_hours' => false,
    ]);

    app(NtfyService::class)->notifyAdmins(
        'new_member',
        'New Member Registration',
        'Someone registered.',
    );

    Http::assertNothingSent();

    app(NtfyService::class)->notifyAdmins(
        'document_uploaded',
        'Document Uploaded',
        'A document was uploaded.',
    );

    Http::assertSent(fn ($request) => str_contains($request->url(), 'nrapa-admin-topic')
        && str_contains($request->header('Title')[0], 'Document Uploaded'));
});
