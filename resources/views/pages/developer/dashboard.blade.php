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

    public function mount(): void
    {
        $this->totalUsers = User::count();
        $this->totalOwners = User::where('role', User::ROLE_OWNER)->count();
        $this->totalAdmins = User::where('role', User::ROLE_ADMIN)->count();
        $this->totalMembers = User::where('role', User::ROLE_MEMBER)->count();
        $this->activeMemberships = Membership::where('status', 'active')->count();
    }
}; ?>

<div>
    <div class="mb-8">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full text-sm mb-4">
            <x-flux::icon name="shield-check" class="w-4 h-4" />
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

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Owner Management</h2>
            <div class="space-y-3">
                <a href="{{ route('developer.owners.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors border border-purple-200 dark:border-purple-800">
                    <x-flux::icon name="star" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    <span class="text-purple-700 dark:text-purple-300 font-medium">Manage Site Owners</span>
                </a>
                <a href="{{ route('owner.admins.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <x-flux::icon name="user-group" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
                    <span class="text-zinc-700 dark:text-zinc-300">View All Administrators</span>
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">System Access</h2>
            <div class="space-y-3">
                <a href="{{ route('admin.members.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <x-flux::icon name="users" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
                    <span class="text-zinc-700 dark:text-zinc-300">All Members</span>
                </a>
                <a href="{{ route('admin.settings.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <x-flux::icon name="cog-6-tooth" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
                    <span class="text-zinc-700 dark:text-zinc-300">System Settings</span>
                </a>
                <a href="{{ route('admin.activity-config.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <x-flux::icon name="adjustments-horizontal" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
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
                <x-flux::icon name="user-plus" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
                <p class="text-zinc-500 dark:text-zinc-400 mb-4">No owners have been nominated yet.</p>
                <a href="{{ route('developer.owners.create') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                    <x-flux::icon name="plus" class="w-4 h-4" />
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
