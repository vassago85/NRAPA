<?php

use App\Models\User;
use App\Models\Permission;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showCreateModal = false;
    public bool $showDeleteModal = false;
    public bool $showPermissionsModal = false;
    public ?User $adminToDelete = null;
    public ?User $adminToEdit = null;
    public array $selectedPermissions = [];

    // Form fields
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $adminType = User::ADMIN_TYPE_STANDARD;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'adminType' => 'required|in:' . User::ADMIN_TYPE_SUPER . ',' . User::ADMIN_TYPE_STANDARD,
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = User::where('role', User::ROLE_ADMIN)->with('permissions');

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
            'permissions' => Permission::ordered()->get()->groupBy('group'),
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
            'admin_type' => $this->adminType,
            'is_admin' => true,
            'nominated_by' => auth()->id(),
            'nominated_at' => now(),
            'email_verified_at' => now(), // Auto-verify admin accounts
        ]);

        // Grant default permissions based on admin type
        $defaultPermissions = $this->adminType === User::ADMIN_TYPE_SUPER
            ? Permission::getDefaultSuperAdminPermissions()
            : Permission::getDefaultStandardAdminPermissions();

        $admin->grantPermissions($defaultPermissions, auth()->user());

        $this->reset(['name', 'email', 'password', 'password_confirmation', 'adminType', 'showCreateModal']);
        session()->flash('success', "Administrator {$admin->name} has been created with default {$admin->admin_type_display_name} permissions.");
    }

    public function openPermissionsModal(User $admin): void
    {
        if (!auth()->user()->canGrantPermissions() || !auth()->user()->canManageUser($admin)) {
            session()->flash('error', 'You do not have permission to manage this administrator\'s permissions.');
            return;
        }

        $this->adminToEdit = $admin;
        $this->selectedPermissions = $admin->permissions()->pluck('slug')->toArray();
        $this->showPermissionsModal = true;
    }

    public function togglePermission(string $slug): void
    {
        if (in_array($slug, $this->selectedPermissions)) {
            $this->selectedPermissions = array_diff($this->selectedPermissions, [$slug]);
        } else {
            $this->selectedPermissions[] = $slug;
        }
    }

    public function savePermissions(): void
    {
        if (!$this->adminToEdit || !auth()->user()->canGrantPermissions() || !auth()->user()->canManageUser($this->adminToEdit)) {
            session()->flash('error', 'You do not have permission to manage this administrator\'s permissions.');
            $this->showPermissionsModal = false;
            return;
        }

        $this->adminToEdit->syncPermissions($this->selectedPermissions, auth()->user());

        $name = $this->adminToEdit->name;
        $this->showPermissionsModal = false;
        $this->adminToEdit = null;
        $this->selectedPermissions = [];
        
        session()->flash('success', "Permissions for {$name} have been updated.");
    }

    public function applyDefaultPermissions(): void
    {
        if (!$this->adminToEdit) return;

        $this->selectedPermissions = $this->adminToEdit->admin_type === User::ADMIN_TYPE_SUPER
            ? Permission::getDefaultSuperAdminPermissions()
            : Permission::getDefaultStandardAdminPermissions();
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
        
        // Revoke all permissions
        $this->adminToDelete->permissions()->detach();
        
        // Demote to member
        $this->adminToDelete->update([
            'role' => User::ROLE_MEMBER,
            'admin_type' => null,
            'is_admin' => false,
        ]);

        $this->showDeleteModal = false;
        $this->adminToDelete = null;
        session()->flash('success', "{$name} has been demoted to member and all permissions revoked.");
    }

    public function updateAdminType(User $admin, string $type): void
    {
        if (!auth()->user()->canAssignRoles() || !auth()->user()->canManageUser($admin)) {
            session()->flash('error', 'You do not have permission to change this admin\'s role.');
            return;
        }

        if (!in_array($type, [User::ADMIN_TYPE_SUPER, User::ADMIN_TYPE_STANDARD])) {
            session()->flash('error', 'Invalid admin type.');
            return;
        }

        $admin->update(['admin_type' => $type]);
        session()->flash('success', "{$admin->name} has been updated to {$admin->admin_type_display_name}.");
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Manage Admins</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Appoint and manage administrator accounts</p>
    </x-slot>

    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <button wire:click="$set('showCreateModal', true)"
            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Admin
        </button>
    </div>

    {{-- Info Box --}}
    <div class="mb-6 rounded-xl border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
        <div class="flex gap-3">
            <svg class="size-5 text-purple-500 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <div class="text-sm text-purple-700 dark:text-purple-300">
                <p class="font-medium">Role-Based Access Control</p>
                <p class="mt-1 text-purple-600 dark:text-purple-400">
                    <strong>Super Admins</strong> have elevated defaults for compliance tasks. 
                    <strong>Standard Admins</strong> have basic member management permissions. 
                    You can customize permissions for each admin individually.
                </p>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/40 border border-emerald-300 dark:border-emerald-700 rounded-xl text-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search administrators..."
            class="w-full md:w-96 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
    </div>

    {{-- Admins Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Admin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Permissions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nominated By</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($admins as $admin)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <td class="px-6 py-4">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $admin->name }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $admin->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if(auth()->user()->canAssignRoles() && auth()->user()->canManageUser($admin))
                                <select wire:change="updateAdminType('{{ $admin->uuid }}', $event.target.value)" 
                                    class="text-sm rounded-lg border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="{{ User::ADMIN_TYPE_STANDARD }}" {{ $admin->admin_type === User::ADMIN_TYPE_STANDARD ? 'selected' : '' }}>Standard Admin</option>
                                    <option value="{{ User::ADMIN_TYPE_SUPER }}" {{ $admin->admin_type === User::ADMIN_TYPE_SUPER ? 'selected' : '' }}>Super Admin</option>
                                </select>
                                @else
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $admin->admin_type === User::ADMIN_TYPE_SUPER ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                    {{ $admin->admin_type_display_name ?? 'Standard Admin' }}
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php $permCount = $admin->permissions->count(); $highRiskCount = $admin->permissions->where('is_high_risk', true)->count(); @endphp
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $permCount }} permission{{ $permCount !== 1 ? 's' : '' }}</span>
                                    @if($highRiskCount > 0)
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                        {{ $highRiskCount }} high-risk
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $admin->nominatedBy?->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if(auth()->user()->canGrantPermissions() && auth()->user()->canManageUser($admin))
                                    <button wire:click="openPermissionsModal('{{ $admin->uuid }}')"
                                        class="px-3 py-1.5 text-sm bg-purple-100 hover:bg-purple-200 text-purple-700 dark:bg-purple-900/50 dark:hover:bg-purple-900 dark:text-purple-300 rounded-lg transition-colors">
                                        Permissions
                                    </button>
                                    @endif
                                    @if(auth()->user()->canManageUser($admin))
                                    <button wire:click="confirmDelete('{{ $admin->uuid }}')"
                                        class="px-3 py-1.5 text-sm bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/50 dark:hover:bg-red-900 dark:text-red-300 rounded-lg transition-colors">
                                        Demote
                                    </button>
                                    @endif
                                </div>
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
        </div>

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
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Admin Type</label>
                            <select wire:model="adminType"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="{{ User::ADMIN_TYPE_STANDARD }}">Standard Admin</option>
                                <option value="{{ User::ADMIN_TYPE_SUPER }}">Super Admin</option>
                            </select>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Super Admins get elevated permissions for compliance tasks.
                            </p>
                            @error('adminType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" wire:click="$set('showCreateModal', false)"
                                class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                                Create Admin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Permissions Modal --}}
    @if($showPermissionsModal && $adminToEdit)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showPermissionsModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
                    <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Manage Permissions</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $adminToEdit->name }} ({{ $adminToEdit->admin_type_display_name ?? 'Admin' }})</p>
                    </div>
                    
                    <div class="p-6 overflow-y-auto max-h-[60vh] space-y-6">
                        <div class="flex justify-end">
                            <button wire:click="applyDefaultPermissions" class="text-sm text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">
                                Reset to Default Permissions
                            </button>
                        </div>

                        @foreach($permissions as $group => $groupPermissions)
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-3">
                                {{ $groupPermissions->first()?->group_display_name ?? ucfirst($group) }}
                            </h3>
                            <div class="space-y-2">
                                @foreach($groupPermissions as $permission)
                                <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors {{ in_array($permission->slug, $selectedPermissions) ? 'border-purple-300 bg-purple-50 dark:border-purple-700 dark:bg-purple-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/30' }}">
                                    <input type="checkbox" 
                                        wire:click="togglePermission('{{ $permission->slug }}')"
                                        {{ in_array($permission->slug, $selectedPermissions) ? 'checked' : '' }}
                                        class="mt-0.5 size-4 rounded border-zinc-300 text-purple-600 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-700">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-zinc-900 dark:text-white">{{ $permission->name }}</span>
                                            @if($permission->is_high_risk)
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                                🔴 High Risk
                                            </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $permission->description }}</p>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="p-6 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3">
                        <button wire:click="$set('showPermissionsModal', false)"
                            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                            Cancel
                        </button>
                        <button wire:click="savePermissions"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                            Save Permissions
                        </button>
                    </div>
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
                        They will lose all administrative privileges and permissions.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showDeleteModal', false)"
                            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                            Cancel
                        </button>
                        <button wire:click="deleteAdmin"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            Demote to Member
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
