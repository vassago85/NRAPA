<?php

use App\Models\User;
use App\Models\SystemSetting;
use App\Models\UserDeletionRequest;
use Livewire\Component;

new class extends Component {
    public int $totalAdmins = 0;
    public int $myAdmins = 0;
    public int $totalMembers = 0;
    public int $pendingApprovals = 0;
    public int $pendingDeletions = 0;
    
    // Storage status
    public bool $privateStorageConfigured = false;
    public string $privateBucket = '';

    public function mount(): void
    {
        $this->totalAdmins = User::where('role', User::ROLE_ADMIN)->count();
        $this->myAdmins = User::where('role', User::ROLE_ADMIN)
            ->where('nominated_by', auth()->id())
            ->count();
        $this->totalMembers = User::where('role', User::ROLE_MEMBER)->count();
        $this->pendingApprovals = \App\Models\Membership::where('status', 'pending')->count();
        $this->pendingDeletions = UserDeletionRequest::pending()->count();
        
        // Check storage configuration
        $this->privateBucket = SystemSetting::get('r2_bucket', '');
        $this->privateStorageConfigured = !empty($this->privateBucket) && !empty(SystemSetting::get('r2_access_key_id'));
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

    {{-- Pending Deletion Requests Alert --}}
    @if($pendingDeletions > 0)
    <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                </div>
                <div>
                    <p class="font-medium text-red-800 dark:text-red-200">{{ $pendingDeletions }} Deletion Request{{ $pendingDeletions > 1 ? 's' : '' }} Pending</p>
                    <p class="text-sm text-red-600 dark:text-red-400">Admin{{ $pendingDeletions > 1 ? 's have' : ' has' }} requested to delete user{{ $pendingDeletions > 1 ? 's' : '' }}.</p>
                </div>
            </div>
            <a href="{{ route('owner.deletion-requests.index') }}" wire:navigate class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                Review Requests
            </a>
        </div>
    </div>
    @endif

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
        </div>
    </div>

    {{-- Site Settings Quick Access --}}
    <div class="mb-8 bg-gradient-to-r from-emerald-50 to-blue-50 dark:from-emerald-900/20 dark:to-blue-900/20 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-800 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                    <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655-5.653a2.548 2.548 0 010-3.586L11.42 15.17z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Site Settings</h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Configure bank accounts, email, storage, and approval workflows</p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('owner.settings.backup') }}" wire:navigate
                    class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Create Backup
                </a>
                <a href="{{ route('owner.settings.index') }}" wire:navigate
                    class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655-5.653a2.548 2.548 0 010-3.586L11.42 15.17z"/></svg>
                    Manage Settings
                </a>
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
                <a href="{{ route('owner.deletion-requests.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors {{ $pendingDeletions > 0 ? 'ring-2 ring-red-500' : '' }}">
                    <svg class="w-5 h-5 {{ $pendingDeletions > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    <span class="text-zinc-700 dark:text-zinc-300">User Deletion Requests</span>
                    @if($pendingDeletions > 0)
                    <span class="ml-auto inline-flex items-center justify-center px-2 py-1 text-xs font-bold text-white bg-red-600 rounded-full">{{ $pendingDeletions }}</span>
                    @endif
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
                <a href="{{ route('owner.admins.index') }}" wire:navigate
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
