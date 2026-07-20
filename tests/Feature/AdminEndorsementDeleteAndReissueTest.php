<?php

use App\Mail\EndorsementDeleted;
use App\Models\EndorsementComponent;
use App\Models\EndorsementFirearm;
use App\Models\EndorsementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Helper: create an already-issued endorsement for a fresh member,
 * with a linked firearm and one component row. This bypasses the full
 * approval/issue flow so we can exercise delete + clone logic directly
 * without setting up dedicated status, activities, documents, etc.
 */
function makeIssuedEndorsement(User $user, string $letterReference = 'END-2026-00042'): EndorsementRequest
{
    $request = EndorsementRequest::create([
        'user_id' => $user->id,
        'request_type' => EndorsementRequest::TYPE_NEW,
        'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_DEDICATED_STATUS,
        'purpose' => EndorsementRequest::PURPOSE_SECTION_16,
        'status' => EndorsementRequest::STATUS_ISSUED,
        'submitted_at' => now()->subDays(5),
        'reviewed_at' => now()->subDays(2),
        'issued_at' => now()->subDays(1),
        'expires_at' => now()->addYear()->subDays(1),
        'letter_reference' => $letterReference,
        'letter_file_path' => 'documents/endorsement-letter-fake.html',
        'dedicated_status_compliant' => true,
        'dedicated_category' => EndorsementRequest::DEDICATED_CATEGORY_SPORT,
        'dedicated_status_snapshot_at' => now()->subDays(1),
        'motivation_note' => 'Historical motivation text.',
    ]);

    EndorsementFirearm::create([
        'endorsement_request_id' => $request->id,
        'firearm_category' => 'handgun',
        'action_type' => 'semi_auto',
        'make' => 'Glock',
        'model' => '17',
        'serial_number' => 'ABC12345',
        'calibre_manual' => '9mm Parabellum',
        'licence_section' => '13',
    ]);

    EndorsementComponent::create([
        'endorsement_request_id' => $request->id,
        'component_type' => 'barrel',
        'component_description' => 'Match-grade replacement barrel',
        'component_serial' => 'BRL-999',
        'component_make' => 'KKM',
        'calibre_manual' => '9mm Parabellum',
        'relates_to_firearm' => true,
    ]);

    return $request->fresh(['firearm', 'components']);
}

test('reissueAsNewLetter clones request into a fresh approved copy without carrying over letter fields', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $source = makeIssuedEndorsement($member, 'END-2026-00099');

    $clone = $source->reissueAsNewLetter($admin);

    expect($clone->id)->not->toBe($source->id);
    expect($clone->user_id)->toBe($member->id);
    expect($clone->request_type)->toBe(EndorsementRequest::TYPE_RENEWAL);
    expect($clone->status)->toBe(EndorsementRequest::STATUS_APPROVED);
    expect($clone->reviewer_id)->toBe($admin->id);

    // Snapshot fields are copied so the letter template has data to work with
    expect($clone->dedicated_category)->toBe($source->dedicated_category);
    expect((bool) $clone->dedicated_status_compliant)->toBeTrue();
    expect($clone->motivation_note)->toBe($source->motivation_note);

    // Fresh letter — no reference/file/issued/expires copied
    expect($clone->letter_reference)->toBeNull();
    expect($clone->letter_file_path)->toBeNull();
    expect($clone->issued_at)->toBeNull();
    expect($clone->expires_at)->toBeNull();

    // Firearm was replicated with a new uuid but same details
    expect($clone->firearm)->not->toBeNull();
    expect($clone->firearm->id)->not->toBe($source->firearm->id);
    expect($clone->firearm->uuid)->not->toBe($source->firearm->uuid);
    expect($clone->firearm->make)->toBe('Glock');
    expect($clone->firearm->model)->toBe('17');
    expect($clone->firearm->serial_number)->toBe('ABC12345');

    // Components were replicated
    expect($clone->components)->toHaveCount(1);
    expect($clone->components->first()->component_serial)->toBe('BRL-999');
    expect($clone->components->first()->id)->not->toBe($source->components->first()->id);

    // Source is not mutated
    $source->refresh();
    expect($source->status)->toBe(EndorsementRequest::STATUS_ISSUED);
    expect($source->letter_reference)->toBe('END-2026-00099');
});

test('reissueAsNewLetter refuses to clone endorsements that are not yet issued', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $draft = EndorsementRequest::create([
        'user_id' => $member->id,
        'request_type' => EndorsementRequest::TYPE_NEW,
        'status' => EndorsementRequest::STATUS_DRAFT,
    ]);

    expect(fn () => $draft->reissueAsNewLetter($admin))
        ->toThrow(\Exception::class, 'Only issued endorsements can be reissued.');
});

test('generateLetterReference returns a sequential END-{YEAR}-{#####} value that skips soft-deleted references', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $year = date('Y');
    $first = EndorsementRequest::generateLetterReference();
    expect($first)->toMatch("/^END-{$year}-\\d{5}$/");

    // Persist it on an issued row then soft-delete — the next reference must skip past it
    // (generateLetterReference uses withTrashed to prevent reference reuse).
    $issued = makeIssuedEndorsement($member, $first);
    $issued->delete();

    $next = EndorsementRequest::generateLetterReference();
    expect($next)->not->toBe($first);
});

test('public verify route returns "not found" for a soft-deleted issued endorsement so QR codes stop working', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $issued = makeIssuedEndorsement($member, 'END-2026-00123');

    $this->get(route('endorsements.verify', ['reference' => $issued->letter_reference]))
        ->assertOk()
        ->assertViewIs('pages.verify-endorsement')
        ->assertViewHas('error', null);

    $issued->delete();

    $this->get(route('endorsements.verify', ['reference' => $issued->letter_reference]))
        ->assertOk()
        ->assertViewIs('pages.verify-endorsement')
        ->assertViewHas('error', 'Endorsement not found');
});

test('deleting an issued endorsement emails the member a QR-invalid warning and soft-deletes the row', function () {
    Mail::fake();

    $member = User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'name' => 'Jane Member',
        'email' => 'jane.member@example.test',
    ]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);

    $issued = makeIssuedEndorsement($member, 'END-2026-00777');

    $this->actingAs($admin);

    Livewire::test('pages::admin.endorsements.show', ['request' => $issued])
        ->call('deleteEndorsement');

    expect(EndorsementRequest::find($issued->id))->toBeNull();
    expect(EndorsementRequest::withTrashed()->find($issued->id)?->trashed())->toBeTrue();

    Mail::assertSent(EndorsementDeleted::class, function (EndorsementDeleted $mail) use ($member) {
        return $mail->hasTo($member->email)
            && $mail->letterReference === 'END-2026-00777'
            && $mail->wasIssued === true
            && $mail->memberName === 'Jane Member';
    });

    Mail::assertSentCount(1);
    Mail::assertNothingQueued();
});

test('deleted endorsement email body carries the do-not-submit / QR-invalid warning for issued letters', function () {
    Mail::fake();

    $member = User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'name' => 'Sam Shooter',
        'email' => 'sam@example.test',
    ]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);

    $issued = makeIssuedEndorsement($member, 'END-2026-00888');

    $this->actingAs($admin);

    Livewire::test('pages::admin.endorsements.show', ['request' => $issued])
        ->call('deleteEndorsement');

    Mail::assertSent(EndorsementDeleted::class, function (EndorsementDeleted $mail) {
        $rendered = $mail->render();

        return str_contains($rendered, 'do not submit it')
            && str_contains($rendered, 'no longer valid')
            && str_contains($rendered, 'END-2026-00888');
    });
});

test('deleting an endorsement for a member with no email skips the notification but still soft-deletes', function () {
    Mail::fake();

    $member = User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'email' => '',
    ]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);

    $issued = makeIssuedEndorsement($member, 'END-2026-00666');

    $this->actingAs($admin);

    Livewire::test('pages::admin.endorsements.show', ['request' => $issued])
        ->call('deleteEndorsement');

    Mail::assertNothingSent();
    expect(EndorsementRequest::withTrashed()->find($issued->id)?->trashed())->toBeTrue();
});
