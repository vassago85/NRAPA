<?php

use App\Models\SystemSetting;
use App\Services\SageNetworkService;
use Livewire\Component;

new class extends Component {
    public string $client_id = '';
    public string $client_secret = '';
    public bool $sage_enabled = false;

    public string $connectionStatus = '';
    public string $groupName = '';
    public string $connectedAt = '';
    public string $testResult = '';

    public function mount(): void
    {
        $this->client_id = SystemSetting::get('sage_client_id', '');
        $this->client_secret = SystemSetting::get('sage_client_secret', '');
        $this->sage_enabled = (bool) SystemSetting::get('sage_enabled', false);
        $this->groupName = SystemSetting::get('sage_group_name', '');
        $this->connectedAt = SystemSetting::get('sage_connected_at', '');
        $this->connectionStatus = SystemSetting::get('sage_api_key') ? 'connected' : 'disconnected';
    }

    public function saveCredentials(): void
    {
        $this->validate([
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
        ]);

        SystemSetting::set('sage_client_id', $this->client_id, 'string', 'sage', 'Sage OAuth Client ID');
        SystemSetting::set('sage_client_secret', $this->client_secret, 'string', 'sage', 'Sage OAuth Client Secret');

        session()->flash('success', 'Sage credentials saved. Click "Connect to Sage" to authorize.');
    }

    public function toggleEnabled(): void
    {
        $this->sage_enabled = ! $this->sage_enabled;
        SystemSetting::set('sage_enabled', $this->sage_enabled ? '1' : '0', 'boolean', 'sage', 'Enable Sage invoicing');

        session()->flash('success', $this->sage_enabled ? 'Sage invoicing enabled.' : 'Sage invoicing paused.');
    }

    public function testConnection(): void
    {
        if ($this->connectionStatus !== 'connected') {
            $this->testResult = 'error:Not connected to Sage. Please connect first.';
            return;
        }

        $sage = new SageNetworkService;
        $result = $sage->testConnection();

        if ($result['success']) {
            $name = $result['data']['groupName'] ?? 'Unknown';
            $this->testResult = "success:Connection successful — {$name}";
        } else {
            $this->testResult = 'error:Connection failed — ' . ($result['error'] ?? 'Unknown error');
        }
    }

    public function disconnect(): void
    {
        SystemSetting::set('sage_api_key', '', 'string', 'sage', 'Sage Network API key');
        SystemSetting::set('sage_group_key', '', 'string', 'sage', 'Sage group key');
        SystemSetting::set('sage_group_name', '', 'string', 'sage', 'Sage group name');
        SystemSetting::set('sage_connected_at', '', 'string', 'sage', 'Sage connected timestamp');

        $this->connectionStatus = 'disconnected';
        $this->groupName = '';
        $this->connectedAt = '';
        $this->testResult = '';

        session()->flash('success', 'Disconnected from Sage.');
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
        <!-- Settings Navigation -->
        <div class="lg:col-span-1">
            <nav class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                @include('partials.owner-settings-nav')
            </nav>
        </div>

        <!-- Sage Settings -->
        <div class="lg:col-span-2 space-y-6">

            {{-- Connection Status Card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Sage Network Integration</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Automatically create invoices in Sage when memberships are approved</p>
                    </div>
                </div>

                {{-- Status indicator --}}
                <div class="flex items-center gap-3 p-4 rounded-lg {{ $connectionStatus === 'connected' ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' : 'bg-zinc-50 dark:bg-zinc-700/50 border border-zinc-200 dark:border-zinc-600' }}">
                    <div class="w-3 h-3 rounded-full {{ $connectionStatus === 'connected' ? 'bg-emerald-500' : 'bg-zinc-400' }}"></div>
                    <div>
                        @if($connectionStatus === 'connected')
                            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Connected to Sage</p>
                            @if($groupName)
                                <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ $groupName }}</p>
                            @endif
                            @if($connectedAt)
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Connected {{ \Carbon\Carbon::parse($connectedAt)->diffForHumans() }}</p>
                            @endif
                        @else
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Not Connected</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Enter credentials below and click "Connect to Sage"</p>
                        @endif
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="flex flex-wrap gap-3 mt-4">
                    @if($connectionStatus === 'connected')
                        <button wire:click="testConnection" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                            <span wire:loading wire:target="testConnection">Testing...</span>
                        </button>
                        <button wire:click="disconnect" wire:confirm="Are you sure you want to disconnect from Sage?"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Disconnect
                        </button>
                        <a href="{{ route('owner.sage.redirect') }}"
                            class="px-4 py-2 bg-zinc-600 hover:bg-zinc-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Reconnect
                        </a>
                    @else
                        @if($client_id && $client_secret)
                            <a href="{{ route('owner.sage.redirect') }}"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                                Connect to Sage
                            </a>
                        @endif
                    @endif
                </div>

                {{-- Test result --}}
                @if($testResult)
                    @php [$testType, $testMsg] = explode(':', $testResult, 2); @endphp
                    <div class="mt-4 p-3 rounded-lg text-sm {{ $testType === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' }}">
                        {{ $testMsg }}
                    </div>
                @endif
            </div>

            {{-- Credentials Card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">OAuth Credentials</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                    From your app on <a href="https://developer.sage.com" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">developer.sage.com</a>.
                    Environment: <span class="font-medium">{{ config('sage.environment', 'sandbox') }}</span>
                </p>

                <form wire:submit="saveCredentials" class="space-y-4">
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Client ID</label>
                        <input type="text" id="client_id" wire:model="client_id" placeholder="Your Sage Client ID"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        @error('client_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="client_secret" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Client Secret</label>
                        <input type="password" id="client_secret" wire:model="client_secret" placeholder="Your Sage Client Secret"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        @error('client_secret') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="submit" class="px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white font-medium rounded-lg transition-colors">
                            Save Credentials
                        </button>
                    </div>
                </form>
            </div>

            {{-- Enable / Disable Toggle --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Enable Sage Invoicing</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            When enabled, approving a membership will automatically create an invoice in Sage.
                            {{ $connectionStatus !== 'connected' ? 'You must connect to Sage first.' : '' }}
                        </p>
                    </div>
                    <button wire:click="toggleEnabled" type="button"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 {{ $sage_enabled ? 'bg-indigo-600' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                        role="switch" aria-checked="{{ $sage_enabled ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $sage_enabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
