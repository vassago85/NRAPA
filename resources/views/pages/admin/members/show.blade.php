<?php

use App\Models\User;
use App\Models\Membership;
use App\Models\UserDeletionRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Member Details - Admin')] class extends Component {
    public User $user;
    public bool $showDeleteModal = false;
    public bool $showRequestDeleteModal = false;
    public string $deleteReason = '';

    public function mount(User $user): void
    {
        $this->user = $user->load([
            'memberships.type',
            'memberships.approver',
            'certificates.certificateType',
            'dedicatedStatusApplications',
            'knowledgeTestAttempts.knowledgeTest',
            'deletionRequests',
        ]);
    }

    #[Computed]
    public function activeMembership()
    {
        return $this->user->memberships->firstWhere('status', 'active');
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->canDeleteUser($this->user);
    }

    #[Computed]
    public function canRequestDelete(): bool
    {
        return auth()->user()->canRequestUserDeletion($this->user) && !$this->user->hasPendingDeletionRequest();
    }

    #[Computed]
    public function pendingDeletionRequest()
    {
        return $this->user->deletionRequests()->pending()->first();
    }

    public function toggleAdmin(): void
    {
        $this->user->update(['is_admin' => !$this->user->is_admin]);
        $this->user->refresh();
    }

    public function deleteUser(): void
    {
        if (!$this->canDelete) {
            session()->flash('error', 'You do not have permission to delete this user.');
            return;
        }

        $this->validate([
            'deleteReason' => 'required|string|min:10|max:500',
        ]);

        $userName = $this->user->name;

        // Create a deletion record for audit purposes
        UserDeletionRequest::create([
            'user_id' => $this->user->id,
            'requested_by' => auth()->id(),
            'actioned_by' => auth()->id(),
            'status' => UserDeletionRequest::STATUS_APPROVED,
            'reason' => $this->deleteReason,
            'actioned_at' => now(),
        ]);

        // Soft delete the user
        $this->user->delete();

        session()->flash('success', "User {$userName} has been deleted.");
        $this->redirect(route('admin.members.index'), navigate: true);
    }

    public function requestDeletion(): void
    {
        if (!$this->canRequestDelete) {
            session()->flash('error', 'You cannot request deletion for this user.');
            return;
        }

        $this->validate([
            'deleteReason' => 'required|string|min:10|max:500',
        ]);

        UserDeletionRequest::create([
            'user_id' => $this->user->id,
            'requested_by' => auth()->id(),
            'reason' => $this->deleteReason,
        ]);

        $this->showRequestDeleteModal = false;
        $this->deleteReason = '';
        $this->user->refresh();
        
        session()->flash('success', 'Deletion request submitted. An owner will review your request.');
    }

    public function getStatusClasses(string $status): string
    {
        return match($status) {
            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'applied' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'suspended', 'revoked' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'expired' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg border border-green-300 bg-green-100 p-4 text-green-800 dark:border-green-700 dark:bg-green-900/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-300 bg-red-100 p-4 text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.members.index') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back
            </a>
            <div class="flex items-center gap-4">
                <div class="flex size-14 items-center justify-center rounded-full bg-emerald-100 text-lg font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                    {{ $this->user->initials() }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->user->name }}</h1>
                    <p class="text-zinc-500 dark:text-zinc-400">{{ $this->user->email }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($this->user->is_admin)
            <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-sm font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                Admin
            </span>
            @endif
            
            @if($this->pendingDeletionRequest)
            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                Deletion Pending
            </span>
            @endif

            @if($this->canDelete)
            <button wire:click="$set('showDeleteModal', true)" class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Delete User
            </button>
            @elseif($this->canRequestDelete)
            <button wire:click="$set('showRequestDeleteModal', true)" class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Request Deletion
            </button>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Member Info --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Member Information</h2>
            </div>
            <div class="p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Full Name</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Email</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->user->email }}</dd>
                    </div>
                    @if($this->user->phone)
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Phone</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->user->phone }}</dd>
                    </div>
                    @endif
                    @if($this->user->date_of_birth)
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Date of Birth</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->user->date_of_birth->format('d M Y') }}</dd>
                    </div>
                    @endif
                    @if($this->user->physical_address)
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Physical Address</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $this->user->physical_address }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Registered</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">{{ $this->user->created_at->format('d M Y \a\t H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Email Verified</dt>
                        <dd class="mt-1">
                            @if($this->user->email_verified_at)
                            <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                {{ $this->user->email_verified_at->format('d M Y') }}
                            </span>
                            @else
                            <span class="text-red-600 dark:text-red-400">Not verified</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Current Membership --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 lg:col-span-2">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Current Membership</h2>
            </div>
            <div class="p-6">
                @if($this->activeMembership)
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Membership Type</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->activeMembership->type->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Member Number</dt>
                        <dd class="mt-1 font-mono font-medium text-zinc-900 dark:text-white">{{ $this->activeMembership->membership_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                Active
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Activated On</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">{{ $this->activeMembership->activated_at?->format('d M Y') ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Expires On</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">
                            @if($this->activeMembership->expires_at)
                                {{ $this->activeMembership->expires_at->format('d M Y') }}
                                @if($this->activeMembership->expires_at->isPast())
                                    <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Expired</span>
                                @elseif($this->activeMembership->expires_at->diffInDays(now()) < 30)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">Expiring Soon</span>
                                @endif
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-sm font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Lifetime</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Approved By</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">{{ $this->activeMembership->approver?->name ?? 'N/A' }}</dd>
                    </div>
                </div>
                @else
                <div class="text-center py-8">
                    <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                    </svg>
                    <p class="mt-4 text-zinc-500 dark:text-zinc-400">No active membership</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Membership History --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Membership History</h2>
        </div>

        @if($this->user->memberships->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Member #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Applied</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->user->memberships as $membership)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $membership->type->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-zinc-900 dark:text-white">{{ $membership->membership_number }}</td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusClasses($membership->status) }}">
                                {{ ucfirst($membership->status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $membership->applied_at->format('d M Y') }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $membership->approved_at?->format('d M Y') ?? '—' }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($membership->expires_at)
                                {{ $membership->expires_at->format('d M Y') }}
                            @else
                                <span class="text-amber-600 dark:text-amber-400">Lifetime</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            @if($membership->status === 'applied')
                            <a href="{{ route('admin.approvals.show', $membership) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                Review
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center">
            <p class="text-zinc-500 dark:text-zinc-400">No membership history</p>
        </div>
        @endif
    </div>

    {{-- Certificates --}}
    @if($this->user->certificates->count() > 0)
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Certificates</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Certificate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Certificate #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Issued</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Valid Until</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->user->certificates as $certificate)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $certificate->certificateType->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-zinc-900 dark:text-white">{{ $certificate->certificate_number }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $certificate->issued_at->format('d M Y') }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($certificate->valid_until)
                                {{ $certificate->valid_until->format('d M Y') }}
                            @else
                                <span class="text-amber-600 dark:text-amber-400">Indefinite</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($certificate->isValid())
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Valid</span>
                            @elseif($certificate->isRevoked())
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Revoked</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">Expired</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Knowledge Test Attempts --}}
    @if($this->user->knowledgeTestAttempts->count() > 0)
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Knowledge Test Attempts</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Test</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Started</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->user->knowledgeTestAttempts as $attempt)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $attempt->knowledgeTest->title ?? 'N/A' }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $attempt->started_at->format('d M Y H:i') }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">
                            @if($attempt->score !== null)
                                {{ $attempt->score }}%
                            @else
                                —
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($attempt->passed === true)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Passed</span>
                            @elseif($attempt->passed === false)
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Failed</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">In Progress</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Delete User Modal (for Owner/Developer) --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showDeleteModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/50">
                        <svg class="size-5 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Delete User</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">This action cannot be undone.</p>
                    </div>
                </div>
                
                <p class="mb-4 text-zinc-600 dark:text-zinc-300">
                    Are you sure you want to delete <strong>{{ $this->user->name }}</strong>? All their data will be archived.
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reason for deletion *</label>
                    <textarea wire:model="deleteReason" rows="3" placeholder="Please provide a reason for deleting this user..."
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                    @error('deleteReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showDeleteModal', false)" 
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button wire:click="deleteUser"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Delete User
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Request Deletion Modal (for Admin) --}}
    @if($showRequestDeleteModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showRequestDeleteModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/50">
                        <svg class="size-5 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Request User Deletion</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">An owner will review your request.</p>
                    </div>
                </div>
                
                <p class="mb-4 text-zinc-600 dark:text-zinc-300">
                    You are requesting to delete <strong>{{ $this->user->name }}</strong>. This request will be reviewed by an owner before any action is taken.
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reason for deletion request *</label>
                    <textarea wire:model="deleteReason" rows="3" placeholder="Please explain why this user should be deleted..."
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                    @error('deleteReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showRequestDeleteModal', false)" 
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button wire:click="requestDeletion"
                        class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                        Submit Request
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
