<?php

use App\Models\SystemSetting;
use Livewire\Component;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    // Storage Settings
    public string $storage_driver = 'local';
    
    // R2/S3 Settings (Private bucket for documents)
    public string $r2_access_key_id = '';
    public string $r2_secret_access_key = '';
    public string $r2_bucket = '';
    public string $r2_endpoint = '';
    public string $r2_url = '';
    public string $r2_region = 'auto';
    
    // R2 Public bucket (for learning images)
    public string $r2_public_bucket = '';
    public string $r2_public_url = '';

    public bool $showSecretKey = false;
    public string $connectionStatus = '';

    public function mount(): void
    {
        $this->storage_driver = SystemSetting::get('storage_driver', 'local');
        $this->r2_access_key_id = SystemSetting::get('r2_access_key_id', '');
        $this->r2_secret_access_key = SystemSetting::get('r2_secret_access_key', '');
        $this->r2_bucket = SystemSetting::get('r2_bucket', '');
        $this->r2_endpoint = SystemSetting::get('r2_endpoint', '');
        $this->r2_url = SystemSetting::get('r2_url', '');
        $this->r2_region = SystemSetting::get('r2_region', 'auto');
        $this->r2_public_bucket = SystemSetting::get('r2_public_bucket', '');
        $this->r2_public_url = SystemSetting::get('r2_public_url', '');
    }

    public function saveStorageSettings(): void
    {
        // Validate based on selected storage driver
        if ($this->storage_driver === 'r2') {
            $this->validate([
                'storage_driver' => 'required|in:local,r2',
                'r2_access_key_id' => 'required|string|max:255',
                'r2_secret_access_key' => 'required|string|max:255',
                'r2_bucket' => 'required|string|max:255',
                'r2_endpoint' => 'required|url|max:255',
                'r2_url' => 'nullable|url|max:255',
                'r2_region' => 'required|string|max:50',
                'r2_public_bucket' => 'required|string|max:255',
                'r2_public_url' => 'required|url|max:255',
            ]);
        } else {
            // Local storage - no R2 fields required
            $this->validate([
                'storage_driver' => 'required|in:local,r2',
            ]);
        }

        SystemSetting::set('storage_driver', $this->storage_driver, 'string', 'storage', 'Storage driver');
        
        // Only save R2 settings if R2 is selected
        if ($this->storage_driver === 'r2') {
            SystemSetting::set('r2_access_key_id', $this->r2_access_key_id, 'string', 'storage', 'R2 Access Key ID');
            SystemSetting::set('r2_secret_access_key', $this->r2_secret_access_key, 'string', 'storage', 'R2 Secret Access Key');
            SystemSetting::set('r2_bucket', $this->r2_bucket, 'string', 'storage', 'R2 Private Bucket Name');
            SystemSetting::set('r2_endpoint', $this->r2_endpoint, 'string', 'storage', 'R2 Endpoint URL');
            SystemSetting::set('r2_url', $this->r2_url, 'string', 'storage', 'R2 Private URL');
            SystemSetting::set('r2_region', $this->r2_region, 'string', 'storage', 'R2 Region');
            SystemSetting::set('r2_public_bucket', $this->r2_public_bucket, 'string', 'storage', 'R2 Public Bucket Name');
            SystemSetting::set('r2_public_url', $this->r2_public_url, 'string', 'storage', 'R2 Public URL');

            // Update runtime config for R2 private bucket
            config([
                'filesystems.default' => 'r2',
                'filesystems.disks.r2.key' => $this->r2_access_key_id,
                'filesystems.disks.r2.secret' => $this->r2_secret_access_key,
                'filesystems.disks.r2.bucket' => $this->r2_bucket,
                'filesystems.disks.r2.endpoint' => $this->r2_endpoint,
                'filesystems.disks.r2.url' => $this->r2_url,
                'filesystems.disks.r2.region' => $this->r2_region,
            ]);
            
            // Update runtime config for R2 public bucket
            config([
                'filesystems.disks.r2_public.key' => $this->r2_access_key_id,
                'filesystems.disks.r2_public.secret' => $this->r2_secret_access_key,
                'filesystems.disks.r2_public.bucket' => $this->r2_public_bucket,
                'filesystems.disks.r2_public.endpoint' => $this->r2_endpoint,
                'filesystems.disks.r2_public.url' => $this->r2_public_url,
                'filesystems.disks.r2_public.region' => $this->r2_region,
            ]);
        } else {
            // Local storage - update default
            config([
                'filesystems.default' => 'local',
            ]);
        }

        session()->flash('success', 'Storage settings saved successfully.');
    }

    public function testConnection(): void
    {
        try {
            // Apply settings temporarily
            config([
                'filesystems.disks.r2.key' => $this->r2_access_key_id,
                'filesystems.disks.r2.secret' => $this->r2_secret_access_key,
                'filesystems.disks.r2.bucket' => $this->r2_bucket,
                'filesystems.disks.r2.endpoint' => $this->r2_endpoint,
                'filesystems.disks.r2.url' => $this->r2_url,
                'filesystems.disks.r2.region' => $this->r2_region,
            ]);

            // Try to list files in the bucket (or create a test file)
            $testFileName = '.nrapa-connection-test-' . time();
            Storage::disk('r2')->put($testFileName, 'test');
            Storage::disk('r2')->delete($testFileName);

            $this->connectionStatus = 'success';
            session()->flash('success', 'Successfully connected to Cloudflare R2!');
        } catch (\Exception $e) {
            $this->connectionStatus = 'error';
            session()->flash('error', 'Connection failed: ' . $e->getMessage());
        }
    }

    public function toggleSecretKey(): void
    {
        $this->showSecretKey = !$this->showSecretKey;
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Storage Settings</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Configure file storage for documents and uploads.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Settings Navigation -->
        <div class="lg:col-span-1">
            <nav class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">Settings</h2>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('owner.settings.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
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
                        <a href="#storage-settings" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                            Storage / R2
                        </a>
                    </li>
                </ul>

                <!-- Cloudflare R2 Info -->
                <div class="mt-6 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                    <h3 class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-2">Cloudflare R2</h3>
                    <p class="text-xs text-orange-700 dark:text-orange-300 mb-2">S3-compatible object storage:</p>
                    <ul class="text-xs text-orange-600 dark:text-orange-400 space-y-1">
                        <li>• No egress fees</li>
                        <li>• 10GB free storage</li>
                        <li>• S3-compatible API</li>
                    </ul>
                    <a href="https://dash.cloudflare.com/?to=/:account/r2/overview" target="_blank" class="mt-3 inline-flex items-center gap-1 text-xs text-orange-700 dark:text-orange-300 hover:underline">
                        Open Cloudflare Dashboard
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Storage Settings Form -->
        <div class="lg:col-span-2">
            <div id="storage-settings" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Storage Configuration</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Configure where files and documents are stored</p>
                    </div>
                </div>

                <form wire:submit="saveStorageSettings" class="space-y-6">
                    <!-- Storage Driver Selection -->
                    <div class="space-y-4">
                        <h3 class="text-md font-semibold text-zinc-900 dark:text-white">Storage Driver</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors {{ $storage_driver === 'local' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-zinc-300 dark:border-zinc-600 hover:border-zinc-400 dark:hover:border-zinc-500' }}">
                                <input type="radio" wire:model.live="storage_driver" value="local" class="mr-3 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">Local Storage</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Use local filesystem (for development/testing)</div>
                                </div>
                            </label>
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors {{ $storage_driver === 'r2' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-zinc-300 dark:border-zinc-600 hover:border-zinc-400 dark:hover:border-zinc-500' }}">
                                <input type="radio" wire:model.live="storage_driver" value="r2" class="mr-3 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">Cloudflare R2</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Use cloud storage (for production)</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    @if($storage_driver === 'r2')
                    <!-- Cloud Storage Notice (only show for R2) -->
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Cloud Storage Recommended for Production</p>
                                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">For Docker deployments, Cloudflare R2 is recommended as local storage is not persistent. Files stored locally will be lost when containers restart.</p>
                            </div>
                        </div>
                    </div>

                    <!-- R2 Configuration -->
                    <div class="space-y-4">
                        <h3 class="text-md font-semibold text-zinc-900 dark:text-white">Cloudflare R2 Configuration</h3>
                        
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <strong>How to get credentials:</strong> Go to Cloudflare Dashboard → R2 → Manage R2 API Tokens → Create API token
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="r2_access_key_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Access Key ID</label>
                                <input type="text" id="r2_access_key_id" wire:model="r2_access_key_id" placeholder="Your R2 Access Key ID"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono text-sm">
                                @error('r2_access_key_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="r2_secret_access_key" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Secret Access Key</label>
                                <div class="relative">
                                    <input type="{{ $showSecretKey ? 'text' : 'password' }}" id="r2_secret_access_key" wire:model="r2_secret_access_key" placeholder="Your R2 Secret Access Key"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent pr-10 font-mono text-sm">
                                    <button type="button" wire:click="toggleSecretKey" class="absolute inset-y-0 right-0 px-3 flex items-center text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                                        @if($showSecretKey)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        @endif
                                    </button>
                                </div>
                                @error('r2_secret_access_key') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="r2_region" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Region</label>
                                <input type="text" id="r2_region" wire:model="r2_region" placeholder="auto"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Usually "auto" for R2</p>
                                @error('r2_region') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="r2_endpoint" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Endpoint URL</label>
                                <input type="url" id="r2_endpoint" wire:model="r2_endpoint" placeholder="https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono text-sm">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Found in R2 bucket settings → S3 API</p>
                                @error('r2_endpoint') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        
                        <!-- Private Bucket Section -->
                        <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-md font-semibold text-zinc-900 dark:text-white mb-2">Private Bucket (Sensitive Documents)</h4>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">For member documents like ID copies, certificates, etc. Access via signed URLs only.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="r2_bucket" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Private Bucket Name</label>
                                    <input type="text" id="r2_bucket" wire:model="r2_bucket" placeholder="nrapa-storage"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    @error('r2_bucket') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Public Bucket Section -->
                        <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-md font-semibold text-zinc-900 dark:text-white mb-2">Public Bucket (Learning Content)</h4>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">For learning images and non-sensitive content. Enable R2.dev public access on this bucket.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="r2_public_bucket" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Public Bucket Name</label>
                                    <input type="text" id="r2_public_bucket" wire:model="r2_public_bucket" placeholder="nrapa-public"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    @error('r2_public_bucket') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                </div>
                                
                                <div>
                                    <label for="r2_public_url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Public URL (R2.dev)</label>
                                    <input type="url" id="r2_public_url" wire:model="r2_public_url" placeholder="https://pub-xxxxxxxx.r2.dev"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono text-sm">
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Enable R2.dev subdomain in bucket settings</p>
                                    @error('r2_public_url') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <!-- Local Storage Notice -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Local Storage Active</p>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">Files will be stored in <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs">storage/app/private</code> and <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs">storage/app/public</code>. This is suitable for local development and testing.</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-wrap gap-3">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
                            Save Storage Settings
                        </button>
                        @if($storage_driver === 'r2')
                        <button type="button" wire:click="testConnection" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            Test Connection
                        </button>
                        @endif
                    </div>
                </form>

                <!-- Current Status -->
                <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-3">Current Status</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Active Driver</p>
                            <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ ucfirst($storage_driver) }}</p>
                        </div>
                        @if($storage_driver === 'r2' && $r2_bucket)
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Bucket</p>
                            <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $r2_bucket }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
