<?php

use App\Models\SystemSetting;
use Livewire\Component;

new class extends Component {
    // Bank Account Settings
    public string $bank_name = '';
    public string $bank_account_name = '';
    public string $bank_account_number = '';
    public string $bank_branch_code = '';
    public string $bank_account_type = 'Cheque';
    public string $bank_reference_prefix = 'NRAPA';

    public function mount(): void
    {
        $this->bank_name = SystemSetting::get('bank_name', '');
        $this->bank_account_name = SystemSetting::get('bank_account_name', '');
        $this->bank_account_number = SystemSetting::get('bank_account_number', '');
        $this->bank_branch_code = SystemSetting::get('bank_branch_code', '');
        $this->bank_account_type = SystemSetting::get('bank_account_type', 'Cheque');
        $this->bank_reference_prefix = SystemSetting::get('bank_reference_prefix', 'NRAPA');
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
        </div>
    </div>
</div>
