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
