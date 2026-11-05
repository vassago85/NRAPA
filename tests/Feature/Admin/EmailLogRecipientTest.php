<?php

use App\Models\EmailLog;
use Livewire\Livewire;
use Tests\Support\ApprovalFixtures;

test('email log rows show the member name and membership number', function () {
    $admin = ApprovalFixtures::admin();
    $member = ApprovalFixtures::member([
        'name' => 'Gert Kruger',
        'email' => 'gskruger1@example.com',
    ]);
    $membership = ApprovalFixtures::activeMembership($member);

    EmailLog::create([
        'user_id' => $member->id,
        'to_email' => $member->email,
        'to_name' => null,
        'subject' => 'NRAPA: changes needed on your Endorsement',
        'mailable_class' => \App\Mail\ApprovalDecisionMail::class,
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.email-logs.index')
        ->set('search', 'kruger')
        ->assertSee('Gert Kruger')
        ->assertSee($membership->membership_number)
        ->assertSee('gskruger1@example.com');
});

test('email logs can be found by membership number', function () {
    $admin = ApprovalFixtures::admin();
    $member = ApprovalFixtures::member(['name' => 'Hidden Member', 'email' => 'hidden@example.com']);
    $membership = ApprovalFixtures::activeMembership($member);

    EmailLog::create([
        'user_id' => $member->id,
        'to_email' => $member->email,
        'subject' => 'NRAPA Activity Approved',
        'mailable_class' => 'ActivityApproved',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.email-logs.index')
        ->set('search', $membership->membership_number)
        ->assertSee('Hidden Member')
        ->assertSee($membership->membership_number);
});
