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
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Owner Settings</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Configure platform-wide settings.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Settings Navigation -->
        <div class="lg:col-span-1">
            <nav class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">Settings</h2>
                <ul class="space-y-1">
                    <li>
                        <a href="#bank-settings" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Bank Account
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('owner.settings.email') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Email / SMTP
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('owner.settings.storage') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                            Storage / R2
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('owner.settings.approvals') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Approval Workflows
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('owner.settings.documents') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Document Assets
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('owner.settings.backup') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            System Backup
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Bank Account Settings -->
        <div class="lg:col-span-2">
            <div id="bank-settings" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
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
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
                            Save Bank Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
