<?php

declare(strict_types=1);

use App\Actions\Memberships\ApproveMembership;
use App\Models\Invoice;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Billing\InvoiceIssuer;
use App\Services\Billing\InvoiceNotIssuableException;
use App\Services\Billing\InvoiceNumberSequence;
use App\Services\Billing\InvoiceRenderer;
use App\Services\CertificateIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Make the invoice renderer a no-op in tests: write a stub PDF payload
    // so the download pipeline resolves to a file without needing Gotenberg
    // or DomPDF (same pattern the certificate tests use).
    $this->instance(InvoiceRenderer::class, new class extends InvoiceRenderer
    {
        public function render(Invoice $invoice): string
        {
            $path = sprintf('invoices/%s/%s.pdf', $invoice->issued_at?->format('Y') ?? date('Y'), $invoice->number);
            Storage::disk(\App\Helpers\StorageHelper::getPrivateDisk())
                ->put($path, '%PDF-1.4 stub for '.$invoice->number);
            $invoice->update(['pdf_path' => $path]);

            return $path;
        }
    });
});

/** Billable annual type so Membership::amount_due resolves. */
function invMakeAnnualType(string $slug = 'inv-annual'): MembershipType
{
    return MembershipType::firstOrCreate(
        ['slug' => $slug],
        [
            'name' => 'Invoiceable Annual',
            'duration_type' => 'annual',
            'duration_months' => 12,
            'requires_renewal' => true,
            'pricing_model' => 'annual',
            'initial_price' => 550,
            'renewal_price' => 550,
            'is_active' => true,
        ],
    );
}

function invMakeConfirmedMembership(
    ?User $user = null,
    ?MembershipType $type = null,
    string $source = 'web',
    ?\DateTimeInterface $paidAt = null,
): Membership {
    $user ??= User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'physical_address' => "702 Esther Street\nPretoria\n0042",
        'postal_address' => "702 Esther Street\nPretoria\n0042",
    ]);
    $type ??= invMakeAnnualType();

    $paidAt ??= now()->subMonth();
    static $counter = 0;
    $counter++;

    return Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'source' => $source,
        'applied_at' => $paidAt,
        'approved_at' => $paidAt,
        'activated_at' => $paidAt,
        'expires_at' => $paidAt->copy()->addYear(),
        'payment_confirmed_at' => $paidAt,
        'payment_reference' => sprintf('NRAPA-TEST-%04d', $counter),
    ]);
}

// ---------- numbering ----------

it('mints the first invoice as I-NRAPA-000001 and advances the counter', function () {
    $membership = invMakeConfirmedMembership();

    $invoice = app(InvoiceIssuer::class)->forMembership($membership);

    expect($invoice->number)->toBe('I-NRAPA-000001')
        ->and((int) SystemSetting::get(InvoiceNumberSequence::SETTING_NEXT))->toBe(2);
});

it('honours a custom prefix and width when both are set before issuing', function () {
    SystemSetting::set(InvoiceNumberSequence::SETTING_PREFIX, 'INV-', 'string', 'invoicing');
    SystemSetting::set(InvoiceNumberSequence::SETTING_WIDTH, '4', 'integer', 'invoicing');
    SystemSetting::set(InvoiceNumberSequence::SETTING_NEXT, '42', 'integer', 'invoicing');

    $membership = invMakeConfirmedMembership();
    $invoice = app(InvoiceIssuer::class)->forMembership($membership);

    expect($invoice->number)->toBe('INV-0042');
});

// ---------- idempotency / immutability ----------

it('returns the same invoice on a second call for the same membership', function () {
    $membership = invMakeConfirmedMembership();
    $issuer = app(InvoiceIssuer::class);

    $first = $issuer->forMembership($membership);
    $second = $issuer->forMembership($membership);

    expect($second->id)->toBe($first->id)
        ->and($second->number)->toBe($first->number)
        ->and(Invoice::count())->toBe(1);
});

// ---------- guards ----------

it('refuses to invoice a membership that is not billable (import)', function () {
    $membership = invMakeConfirmedMembership(source: 'import');

    expect(fn () => app(InvoiceIssuer::class)->forMembership($membership))
        ->toThrow(InvoiceNotIssuableException::class);
});

it('refuses to invoice a membership that has no payment_confirmed_at', function () {
    $membership = invMakeConfirmedMembership();
    $membership->update(['payment_confirmed_at' => null]);

    expect(fn () => app(InvoiceIssuer::class)->forMembership($membership->fresh()))
        ->toThrow(InvoiceNotIssuableException::class);
});

// ---------- snapshot ----------

it('freezes the member address and bank details on the invoice at issue time', function () {
    SystemSetting::set('bank_name', 'FIRST NATIONAL BANK', 'string', 'bank');
    SystemSetting::set('bank_account_number', '62864215176', 'string', 'bank');

    $membership = invMakeConfirmedMembership();
    $invoice = app(InvoiceIssuer::class)->forMembership($membership);

    // Member later changes their address — the invoice snapshot must stand.
    $membership->user->update(['physical_address' => 'New address somewhere else']);
    SystemSetting::set('bank_account_number', '99999999999', 'string', 'bank');

    $invoice->refresh();

    expect($invoice->payload['to']['physical_address'])->toContain('Esther Street')
        ->and($invoice->payload['bank']['account_number'])->toBe('62864215176')
        ->and($invoice->payload['bank']['bank_name'])->toBe('FIRST NATIONAL BANK');
});

// ---------- line item ----------

it('tags a renewal line with the RENEWMEM sku', function () {
    $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $type = invMakeAnnualType();

    $prev = invMakeConfirmedMembership($user, $type);
    // Renewal: new membership rows pointing back to the previous one.
    $renewal = Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'source' => 'web',
        'applied_at' => now(),
        'approved_at' => now(),
        'activated_at' => now(),
        'expires_at' => now()->addYear(),
        'payment_confirmed_at' => now(),
        'payment_reference' => 'NRAPA-RENEWAL-'.$prev->id,
        'previous_membership_id' => $prev->id,
    ]);

    $invoice = app(InvoiceIssuer::class)->forMembership($renewal);

    expect($invoice->line_items[0]['sku'])->toBe('RENEWMEM')
        ->and($invoice->line_items[0]['description'])->toContain('Annual Membership Renewal');
});

it('tags a brand-new membership with the NEWMEM sku', function () {
    $invoice = app(InvoiceIssuer::class)->forMembership(invMakeConfirmedMembership());

    expect($invoice->line_items[0]['sku'])->toBe('NEWMEM')
        ->and($invoice->line_items[0]['description'])->toContain('New Membership');
});

// ---------- download controller ----------

it('lets an admin download an invoice', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
    $membership = invMakeConfirmedMembership();
    $invoice = app(InvoiceIssuer::class)->forMembership($membership);

    $response = $this->actingAs($admin)
        ->get(route('admin.invoices.download', $invoice->number));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('rejects a non-admin trying to download an invoice', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $membership = invMakeConfirmedMembership();
    $invoice = app(InvoiceIssuer::class)->forMembership($membership);

    $this->actingAs($member)
        ->get(route('admin.invoices.download', $invoice->number))
        ->assertForbidden();
});

it('lazily issues an invoice when admin clicks Issue on an un-invoiced row', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
    $membership = invMakeConfirmedMembership();

    expect(Invoice::count())->toBe(0);

    $this->actingAs($admin)
        ->get(route('admin.invoices.download-for-membership', $membership))
        ->assertOk();

    expect(Invoice::count())->toBe(1)
        ->and(Invoice::first()->issued_by)->toBe($admin->id);
});

// ---------- backfill command ----------

it('backfills existing confirmed memberships in payment date order', function () {
    $type = invMakeAnnualType();

    $june = invMakeConfirmedMembership(type: $type, paidAt: now()->subMonths(4)->startOfDay());
    $august = invMakeConfirmedMembership(type: $type, paidAt: now()->subMonths(2)->startOfDay());
    $july = invMakeConfirmedMembership(type: $type, paidAt: now()->subMonths(3)->startOfDay());

    $this->artisan('nrapa:backfill-invoices')->assertExitCode(0);

    expect(Invoice::count())->toBe(3);

    $juneInvoice = Invoice::where('source_id', $june->id)->first();
    $julyInvoice = Invoice::where('source_id', $july->id)->first();
    $augustInvoice = Invoice::where('source_id', $august->id)->first();

    expect($juneInvoice->number)->toBe('I-NRAPA-000001')
        ->and($julyInvoice->number)->toBe('I-NRAPA-000002')
        ->and($augustInvoice->number)->toBe('I-NRAPA-000003');
});

it('backfill is idempotent — running twice does not mint new invoices', function () {
    invMakeConfirmedMembership();
    invMakeConfirmedMembership();

    $this->artisan('nrapa:backfill-invoices')->assertExitCode(0);
    expect(Invoice::count())->toBe(2);

    $this->artisan('nrapa:backfill-invoices')->assertExitCode(0);
    expect(Invoice::count())->toBe(2);
});

it('backfill skips imported memberships', function () {
    invMakeConfirmedMembership(source: 'import');
    invMakeConfirmedMembership(source: 'web');

    $this->artisan('nrapa:backfill-invoices')->assertExitCode(0);

    expect(Invoice::count())->toBe(1);
});

it('issues an invoice when a billable application is approved', function () {
    Mail::fake();

    $certificates = Mockery::mock(CertificateIssueService::class);
    $certificates->shouldReceive('issueMembershipCertificate')->andReturnNull();
    $certificates->shouldReceive('issueWelcomeLetter')->andReturnNull();
    $certificates->shouldReceive('issueMembershipCard')->andReturnNull();
    app()->instance(CertificateIssueService::class, $certificates);

    $type = invMakeAnnualType();
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);

    $membership = Membership::create([
        'user_id' => $member->id,
        'membership_type_id' => $type->id,
        'status' => 'applied',
        'source' => 'web',
        'applied_at' => now(),
        'payment_reference' => 'NRAPA-NEW-0001',
    ]);

    app(ApproveMembership::class)->approveApplication($membership, $admin);

    $invoice = Invoice::where('source_type', Membership::class)
        ->where('source_id', $membership->id)
        ->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->number)->toBe('I-NRAPA-000001')
        ->and($invoice->issued_by)->toBe($admin->id)
        ->and($invoice->total)->toEqual(550);
});

it('renders invoice HTML with the member name, bank reference, and balance due', function () {
    SystemSetting::set('bank_name', 'FIRST NATIONAL BANK', 'string', 'bank');
    SystemSetting::set('bank_account_number', '62864215176', 'string', 'bank');

    $membership = invMakeConfirmedMembership();
    $invoice = app(InvoiceIssuer::class)->forMembership($membership);

    $html = view('documents.invoices.standard', ['invoice' => $invoice])->render();

    expect($html)
        ->toContain($invoice->number)
        ->toContain(strtoupper($membership->user->name))
        ->toContain($membership->payment_reference)
        ->toContain('FIRST NATIONAL BANK')
        ->toContain('62864215176')
        ->toContain('Balance Due')
        ->toContain('R550.00');
});
