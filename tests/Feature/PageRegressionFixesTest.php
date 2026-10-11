<?php

use App\Models\TermsVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function () {
    $this->terms = TermsVersion::create([
        'version' => '2.1',
        'title' => 'Membership Terms 2.1',
        'is_active' => false,
        'html_content' => '<h2>Clause one</h2><p>Members must behave.</p>',
        'published_at' => null,
    ]);

    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()]);
    $this->admin->termsAcceptances()->create(['terms_version_id' => $this->terms->id, 'accepted_at' => now()]);
});

test('admin can preview a terms version with its html rendered', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.terms.preview', $this->terms))
        ->assertOk()
        ->assertSee('Membership Terms 2.1')
        ->assertSee('Version 2.1')
        ->assertSee('Not active')
        ->assertSee('<h2>Clause one</h2><p>Members must behave.</p>', false);
});

test('terms preview shows a placeholder when the version has no content', function () {
    $empty = TermsVersion::create([
        'version' => '3.0',
        'title' => 'Empty Draft',
        'is_active' => false,
        'html_content' => null,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.settings.terms.preview', $empty))
        ->assertOk()
        ->assertSee('This version has no content yet.');
});

test('members cannot preview terms versions', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER, 'email_verified_at' => now()]);
    $member->termsAcceptances()->create(['terms_version_id' => $this->terms->id, 'accepted_at' => now()]);

    $response = $this->actingAs($member)->get(route('admin.settings.terms.preview', $this->terms));

    expect($response->getStatusCode())->toBeIn([302, 403]);
});

test('bullet import page renders and lists import errors without clobbering the validation bag', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.bullet-database.import'))
        ->assertOk();

    Livewire::test('pages::admin.bullet-database.import')
        ->set('file', UploadedFile::fake()->createWithContent('bullets.csv', "name,manufacturer\n"))
        ->call('parseFile')
        ->assertSet('importErrors', [['row' => 0, 'message' => 'CSV must have a header row and at least one data row.']])
        ->assertSee('Validation Errors (1)')
        ->assertSee('CSV must have a header row and at least one data row.');
});

test('bullet import still reports form validation errors', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.bullet-database.import')
        ->call('parseFile')
        ->assertHasErrors(['file' => 'required']);
});

test('the dead member documents upload route is gone', function () {
    expect(Route::has('documents.upload'))->toBeFalse();
});

test('firstJoinedYear falls back to the current year for an unsaved user', function () {
    $user = new User(['role' => User::ROLE_MEMBER]);

    expect($user->firstJoinedYear())->toBe(now()->year);
});

test('firstJoinedYear uses created_at when the user has no memberships', function () {
    $user = User::factory()->create(['created_at' => now()->subYears(4)]);

    expect($user->firstJoinedYear())->toBe(now()->subYears(4)->year);
});
