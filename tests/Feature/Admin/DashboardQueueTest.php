<?php

use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Support\ApprovalFixtures;

beforeEach(function () {
    ApprovalFixtures::stubDocumentRendering();
    $this->admin = ApprovalFixtures::admin();
});

/**
 * Imported members who never signed in, inserted in bulk so model hooks
 * (and the approval sync) stay out of the way.
 */
function seedNeverSignedInMembers(int $count, int $offset = 0): void
{
    $typeId = ApprovalFixtures::membershipType()->id;
    $now = now()->toDateTimeString();
    $password = bcrypt('password');

    foreach (array_chunk(range($offset + 1, $offset + $count), 250) as $chunk) {
        DB::table('users')->insert(array_map(fn (int $i) => [
            'uuid' => (string) Str::uuid(),
            'name' => "Imported Member {$i}",
            'email' => "imported{$i}@example.test",
            'password' => $password,
            'role' => User::ROLE_MEMBER,
            'created_at' => now()->subDays(30)->toDateTimeString(),
            'updated_at' => $now,
        ], $chunk));

        $ids = DB::table('users')->whereIn('email', array_map(fn (int $i) => "imported{$i}@example.test", $chunk))->pluck('id', 'email');

        DB::table('memberships')->insert(array_map(fn (int $i) => [
            'uuid' => (string) Str::uuid(),
            'user_id' => $ids["imported{$i}@example.test"],
            'membership_type_id' => $typeId,
            'membership_number' => 'IMP-'.$i,
            'status' => 'active',
            'source' => 'import',
            'applied_at' => $now,
            'activated_at' => $now,
            'expires_at' => now()->addMonths(6)->toDateTimeString(),
            'created_at' => $now,
            'updated_at' => $now,
        ], $chunk));
    }
}

function awaitingPaymentMembers(int $count): void
{
    foreach (range(1, $count) as $i) {
        Membership::create([
            'user_id' => ApprovalFixtures::member()->id,
            'membership_type_id' => ApprovalFixtures::membershipType()->id,
            'status' => 'applied',
            'source' => 'web',
            'applied_at' => now()->subDays(10),
        ]);
    }
}

function dashboardQueryCount(User $admin): array
{
    Cache::flush();
    DB::flushQueryLog();
    DB::enableQueryLog();

    $html = test()->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return [$count, $html];
}

it('caps member rows at five and keeps the query count flat as membership grows', function () {
    ApprovalFixtures::paymentApproval();
    ApprovalFixtures::documentApproval();
    ApprovalFixtures::activityApproval();
    awaitingPaymentMembers(8);

    seedNeverSignedInMembers(10);
    [$smallQueries] = dashboardQueryCount($this->admin);

    seedNeverSignedInMembers(990, 10);
    expect(User::where('role', User::ROLE_MEMBER)->count())->toBeGreaterThanOrEqual(1000);

    [$largeQueries, $html] = dashboardQueryCount($this->admin);

    expect(preg_match_all('/data-member-row[\s>]/', $html))->toBeLessThanOrEqual(5)->toBeGreaterThan(0)
        ->and($html)->not->toContain('Imported Member 1<')
        ->and($largeQueries)->toBe($smallQueries)
        ->and($largeQueries)->toBeLessThan(80);
});

it('shows a waiting-on-you total equal to the number of queue rows', function () {
    ApprovalFixtures::paymentApproval();
    ApprovalFixtures::documentApproval();
    ApprovalFixtures::activityApproval();
    ApprovalFixtures::calibreApproval();
    awaitingPaymentMembers(2);

    $html = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->getContent();

    preg_match('/data-tile-value>(\d+)</', $html, $tile);
    $rows = substr_count($html, 'data-queue-row="data-queue-row"');

    expect((int) $tile[1])->toBe(4)->toBe($rows);
});

it('filters the queue by type with the chips', function () {
    ApprovalFixtures::paymentApproval();
    ApprovalFixtures::documentApproval();
    ApprovalFixtures::calibreApproval();

    $component = Livewire::actingAs($this->admin)
        ->test('pages::admin.dashboard')
        ->call('filter', 'document');

    expect($component->get('type'))->toBe('document')
        ->and(count($component->instance()->queue))->toBe(1)
        ->and($component->instance()->queue->first()->type->value)->toBe('document');

    $component->call('filter', 'not-a-type');
    expect($component->get('type'))->toBeNull()
        ->and(count($component->instance()->queue))->toBe(3);
});

it('shows total, active and expired from the membership snapshot', function () {
    $active = ApprovalFixtures::member();
    ApprovalFixtures::activeMembership($active);

    $lapsed = ApprovalFixtures::member();
    ApprovalFixtures::activeMembership($lapsed)->update(['expires_at' => now()->subMonth()]);

    ApprovalFixtures::member();

    $html = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)
        ->toMatch('/data-stat="total">3</')
        ->toMatch('/data-stat="active">1</')
        ->toMatch('/data-stat="expired">1</');
});

it('is not available to members', function () {
    $this->actingAs(ApprovalFixtures::member())->get(route('admin.dashboard'))->assertForbidden();
});
