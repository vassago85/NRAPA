<?php

use App\Models\SystemSetting;
use App\Services\InvoiceNinja\InvoiceNinjaClient;
use App\Services\InvoiceNinja\InvoiceNinjaException;
use App\Services\InvoiceNinja\IssueRanyatiInvoice;
use App\Services\InvoiceNinja\RanyatiInvoiceSettings;
use Livewire\Component;

new class extends Component {
    public bool $enabled = false;

    public string $api_url = '';

    public string $api_token = '';

    public bool $tokenSaved = false;

    public string $client_id = '';

    public string $hosting_fee = '1500';

    public string $member_fee = '100';

    public string $testResult = '';

    public function mount(RanyatiInvoiceSettings $settings): void
    {
        $this->enabled = $settings->enabled();
        $this->api_url = $settings->apiUrl();
        $this->tokenSaved = $settings->apiToken() !== '';
        $this->client_id = $settings->clientId();
        $this->hosting_fee = $this->amount($settings->hostingFee());
        $this->member_fee = $this->amount($settings->memberFee());
    }

    public function save(): void
    {
        $this->validate([
            'api_url' => ['required', 'url', 'max:255'],
            'api_token' => [$this->tokenSaved ? 'nullable' : 'required', 'string', 'max:255'],
            'client_id' => ['required', 'string', 'max:64'],
            'hosting_fee' => ['required', 'numeric', 'min:0'],
            'member_fee' => ['required', 'numeric', 'min:0'],
        ]);

        SystemSetting::set('invoice_ninja_api_url', rtrim($this->api_url, '/'), 'string', 'invoice_ninja', 'Invoice Ninja base URL');
        SystemSetting::set('invoice_ninja_client_id', trim($this->client_id), 'string', 'invoice_ninja', 'Invoice Ninja client id for Ranyati');
        SystemSetting::set('invoice_ninja_hosting_fee', $this->hosting_fee, 'string', 'invoice_ninja', 'Monthly NRAPA hosting fee charged to Ranyati');
        SystemSetting::set('invoice_ninja_member_fee', $this->member_fee, 'string', 'invoice_ninja', 'Fee per new member or renewal charged to Ranyati');

        if (trim($this->api_token) !== '') {
            SystemSetting::set('invoice_ninja_api_token', trim($this->api_token), 'string', 'invoice_ninja', 'Invoice Ninja API token');
            $this->tokenSaved = true;
            $this->api_token = '';
        }

        session()->flash('success', 'Invoice Ninja settings saved.');
    }

    public function toggleEnabled(): void
    {
        $this->enabled = ! $this->enabled;
        SystemSetting::set(
            'invoice_ninja_enabled',
            $this->enabled ? '1' : '0',
            'string',
            'invoice_ninja',
            'Automatically invoice Ranyati each month',
        );

        session()->flash('success', $this->enabled
            ? 'Monthly Ranyati invoicing is on. It runs on the 1st for the previous month.'
            : 'Monthly Ranyati invoicing is paused.');
    }

    public function testConnection(InvoiceNinjaClient $client): void
    {
        $this->testResult = '';

        try {
            $remote = $client->client(app(RanyatiInvoiceSettings::class)->clientId());
            $name = $remote['display_name'] ?? $remote['name'] ?? 'the client';
            $this->testResult = 'success:Connected. Invoices will be billed to '.$name.'.';
        } catch (InvoiceNinjaException $e) {
            $this->testResult = 'error:'.$e->getMessage();
        }
    }

    public function invoicePreviousMonth(IssueRanyatiInvoice $issuer): void
    {
        $this->testResult = '';
        $previous = now()->timezone('Africa/Johannesburg')->subMonth();

        try {
            $invoice = $issuer->issue($previous->year, $previous->month);
            $number = $invoice->invoice_number ?: $invoice->invoice_ninja_id;
            session()->flash('success', 'Invoice '.$number.' for '.$previous->format('F Y').' is '.$invoice->status.'.');
        } catch (InvoiceNinjaException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function amount(float $amount): string
    {
        return abs($amount - round($amount)) < 0.001
            ? (string) (int) round($amount)
            : number_format($amount, 2, '.', '');
    }
}; ?>

<div>
    <x-slot name="header">
        @include('partials.owner-settings-heading')
    </x-slot>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-900/40 border border-emerald-200 dark:border-emerald-800 rounded-xl">
            <p class="text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/40 border border-red-200 dark:border-red-800 rounded-xl">
            <p class="text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <nav class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                @include('partials.owner-settings-nav')
            </nav>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Invoice Ninja</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">On the 1st of each month, invoice Ranyati for the previous month: hosting, plus a fee for every new member and renewal whose payment was confirmed.</p>
                    </div>
                    <button type="button" wire:click="toggleEnabled"
                        class="shrink-0 px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $enabled ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-zinc-200 hover:bg-zinc-300 text-zinc-800 dark:bg-zinc-700 dark:hover:bg-zinc-600 dark:text-zinc-100' }}">
                        {{ $enabled ? 'On' : 'Off' }}
                    </button>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="button" wire:click="testConnection" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <span wire:loading.remove wire:target="testConnection">Test connection</span>
                        <span wire:loading wire:target="testConnection">Testing...</span>
                    </button>
                    <button type="button" wire:click="invoicePreviousMonth" wire:confirm="Create and email last month's Ranyati invoice in Invoice Ninja?"
                        class="px-4 py-2 bg-zinc-700 hover:bg-zinc-800 text-white text-sm font-medium rounded-lg transition-colors">
                        <span wire:loading.remove wire:target="invoicePreviousMonth">Invoice previous month</span>
                        <span wire:loading wire:target="invoicePreviousMonth">Sending...</span>
                    </button>
                </div>

                @if($testResult)
                    @php [$testType, $testMsg] = explode(':', $testResult, 2); @endphp
                    <div class="mt-4 p-3 rounded-lg text-sm {{ $testType === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' }}">
                        {{ $testMsg }}
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-1">Connection</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">Defaults to the National Rifle and Pistol Association client already in Invoice Ninja. The invoice email goes to that client's contact, and the due date follows its 30-day terms.</p>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label for="api_url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">API URL</label>
                        <input type="url" id="api_url" wire:model="api_url" placeholder="https://invoice.charsley.co.za"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                        @error('api_url') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="api_token" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">API token</label>
                        <input type="password" id="api_token" wire:model="api_token" placeholder="{{ $tokenSaved ? 'Saved. Leave blank to keep it.' : 'Paste the Invoice Ninja API token' }}" autocomplete="off"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        @error('api_token') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="client_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Client ID</label>
                        <input type="text" id="client_id" wire:model="client_id" placeholder="Hashed client id"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        @error('client_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="hosting_fee" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Hosting per month (R)</label>
                            <input type="number" step="0.01" min="0" id="hosting_fee" wire:model="hosting_fee"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                            @error('hosting_fee') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="member_fee" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Per new member or renewal (R)</label>
                            <input type="number" step="0.01" min="0" id="member_fee" wire:model="member_fee"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                            @error('member_fee') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="submit" class="px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white font-medium rounded-lg transition-colors">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
