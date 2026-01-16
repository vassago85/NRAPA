<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showCreateModal = false;
    public bool $showDeleteModal = false;
    public ?User $adminToDelete = null;

    // Form fields
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = User::where('role', User::ROLE_ADMIN);

        // If user is owner (not developer), only show their admins
        if (auth()->user()->isOwner() && !auth()->user()->isDeveloper()) {
            $query->where('nominated_by', auth()->id());
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        return [
            'admins' => $query->latest()->paginate(10),
        ];
    }

    public function createAdmin(): void
    {
        $this->validate();

        $admin = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => User::ROLE_ADMIN,
            'is_admin' => true,
            'nominated_by' => auth()->id(),
            'nominated_at' => now(),
            'email_verified_at' => now(), // Auto-verify admin accounts
        ]);

        $this->reset(['name', 'email', 'password', 'password_confirmation', 'showCreateModal']);
        session()->flash('success', "Administrator {$admin->name} has been created successfully.");
    }

    public function confirmDelete(User $admin): void
    {
        if (!auth()->user()->canManageUser($admin)) {
            session()->flash('error', 'You do not have permission to delete this administrator.');
            return;
        }

        $this->adminToDelete = $admin;
        $this->showDeleteModal = true;
    }

    public function deleteAdmin(): void
    {
        if (!$this->adminToDelete || !auth()->user()->canManageUser($this->adminToDelete)) {
            session()->flash('error', 'You do not have permission to delete this administrator.');
            $this->showDeleteModal = false;
            return;
        }

        $name = $this->adminToDelete->name;
        $this->adminToDelete->update([
            'role' => User::ROLE_MEMBER,
            'is_admin' => false,
        ]);

        $this->showDeleteModal = false;
        $this->adminToDelete = null;
        session()->flash('success', "{$name} has been demoted to member.");
    }
}; ?>

<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Manage Administrators</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Create and manage administrator accounts.</p>
        </div>
        <button wire:click="$set('showCreateModal', true)"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
            <x-flux::icon name="plus" class="w-5 h-5" />
            Create Admin
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search administrators..."
            class="w-full md:w-96 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    </div>

    {{-- Admins Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nominated By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($admins as $admin)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $admin->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                            {{ $admin->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                            {{ $admin->nominatedBy?->name ?? 'System' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                            {{ $admin->nominated_at?->format('d M Y') ?? $admin->created_at->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            @if(auth()->user()->canManageUser($admin))
                                <button wire:click="confirmDelete({{ $admin->id }})"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    Demote
                                </button>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            No administrators found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($admins->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $admins->links() }}
            </div>
        @endif
    </div>

    {{-- Create Admin Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showCreateModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">Create New Administrator</h2>
                    
                    <form wire:submit="createAdmin" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name</label>
                            <input type="text" wire:model="name"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Email</label>
                            <input type="email" wire:model="email"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Password</label>
                            <input type="password" wire:model="password"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Confirm Password</label>
                            <input type="password" wire:model="password_confirmation"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" wire:click="$set('showCreateModal', false)"
                                class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                Create Admin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal && $adminToDelete)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showDeleteModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">Demote Administrator</h2>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                        Are you sure you want to demote <strong>{{ $adminToDelete->name }}</strong> to a regular member? 
                        They will lose all administrative privileges.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showDeleteModal', false)"
                            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button wire:click="deleteAdmin"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                            Demote to Member
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
