<?php

use App\Models\MemberMessage;
use App\Models\User;
use Livewire\Livewire;

/**
 * Admins and members each track their own unread messages: opening a thread marks only the
 * other side's messages in that thread as read, and inbox counts reflect exactly that.
 */
function makeMemberMessage(User $member, User $sender, string $direction, ?MemberMessage $parent = null, bool $read = false): MemberMessage
{
    return MemberMessage::create([
        'user_id' => $member->id,
        'sent_by_user_id' => $sender->id,
        'direction' => $direction,
        'parent_id' => $parent?->id,
        'subject' => $parent?->subject ?? 'Subject '.uniqid(),
        'body' => 'Body',
        'read_at' => $read ? now()->subDay() : null,
    ]);
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->alice = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $this->bob = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $in = MemberMessage::DIRECTION_MEMBER_TO_ADMIN;
    $out = MemberMessage::DIRECTION_ADMIN_TO_MEMBER;

    // Alice started a thread; admin replied; Alice replied again. Nothing read yet.
    $this->aliceThread = makeMemberMessage($this->alice, $this->alice, $in);
    $this->adminReply = makeMemberMessage($this->alice, $this->admin, $out, $this->aliceThread);
    $this->aliceFollowUp = makeMemberMessage($this->alice, $this->alice, $in, $this->aliceThread);

    // Admin started a separate thread to Alice, unread by her.
    $this->adminThread = makeMemberMessage($this->alice, $this->admin, $out);

    // Bob has his own unread thread, plus one fully read thread.
    $this->bobThread = makeMemberMessage($this->bob, $this->bob, $in);
    $this->bobReadThread = makeMemberMessage($this->bob, $this->bob, $in, null, read: true);
});

test('scopes select the unread messages for each side and the thread roots', function () {
    expect(MemberMessage::unreadByAdmins()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->aliceThread->id, $this->aliceFollowUp->id, $this->bobThread->id])->sort()->values()->all());

    expect(MemberMessage::where('user_id', $this->alice->id)->unreadByMember()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->adminReply->id, $this->adminThread->id])->sort()->values()->all());

    expect(MemberMessage::threadRoots()->count())->toBe(4);
});

test('admin opening a thread marks only incoming messages in that thread as read', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.messages.show', ['message' => $this->aliceFollowUp]);

    expect($this->aliceThread->fresh()->read_at)->not->toBeNull()
        ->and($this->aliceFollowUp->fresh()->read_at)->not->toBeNull()
        ->and($this->adminReply->fresh()->read_at)->toBeNull()
        ->and($this->bobThread->fresh()->read_at)->toBeNull();
});

test('admin can mark a thread unread again', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.messages.show', ['message' => $this->aliceThread])
        ->call('markThreadUnread');

    expect($this->aliceThread->fresh()->read_at)->toBeNull()
        ->and($this->aliceFollowUp->fresh()->read_at)->toBeNull()
        ->and(MemberMessage::unreadByAdmins()->count())->toBe(3);
});

test('member opening a thread marks only admin replies in that thread as read', function () {
    $this->actingAs($this->alice);

    Livewire::test('pages::member.messages.show', ['message' => $this->aliceThread]);

    expect($this->adminReply->fresh()->read_at)->not->toBeNull()
        ->and($this->adminThread->fresh()->read_at)->toBeNull()
        ->and($this->aliceThread->fresh()->read_at)->toBeNull()
        ->and($this->aliceFollowUp->fresh()->read_at)->toBeNull();
});

test('member cannot open another member\'s thread', function () {
    $this->actingAs($this->bob);

    Livewire::test('pages::member.messages.show', ['message' => $this->aliceThread])
        ->assertForbidden();

    expect($this->adminReply->fresh()->read_at)->toBeNull();
});

test('member mark all read only touches their own admin messages', function () {
    $bobAdminMessage = makeMemberMessage($this->bob, $this->admin, MemberMessage::DIRECTION_ADMIN_TO_MEMBER);
    $this->actingAs($this->alice);

    Livewire::test('pages::member.messages.index')->call('markAllRead');

    expect(MemberMessage::where('user_id', $this->alice->id)->unreadByMember()->count())->toBe(0)
        ->and($bobAdminMessage->fresh()->read_at)->toBeNull()
        ->and(MemberMessage::unreadByAdmins()->count())->toBe(3);
});

test('member inbox lists only their own threads with unread reply counts', function () {
    $this->actingAs($this->alice);

    $threads = Livewire::test('pages::member.messages.index')->instance()->threads;

    expect($threads->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->aliceThread->id, $this->adminThread->id])->sort()->values()->all())
        ->and($threads->firstWhere('id', $this->aliceThread->id)->unread_replies_count)->toBe(1)
        ->and($threads->firstWhere('id', $this->adminThread->id)->unread_replies_count)->toBe(0);
});

test('admin inbox counts and filters unread incoming threads', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test('pages::admin.messages.index');
    $all = $component->instance()->threads;

    expect($all->total())->toBe(4)
        ->and($all->getCollection()->firstWhere('id', $this->aliceThread->id)->incoming_unread_count)->toBe(1)
        ->and($component->instance()->incomingUnreadTotal)->toBe(3);

    $unread = $component->set('filter', 'unread')->instance()->threads;
    expect($unread->getCollection()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->aliceThread->id, $this->bobThread->id])->sort()->values()->all());

    $incoming = $component->set('filter', 'incoming')->instance()->threads;
    expect($incoming->getCollection()->pluck('id')->all())->not->toContain($this->adminThread->id)
        ->and($incoming->total())->toBe(3);
});

test('admin inbox unread filter picks up a thread whose only unread message is a reply', function () {
    $this->aliceThread->update(['read_at' => now()]);
    $this->actingAs($this->admin);

    $unread = Livewire::test('pages::admin.messages.index')->set('filter', 'unread')->instance()->threads;

    expect($unread->getCollection()->pluck('id')->all())->toContain($this->aliceThread->id);
});

test('admin navigation badge shows the unread incoming total', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.messages.index'))
        ->assertOk()
        ->assertSeeInOrder(['Admin Inbox']);

    $html = $this->actingAs($this->admin)->get(route('admin.messages.index'))->getContent();
    expect($html)->toMatch('/bg-amber-500 text-white">\s*3\s*</');

    MemberMessage::query()->update(['read_at' => now()]);

    $html = $this->actingAs($this->admin)->get(route('admin.messages.index'))->getContent();
    expect($html)->not->toMatch('/bg-amber-500 text-white">\s*\d+\s*</');
});
