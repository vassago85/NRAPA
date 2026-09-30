<?php

use App\Models\ShootingActivity;
use App\Models\User;
use Livewire\Livewire;

test('activity edit declares activity_tag_id so validation does not throw', function () {
    $member = User::factory()->create();
    $activity = ShootingActivity::create([
        'user_id' => $member->id,
        'activity_date' => now()->subDay(),
        'location' => 'Test Range',
        'status' => 'pending',
    ]);

    $this->actingAs($member);

    $component = Livewire::test('pages::member.activities.edit', ['activity' => $activity])
        ->set('activity_tag_id', 1);

    expect($component->get('activity_tag_id'))->toBe(1);

    try {
        $component->call('update');
    } catch (\Exception $e) {
        expect($e->getMessage())->not->toContain('No property found for validation');
    }
});
