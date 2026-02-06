<?php

use App\Services\BackupService;
use App\Models\SystemSetting;
use Livewire\Component;
use Illuminate\Support\Facades\Crypt;

new class extends Component {
    public string $dbPassword = '';
    public string $storagePassword = '';
    public bool $isBackingUp = false;
    public ?string $backupMessage = null;
    public bool $backupSuccess = false;
    public ?string $backupDownloadUrl = null;
    public ?string $backupFileName = null;
    
    // Automatic backup settings
    public string $databaseBackupPassword = '';
    public bool $showPassword = false;
    
    // Backup bucket settings
    public string $backupBucket = '';

    public function mount(): void
    {
        // Load stored database backup password (for automatic backups)
        // Password is stored encrypted, so we show empty if it exists (for security)
        $storedPassword = SystemSetting::get('database_backup_password', '');
        $this->databaseBackupPassword = $storedPassword ? '••••••••' : ''; // Show dots if password is set
        
        // Load backup bucket name
        $this->backupBucket = SystemSetting::get('backup_r2_bucket', '');
    }

    public function createBackup(): void
    {
        $this->validate([
            'dbPassword' => 'required|string',
            'storagePassword' => 'nullable|string',
        ]);

        $this->isBackingUp = true;
        $this->backupMessage = null;
        $this->backupSuccess = false;
        $this->backupDownloadUrl = null;
        $this->backupFileName = null;

        try {
            $backupService = new BackupService();
            $result = $backupService->createBackup($this->dbPassword, $this->storagePassword ?? '');

            if ($result['success']) {
                $this->backupSuccess = true;
                $this->backupMessage = $result['message'];
                $this->backupDownloadUrl = $result['backup_path'];
                $this->backupFileName = $result['backup_name'] ?? 'backup.zip';
                session()->flash('success', $result['message']);
            } else {
                $this->backupSuccess = false;
                $this->backupMessage = $result['message'];
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            $this->backupSuccess = false;
            $this->backupMessage = 'Backup failed: ' . $e->getMessage();
            session()->flash('error', $this->backupMessage);
        } finally {
            $this->isBackingUp = false;
            $this->dbPassword = '';
            $this->storagePassword = '';
        }
    }

    public function saveAutomaticBackupSettings(): void
    {
        // Check if storage settings are locked
        if (SystemSetting::get('storage_settings_locked', false) && !auth()->user()->hasRoleLevel(\App\Models\User::ROLE_DEVELOPER)) {
            session()->flash('error', 'Storage settings are locked. Please contact the developer to make changes.');
            return;
        }
        
        $this->validate([
            'backupBucket' => 'nullable|string|max:255',
        ]);
        
        // Save backup bucket
        if ($this->backupBucket) {
            SystemSetting::set('backup_r2_bucket', $this->backupBucket, 'string', 'backup', 'Dedicated R2 bucket for backups');
        }

        // Only update password if user entered a real password (not placeholder dots)
        if ($this->databaseBackupPassword && $this->databaseBackupPassword !== '••••••••') {
            $this->validate([
                'databaseBackupPassword' => 'required|string|min:1',
            ]);
            
            // Encrypt and store the password securely
            $encryptedPassword = Crypt::encryptString($this->databaseBackupPassword);
            SystemSetting::set('database_backup_password', $encryptedPassword, 'string', 'backup', 'Database Backup Password (for automatic daily backups)');

            // Reset to show dots
            $this->databaseBackupPassword = '••••••••';
        }

        session()->flash('success', 'Automatic backup settings saved successfully.');
    }

    public function togglePasswordVisibility(): void
    {
        $this->showPassword = !$this->showPassword;
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">System Backup</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Create a complete backup of your database and files.</p>
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
                        <a href="#backup-settings" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            System Backup
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Backup Form -->
        <div class="lg:col-span-2">
            <div id="backup-settings" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Create System Backup</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Backup database, user data, and all files</p>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">What's Included in the Backup</p>
                            <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1 list-disc list-inside">
                                <li>Complete database dump (SQL file)</li>
                                <li>User data export (CSV file)</li>
                                <li>All files from storage (public and private directories)</li>
                                <li>Everything packaged in a ZIP archive</li>
                                <li>Uploaded to your configured cloud storage (if configured)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <form wire:submit="createBackup" class="space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label for="dbPassword" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Database Password <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="password" 
                                id="dbPassword" 
                                wire:model="dbPassword" 
                                placeholder="Enter database password"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                :disabled="$isBackingUp"
                                required>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Password for the database user configured in your .env file</p>
                            @error('dbPassword') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="storagePassword" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Storage Password (Optional)
                            </label>
                            <input 
                                type="password" 
                                id="storagePassword" 
                                wire:model="storagePassword" 
                                placeholder="Enter storage password if required"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                :disabled="$isBackingUp">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Only needed if your storage requires additional authentication</p>
                            @error('storagePassword') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if($isBackingUp)
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                        <div class="flex items-center gap-3">
                            <svg class="animate-spin w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Creating backup... This may take a few minutes.</p>
                        </div>
                    </div>
                    @endif

                    @if($backupSuccess && $backupDownloadUrl)
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-green-800 dark:text-green-200 mb-2">Backup Created Successfully!</p>
                                <p class="text-sm text-green-700 dark:text-green-300 mb-3">{{ $backupMessage }}</p>
                                <a href="{{ $backupDownloadUrl }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download Backup ({{ $backupFileName }})
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button 
                            type="submit" 
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="$isBackingUp">
                            @if($isBackingUp)
                                <span class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Creating Backup...
                                </span>
                            @else
                                Create Backup Now
                            @endif
                        </button>
                    </div>
                </form>

                <!-- Automatic Backup Settings -->
                <div class="mt-8 pt-8 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Automatic Daily Backups</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Configure automatic database backups</p>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">Automatic Backup Schedule</p>
                                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1 list-disc list-inside">
                                    <li>Database backups run automatically every day at 2:00 AM (SAST)</li>
                                    <li>Backups are compressed and uploaded to your configured cloud storage</li>
                                    <li>Only the last 30 days of backups are kept (older backups are automatically deleted)</li>
                                    <li>Backups are stored in: <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs">backups/database/</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    @if(\App\Models\SystemSetting::get('storage_settings_locked', false) && !auth()->user()->hasRoleLevel(\App\Models\User::ROLE_DEVELOPER))
                    <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Storage settings are locked. Contact the developer to make changes.</p>
                        </div>
                    </div>
                    @endif

                    <form wire:submit="saveAutomaticBackupSettings" class="space-y-4">
                        {{-- Backup Bucket --}}
                        <div>
                            <label for="backupBucket" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Backup R2 Bucket Name
                            </label>
                            <input 
                                type="text" 
                                id="backupBucket" 
                                wire:model="backupBucket" 
                                placeholder="e.g. nrapa-backups"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                {{ \App\Models\SystemSetting::get('storage_settings_locked', false) && !auth()->user()->hasRoleLevel(\App\Models\User::ROLE_DEVELOPER) ? 'disabled' : '' }}>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">A separate R2 bucket dedicated to backups. Uses the same R2 API credentials as your main storage. Create this bucket in the Cloudflare R2 dashboard first.</p>
                            @error('backupBucket') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="databaseBackupPassword" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Database Password for Automatic Backups <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="{{ $showPassword ? 'text' : 'password' }}" 
                                    id="databaseBackupPassword" 
                                    wire:model="databaseBackupPassword" 
                                    placeholder="{{ empty($databaseBackupPassword) || $databaseBackupPassword === '••••••••' ? 'Enter database password' : 'Enter new password to update' }}"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10">
                                <button 
                                    type="button" 
                                    wire:click="togglePasswordVisibility" 
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                                    @if($showPassword)
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    @endif
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">This password is stored securely and used for automatic daily backups. It will not be shown again after saving.</p>
                            @error('databaseBackupPassword') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <button 
                                type="submit" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                Save Automatic Backup Settings
                            </button>
                        </div>
                    </form>

                    @if(!empty($databaseBackupPassword))
                    <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm text-green-700 dark:text-green-300">Automatic backups are configured. Next backup will run at 2:00 AM.</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
