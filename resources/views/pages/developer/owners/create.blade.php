<?php

use App\Models\User;
use Livewire\Component;

new class extends Component {
    public string $search = '';
    public ?User $selectedUser = null;
    public bool $createNew = false;

    // New user form fields
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function with(): array
    {
        $existingUsers = collect();

        if ($this->search && strlen($this->search) >= 2) {
            $existingUsers = User::where('role', User::ROLE_MEMBER)
                ->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%");
                })
                ->limit(10)
                ->get();
        }

        return [
            'existingUsers' => $existingUsers,
        ];
    }

    public function selectUser(int $userId): void
    {
        $this->selectedUser = User::find($userId);
        $this->createNew = false;
    }

    public function clearSelection(): void
    {
        $this->selectedUser = null;
        $this->search = '';
    }

    public function promoteToOwner(): void
    {
        if (!$this->selectedUser) {
            session()->flash('error', 'Please select a user first.');
            return;
        }

        if ($this->selectedUser->role !== User::ROLE_MEMBER) {
            session()->flash('error', 'This user already has elevated privileges.');
            return;
        }

        $this->selectedUser->update([
            'role' => User::ROLE_OWNER,
            'is_admin' => true,
            'nominated_by' => auth()->id(),
            'nominated_at' => now(),
        ]);

        session()->flash('success', "{$this->selectedUser->name} has been nominated as a site owner.");
        $this->redirect(route('developer.owners.index'), navigate: true);
    }

    public function createAndPromote(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $owner = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => User::ROLE_OWNER,
            'is_admin' => true,
            'nominated_by' => auth()->id(),
            'nominated_at' => now(),
            'email_verified_at' => now(),
        ]);

        session()->flash('success', "{$owner->name} has been created and nominated as a site owner.");
        $this->redirect(route('developer.owners.index'), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-full text-sm mb-4">
            <x-flux::icon name="shield-check" class="w-4 h-4" />
            Developer Access
        </div>
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Nominate Site Owner</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">
            Promote an existing member to owner, or create a new owner account.
        </p>
    </div>

    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Option 1: Promote Existing User --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Promote Existing Member</h2>
            
            @if($selectedUser)
                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-purple-200 dark:bg-purple-800 flex items-center justify-center">
                                <span class="text-purple-700 dark:text-purple-300 font-medium">{{ $selectedUser->initials() }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-purple-900 dark:text-purple-100">{{ $selectedUser->name }}</p>
                                <p class="text-sm text-purple-700 dark:text-purple-300">{{ $selectedUser->email }}</p>
                            </div>
                        </div>
                        <button wire:click="clearSelection" class="text-purple-600 hover:text-purple-800 dark:text-purple-400">
                            <x-flux::icon name="x-mark" class="w-5 h-5" />
                        </button>
                    </div>
                </div>
                
                <button wire:click="promoteToOwner"
                    class="w-full py-2 px-4 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                    Nominate as Owner
                </button>
            @else
                <div class="mb-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Search for a member</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or email..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                @if($existingUsers->isNotEmpty())
                    <ul class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($existingUsers as $user)
                            <li>
                                <button wire:click="selectUser({{ $user->id }})"
                                    class="w-full flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors text-left">
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-600 flex items-center justify-center">
                                        <span class="text-zinc-700 dark:text-zinc-300 text-sm">{{ $user->initials() }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                                    </div>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @elseif(strlen($search) >= 2)
                    <p class="text-center text-zinc-500 dark:text-zinc-400 py-4">No members found matching "{{ $search }}"</p>
                @else
                    <p class="text-center text-zinc-500 dark:text-zinc-400 py-4">Enter at least 2 characters to search</p>
                @endif
            @endif
        </div>

        {{-- Option 2: Create New Owner --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Create New Owner Account</h2>
            
            <form wire:submit="createAndPromote" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Full Name</label>
                    <input type="text" wire:model="name"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Email</label>
                    <input type="email" wire:model="email"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Password</label>
                    <input type="password" wire:model="password"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Confirm Password</label>
                    <input type="password" wire:model="password_confirmation"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <button type="submit"
                    class="w-full py-2 px-4 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                    Create & Nominate as Owner
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('developer.owners.index') }}" wire:navigate
            class="text-zinc-600 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-300">
            ← Back to Owners
        </a>
    </div>
</div>
