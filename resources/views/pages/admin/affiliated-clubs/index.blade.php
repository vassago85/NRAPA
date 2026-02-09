<?php

use App\Mail\AffiliatedClubInviteMail;
use App\Models\AffiliatedClub;
use App\Models\AffiliatedClubInvite;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Affiliated Clubs - Admin')] class extends Component {
    public bool $showEditModal = false;
    public bool $showInviteModal = false;
    public bool $showAssignModal = false;
    public bool $showMembersModal = false;
    public ?AffiliatedClub $editingClub = null;
    public ?AffiliatedClub $managingClub = null;

    // Club form fields
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|alpha_dash')]
    public string $slug = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|in:hunter,sport,both')]
    public string $dedicated_type = 'both';

    #[Validate('required|numeric|min:0')]
    public float $initial_fee = 0;

    #[Validate('required|numeric|min:0')]
    public float $renewal_fee = 0;

    public bool $requires_competency = true;

    #[Validate('required|integer|min:0|max:12')]
    public int $required_activities_per_year = 2;

    #[Validate('nullable|string|max:255')]
    public ?string $contact_name = null;

    #[Validate('nullable|email|max:255')]
    public ?string $contact_email = null;

    #[Validate('nullable|string|max:50')]
    public ?string $contact_phone = null;

    public bool $is_active = true;

    #[Validate('required|integer|min:0')]
    public int $sort_order = 0;

    // Invite form
    public string $inviteEmail = '';

    // Assign form
    public string $memberSearch = '';
    public ?int $selectedMemberId = null;

    #[Computed]
    public function clubs()
    {
        return AffiliatedClub::ordered()->withCount('memberships')->get();
    }

    #[Computed]
    public function clubMembers()
    {
        if (!$this->managingClub) {
            return collect();
        }

        return Membership::where('affiliated_club_id', $this->managingClub->id)
            ->whereIn('status', ['applied', 'approved', 'active'])
            ->with('user', 'type')
            ->latest()
            ->get();
    }

    #[Computed]
    public function clubInvites()
    {
        if (!$this->managingClub) {
            return collect();
        }

        return $this->managingClub->invites()
            ->pending()
            ->latest()
            ->get();
    }

    #[Computed]
    public function searchResults()
    {
        if (strlen($this->memberSearch) < 2) {
            return collect();
        }

        return User::where(function ($q) {
                $q->where('name', 'like', "%{$this->memberSearch}%")
                  ->orWhere('email', 'like', "%{$this->memberSearch}%");
            })
            ->limit(10)
            ->get();
    }

    // ── Club CRUD ────────────────────────────────────────

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingClub = null;
        $this->showEditModal = true;
    }

    public function openEditModal(int $clubId): void
    {
        $club = AffiliatedClub::findOrFail($clubId);
        $this->editingClub = $club;
        $this->name = $club->name;
        $this->slug = $club->slug;
        $this->description = $club->description ?? '';
        $this->dedicated_type = $club->dedicated_type;
        $this->initial_fee = (float) $club->initial_fee;
        $this->renewal_fee = (float) $club->renewal_fee;
        $this->requires_competency = $club->requires_competency;
        $this->required_activities_per_year = $club->required_activities_per_year;
        $this->contact_name = $club->contact_name;
        $this->contact_email = $club->contact_email;
        $this->contact_phone = $club->contact_phone;
        $this->is_active = $club->is_active;
        $this->sort_order = $club->sort_order;
        $this->showEditModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'dedicated_type' => $this->dedicated_type,
            'initial_fee' => $this->initial_fee,
            'renewal_fee' => $this->renewal_fee,
            'requires_competency' => $this->requires_competency,
            'required_activities_per_year' => $this->required_activities_per_year,
            'contact_name' => $this->contact_name ?: null,
            'contact_email' => $this->contact_email ?: null,
            'contact_phone' => $this->contact_phone ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingClub) {
            $this->editingClub->update($data);
            session()->flash('success', 'Affiliated club updated successfully.');
        } else {
            AffiliatedClub::create($data);
            session()->flash('success', 'Affiliated club created successfully.');
        }

        $this->showEditModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $clubId): void
    {
        $club = AffiliatedClub::findOrFail($clubId);
        $club->update(['is_active' => !$club->is_active]);
    }

    // ── Members Management ───────────────────────────────

    public function openMembersModal(int $clubId): void
    {
        $this->managingClub = AffiliatedClub::findOrFail($clubId);
        $this->showMembersModal = true;
    }

    public function removeFromClub(int $membershipId): void
    {
        $membership = Membership::findOrFail($membershipId);

        if ($membership->affiliated_club_id !== $this->managingClub?->id) {
            return;
        }

        // Remove club association -- keeps them as a regular member
        $membership->update([
            'affiliated_club_id' => null,
            'notes' => trim(($membership->notes ?? '') . "\nRemoved from {$this->managingClub->name} by admin on " . now()->format('Y-m-d')),
        ]);

        session()->flash('success', "{$membership->user->name} has been removed from {$this->managingClub->name} and remains a regular member.");
        unset($this->clubMembers);
    }

    // ── Invite ───────────────────────────────────────────

    public function openInviteModal(int $clubId): void
    {
        $this->managingClub = AffiliatedClub::findOrFail($clubId);
        $this->inviteEmail = '';
        $this->showInviteModal = true;
    }

    public function sendInvite(): void
    {
        $this->validate([
            'inviteEmail' => ['required', 'email', 'max:255'],
        ], [
            'inviteEmail.required' => 'Please enter an email address.',
            'inviteEmail.email' => 'Please enter a valid email address.',
        ]);

        if (!$this->managingClub) {
            return;
        }

        // Check for existing pending invite to this club for this email
        $existingInvite = AffiliatedClubInvite::where('affiliated_club_id', $this->managingClub->id)
            ->where('email', $this->inviteEmail)
            ->pending()
            ->first();

        if ($existingInvite) {
            $this->addError('inviteEmail', 'A pending invite already exists for this email address.');
            return;
        }

        $invite = AffiliatedClubInvite::create([
            'affiliated_club_id' => $this->managingClub->id,
            'email' => $this->inviteEmail,
        ]);

        $inviteUrl = route('membership.club-apply', [
            'club' => $this->managingClub->slug,
            'token' => $invite->token,
        ]);

        try {
            Mail::to($this->inviteEmail)
                ->queue(new AffiliatedClubInviteMail($invite, $this->managingClub, $inviteUrl));

            session()->flash('success', "Invitation sent to {$this->inviteEmail} for {$this->managingClub->name}.");
        } catch (\Exception $e) {
            \Log::error('Failed to send club invite email', [
                'invite_id' => $invite->id,
                'email' => $this->inviteEmail,
                'error' => $e->getMessage(),
            ]);
            session()->flash('success', "Invite created but email delivery may be delayed. Check email logs.");
        }

        $this->inviteEmail = '';
        $this->showInviteModal = false;
        unset($this->clubInvites);
    }

    // ── Assign Existing Member ───────────────────────────

    public function openAssignModal(int $clubId): void
    {
        $this->managingClub = AffiliatedClub::findOrFail($clubId);
        $this->memberSearch = '';
        $this->selectedMemberId = null;
        $this->showAssignModal = true;
    }

    public function selectMember(int $userId): void
    {
        $this->selectedMemberId = $userId;
    }

    public function assignMember(): void
    {
        if (!$this->managingClub || !$this->selectedMemberId) {
            return;
        }

        $user = User::findOrFail($this->selectedMemberId);

        // Find the user's active membership
        $membership = $user->memberships()
            ->whereIn('status', ['applied', 'approved', 'active'])
            ->first();

        if (!$membership) {
            $this->addError('memberSearch', 'This user does not have an active membership to assign to a club.');
            return;
        }

        if ($membership->affiliated_club_id === $this->managingClub->id) {
            $this->addError('memberSearch', 'This member is already assigned to this club.');
            return;
        }

        $membership->update([
            'affiliated_club_id' => $this->managingClub->id,
            'notes' => trim(($membership->notes ?? '') . "\nAssigned to {$this->managingClub->name} by admin on " . now()->format('Y-m-d')),
        ]);

        session()->flash('success', "{$user->name} has been assigned to {$this->managingClub->name}.");

        $this->showAssignModal = false;
        $this->memberSearch = '';
        $this->selectedMemberId = null;
        unset($this->clubMembers);
    }

    // ── Helpers ──────────────────────────────────────────

    protected function resetForm(): void
    {
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->dedicated_type = 'both';
        $this->initial_fee = 0;
        $this->renewal_fee = 0;
        $this->requires_competency = true;
        $this->required_activities_per_year = 2;
        $this->contact_name = null;
        $this->contact_email = null;
        $this->contact_phone = null;
        $this->is_active = true;
        $this->sort_order = (int) AffiliatedClub::max('sort_order') + 1;
        $this->editingClub = null;
    }

    public function updatedName(): void
    {
        if (!$this->editingClub) {
            $this->slug = \Illuminate\Support\Str::slug($this->name);
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Affiliated Clubs</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Manage affiliated clubs, invite members, and assign existing members to clubs.</p>
        </div>
        <button wire:click="openCreateModal" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Club
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg border border-green-300 bg-green-100 p-4 text-green-800 dark:border-green-700 dark:bg-green-900/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Clubs Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Club</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Fees</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Members</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Active</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->clubs as $club)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 {{ !$club->is_active ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $club->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $club->slug }}</p>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $club->dedicated_type === 'both' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : ($club->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                {{ $club->dedicated_type === 'both' ? 'Hunter & Sport' : ucfirst($club->dedicated_type) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <p class="text-sm text-zinc-900 dark:text-white">R{{ number_format($club->initial_fee, 2) }} <span class="text-zinc-400">sign-up</span></p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">R{{ number_format($club->renewal_fee, 2) }}/year</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <button wire:click="openMembersModal({{ $club->id }})" class="inline-flex items-center gap-1 text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                <span>{{ $club->memberships_count }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <button wire:click="toggleActive({{ $club->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $club->is_active ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $club->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openInviteModal({{ $club->id }})" class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50" title="Send Invite">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Invite
                                </button>
                                <button wire:click="openAssignModal({{ $club->id }})" class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50" title="Assign Member">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    Assign
                                </button>
                                <button wire:click="openEditModal({{ $club->id }})" class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600">
                                    Edit
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            <p>No affiliated clubs configured yet.</p>
                            <p class="text-sm mt-1">Click "Add Club" to create one.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ Edit/Create Club Modal ═══ --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showEditModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800 max-h-[90vh] overflow-y-auto">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">
                    {{ $editingClub ? 'Edit Affiliated Club' : 'Create Affiliated Club' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Club Name *</label>
                            <input type="text" wire:model.live="name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Slug *</label>
                            <input type="text" wire:model="slug" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white" {{ $editingClub ? 'disabled' : '' }}>
                            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Dedicated Type *</label>
                        <select wire:model="dedicated_type" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <option value="hunter">Dedicated Hunter</option>
                            <option value="sport">Dedicated Sport Shooter</option>
                            <option value="both">Both (Hunter & Sport)</option>
                        </select>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Determines the dedicated status members of this club receive.</p>
                    </div>

                    {{-- Fee Schedule --}}
                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Fee Schedule</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Initial Fee (R) *</label>
                                <input type="number" wire:model="initial_fee" step="0.01" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Sign-up fee for new club members</p>
                                @error('initial_fee') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Renewal Fee (R) *</label>
                                <input type="number" wire:model="renewal_fee" step="0.01" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Annual renewal fee for club members</p>
                                @error('renewal_fee') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Requirements --}}
                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Requirements</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="requires_competency" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Require SAPS Firearm Competency</span>
                            </label>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Activities per Year *</label>
                                <input type="number" wire:model="required_activities_per_year" min="0" max="12" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Match results with member name as evidence</p>
                                @error('required_activities_per_year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Contact Info --}}
                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Contact Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Contact Name</label>
                                <input type="text" wire:model="contact_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Contact Email</label>
                                <input type="email" wire:model="contact_email" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Contact Phone</label>
                                <input type="text" wire:model="contact_phone" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Active</span>
                        </label>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sort Order</label>
                            <input type="number" wire:model="sort_order" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="$set('showEditModal', false)" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            {{ $editingClub ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ Send Invite Modal ═══ --}}
    @if($showInviteModal && $managingClub)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showInviteModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">Send Invite</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                    Send an invitation email to join NRAPA via <strong>{{ $managingClub->name }}</strong>.
                    The recipient will receive a link to apply as a club member.
                </p>

                <form wire:submit="sendInvite" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Email Address *</label>
                        <input type="email" wire:model="inviteEmail" placeholder="member@example.com" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white" autofocus>
                        @error('inviteEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3">
                        <p class="text-xs text-blue-700 dark:text-blue-300">
                            The invite link will be valid for 30 days. The recipient must register or log in to accept the invitation and complete their club membership application.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showInviteModal', false)" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Send Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ Assign Existing Member Modal ═══ --}}
    @if($showAssignModal && $managingClub)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showAssignModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">Assign Member to Club</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                    Search for an existing NRAPA member to assign to <strong>{{ $managingClub->name }}</strong>.
                </p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search Member</label>
                        <input type="text" wire:model.live.debounce.300ms="memberSearch" placeholder="Name, email, or membership number..." class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white" autofocus>
                        @error('memberSearch') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if(strlen($memberSearch) >= 2)
                    <div class="max-h-48 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        @forelse($this->searchResults as $user)
                        <button type="button" wire:click="selectMember({{ $user->id }})"
                            class="flex w-full items-center gap-3 px-3 py-2 text-left hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors {{ $selectedMemberId === $user->id ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $user->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $user->email }}</p>
                            </div>
                            @if($selectedMemberId === $user->id)
                            <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </button>
                        @empty
                        <p class="px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400 text-center">No members found.</p>
                        @endforelse
                    </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showAssignModal', false)" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button type="button" wire:click="assignMember" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-50" {{ !$selectedMemberId ? 'disabled' : '' }}>
                            Assign to Club
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ Club Members Modal ═══ --}}
    @if($showMembersModal && $managingClub)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showMembersModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $managingClub->name }} Members</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->clubMembers->count() }} member(s)</p>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="openInviteModal({{ $managingClub->id }})" class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Invite
                        </button>
                        <button wire:click="openAssignModal({{ $managingClub->id }})" class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            Assign
                        </button>
                    </div>
                </div>

                {{-- Pending Invites --}}
                @if($this->clubInvites->count() > 0)
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Pending Invites</h3>
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->clubInvites as $invite)
                        <div class="flex items-center justify-between px-3 py-2">
                            <div>
                                <p class="text-sm text-zinc-900 dark:text-white">{{ $invite->email }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Sent {{ $invite->created_at->diffForHumans() }} &middot; Expires {{ $invite->expires_at->format('d M Y') }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Current Members --}}
                @if($this->clubMembers->count() > 0)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->clubMembers as $membership)
                    <div class="flex items-center justify-between px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $membership->user->name }}</p>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $membership->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($membership->status === 'approved' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                    {{ ucfirst($membership->status) }}
                                </span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $membership->user->email }}</p>
                        </div>
                        <button wire:click="removeFromClub({{ $membership->id }})" wire:confirm="Remove {{ $membership->user->name }} from {{ $managingClub->name }}? They will remain a regular NRAPA member." class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Remove
                        </button>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-center text-sm text-zinc-500 dark:text-zinc-400 py-6">No members assigned to this club yet.</p>
                @endif

                <div class="flex justify-end mt-6">
                    <button wire:click="$set('showMembersModal', false)" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
