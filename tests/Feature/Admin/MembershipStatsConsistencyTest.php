<?php

use App\Jobs\BulkMemberAction;
use App\Models\AuditLog;
use App\Models\Membership;
use App\Models\MemberMessage;
use App\Models\User;
use App\Support\Membership\MemberCsvExport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\ApprovalFixtures;

beforeEach(function () {
    ApprovalFixtures::stubDocumentRendering();
    config(['mail.min_gap_seconds' => 0]);
    $this->admin = ApprovalFixtures::admin();
});

/**
 * One fixture covering each state, plus a lifetime member whose stale expiry
 * date must not make them count as expired.
 */
function seedMixedMembership(): void
{
    ApprovalFixtures::activeMembership(ApprovalFixtures::member());
    ApprovalFixtures::activeMembership(ApprovalFixtures::member())->update(['expires_at' => now()->addDays(10)]);

    $lifetime = ApprovalFixtures::membershipType(['slug' => 'queue-lifetime', 'name' => 'Queue Lifetime', 'duration_type' => 'lifetime', 'requires_renewal' => false]);
    ApprovalFixtures::activeMembership(ApprovalFixtures::member())
        ->update(['membership_type_id' => $lifetime->id, 'expires_at' => now()->subYears(2)]);

    ApprovalFixtures::activeMembership(ApprovalFixtures::member())->update(['expires_at' => now()->subMonth()]);
    ApprovalFixtures::activeMembership(ApprovalFixtures::member())->update(['status' => 'expired', 'expires_at' => now()->subYear()]);

    Membership::create([
        'user_id' => ApprovalFixtures::member()->id,
        'membership_type_id' => ApprovalFixtures::membershipType()->id,
        'status' => 'applied',
        'source' => 'web',
        'applied_at' => now()->subDays(3),
    ]);

    ApprovalFixtures::member();
}

function statsFrom(string $html): array
{
    $stats = [];
    foreach (['total', 'active', 'expired'] as $key) {
        preg_match('/data-stat="'.$key.'">([\d,]+)</', $html, $match);
        $stats[$key] = isset($match[1]) ? (int) str_replace(',', '', $match[1]) : null;
    }

    return $stats;
}

it('renders identical total, active and expired numbers on dashboard, members and reports', function () {
    seedMixedMembership();

    $this->actingAs($this->admin);

    $dashboard = statsFrom($this->get(route('admin.dashboard'))->assertOk()->getContent());
    $members = statsFrom($this->get(route('admin.members.index'))->assertOk()->getContent());
    $reports = statsFrom($this->get(route('admin.reports.index'))->assertOk()->getContent());

    expect($dashboard)->toBe(['total' => 7, 'active' => 3, 'expired' => 2])
        ->and($members)->toBe($dashboard)
        ->and($reports)->toBe($dashboard);
});

it('lists exactly the members each segment chip counts', function () {
    seedMixedMembership();

    $component = Livewire::actingAs($this->admin)->test('pages::admin.members.index');

    foreach ($component->instance()->segmentChips() as $segment => $chip) {
        $component->call('setSegment', $segment);

        expect($component->instance()->members->total())->toBe($chip['count'], "segment {$segment}");
    }

    expect($component->instance()->segmentChips()['expiring_30']['count'])->toBe(1)
        ->and($component->instance()->segmentChips()['awaiting_payment']['count'])->toBe(1);
});

it('acts on the whole segment, not just the visible page, when all matching are selected', function () {
    Mail::fake();

    $members = collect(range(1, 25))->map(function () {
        $member = ApprovalFixtures::member();
        ApprovalFixtures::activeMembership($member);

        return $member;
    });
    $lapsed = ApprovalFixtures::member();
    ApprovalFixtures::activeMembership($lapsed)->update(['expires_at' => now()->subMonth()]);
    $bounced = $members->last();
    $bounced->forceFill(['email_status' => 'bounced'])->saveQuietly();

    $component = Livewire::actingAs($this->admin)
        ->test('pages::admin.members.index')
        ->call('setSegment', 'active')
        ->call('toggleSelectAll', true);

    expect($component->get('selectedUserIds'))->toHaveCount(20)
        ->and($component->instance()->matchingCount)->toBe(25);

    $component->call('selectAllInSegment')
        ->set('bulkMessageSubject', 'Range day')
        ->set('bulkMessageBody', 'See you on Saturday.')
        ->call('sendBulkMessage')
        ->assertHasNoErrors();

    expect(MemberMessage::count())->toBe(25)
        ->and(MemberMessage::where('user_id', $lapsed->id)->exists())->toBeFalse()
        ->and(AuditLog::where('event', 'member.bulk_message_sent')->count())->toBe(25)
        ->and(AuditLog::where('event', 'member.bulk_message_sent')->where('auditable_id', $bounced->id)->first()->meta['emailed'])->toBeFalse();

    Mail::assertNotSent(\App\Mail\MemberMessageMail::class, fn ($mail) => $mail->hasTo($bounced->email));
    Mail::assertSent(\App\Mail\MemberMessageMail::class, 24);

    $batches = AuditLog::where('event', 'member.bulk_message_sent')->get()->pluck('meta.bulk_batch')->unique();
    expect($batches)->toHaveCount(1)
        ->and($component->get('selectAllMatching'))->toBeFalse();
});

it('queues bulk actions instead of running them in the request', function () {
    Queue::fake();
    ApprovalFixtures::activeMembership(ApprovalFixtures::member());

    Livewire::actingAs($this->admin)
        ->test('pages::admin.members.index')
        ->call('selectAllInSegment')
        ->set('bulkMessageSubject', 'Hello')
        ->set('bulkMessageBody', 'Body')
        ->call('sendBulkMessage');

    Queue::assertPushed(BulkMemberAction::class, fn ($job) => $job->segment === 'all' && $job->memberIds === []);
});

it('changes membership type per member with an audit entry each and skips staff', function () {
    $members = collect(range(1, 3))->map(fn () => tap(ApprovalFixtures::member(), fn ($m) => ApprovalFixtures::activeMembership($m)));
    $staff = ApprovalFixtures::admin();
    $newType = ApprovalFixtures::membershipType(['slug' => 'queue-dedicated', 'name' => 'Queue Dedicated']);

    (new BulkMemberAction($this->admin->id, BulkMemberAction::ACTION_CHANGE_TYPE, ['membership_type_id' => $newType->id],
        $members->pluck('id')->push($staff->id)->all()))->handle();

    foreach ($members as $member) {
        expect($member->memberships()->first()->membership_type_id)->toBe($newType->id);
    }

    $entries = AuditLog::where('event', 'member.bulk_membership_type_changed')->get();
    expect($entries)->toHaveCount(3)
        ->and($entries->pluck('auditable_id')->sort()->values()->all())->toBe($members->pluck('id')->sort()->values()->all())
        ->and($entries->first()->new_values['membership_type_id'])->toBe($newType->id)
        ->and($entries->first()->user_id)->toBe($this->admin->id);
});

it('exports the whole segment as CSV with one audit entry per exported member', function () {
    collect(range(1, 4))->each(fn () => ApprovalFixtures::activeMembership(ApprovalFixtures::member()));
    ApprovalFixtures::member();

    $out = fopen('php://memory', 'w+');
    $exported = (new MemberCsvExport($this->admin, 'active'))->write($out);
    rewind($out);
    $lines = array_filter(explode("\n", stream_get_contents($out)));
    fclose($out);

    expect($exported)->toBe(4)
        ->and($lines)->toHaveCount(5)
        ->and(AuditLog::where('event', 'member.exported')->count())->toBe(4);
});

it('remembers the page size per admin', function () {
    collect(range(1, 30))->each(fn () => ApprovalFixtures::member());

    Livewire::actingAs($this->admin)
        ->test('pages::admin.members.index')
        ->set('perPage', 50)
        ->assertSet('perPage', 50);

    expect($this->admin->fresh()->adminPreference('members.per_page'))->toBe(50);

    $component = Livewire::actingAs($this->admin->fresh())->test('pages::admin.members.index');
    expect($component->get('perPage'))->toBe(50)
        ->and(count($component->instance()->members->items()))->toBe(30);

    $component->set('perPage', 7)->assertSet('perPage', 20);
});
