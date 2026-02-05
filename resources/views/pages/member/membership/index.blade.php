<?php

use App\Models\Membership;
use App\Models\MembershipType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My Membership')] class extends Component {
    public bool $showChangeModal = false;
    public ?int $selectedNewTypeId = null;
    public string $changeReason = '';
    public bool $showDetails = false;

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function memberships()
    {
        return $this->user->memberships()->with('type')->latest()->get();
    }

    #[Computed]
    public function activeMembership()
    {
        return $this->user->activeMembership;
    }

    #[Computed]
    public function membershipStatus(): array
    {
        $membership = $this->activeMembership;
        
        if (!$membership || !$membership->expires_at) {
            return [
                'days_to_expiry' => null,
                'expiring_soon' => false,
                'expired' => false,
            ];
        }

        $daysToExpiry = now()->startOfDay()->diffInDays($membership->expires_at->startOfDay(), false);
        
        return [
            'days_to_expiry' => $daysToExpiry,
            'expiring_soon' => $daysToExpiry >= 0 && $daysToExpiry <= 30,
            'expired' => $daysToExpiry < 0,
        ];
    }

    #[Computed]
    public function availableMembershipTypes()
    {
        $currentTypeId = $this->activeMembership?->membership_type_id;
        
        return MembershipType::active()
            ->displayOnSignup()
            ->when($currentTypeId, fn($q) => $q->where('id', '!=', $currentTypeId))
            ->ordered()
            ->get();
    }

    #[Computed]
    public function hasPendingChangeRequest()
    {
        return $this->user->memberships()
            ->where('status', 'pending_change')
            ->exists();
    }

    #[Computed]
    public function canRenew(): bool
    {
        $membership = $this->activeMembership;
        if (!$membership) return false;
        
        return $membership->requiresRenewal() && $membership->isRenewable();
    }

    #[Computed]
    public function canChangeMembership(): bool
    {
        return $this->availableMembershipTypes->count() > 0 && !$this->hasPendingChangeRequest;
    }

    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
    }

    public function openChangeModal(): void
    {
        if ($this->hasPendingChangeRequest) {
            session()->flash('error', 'You already have a pending membership change request.');
            return;
        }
        
        $this->selectedNewTypeId = null;
        $this->changeReason = '';
        $this->showChangeModal = true;
    }

    public function submitChangeRequest(): void
    {
        $this->validate([
            'selectedNewTypeId' => ['required', 'exists:membership_types,id'],
            'changeReason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'selectedNewTypeId.required' => 'Please select a new membership type.',
            'changeReason.required' => 'Please provide a reason for the change.',
            'changeReason.min' => 'Please provide more detail (at least 10 characters).',
        ]);

        $newType = MembershipType::findOrFail($this->selectedNewTypeId);

        Membership::create([
            'user_id' => $this->user->id,
            'membership_type_id' => $newType->id,
            'status' => 'pending_change',
            'applied_at' => now(),
            'notes' => "Change request from {$this->activeMembership->type->name} to {$newType->name}.\n\nReason: {$this->changeReason}",
        ]);

        $this->showChangeModal = false;
        session()->flash('success', 'Your membership change request has been submitted for review.');
    }

    public function getStatusClasses(string $status): string
    {
        return match($status) {
            'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
            'applied' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
            'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
            'pending_change' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
            'pending_payment' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
            'suspended', 'revoked' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
            'expired' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300',
        };
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">My Membership</h1>
            <p class="text-zinc-500 dark:text-zinc-400">View and manage your NRAPA membership.</p>
        </div>
        @if(!$this->activeMembership)
        <a href="{{ route('membership.apply') }}" wire:navigate 
            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Apply for Membership
        </a>
        @endif
    </div>

    @if(session('success'))
    <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-4">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    {{-- Current Membership Summary Card --}}
    @if($this->activeMembership)
    @php
        $membership = $this->activeMembership;
        $status = $this->membershipStatus;
    @endphp
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm overflow-hidden">
        {{-- Top Row: Type & Status --}}
        <div class="p-6 pb-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Membership Type</p>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mt-1">{{ $membership->type->name }}</h2>
                </div>
                <div class="flex items-center gap-2">
                    @if($status['expired'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 dark:bg-red-900/50 px-3 py-1 text-sm font-medium text-red-800 dark:text-red-300">
                            <span class="size-1.5 rounded-full bg-red-500"></span>
                            Expired
                        </span>
                    @elseif($status['expiring_soon'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 dark:bg-amber-900/50 px-3 py-1 text-sm font-medium text-amber-800 dark:text-amber-300">
                            <span class="size-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            Expiring Soon
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/50 px-3 py-1 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                            Active
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Key Facts Row --}}
        <div class="px-6 pb-4">
            <div class="flex flex-wrap gap-x-8 gap-y-3 text-sm">
                {{-- Expires On / Lifetime --}}
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">Expires</span>
                    <p class="font-medium text-zinc-900 dark:text-white mt-0.5">
                        @if($membership->type->isLifetime())
                            <span class="text-amber-600 dark:text-amber-400">Lifetime - Never</span>
                        @elseif($membership->expires_at)
                            {{ $membership->expires_at->format('d M Y') }}
                            @if($status['expiring_soon'])
                                <span class="text-amber-600 dark:text-amber-400">({{ $status['days_to_expiry'] }} days)</span>
                            @endif
                        @else
                            N/A
                        @endif
                    </p>
                </div>
                {{-- Member Number --}}
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">Member #</span>
                    <p class="font-mono font-medium text-zinc-900 dark:text-white mt-0.5">{{ $membership->membership_number }}</p>
                </div>
                {{-- Member Since --}}
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">Member Since</span>
                    <p class="font-medium text-zinc-900 dark:text-white mt-0.5">{{ $membership->activated_at?->format('d M Y') ?? 'Pending' }}</p>
                </div>
            </div>
        </div>

        {{-- Actions Row --}}
        <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-700 flex flex-wrap items-center gap-3">
            @if($this->canRenew)
            <a href="{{ route('membership.apply') }}" wire:navigate 
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition-colors">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Renew Membership
            </a>
            @endif
            
            @if($this->canChangeMembership)
            <button wire:click="openChangeModal" 
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Change Type
            </button>
            @elseif($this->hasPendingChangeRequest)
            <span class="inline-flex items-center gap-2 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 px-4 py-2 text-sm font-medium text-purple-700 dark:text-purple-300">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Change Pending
            </span>
            @endif

            {{-- View Details Toggle --}}
            <button wire:click="toggleDetails" 
                class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 ml-auto transition-colors">
                {{ $showDetails ? 'Hide details' : 'View details' }}
                <svg class="size-4 transition-transform {{ $showDetails ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>

        {{-- Collapsible Details Section --}}
        @if($showDetails)
        <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">Duration</span>
                    <p class="font-medium text-zinc-900 dark:text-white mt-0.5">
                        @if($membership->type->isLifetime())
                            Lifetime
                        @else
                            {{ $membership->type->duration_months }} months
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">Renewal Required</span>
                    <p class="font-medium text-zinc-900 dark:text-white mt-0.5">
                        {{ $membership->type->requires_renewal ? 'Yes - Annual' : 'No' }}
                    </p>
                </div>
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">Dedicated Status</span>
                    <p class="mt-0.5">
                        @if($membership->type->allows_dedicated_status)
                            <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/50 px-2 py-0.5 text-xs font-medium text-blue-800 dark:text-blue-300">Eligible</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-zinc-100 dark:bg-zinc-700 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:text-zinc-400">Not Available</span>
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">Price</span>
                    <p class="font-medium text-zinc-900 dark:text-white mt-0.5">R{{ number_format($membership->type->price, 2) }}</p>
                </div>
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">Applied</span>
                    <p class="font-medium text-zinc-900 dark:text-white mt-0.5">{{ $membership->applied_at->format('d M Y') }}</p>
                </div>
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">Activated</span>
                    <p class="font-medium text-zinc-900 dark:text-white mt-0.5">{{ $membership->activated_at?->format('d M Y') ?? 'Pending' }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Membership History --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
            <h2 class="font-semibold text-zinc-900 dark:text-white">Membership History</h2>
        </div>

        @if($this->memberships->count() > 0)
            @if($this->memberships->count() === 1)
            {{-- Single membership: show as simple card --}}
            @php $membership = $this->memberships->first(); @endphp
            <div class="p-6">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $membership->type->name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Applied {{ $membership->applied_at->format('d M Y') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusClasses($membership->status) }}">
                            {{ ucfirst(str_replace('_', ' ', $membership->status)) }}
                        </span>
                        <a href="{{ route('membership.show', $membership) }}" wire:navigate 
                            class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 font-medium">
                            View
                        </a>
                    </div>
                </div>
            </div>
            @else
            {{-- Multiple memberships: show as table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-100 dark:border-zinc-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Applied</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Expires</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($this->memberships as $membership)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/30 transition-colors">
                            <td class="px-6 py-3 text-zinc-900 dark:text-white">{{ $membership->type->name }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $this->getStatusClasses($membership->status) }}">
                                    {{ ucfirst(str_replace('_', ' ', $membership->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-zinc-500 dark:text-zinc-400">{{ $membership->applied_at->format('d M Y') }}</td>
                            <td class="px-6 py-3 text-zinc-500 dark:text-zinc-400">
                                @if($membership->expires_at)
                                    {{ $membership->expires_at->format('d M Y') }}
                                @else
                                    <span class="text-amber-600 dark:text-amber-400">Lifetime</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('membership.show', $membership) }}" wire:navigate 
                                    class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 font-medium">
                                    View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        @else
        <div class="p-8 text-center">
            <svg class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/>
            </svg>
            <h3 class="mt-4 font-medium text-zinc-900 dark:text-white">No Membership History</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">You haven't applied for a membership yet.</p>
            <a href="{{ route('membership.apply') }}" wire:navigate 
                class="mt-4 inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                Apply Now
            </a>
        </div>
        @endif
    </div>

    {{-- Change Membership Modal --}}
    @if($showChangeModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="document.body.classList.add('overflow-hidden')" x-on:remove="document.body.classList.remove('overflow-hidden')">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showChangeModal', false)" class="fixed inset-0 bg-black/50 transition-opacity"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white dark:bg-zinc-800 p-6 shadow-xl">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">Change Membership Type</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                    Request to change from <strong class="text-zinc-700 dark:text-zinc-300">{{ $this->activeMembership->type->name }}</strong> to a different type.
                </p>

                <form wire:submit="submitChangeRequest" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Select New Type <span class="text-red-500">*</span></label>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($this->availableMembershipTypes as $type)
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                                {{ $selectedNewTypeId === $type->id 
                                    ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-900/20' 
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' }}">
                                <input type="radio" wire:model="selectedNewTypeId" value="{{ $type->id }}" class="mt-1 text-emerald-600 focus:ring-emerald-500">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $type->name }}</span>
                                        @if($type->is_featured)
                                            <span class="text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300 px-1.5 py-0.5 rounded">Popular</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                                        R{{ number_format($type->price, 0) }}
                                        {{ $type->duration_type === 'lifetime' ? '(Lifetime)' : '/year' }}
                                    </p>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('selectedNewTypeId') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reason for Change <span class="text-red-500">*</span></label>
                        <textarea wire:model="changeReason" rows="3" 
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            placeholder="Please explain why you'd like to change..."></textarea>
                        @error('changeReason') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <p class="text-sm text-amber-800 dark:text-amber-300">
                            <strong>Note:</strong> Changes may require additional payment. An administrator will contact you.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showChangeModal', false)" 
                            class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition-colors">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
