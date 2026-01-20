<?php

use App\Models\User;
use App\Models\Membership;
use Livewire\Component;

new class extends Component {
    public int $totalUsers = 0;
    public int $totalOwners = 0;
    public int $totalAdmins = 0;
    public int $totalMembers = 0;
    public int $activeMemberships = 0;
    
    public string $userSearch = '';
    public ?int $selectedUserId = null;

    public function mount(): void
    {
        $this->totalUsers = User::count();
        $this->totalOwners = User::where('role', User::ROLE_OWNER)->count();
        $this->totalAdmins = User::where('role', User::ROLE_ADMIN)->count();
        $this->totalMembers = User::where('role', User::ROLE_MEMBER)->count();
        $this->activeMemberships = Membership::where('status', 'active')->count();
    }

    public function getSearchResultsProperty()
    {
        if (strlen($this->userSearch) < 2) {
            return collect();
        }
        
        return User::where(function ($q) {
                $q->where('name', 'like', "%{$this->userSearch}%")
                  ->orWhere('email', 'like', "%{$this->userSearch}%");
            })
            ->where('id', '!=', auth()->id())
            ->limit(10)
            ->get();
    }

    public function impersonateUser(int $userId)
    {
        return $this->redirect(route('dev.impersonate', $userId));
    }
}; ?>

<div>
    <div class="mb-8">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full text-sm mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Developer Access
        </div>
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Developer Dashboard</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Full system access and owner management.</p>
    </div>

    {{-- System Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Users</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalUsers }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-purple-200 dark:border-purple-700 p-4">
            <p class="text-sm text-purple-600 dark:text-purple-400">Owners</p>
            <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ $totalOwners }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-blue-200 dark:border-blue-700 p-4">
            <p class="text-sm text-blue-600 dark:text-blue-400">Admins</p>
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $totalAdmins }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Members</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalMembers }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-green-200 dark:border-green-700 p-4">
            <p class="text-sm text-green-600 dark:text-green-400">Active Memberships</p>
            <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $activeMemberships }}</p>
        </div>
    </div>

    {{-- Login as User --}}
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl p-4 mb-8">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <h3 class="font-semibold text-red-800 dark:text-red-200">Login as User</h3>
        </div>
        <p class="text-sm text-red-700 dark:text-red-300 mb-3">Impersonate any user to test their experience. You can return to your account anytime.</p>
        
        <div class="relative">
            <input type="text" 
                   wire:model.live.debounce.300ms="userSearch" 
                   placeholder="Search by name or email..."
                   class="w-full rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-zinc-800 px-4 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-500 focus:ring-2 focus:ring-red-500">
            
            @if($this->searchResults->count() > 0)
                <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-64 overflow-y-auto">
                    @foreach($this->searchResults as $user)
                        <a href="{{ route('dev.impersonate', $user) }}" 
                           class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700 border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                            <div class="flex-1">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($user->role === 'developer') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200
                                @elseif($user->role === 'owner') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200
                                @elseif($user->role === 'admin') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                @else bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200
                                @endif">
                                {{ ucfirst($user->role) }}
                            </span>
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                        </a>
                    @endforeach
                </div>
            @elseif(strlen($userSearch) >= 2)
                <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg p-4 text-center text-sm text-zinc-500">
                    No users found
                </div>
            @endif
        </div>
        
        {{-- Quick role buttons (only in dev/test environments) --}}
        @if(app()->environment('local', 'development', 'testing'))
        <div class="flex flex-wrap gap-2 mt-3">
            <span class="text-xs text-red-600 dark:text-red-400 self-center mr-2">Quick:</span>
            <a href="{{ route('dev.login', 'owner') }}" class="px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-full hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                Test Owner
            </a>
            <a href="{{ route('dev.login', 'admin') }}" class="px-3 py-1 text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 rounded-full hover:bg-amber-200 dark:hover:bg-amber-900/50 transition">
                Test Admin
            </a>
            <a href="{{ route('dev.login', 'member') }}" class="px-3 py-1 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 rounded-full hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition">
                Test Member
            </a>
        </div>
        @endif
    </div>

    {{-- Dev Quick Test Links --}}
    @if(app()->environment('local', 'development', 'testing'))
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4 mb-8">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <h3 class="font-semibold text-amber-800 dark:text-amber-200">Quick Test Pages</h3>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('two-factor.show') }}" wire:navigate class="px-3 py-1.5 text-sm font-medium bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded-lg hover:bg-amber-300 dark:hover:bg-amber-700 transition">
                2FA + Security Questions
            </a>
            <a href="{{ route('profile.edit') }}" wire:navigate class="px-3 py-1.5 text-sm font-medium bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded-lg hover:bg-amber-300 dark:hover:bg-amber-700 transition">
                Profile Settings
            </a>
            <a href="{{ route('documents.index') }}" wire:navigate class="px-3 py-1.5 text-sm font-medium bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded-lg hover:bg-amber-300 dark:hover:bg-amber-700 transition">
                My Documents
            </a>
            <a href="{{ route('admin.learning.index') }}" wire:navigate class="px-3 py-1.5 text-sm font-medium bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded-lg hover:bg-amber-300 dark:hover:bg-amber-700 transition">
                Learning Center (Admin)
            </a>
            <a href="{{ route('admin.membership-types.index') }}" wire:navigate class="px-3 py-1.5 text-sm font-medium bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded-lg hover:bg-amber-300 dark:hover:bg-amber-700 transition">
                Membership Types
            </a>
            <a href="{{ route('firearms.index') }}" wire:navigate class="px-3 py-1.5 text-sm font-medium bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded-lg hover:bg-amber-300 dark:hover:bg-amber-700 transition">
                Virtual Safe
            </a>
        </div>
    </div>
    @endif

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Owner Management</h2>
            <div class="space-y-3">
                <a href="{{ route('developer.owners.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors border border-purple-200 dark:border-purple-800">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    <span class="text-purple-700 dark:text-purple-300 font-medium">Manage Site Owners</span>
                </a>
                <a href="{{ route('owner.admins.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-zinc-700 dark:text-zinc-300">View All Administrators</span>
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">System Access</h2>
            <div class="space-y-3">
                <a href="{{ route('admin.members.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-zinc-700 dark:text-zinc-300">All Members</span>
                </a>
                <a href="{{ route('admin.settings.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-zinc-700 dark:text-zinc-300">System Settings</span>
                </a>
                <a href="{{ route('admin.activity-config.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    <span class="text-zinc-700 dark:text-zinc-300">Activity Configuration</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Current Owners --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Current Site Owners</h2>
            <a href="{{ route('developer.owners.create') }}" wire:navigate
                class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                + Nominate New Owner
            </a>
        </div>
        @php
            $owners = User::where('role', User::ROLE_OWNER)->with('nominatedBy')->get();
        @endphp
        @if($owners->isEmpty())
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <p class="text-zinc-500 dark:text-zinc-400 mb-4">No owners have been nominated yet.</p>
                <a href="{{ route('developer.owners.create') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nominate First Owner
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-2 text-sm font-medium text-zinc-500 dark:text-zinc-400">Name</th>
                            <th class="text-left py-2 text-sm font-medium text-zinc-500 dark:text-zinc-400">Email</th>
                            <th class="text-left py-2 text-sm font-medium text-zinc-500 dark:text-zinc-400">Nominated</th>
                            <th class="text-left py-2 text-sm font-medium text-zinc-500 dark:text-zinc-400">Admins Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                        @foreach($owners as $owner)
                            <tr>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $owner->name }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200">
                                            Owner
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $owner->email }}</td>
                                <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $owner->nominated_at?->format('d M Y') ?? 'N/A' }}</td>
                                <td class="py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ User::where('nominated_by', $owner->id)->where('role', User::ROLE_ADMIN)->count() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
