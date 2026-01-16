<?php

use App\Models\User;
use Livewire\Component;

new class extends Component {
    public int $totalAdmins = 0;
    public int $myAdmins = 0;
    public int $totalMembers = 0;
    public int $pendingApprovals = 0;

    public function mount(): void
    {
        $this->totalAdmins = User::where('role', User::ROLE_ADMIN)->count();
        $this->myAdmins = User::where('role', User::ROLE_ADMIN)
            ->where('nominated_by', auth()->id())
            ->count();
        $this->totalMembers = User::where('role', User::ROLE_MEMBER)->count();
        $this->pendingApprovals = \App\Models\Membership::where('status', 'pending')->count();
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
                    <x-flux::icon name="user-group" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
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
                    <x-flux::icon name="users" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
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
                    <x-flux::icon name="identification" class="w-6 h-6 text-green-600 dark:text-green-400" />
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
                    <x-flux::icon name="clock" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Approvals</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $pendingApprovals }}</p>
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
                    <x-flux::icon name="user-plus" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
                    <span class="text-zinc-700 dark:text-zinc-300">Manage Administrators</span>
                </a>
                <a href="{{ route('admin.approvals.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <x-flux::icon name="clipboard-document-check" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
                    <span class="text-zinc-700 dark:text-zinc-300">Review Pending Approvals</span>
                </a>
                <a href="{{ route('admin.members.index') }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <x-flux::icon name="users" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
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
