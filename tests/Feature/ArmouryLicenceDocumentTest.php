<?php

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Models\UserFirearm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Regression: the Virtual Safe edit page stored licence uploads on an undefined
 * 'private' disk (so every save with a licence failed) and linked to them with a
 * public Storage::url(). Licences now live on the private disk behind an owner/staff route.
 */
beforeEach(function () {
    Storage::fake('local');

    $type = MembershipType::create([
        'slug' => 'armoury-licence-annual',
        'name' => 'Annual',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 500,
        'is_active' => true,
    ]);

    $makeMember = function () use ($type) {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER, 'email_verified_at' => now()]);
        Membership::create([
            'user_id' => $user->id,
            'membership_type_id' => $type->id,
            'status' => 'active',
            'applied_at' => now(),
            'activated_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        return $user;
    };

    $this->owner = $makeMember();
    $this->otherMember = $makeMember();
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()]);

    $this->firearm = UserFirearm::create([
        'user_id' => $this->owner->id,
        'firearm_type' => 'rifle',
        'action' => 'bolt_action',
        'receiver_serial_number' => 'LIC-TEST-001',
        'license_status' => 'valid',
    ]);
});

function uploadLicence(User $owner, UserFirearm $firearm, string $name)
{
    return Livewire::actingAs($owner)
        ->test('pages::member.armoury.edit', ['firearm' => $firearm])
        ->set('license_document', UploadedFile::fake()->create($name, 100, 'application/pdf'))
        ->call('save');
}

test('member can attach a licence document and it is stored privately', function () {
    uploadLicence($this->owner, $this->firearm, 'licence.pdf')->assertHasNoErrors();

    $path = $this->firearm->fresh()->license_document_path;

    expect($path)->toStartWith("licenses/{$this->owner->id}/");
    Storage::disk('local')->assertExists($path);
});

test('replacing a licence deletes the previous file', function () {
    uploadLicence($this->owner, $this->firearm, 'old.pdf')->assertHasNoErrors();
    $oldPath = $this->firearm->fresh()->license_document_path;

    uploadLicence($this->owner, $this->firearm->fresh(), 'new.pdf')->assertHasNoErrors();

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($this->firearm->fresh()->license_document_path);
});

test('licence download is served to the owner and staff only', function () {
    uploadLicence($this->owner, $this->firearm, 'licence.pdf')->assertHasNoErrors();
    $firearm = $this->firearm->fresh();

    $this->actingAs($this->owner)->get(route('armoury.licence', $firearm))->assertOk();
    $this->actingAs($this->admin)->get(route('armoury.licence', $firearm))->assertOk();
    $this->actingAs($this->otherMember)->get(route('armoury.licence', $firearm))->assertForbidden();
});

test('licence download returns 404 when nothing is uploaded', function () {
    $this->actingAs($this->owner)
        ->get(route('armoury.licence', $this->firearm))
        ->assertNotFound();
});
