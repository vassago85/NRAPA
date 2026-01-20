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
    public function availableMembershipTypes()
    {
        // Get all active types available for signup, excluding current type
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
        // Check if user already has a pending membership change request
        return $this->user->memberships()
            ->where('status', 'pending_change')
            ->exists();
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

        // Create a new membership record with pending_change status
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
            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'applied' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'pending_change' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'pending_payment' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
            'suspended', 'revoked' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'expired' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">My Membership</h1>
            <p class="text-zinc-600 dark:text-zinc-400">
                View and manage your NRAPA membership status.
            </p>
        </div>
        @if(!$this->activeMembership)
        <a href="{{ route('membership.apply') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Apply for Membership
        </a>
        @endif
    </div>

    {{-- Current Membership Details --}}
    @if($this->activeMembership)
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center justify-between border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Current Membership</h2>
            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
        </div>

        <div class="p-6">
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Membership Type</p>
                    <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $this->activeMembership->type->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Membership Number</p>
                    <p class="mt-1 font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->activeMembership->membership_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Member Since</p>
                    <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $this->activeMembership->activated_at?->format('d F Y') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        @if($this->activeMembership->type->isLifetime())
                            Validity
                        @else
                            Expires On
                        @endif
                    </p>
                    <div class="mt-1">
                        @if($this->activeMembership->type->isLifetime())
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-sm font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Lifetime - Never Expires</span>
                        @else
                            <span class="font-semibold text-zinc-900 dark:text-white">{{ $this->activeMembership->expires_at?->format('d F Y') ?? 'N/A' }}</span>
                            @if($this->activeMembership->expires_at && $this->activeMembership->expires_at->diffInDays(now()) < 30)
                                <span class="ml-2 inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">Expiring Soon</span>
                            @endif
                        @endif
                    </div>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Renewal Required</p>
                    <div class="mt-1">
                        @if($this->activeMembership->type->requires_renewal)
                            <span class="font-semibold text-zinc-900 dark:text-white">Yes</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-sm font-medium text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">No - Lifetime</span>
                        @endif
                    </div>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Dedicated Status Eligible</p>
                    <div class="mt-1">
                        @if($this->activeMembership->type->allows_dedicated_status)
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-sm font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Eligible</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-sm font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">Not Available</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-zinc-200 p-6 dark:border-zinc-700">
            <div class="flex flex-wrap gap-3">
                @if($this->activeMembership->requiresRenewal() && $this->activeMembership->isRenewable())
                <a href="{{ route('membership.apply') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Renew Membership
                </a>
                @endif
                
                @if($this->availableMembershipTypes->count() > 0 && !$this->hasPendingChangeRequest)
                <button wire:click="openChangeModal" class="inline-flex items-center gap-2 rounded-lg border border-blue-600 px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-900/20">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                    </svg>
                    Change Membership Type
                </button>
                @elseif($this->hasPendingChangeRequest)
                <span class="inline-flex items-center gap-2 rounded-lg border border-purple-300 bg-purple-50 px-4 py-2 text-sm font-medium text-purple-700 dark:border-purple-700 dark:bg-purple-900/20 dark:text-purple-300">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Change Request Pending
                </span>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Membership History --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Membership History</h2>
        </div>

        @if($this->memberships->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Member #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Applied</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->memberships as $membership)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $membership->type->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-zinc-900 dark:text-white">{{ $membership->membership_number }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusClasses($membership->status) }}">
                                {{ ucfirst($membership->status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $membership->applied_at->format('d M Y') }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($membership->expires_at)
                                {{ $membership->expires_at->format('d M Y') }}
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Lifetime</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            <a href="{{ route('membership.show', $membership) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center">
            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No Membership History</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                You haven't applied for a membership yet.
            </p>
            <a href="{{ route('membership.apply') }}" wire:navigate class="mt-4 inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                Apply Now
            </a>
        </div>
        @endif
    </div>

    {{-- Change Membership Modal --}}
    @if($showChangeModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showChangeModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">Change Membership Type</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6">
                    Request to change from <strong>{{ $this->activeMembership->type->name }}</strong> to a different membership type. 
                    Your request will be reviewed by an administrator.
                </p>

                <form wire:submit="submitChangeRequest" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Select New Membership Type *</label>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($this->availableMembershipTypes as $type)
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                                {{ $selectedNewTypeId === $type->id 
                                    ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20' 
                                    : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                                <input type="radio" wire:model="selectedNewTypeId" value="{{ $type->id }}" class="mt-1 text-blue-600 focus:ring-blue-500">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $type->name }}</span>
                                        @if($type->is_featured)
                                            <span class="text-xs bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded dark:bg-amber-900 dark:text-amber-200">Recommended</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                                        R{{ number_format($type->total_price, 0) }}
                                        @if($type->duration_type === 'lifetime')
                                            (Lifetime)
                                        @else
                                            /year
                                        @endif
                                    </p>
                                    @if($type->description)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">{{ $type->description }}</p>
                                    @endif
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('selectedNewTypeId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reason for Change *</label>
                        <textarea wire:model="changeReason" rows="3" 
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                            placeholder="Please explain why you'd like to change your membership type..."></textarea>
                        @error('changeReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            <strong>Note:</strong> Membership changes may require additional payment or pro-rata adjustments. 
                            An administrator will contact you regarding any payment differences.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showChangeModal', false)" 
                            class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Submit Change Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
