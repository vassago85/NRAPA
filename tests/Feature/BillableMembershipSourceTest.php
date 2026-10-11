<?php

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Livewire\Livewire;

/**
 * Web and admin applications are billed; imports and transfers are not. Billed applications sit in
 * "Awaiting payment" until a proof of payment or confirmation arrives; everything else goes straight
 * to the approval queue.
 */
beforeEach(function () {
    $this->type = MembershipType::create([
        'slug' => 'billing-annual',
        'name' => 'Billing Annual',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 500,
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->apply = function (?string $source, string $status = 'applied', array $extra = []) {
        return Membership::create(array_merge([
            'user_id' => User::factory()->create(['role' => User::ROLE_MEMBER])->id,
            'membership_type_id' => $this->type->id,
            'status' => $status,
            'source' => $source,
            'applied_at' => now()->subDay(),
        ], $extra));
    };
});

test('only web and admin applications are billable', function (?string $source, bool $billable) {
    $membership = ($this->apply)($source);

    expect($membership->isBillable())->toBe($billable);
})->with([
    'web' => ['web', true],
    'admin' => ['admin', true],
    'import' => ['import', false],
    'transfer' => ['transfer', false],
]);

test('billable scope keeps billable sources and drops rejected or unpaid type changes', function () {
    $web = ($this->apply)('web', 'active');
    $admin = ($this->apply)('admin');
    ($this->apply)('import', 'active');
    ($this->apply)('transfer');
    ($this->apply)('web', 'revoked');
    ($this->apply)('web', 'pending_change');

    expect(Membership::billable()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$web->id, $admin->id])->sort()->values()->all());
});

test('approvals page splits applications into awaiting payment and ready to approve', function () {
    $webUnpaid = ($this->apply)('web');
    $adminUnpaid = ($this->apply)('admin');
    $webWithPop = ($this->apply)('web', 'applied', ['proof_of_payment_path' => 'proof-of-payment/x.pdf']);
    $adminConfirmed = ($this->apply)('admin', 'applied', ['payment_confirmed_at' => now()]);
    $import = ($this->apply)('import');
    $transfer = ($this->apply)('transfer');
    $pendingPayment = ($this->apply)('web', 'pending_payment');

    $this->actingAs($this->admin);
    $page = Livewire::test('pages::admin.approvals.index')->instance();

    $awaitingIds = collect($page->awaitingPayment)->pluck('id')->sort()->values()->all();
    expect($awaitingIds)->toBe(collect([$webUnpaid->id, $adminUnpaid->id, $pendingPayment->id])->sort()->values()->all());

    $readyIds = collect($page->pendingApprovals)
        ->whereIn('type', ['membership', 'transfer'])
        ->pluck('id')->sort()->values()->all();
    expect($readyIds)->toBe(collect([$webWithPop->id, $adminConfirmed->id, $import->id, $transfer->id])->sort()->values()->all());

    expect($page->stats['awaiting_payment'])->toBe(3)
        ->and($page->stats['memberships'])->toBeGreaterThanOrEqual(4);
});

test('approval detail flags only unpaid billable applications as awaiting payment', function (?string $source, string $status, array $extra, bool $awaiting) {
    $membership = ($this->apply)($source, $status, $extra);
    $this->actingAs($this->admin);

    expect(Livewire::test('pages::admin.approvals.show', ['membership' => $membership])->instance()->isAwaitingPayment)
        ->toBe($awaiting);
})->with([
    'web unpaid' => ['web', 'applied', [], true],
    'admin unpaid' => ['admin', 'applied', [], true],
    'web with proof of payment' => ['web', 'applied', ['proof_of_payment_path' => 'proof-of-payment/x.pdf'], false],
    'web payment confirmed' => ['web', 'applied', ['payment_confirmed_at' => '2026-10-01 10:00:00'], false],
    'import' => ['import', 'applied', [], false],
    'transfer' => ['transfer', 'applied', [], false],
    'pending payment' => ['web', 'pending_payment', [], true],
]);

test('members list reports the membership awaiting payment for billable applicants only', function () {
    $webUnpaid = ($this->apply)('web');
    $import = ($this->apply)('import');
    $this->actingAs($this->admin);

    $page = Livewire::test('pages::admin.members.index')->instance();

    expect($page->getAwaitingPaymentMembership($webUnpaid->user->load('memberships'))?->id)->toBe($webUnpaid->id)
        ->and($page->getAwaitingPaymentMembership($import->user->load('memberships')))->toBeNull();
});

test('admin dashboard and approvals pages render with every source present', function () {
    foreach (['web', 'admin', 'import', 'transfer'] as $source) {
        ($this->apply)($source);
    }

    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.approvals.index'))->assertOk();
});
