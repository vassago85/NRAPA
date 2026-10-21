<?php

use App\Mail\PopResubmitRequested;
use App\Models\AuditLog;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\NtfyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    Storage::fake('local');

    $this->basic = MembershipType::create([
        'slug' => 'basic-pop',
        'name' => 'Basic Membership',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 350,
        'is_active' => true,
    ]);

    $this->dedicated = MembershipType::create([
        'slug' => 'dedicated-pop',
        'name' => 'Dedicated Sport Shooter',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'upgrade_price' => 750,
        'allows_dedicated_status' => true,
        'is_active' => true,
    ]);

    $this->member = User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'name' => 'Chris Client',
    ]);

    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'admin_type' => User::ADMIN_TYPE_STANDARD,
        'is_admin' => true,
        'name' => 'Ada Admin',
    ]);

    $this->current = Membership::create([
        'user_id' => $this->member->id,
        'membership_type_id' => $this->basic->id,
        'status' => 'active',
        'source' => 'web',
        'applied_at' => now()->subYear(),
        'approved_at' => now()->subYear(),
        'activated_at' => now()->subYear(),
        'expires_at' => now()->addMonths(6),
    ]);
});

function pendingUpgrade(string $path = 'proof-of-payment/activities.pdf'): Membership
{
    Storage::disk('local')->put($path, 'activity export');

    return Membership::create([
        'user_id' => test()->member->id,
        'membership_type_id' => test()->dedicated->id,
        'previous_membership_id' => test()->current->id,
        'status' => 'pending_payment',
        'source' => 'web',
        'change_amount' => 750,
        'proof_of_payment_path' => $path,
        'applied_at' => now(),
    ]);
}

test('requesting the pop again keeps the uploaded file and emails the member', function () {
    $upgrade = pendingUpgrade();

    Livewire::actingAs($this->admin)
        ->test('pages::admin.approvals.show', ['membership' => $upgrade])
        ->assertSee('Request POP again')
        ->set('popRequestMessage', 'Incorrect upload. Please resubmit your proof of payment.')
        ->call('requestPopAgain')
        ->assertHasNoErrors()
        ->assertSee('The file they uploaded is still here');

    $upgrade->refresh();

    expect($upgrade->status)->toBe('pending_payment')
        ->and((float) $upgrade->change_amount)->toBe(750.0)
        ->and($upgrade->proof_of_payment_path)->toBe('proof-of-payment/activities.pdf')
        ->and($upgrade->awaitsPopResubmit())->toBeTrue()
        ->and($upgrade->pop_resubmit_message)->toBe('Incorrect upload. Please resubmit your proof of payment.');

    Storage::disk('local')->assertExists('proof-of-payment/activities.pdf');

    Mail::assertSent(PopResubmitRequested::class, function (PopResubmitRequested $mail) use ($upgrade) {
        return $mail->hasTo($this->member->email)
            && $mail->membership->is($upgrade)
            && $mail->reason === 'Incorrect upload. Please resubmit your proof of payment.';
    });

    expect(AuditLog::where('event', 'pop_resubmit_requested')->where('auditable_id', $upgrade->id)->exists())->toBeTrue();
});

test('the request button stays hidden until a file has been uploaded', function () {
    $upgrade = pendingUpgrade();
    $upgrade->update(['proof_of_payment_path' => null]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.approvals.show', ['membership' => $upgrade])
        ->assertDontSee('Request POP again')
        ->call('requestPopAgain')
        ->assertSee('There is no uploaded file to send back');

    expect($upgrade->fresh()->awaitsPopResubmit())->toBeFalse();
    Mail::assertNothingSent();
});

test('the member can replace the file and admins are told it was resubmitted', function () {
    $upgrade = pendingUpgrade();
    $upgrade->update([
        'pop_resubmit_requested_at' => now(),
        'pop_resubmit_message' => 'Incorrect upload. Please resubmit your proof of payment.',
    ]);

    Livewire::actingAs($this->member)
        ->test('pages::member.membership.index')
        ->assertSee('Incorrect upload. Please resubmit your proof of payment.')
        ->assertSee('Drop file here or click to browse');

    $ntfy = Mockery::mock(NtfyService::class);
    $ntfy->shouldReceive('notifyAdmins')
        ->once()
        ->with('payment_received', 'Proof of Payment Resubmitted', Mockery::type('string'));
    app()->instance(NtfyService::class, $ntfy);

    Livewire::actingAs($this->member)
        ->test('pages::member.membership.show', ['membership' => $upgrade])
        ->assertSee('Incorrect upload. Please resubmit your proof of payment.')
        ->assertSee('Upload Proof of Payment')
        ->assertDontSee('Proof of payment uploaded — pending admin review')
        ->set('proofOfPayment', UploadedFile::fake()->create('bank-pop.pdf', 100, 'application/pdf'))
        ->call('uploadProofOfPayment')
        ->assertHasNoErrors();

    $upgrade->refresh();

    expect($upgrade->status)->toBe('pending_payment')
        ->and((float) $upgrade->change_amount)->toBe(750.0)
        ->and($upgrade->awaitsPopResubmit())->toBeFalse()
        ->and($upgrade->pop_resubmit_message)->toBeNull()
        ->and($upgrade->proof_of_payment_path)->not->toBe('proof-of-payment/activities.pdf')
        ->and($upgrade->proof_of_payment_path)->toStartWith('proof-of-payment/');

    Storage::disk('local')->assertMissing('proof-of-payment/activities.pdf');
    Storage::disk('local')->assertExists($upgrade->proof_of_payment_path);

    Livewire::actingAs($this->member)
        ->test('pages::member.membership.index')
        ->assertDontSee('Please upload your proof of payment again');
});
