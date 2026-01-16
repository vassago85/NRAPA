<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showRevokeModal = false;
    public ?User $ownerToRevoke = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = User::where('role', User::ROLE_OWNER)->with('nominatedBy');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        return [
            'owners' => $query->latest()->paginate(10),
        ];
    }

    public function confirmRevoke(User $owner): void
    {
        $this->ownerToRevoke = $owner;
        $this->showRevokeModal = true;
    }

    public function revokeOwner(): void
    {
        if (!$this->ownerToRevoke) {
            return;
        }

        $name = $this->ownerToRevoke->name;
        
        // Also demote all admins created by this owner
        User::where('nominated_by', $this->ownerToRevoke->id)
            ->where('role', User::ROLE_ADMIN)
            ->update([
                'role' => User::ROLE_MEMBER,
                'is_admin' => false,
            ]);

        $this->ownerToRevoke->update([
            'role' => User::ROLE_MEMBER,
            'is_admin' => false,
        ]);

        $this->showRevokeModal = false;
        $this->ownerToRevoke = null;
        session()->flash('success', "{$name} has been revoked as owner. All their admins have been demoted to members.");
    }
}; ?>

<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full text-sm mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Developer Access
            </div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Manage Site Owners</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Nominate and manage site owners who can create administrators.</p>
        </div>
        <a href="{{ route('developer.owners.create') }}" wire:navigate
            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nominate Owner
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search owners..."
            class="w-full md:w-96 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
    </div>

    {{-- Owners Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Owner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nominated</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Admins</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($owners as $owner)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                    <span class="text-purple-700 dark:text-purple-300 font-medium">{{ $owner->initials() }}</span>
                                </div>
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $owner->name }}</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Owner</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                            {{ $owner->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-zinc-900 dark:text-white">{{ $owner->nominated_at?->format('d M Y') ?? 'N/A' }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">by {{ $owner->nominatedBy?->name ?? 'System' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $adminCount = User::where('nominated_by', $owner->id)->where('role', User::ROLE_ADMIN)->count();
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $adminCount > 0 ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                                {{ $adminCount }} admin{{ $adminCount !== 1 ? 's' : '' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <button wire:click="confirmRevoke({{ $owner->id }})"
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium">
                                Revoke
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            <p class="text-zinc-500 dark:text-zinc-400">No owners found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($owners->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $owners->links() }}
            </div>
        @endif
    </div>

    {{-- Revoke Confirmation Modal --}}
    @if($showRevokeModal && $ownerToRevoke)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showRevokeModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-full">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Revoke Owner Access</h2>
                    </div>
                    
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4 mb-4">
                        <p class="text-amber-800 dark:text-amber-200 text-sm">
                            <strong>Warning:</strong> This will also demote all administrators created by this owner to regular members.
                        </p>
                    </div>

                    <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                        Are you sure you want to revoke <strong>{{ $ownerToRevoke->name }}</strong>'s owner access?
                    </p>

                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showRevokeModal', false)"
                            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button wire:click="revokeOwner"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                            Revoke Owner Access
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
