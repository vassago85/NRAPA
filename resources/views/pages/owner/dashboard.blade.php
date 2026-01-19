<?php

use App\Models\User;
use App\Models\SystemSetting;
use Livewire\Component;

new class extends Component {
    public int $totalAdmins = 0;
    public int $myAdmins = 0;
    public int $totalMembers = 0;
    public int $pendingApprovals = 0;
    
    // Storage status
    public bool $privateStorageConfigured = false;
    public bool $publicStorageConfigured = false;
    public string $privateBucket = '';
    public string $publicBucket = '';

    public function mount(): void
    {
        $this->totalAdmins = User::where('role', User::ROLE_ADMIN)->count();
        $this->myAdmins = User::where('role', User::ROLE_ADMIN)
            ->where('nominated_by', auth()->id())
            ->count();
        $this->totalMembers = User::where('role', User::ROLE_MEMBER)->count();
        $this->pendingApprovals = \App\Models\Membership::where('status', 'pending')->count();
        
        // Check storage configuration
        $this->privateBucket = SystemSetting::get('r2_bucket', '');
        $this->publicBucket = SystemSetting::get('r2_public_bucket', '');
        $this->privateStorageConfigured = !empty($this->privateBucket) && !empty(SystemSetting::get('r2_access_key_id'));
        $this->publicStorageConfigured = !empty($this->publicBucket) && !empty(SystemSetting::get('r2_public_url'));
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Owner Dashboard</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Manage administrators and oversee the platform.</p>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Admins</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalAdmins }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">My Admins</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $myAdmins }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Members</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalMembers }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Approvals</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $pendingApprovals }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Storage Status --}}
    <div class="mb-8 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Storage Status</h2>
            <a href="{{ route('owner.settings.storage') }}" wire:navigate class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Configure</a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Private Bucket --}}
            <div class="p-4 rounded-lg {{ $privateStorageConfigured ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                <div class="flex items-center gap-3">
                    @if($privateStorageConfigured)
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @endif
                    <div>
                        <p class="font-medium {{ $privateStorageConfigured ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">Private Bucket (Documents)</p>
                        <p class="text-sm {{ $privateStorageConfigured ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            @if($privateStorageConfigured)
                                {{ $privateBucket }}
                            @else
                                Not configured
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            {{-- Public Bucket --}}
            <div class="p-4 rounded-lg {{ $publicStorageConfigured ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' }}">
                <div class="flex items-center gap-3">
                    @if($publicStorageConfigured)
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @endif
                    <div>
                        <p class="font-medium {{ $publicStorageConfigured ? 'text-green-800 dark:text-green-200' : 'text-amber-800 dark:text-amber-200' }}">Public Bucket (Learning Images)</p>
                        <p class="text-sm {{ $publicStorageConfigured ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                            @if($publicStorageConfigured)
                                {{ $publicBucket }}
                            @else
                                Not configured - learning images won't work
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <a href="{{ route('owner.admins.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    <span class="text-zinc-700 dark:text-zinc-300">Manage Administrators</span>
                </a>
                <a href="{{ route('admin.approvals.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span class="text-zinc-700 dark:text-zinc-300">Review Pending Approvals</span>
                </a>
                <a href="{{ route('admin.members.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-zinc-700 dark:text-zinc-300">View All Members</span>
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Your Administrators</h2>
            @php
                $admins = User::where('role', User::ROLE_ADMIN)
                    ->where('nominated_by', auth()->id())
                    ->latest()
                    ->take(5)
                    ->get();
            @endphp
            @if($admins->isEmpty())
                <p class="text-zinc-500 dark:text-zinc-400 text-center py-4">You haven't created any administrators yet.</p>
                <a href="{{ route('owner.admins.create') }}" wire:navigate
                    class="block w-full text-center py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Create Your First Admin
                </a>
            @else
                <ul class="space-y-2">
                    @foreach($admins as $admin)
                        <li class="flex items-center justify-between p-2 rounded-lg bg-zinc-50 dark:bg-zinc-700/50">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $admin->name }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $admin->email }}</p>
                            </div>
                            <span class="text-xs text-zinc-400">{{ $admin->nominated_at?->diffForHumans() ?? 'N/A' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
