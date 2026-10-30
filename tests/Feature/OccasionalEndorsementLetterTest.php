<?php

use App\Models\EndorsementRequest;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\EndorsementLetterIssuer;
use App\Support\Approvals\PreflightCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Build a membership type that does NOT grant any dedicated status.
 * The whole point of the occasional (Section 15) letter is that it's
 * available to basic members who aren't dedicated hunters / sport shooters.
 */
function occasionalBasicType(): MembershipType
{
    return MembershipType::create([
        'slug' => 'occasional-test-basic-'.uniqid(),
        'name' => 'Basic Annual',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'expiry_rule' => 'rolling',
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 400.00,
        'is_active' => true,
        'dedicated_type' => null, // explicitly basic — no dedicated status
    ]);
}

function occasionalBasicMember(): User
{
    $user = User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'name' => 'Occasional Olivia',
        'id_number' => '8502150080081',
    ]);

    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => occasionalBasicType()->id,
        'status' => 'active',
        'source' => 'import',
        'applied_at' => now()->subMonths(2),
        'approved_at' => now()->subMonths(2),
        'activated_at' => now()->subMonths(2),
        'expires_at' => now()->addMonths(10),
        'membership_number' => 'NRAPA-OCC-TEST-'.random_int(1000, 9999),
    ]);

    return $user->fresh();
}

function occasionalDraft(User $user, array $overrides = []): EndorsementRequest
{
    return EndorsementRequest::create(array_merge([
        'user_id' => $user->id,
        'request_type' => EndorsementRequest::TYPE_NEW,
        'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL,
        'status' => EndorsementRequest::STATUS_DRAFT,
        'firearm_make' => 'CZ',
        'firearm_model' => '457',
        'firearm_calibre' => '.22 LR',
        'firearm_type' => 'rifle',
        'firearm_serial' => 'Barrel: CZ-4571234',
        'declaration_accepted_at' => now(),
        'declaration_text' => 'Occasional (Section 15) declaration accepted for testing.',
    ], $overrides));
}

test('occasional (Section 15) letter is submittable by a basic member who is in good standing', function () {
    Queue::fake();
    $user = occasionalBasicMember();

    $request = occasionalDraft($user);

    expect($request->isOccasional())->toBeTrue();
    expect($request->canSubmit())->toBeTrue();
    expect($request->getSubmissionErrors())->toBe([]);

    expect($request->submit())->toBeTrue();
    expect($request->fresh()->status)->toBe(EndorsementRequest::STATUS_SUBMITTED);
});

test('occasional preflight does not fail on dedicated status or competency — only good standing', function () {
    $user = occasionalBasicMember();
    $request = occasionalDraft($user, ['status' => EndorsementRequest::STATUS_SUBMITTED, 'submitted_at' => now()]);

    $checks = $request->preflightChecks();

    // Dedicated-status compliance and competency checks must be absent for
    // occasional letters, otherwise basic members could never qualify.
    $keys = collect($checks)->map(fn (PreflightCheck $c) => $c->key)->all();
    expect($keys)->not->toContain('dedicated_compliant');
    expect($keys)->not->toContain('competency');

    // Membership-active and acknowledgements checks must still pass.
    foreach ($checks as $check) {
        expect($check->passed)->toBeTrue('Preflight check '.$check->key.' failed unexpectedly: '.$check->reason);
    }
});

test('issuer writes an occasional letter with no dedicated category or compliance flag', function () {
    $user = occasionalBasicMember();
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
    $request = occasionalDraft($user, ['status' => EndorsementRequest::STATUS_APPROVED]);

    // Stub the PDF renderer so the test does not need Gotenberg/dompdf.
    $renderer = \Mockery::mock(\App\Contracts\DocumentRenderer::class);
    $renderer->shouldReceive('renderEndorsementLetter')
        ->once()
        ->andReturn('documents/test-occasional-letter.pdf');
    app()->instance(\App\Contracts\DocumentRenderer::class, $renderer);

    // Even if the admin UI accidentally hands in a stale dedicated category,
    // the issuer must null it out for occasional letters so the DFO never
    // sees "Dedicated Status" on a Section 15 letter.
    app(EndorsementLetterIssuer::class)->issueApprovedLetter(
        $request,
        $admin,
        dedicatedCategory: 'Dedicated Sport Shooter',
        dedicatedStatusCompliant: true,
    );

    $request->refresh();

    expect($request->status)->toBe(EndorsementRequest::STATUS_ISSUED);
    expect($request->letter_reference)->toMatch('/^END-\d{4}-\d{5}$/');
    expect($request->dedicated_category)->toBeNull();
    expect($request->dedicated_status_compliant)->toBeNull();
    expect($request->expires_at)->not->toBeNull();
});

test('occasional letter template contains the Section 15 wording and omits dedicated-status language', function () {
    $user = occasionalBasicMember();
    $request = occasionalDraft($user, [
        'status' => EndorsementRequest::STATUS_ISSUED,
        'letter_reference' => 'END-2026-00099',
        'issued_at' => now(),
        'expires_at' => now()->addYear(),
    ]);

    $html = view('documents.letters.endorsement', [
        'request' => $request->fresh(['user', 'user.activeMembership']),
    ])->render();

    // Positive assertions — the wording DFOs expect for Section 15 motivations.
    expect($html)->toContain('Section 15');
    expect($html)->toContain('occasional sport shooting or occasional hunting');
    expect($html)->toContain('member in good standing');
    expect($html)->toContain('CZ');  // make
    expect($html)->toContain('457'); // model
    expect($html)->toContain('.22 LR'); // calibre
    expect($html)->toContain('CZ-4571234'); // serial

    // Negative assertions — the exact lines that make a dedicated-status
    // letter the wrong proof for a Section 15 application. These labels only
    // live in the dedicated-status member-info rows and the body of the
    // dedicated-status variant; neither should render on an occasional letter.
    expect($html)->not->toContain('>Dedicated Status<');
    expect($html)->not->toContain('>Dedicated Category<');
    expect($html)->not->toContain('dedicated hunter and/or dedicated sports person');
    expect($html)->not->toContain("Issued under the member's");

    // And it must say, in plain English, that this is not a dedicated letter.
    expect($html)->toContain('not a dedicated status endorsement');
});

test('issuing a dedicated-status endorsement still refuses when dedicated status is not compliant', function () {
    $user = occasionalBasicMember(); // basic member, no dedicated type → cannot pass compliance
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);

    $dedicatedRequest = EndorsementRequest::create([
        'user_id' => $user->id,
        'request_type' => EndorsementRequest::TYPE_NEW,
        'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_DEDICATED_STATUS,
        'purpose' => EndorsementRequest::PURPOSE_SECTION_16,
        'status' => EndorsementRequest::STATUS_APPROVED,
        'declaration_accepted_at' => now(),
        'declaration_text' => 'Dedicated declaration (test).',
    ]);

    // Non-compliant dedicated status must block issuance — this is the whole
    // point of the dedicated-status variant and must survive the occasional
    // branch being added to issue().
    expect(fn () => $dedicatedRequest->issue(
        $admin,
        'END-TEST-0001',
        null,
        dedicatedStatusCompliant: false,
        dedicatedCategory: 'Dedicated Sport Shooter',
    ))->toThrow(\Exception::class, 'Dedicated Status is not compliant');
});

test('admin create form writes an occasional request with inline firearm fields and no dedicated category', function () {
    Queue::fake();

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
    $member = occasionalBasicMember();

    // Avoid Gotenberg — assert against the request+audit, not the PDF.
    $renderer = \Mockery::mock(\App\Contracts\DocumentRenderer::class);
    $renderer->shouldReceive('renderEndorsementLetter')
        ->zeroOrMoreTimes()
        ->andReturn('documents/admin-form-occasional-letter.pdf');
    app()->instance(\App\Contracts\DocumentRenderer::class, $renderer);

    Livewire::actingAs($admin)
        ->test('pages::admin.endorsements.create')
        ->call('selectMember', $member->id)
        ->set('endorsementType', EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL)
        ->set('firearmCategory', 'rifle')
        ->set('make', 'Ruger')
        ->set('model', 'American')
        ->set('calibreManual', '.223 Remington')
        ->set('frameSerial', 'RUG-12345')
        ->call('createEndorsement');

    $created = EndorsementRequest::where('user_id', $member->id)->first();

    expect($created)->not->toBeNull();
    expect($created->endorsement_type)->toBe(EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL);
    expect($created->dedicated_category)->toBeNull();
    expect($created->purpose)->toBeNull();
    expect($created->firearm_make)->toBe('Ruger');
    expect($created->firearm_model)->toBe('American');
    expect($created->firearm_calibre)->toBe('.223 Remington');
    expect($created->firearm_serial)->toBe('Frame: RUG-12345');
    // No related firearm row — occasional data lives inline, same as self-defence.
    expect($created->firearm()->exists())->toBeFalse();
});

test('admin create form writes a self-defence request with inline firearm fields and the correct endorsement_type', function () {
    Queue::fake();

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
    $member = occasionalBasicMember();

    $renderer = \Mockery::mock(\App\Contracts\DocumentRenderer::class);
    $renderer->shouldReceive('renderEndorsementLetter')
        ->zeroOrMoreTimes()
        ->andReturn('documents/admin-form-sd-letter.pdf');
    app()->instance(\App\Contracts\DocumentRenderer::class, $renderer);

    Livewire::actingAs($admin)
        ->test('pages::admin.endorsements.create')
        ->call('selectMember', $member->id)
        ->set('endorsementType', EndorsementRequest::ENDORSEMENT_TYPE_SELF_DEFENCE)
        ->set('dedicatedCategory', 'Dedicated Sport Shooter')
        ->set('firearmCategory', 'handgun')
        ->set('make', 'Glock')
        ->set('model', '19')
        ->set('calibreManual', '9mm Luger')
        ->set('frameSerial', 'GLK-12345')
        ->call('createEndorsement');

    $created = EndorsementRequest::where('user_id', $member->id)->first();

    expect($created)->not->toBeNull();
    // The exact regression from the WhatsApp report: a Section 13 letter must
    // store endorsement_type = self_defence, not occasional, so the Blade
    // template renders the self-defence banner and body.
    expect($created->endorsement_type)->toBe(EndorsementRequest::ENDORSEMENT_TYPE_SELF_DEFENCE);
    expect($created->isSelfDefence())->toBeTrue();
    expect($created->isOccasional())->toBeFalse();
    expect($created->dedicated_category)->toBe('Dedicated Sport Shooter');
    expect($created->firearm_type)->toBe('handgun');
    expect($created->firearm_make)->toBe('Glock');
    expect($created->firearm_serial)->toBe('Frame: GLK-12345');
    expect($created->firearm()->exists())->toBeFalse();
});

test('self-defence letter template renders the self-defence banner and body, not the occasional wording', function () {
    $user = occasionalBasicMember();

    $request = EndorsementRequest::create([
        'user_id' => $user->id,
        'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_SELF_DEFENCE,
        'request_type' => EndorsementRequest::TYPE_NEW,
        'status' => EndorsementRequest::STATUS_ISSUED,
        'submitted_at' => now()->subDay(),
        'declaration_accepted_at' => now()->subDay(),
        'declaration_text' => 'Section 13 self-defence declaration (test).',
        'letter_reference' => 'END-2026-00123',
        'issued_at' => now(),
        'expires_at' => now()->addYear(),
        'dedicated_category' => 'Dedicated Sport Shooter',
        'dedicated_status_compliant' => true,
        'firearm_type' => 'handgun',
        'firearm_make' => 'Glock',
        'firearm_model' => '19',
        'firearm_calibre' => '9mm Luger',
        'firearm_serial' => 'Frame: GLK-12345',
    ]);

    $html = view('documents.letters.endorsement', [
        'request' => $request->fresh(['user', 'user.activeMembership']),
    ])->render();

    // Must render the Section 13 banner subtitle and body…
    expect($html)->toContain('Self-defence supporting letter');
    expect($html)->toContain('Section 13 of the Firearms Control Act');

    // …and must NOT render the Section 15 occasional banner or body.
    expect($html)->not->toContain('Occasional sport shooting or hunting (Section 15)');
    expect($html)->not->toContain('not a dedicated status endorsement');
    expect($html)->not->toContain('fit for occasional sport shooting or occasional hunting');
});

test('admin create form requires at least one serial on an occasional request', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
    $member = occasionalBasicMember();

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.endorsements.create')
        ->call('selectMember', $member->id)
        ->set('endorsementType', EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL)
        ->set('firearmCategory', 'rifle')
        ->set('make', 'Ruger')
        ->set('model', 'American')
        ->set('calibreManual', '.223 Remington')
        ->call('createEndorsement');

    $component->assertHasErrors('barrelSerial');
    expect(EndorsementRequest::where('user_id', $member->id)->count())->toBe(0);
});

test('occasional request from the member page submits as the ENDORSEMENT_TYPE_OCCASIONAL variant', function () {
    Queue::fake();

    $user = occasionalBasicMember();

    Livewire::actingAs($user)
        ->test('pages::member.endorsements.occasional')
        ->set('firearmType', 'rifle')
        ->set('firearmMake', 'Howa')
        ->set('firearmModel', '1500')
        ->set('firearmCalibre', '.308 Winchester')
        ->set('firearmFrameSerial', 'HOWA-999')
        ->set('declarationAccepted', true)
        ->call('submit');

    $submitted = EndorsementRequest::where('user_id', $user->id)
        ->where('endorsement_type', EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL)
        ->first();

    expect($submitted)->not->toBeNull();
    expect($submitted->status)->toBe(EndorsementRequest::STATUS_SUBMITTED);
    expect($submitted->firearm_make)->toBe('Howa');
    expect($submitted->firearm_serial)->toBe('Frame: HOWA-999');
    expect($submitted->declaration_accepted_at)->not->toBeNull();
});

test('choosing a section 13 letter locks the licence section to section 13', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.endorsements.create')
        ->set('endorsementType', EndorsementRequest::ENDORSEMENT_TYPE_SELF_DEFENCE)
        ->set('firearmCategory', 'handgun');

    // The licence section must follow the letter. Leaving it on 16 (or offering
    // 15 and 16 in the dropdown) is the bug: a Section 13 letter was being
    // categorised as a Section 15/16 licence.
    $component->assertSet('licenceSection', '13');

    $html = $component->html();
    expect($html)->toContain('Section 13 (Self-defence)');
    expect($html)->not->toContain('value="15"');
    expect($html)->not->toContain('value="16"');
    expect($html)->toContain('Handgun');
    expect($html)->not->toContain('Self-Loading Rifle');

    $component
        ->set('endorsementType', EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL)
        ->assertSet('licenceSection', '15')
        ->set('endorsementType', EndorsementRequest::ENDORSEMENT_TYPE_DEDICATED_STATUS)
        ->assertSet('licenceSection', '16');
});

test('a section 15 letter locks its licence section and a dedicated letter can still choose one', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
    $member = occasionalBasicMember();

    $occasional = Livewire::actingAs($admin)
        ->test('pages::admin.endorsements.create')
        ->set('endorsementType', EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL)
        ->set('firearmCategory', 'rifle');

    $occasional->assertSet('licenceSection', '15');
    $occasionalHtml = $occasional->html();
    expect($occasionalHtml)->toContain('Section 15 (Occasional hunting/sport)');
    expect($occasionalHtml)->not->toContain('value="13"');
    expect($occasionalHtml)->not->toContain('value="16"');

    $dedicated = Livewire::actingAs($admin)
        ->test('pages::admin.endorsements.create')
        ->set('firearmCategory', 'rifle');

    $dedicated->assertSet('licenceSection', '16');
    $dedicatedHtml = $dedicated->html();
    expect($dedicatedHtml)->toContain('value="13"');
    expect($dedicatedHtml)->toContain('value="15"');
    expect($dedicatedHtml)->toContain('value="16"');
    expect($dedicatedHtml)->toContain('Self-Loading Rifle');

    $dedicated
        ->call('selectMember', $member->id)
        ->set('dedicatedCategory', 'Dedicated Hunter')
        ->set('licenceSection', '15')
        ->set('make', 'Howa')
        ->set('model', '1500')
        ->call('createEndorsement');

    $created = EndorsementRequest::where('user_id', $member->id)->first();

    expect($created)->not->toBeNull();
    expect($created->endorsement_type)->toBe(EndorsementRequest::ENDORSEMENT_TYPE_DEDICATED_STATUS);
    expect($created->dedicated_category)->toBe('Dedicated Hunter');
    expect($created->firearm)->not->toBeNull();
    expect($created->firearm->licence_section)->toBe('15');
});
