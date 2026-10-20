<?php

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\RanyatiInvoice;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\InvoiceNinja\InvoiceNinjaException;
use App\Services\InvoiceNinja\IssueRanyatiInvoice;
use App\Services\InvoiceNinja\RanyatiInvoiceSettings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->type = MembershipType::create([
        'slug' => 'ranyati-annual',
        'name' => 'Ranyati Annual',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 500,
        'renewal_price' => 500,
        'is_active' => true,
    ]);
});

function configureRanyatiInvoicing(bool $enabled = true): void
{
    SystemSetting::set('invoice_ninja_enabled', $enabled ? '1' : '0', 'string', 'invoice_ninja');
    SystemSetting::set('invoice_ninja_api_url', 'https://invoice.charsley.co.za', 'string', 'invoice_ninja');
    SystemSetting::set('invoice_ninja_api_token', 'test-token', 'string', 'invoice_ninja');
    SystemSetting::set('invoice_ninja_client_id', 'RanyatiClient', 'string', 'invoice_ninja');
}

function ranyatiMembership(MembershipType $type, array $extra = []): Membership
{
    return Membership::create(array_merge([
        'user_id' => User::factory()->create()->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'source' => 'web',
        'applied_at' => '2026-09-01 10:00:00',
        'payment_confirmed_at' => '2026-09-15 10:00:00',
    ], $extra));
}

function fakeInvoiceNinja(int $emailStatus = 200): void
{
    Http::fake([
        'https://invoice.charsley.co.za/api/v1/invoices' => Http::response([
            'data' => ['id' => 'inv_1', 'number' => '0042'],
        ], 200),
        'https://invoice.charsley.co.za/api/v1/invoices/bulk' => Http::response([
            'message' => $emailStatus === 200 ? 'Success' : 'Client has no email',
        ], $emailStatus),
        'https://invoice.charsley.co.za/api/v1/clients/*' => Http::response([
            'data' => ['id' => 'RanyatiClient', 'display_name' => 'Ranyati Group'],
        ], 200),
    ]);
}

test('the monthly invoice is hosting plus one hundred per paid new member and renewal', function () {
    $previous = ranyatiMembership($this->type, [
        'payment_confirmed_at' => '2026-01-15 10:00:00',
    ]);
    ranyatiMembership($this->type);
    ranyatiMembership($this->type, ['source' => 'admin']);
    ranyatiMembership($this->type, ['previous_membership_id' => $previous->id]);
    ranyatiMembership($this->type, ['source' => 'import']);
    ranyatiMembership($this->type, ['payment_confirmed_at' => '2026-08-15 10:00:00']);
    ranyatiMembership($this->type, ['payment_confirmed_at' => null, 'status' => 'applied']);
    ranyatiMembership($this->type, ['status' => 'revoked']);

    $preview = app(IssueRanyatiInvoice::class)->preview(2026, 9);

    expect(app(RanyatiInvoiceSettings::class)->clientId())->toBe(RanyatiInvoiceSettings::DEFAULT_CLIENT_ID)
        ->and($preview['new_members'])->toBe(2)
        ->and($preview['renewals'])->toBe(1)
        ->and($preview['hosting_fee'])->toBe(1500.0)
        ->and($preview['member_fee'])->toBe(100.0)
        ->and($preview['total'])->toBe(1800.0);
});

test('a month with no paid members still invoices the hosting fee', function () {
    configureRanyatiInvoicing();
    fakeInvoiceNinja();

    $invoice = app(IssueRanyatiInvoice::class)->issue(2026, 8);

    expect($invoice->status)->toBe(RanyatiInvoice::STATUS_SENT)
        ->and((float) $invoice->total)->toBe(1500.0);

    Http::assertSent(function ($request) {
        if (! str_ends_with($request->url(), '/api/v1/invoices')) {
            return false;
        }

        $items = $request->data()['line_items'];

        return count($items) === 1
            && $items[0]['product_key'] === 'NRAPA hosting'
            && (float) $items[0]['cost'] === 1500.0
            && $request->data()['client_id'] === 'RanyatiClient'
            && $request->data()['po_number'] === 'NRAPA-2026-08'
            && $request->data()['date'] === '2026-09-01'
            && $request->data()['due_date'] === '2026-10-01';
    });
});

test('the invoice is created and emailed once for the month', function () {
    configureRanyatiInvoicing();
    fakeInvoiceNinja();
    ranyatiMembership($this->type);
    ranyatiMembership($this->type, ['previous_membership_id' => ranyatiMembership($this->type, [
        'payment_confirmed_at' => '2026-01-15 10:00:00',
    ])->id]);

    $issuer = app(IssueRanyatiInvoice::class);
    $first = $issuer->issue(2026, 9);
    $second = $issuer->issue(2026, 9);

    expect($first->status)->toBe('sent')
        ->and($first->invoice_number)->toBe('0042')
        ->and($second->id)->toBe($first->id)
        ->and(RanyatiInvoice::count())->toBe(1);

    $creates = collect(Http::recorded())->filter(
        fn (array $pair) => str_ends_with($pair[0]->url(), '/api/v1/invoices') && $pair[0]->method() === 'POST'
    );
    expect($creates)->toHaveCount(1);

    $payload = $creates->first()[0]->data();
    expect($payload['line_items'][1]['quantity'])->toBe(2)
        ->and((float) $payload['line_items'][1]['cost'])->toBe(100.0)
        ->and((float) $payload['line_items'][1]['tax_rate1'])->toBe(0.0)
        ->and($payload['line_items'][0]['notes'])->toContain('September 2026');
});

test('a failed email is retried without creating a second invoice', function () {
    configureRanyatiInvoicing();
    Http::fake([
        'https://invoice.charsley.co.za/api/v1/invoices/bulk' => Http::sequence()
            ->push(['message' => 'Client has no email'], 422)
            ->push(['message' => 'Success'], 200),
        'https://invoice.charsley.co.za/api/v1/invoices' => Http::response([
            'data' => ['id' => 'inv_1', 'number' => '0042'],
        ], 200),
    ]);
    ranyatiMembership($this->type);

    $issuer = app(IssueRanyatiInvoice::class);

    expect(fn () => $issuer->issue(2026, 9))->toThrow(InvoiceNinjaException::class);
    expect(RanyatiInvoice::first()->status)->toBe(RanyatiInvoice::STATUS_CREATED);

    $invoice = $issuer->issue(2026, 9);

    expect($invoice->status)->toBe(RanyatiInvoice::STATUS_SENT)
        ->and(RanyatiInvoice::count())->toBe(1);

    $creates = collect(Http::recorded())->filter(
        fn (array $pair) => str_ends_with($pair[0]->url(), '/api/v1/invoices') && $pair[0]->method() === 'POST'
    );
    expect($creates)->toHaveCount(1);
});

test('the scheduled command invoices the previous month only when enabled', function () {
    configureRanyatiInvoicing(enabled: false);
    fakeInvoiceNinja();

    $this->artisan('nrapa:invoice-ranyati', ['period' => '2026-09'])
        ->expectsOutputToContain('turned off')
        ->assertSuccessful();

    Http::assertNothingSent();

    configureRanyatiInvoicing();

    $this->artisan('nrapa:invoice-ranyati', ['period' => '2026-09', '--dry-run' => true])
        ->expectsOutputToContain('R1,500.00')
        ->assertSuccessful();

    Http::assertNothingSent();

    $this->artisan('nrapa:invoice-ranyati', ['period' => '2026-09'])
        ->expectsOutputToContain('0042')
        ->assertSuccessful();

    expect(RanyatiInvoice::where('period', '2026-09')->value('status'))->toBe('sent');

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'nrapa:invoice-ranyati'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 7 1 * *');
});

test('owner settings keep a saved token when the field is left blank', function () {
    $owner = User::factory()->create(['role' => User::ROLE_OWNER]);

    Livewire::actingAs($owner)
        ->test('pages::owner.settings.invoice-ninja')
        ->set('api_token', 'secret-token')
        ->set('client_id', 'RanyatiClient')
        ->call('save')
        ->assertHasNoErrors();

    expect(SystemSetting::get('invoice_ninja_api_token'))->toBe('secret-token');

    Livewire::actingAs($owner)
        ->test('pages::owner.settings.invoice-ninja')
        ->set('api_token', '')
        ->set('hosting_fee', '1600')
        ->call('save')
        ->assertHasNoErrors();

    expect(SystemSetting::get('invoice_ninja_api_token'))->toBe('secret-token')
        ->and(SystemSetting::get('invoice_ninja_hosting_fee'))->toBe('1600');
});

test('billing can send the selected month to invoice ninja', function () {
    configureRanyatiInvoicing();
    fakeInvoiceNinja();
    ranyatiMembership($this->type);

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    Livewire::actingAs($admin)
        ->test('pages::admin.billing.index')
        ->set('selectedYear', 2026)
        ->set('selectedMonth', 9)
        ->assertSet('newMembers', 1)
        ->assertSet('ranyatiTotal', 1600)
        ->call('sendRanyatiInvoice')
        ->assertSee('0042');

    expect(RanyatiInvoice::where('period', '2026-09')->value('status'))->toBe('sent');
});
