<?php

use App\Models\Invoice;
use App\Models\SystemSetting;
use App\Services\Billing\InvoiceIssuer;
use App\Services\Billing\InvoiceNumberSequence;
use Livewire\Component;

new class extends Component {
    // Bank Account Settings
    public string $bank_name = '';
    public string $bank_account_name = '';
    public string $bank_account_number = '';
    public string $bank_branch_code = '';
    public string $bank_account_type = 'Cheque';
    public string $bank_reference_prefix = 'NRAPA';

    // Invoicing settings
    public string $invoice_prefix = InvoiceNumberSequence::DEFAULT_PREFIX;
    public int $invoice_width = InvoiceNumberSequence::DEFAULT_WIDTH;
    public int $invoice_next = InvoiceNumberSequence::DEFAULT_NEXT;
    public string $invoice_business_name = InvoiceIssuer::DEFAULT_BUSINESS_NAME;
    public string $invoice_business_vat = '';
    public string $invoice_physical_address = InvoiceIssuer::DEFAULT_ADDRESS;
    public string $invoice_postal_address = InvoiceIssuer::DEFAULT_ADDRESS;

    public function mount(): void
    {
        $this->bank_name = SystemSetting::get('bank_name', '');
        $this->bank_account_name = SystemSetting::get('bank_account_name', '');
        $this->bank_account_number = SystemSetting::get('bank_account_number', '');
        $this->bank_branch_code = SystemSetting::get('bank_branch_code', '');
        $this->bank_account_type = SystemSetting::get('bank_account_type', 'Cheque');
        $this->bank_reference_prefix = SystemSetting::get('bank_reference_prefix', 'NRAPA');

        $this->invoice_prefix = SystemSetting::get(InvoiceNumberSequence::SETTING_PREFIX, InvoiceNumberSequence::DEFAULT_PREFIX);
        $this->invoice_width = (int) SystemSetting::get(InvoiceNumberSequence::SETTING_WIDTH, InvoiceNumberSequence::DEFAULT_WIDTH);
        $this->invoice_next = (int) SystemSetting::get(InvoiceNumberSequence::SETTING_NEXT, InvoiceNumberSequence::DEFAULT_NEXT);
        $this->invoice_business_name = SystemSetting::get(InvoiceIssuer::SETTING_BUSINESS_NAME, InvoiceIssuer::DEFAULT_BUSINESS_NAME);
        $this->invoice_business_vat = SystemSetting::get(InvoiceIssuer::SETTING_BUSINESS_VAT, '');
        $this->invoice_physical_address = SystemSetting::get(InvoiceIssuer::SETTING_PHYSICAL_ADDRESS, InvoiceIssuer::DEFAULT_ADDRESS);
        $this->invoice_postal_address = SystemSetting::get(InvoiceIssuer::SETTING_POSTAL_ADDRESS, InvoiceIssuer::DEFAULT_ADDRESS);
    }

    public function getInvoicesIssuedProperty(): int
    {
        return Invoice::query()->count();
    }

    public function getNextInvoicePreviewProperty(): string
    {
        return app(InvoiceNumberSequence::class)->peek();
    }

    public function saveBankSettings(): void
    {
        $this->validate([
            'bank_name' => 'required|string|max:255',
            'bank_account_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'bank_branch_code' => 'required|string|max:20',
            'bank_account_type' => 'required|string|max:50',
            'bank_reference_prefix' => 'required|string|max:20',
        ]);

        SystemSetting::set('bank_name', $this->bank_name, 'string', 'bank', 'Bank name');
        SystemSetting::set('bank_account_name', $this->bank_account_name, 'string', 'bank', 'Account holder name');
        SystemSetting::set('bank_account_number', $this->bank_account_number, 'string', 'bank', 'Account number');
        SystemSetting::set('bank_branch_code', $this->bank_branch_code, 'string', 'bank', 'Branch code');
        SystemSetting::set('bank_account_type', $this->bank_account_type, 'string', 'bank', 'Account type');
        SystemSetting::set('bank_reference_prefix', $this->bank_reference_prefix, 'string', 'bank', 'Payment reference prefix');

        session()->flash('success', 'Bank account settings saved successfully.');
    }

    /**
     * The next-number field is locked once any invoice has been issued so an
     * admin cannot renumber or collide with issued invoices. Prefix/width and
     * the FROM-block address stay editable, with the understanding that
     * changing them only affects FUTURE invoices (existing ones carry their
     * snapshot).
     */
    public function saveInvoiceSettings(): void
    {
        $this->validate([
            'invoice_prefix' => 'required|string|max:30',
            'invoice_width' => 'required|integer|min:1|max:12',
            'invoice_next' => 'required|integer|min:1',
            'invoice_business_name' => 'required|string|max:255',
            'invoice_business_vat' => 'nullable|string|max:50',
            'invoice_physical_address' => 'required|string|max:500',
            'invoice_postal_address' => 'required|string|max:500',
        ]);

        SystemSetting::set(InvoiceNumberSequence::SETTING_PREFIX, $this->invoice_prefix, 'string', 'invoicing', 'Invoice number prefix');
        SystemSetting::set(InvoiceNumberSequence::SETTING_WIDTH, (string) $this->invoice_width, 'integer', 'invoicing', 'Zero-pad width for invoice numbers');

        if ($this->invoicesIssued === 0) {
            SystemSetting::set(InvoiceNumberSequence::SETTING_NEXT, (string) $this->invoice_next, 'integer', 'invoicing', 'Next invoice number to mint');
        }

        SystemSetting::set(InvoiceIssuer::SETTING_BUSINESS_NAME, $this->invoice_business_name, 'string', 'invoicing', 'Business name shown on invoices');
        SystemSetting::set(InvoiceIssuer::SETTING_BUSINESS_VAT, $this->invoice_business_vat, 'string', 'invoicing', 'Business VAT number shown on invoices');
        SystemSetting::set(InvoiceIssuer::SETTING_PHYSICAL_ADDRESS, $this->invoice_physical_address, 'string', 'invoicing', 'Business physical address on invoices');
        SystemSetting::set(InvoiceIssuer::SETTING_POSTAL_ADDRESS, $this->invoice_postal_address, 'string', 'invoicing', 'Business postal address on invoices');

        session()->flash('success', 'Invoicing settings saved.');
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Settings Navigation -->
        <div class="lg:col-span-1">
            <nav class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                @include('partials.owner-settings-nav')
            </nav>
        </div>

        <!-- Bank Account Settings -->
        <div class="lg:col-span-2">
            <div id="bank-settings" class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Bank Account Details</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Payment details shown to members for EFT payments</p>
                    </div>
                </div>

                <form wire:submit="saveBankSettings" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="bank_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Bank Name</label>
                            <input type="text" id="bank_name" wire:model="bank_name" placeholder="e.g. FNB, ABSA, Standard Bank"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @error('bank_name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank_account_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Account Name</label>
                            <input type="text" id="bank_account_name" wire:model="bank_account_name" placeholder="e.g. NRAPA"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @error('bank_account_name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank_account_number" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Account Number</label>
                            <input type="text" id="bank_account_number" wire:model="bank_account_number" placeholder="Account number"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @error('bank_account_number') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank_branch_code" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Branch Code</label>
                            <input type="text" id="bank_branch_code" wire:model="bank_branch_code" placeholder="e.g. 250655"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @error('bank_branch_code') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank_account_type" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Account Type</label>
                            <select id="bank_account_type" wire:model="bank_account_type"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="Cheque">Cheque Account</option>
                                <option value="Savings">Savings Account</option>
                                <option value="Business">Business Account</option>
                            </select>
                            @error('bank_account_type') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank_reference_prefix" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reference Prefix</label>
                            <input type="text" id="bank_reference_prefix" wire:model="bank_reference_prefix" placeholder="e.g. NRAPA"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Members will use: {{ $bank_reference_prefix }}-SURNAME-ID</p>
                            @error('bank_reference_prefix') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="submit" class="px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white font-medium rounded-lg transition-colors">
                            Save Bank Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Invoicing Settings -->
            <div id="invoicing-settings" class="mt-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Invoicing</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Numbering and the FROM block shown on every invoice PDF</p>
                    </div>
                </div>

                <form wire:submit="saveInvoiceSettings" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="invoice_prefix" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Number Prefix</label>
                            <input type="text" id="invoice_prefix" wire:model="invoice_prefix"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @error('invoice_prefix') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="invoice_width" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Number Width</label>
                            <input type="number" min="1" max="12" id="invoice_width" wire:model="invoice_width"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @error('invoice_width') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="invoice_next" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Next Number
                                @if($this->invoicesIssued > 0)
                                    <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">(locked)</span>
                                @endif
                            </label>
                            <input type="number" min="1" id="invoice_next" wire:model="invoice_next"
                                @disabled($this->invoicesIssued > 0)
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:opacity-60 disabled:cursor-not-allowed">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Preview: <span class="font-mono">{{ $this->nextInvoicePreview }}</span>
                                @if($this->invoicesIssued > 0)
                                    &middot; {{ $this->invoicesIssued }} invoice{{ $this->invoicesIssued === 1 ? '' : 's' }} already issued, so the counter is read-only.
                                @endif
                            </p>
                            @error('invoice_next') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label for="invoice_business_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Business Name (From)</label>
                            <input type="text" id="invoice_business_name" wire:model="invoice_business_name"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @error('invoice_business_name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="invoice_business_vat" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">VAT Number (optional)</label>
                            <input type="text" id="invoice_business_vat" wire:model="invoice_business_vat" placeholder="Blank for non-VAT"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @error('invoice_business_vat') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="invoice_physical_address" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Physical Address</label>
                                <textarea id="invoice_physical_address" wire:model="invoice_physical_address" rows="3"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                                @error('invoice_physical_address') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="invoice_postal_address" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Postal Address</label>
                                <textarea id="invoice_postal_address" wire:model="invoice_postal_address" rows="3"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                                @error('invoice_postal_address') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                            Save Invoicing Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
