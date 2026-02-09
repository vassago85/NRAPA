<?php

use App\Models\Membership;
use App\Models\Certificate;
use App\Models\AuditLog;
use App\Mail\MembershipApproved;
use App\Mail\MembershipRejected;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Review Application - Admin')] class extends Component {
    public Membership $membership;
    public string $rejectionReason = '';
    public bool $showRejectModal = false;

    public function mount(Membership $membership): void
    {
        $this->membership = $membership->load(['user', 'type', 'affiliatedClub']);

        // If the user has been deleted, show error and allow dismissal
        if (!$this->membership->user) {
            session()->flash('error', 'The member associated with this application has been deleted.');
        }
    }

    public function dismiss(): void
    {
        // Delete an orphaned membership application (user deleted)
        $this->membership->update(['status' => 'revoked', 'suspension_reason' => 'Member account deleted']);

        AuditLog::log(
            'membership_dismissed',
            $this->membership,
            ['status' => $this->membership->getOriginal('status')],
            ['status' => 'revoked', 'reason' => 'Member account deleted'],
            Auth::user()
        );

        session()->flash('success', 'Orphaned application dismissed.');
        $this->redirect(route('admin.approvals.index'), navigate: true);
    }

    public function approve(): void
    {
        if ($this->membership->status !== 'applied') {
            session()->flash('error', 'This application has already been processed.');
            return;
        }

        if (!$this->membership->type) {
            session()->flash('error', 'Cannot approve: membership type is missing.');
            return;
        }

        $admin = Auth::user();

        // Calculate expiry date based on membership type
        $expiresAt = $this->membership->type->calculateExpiryDate(now());

        // Update membership
        $this->membership->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'activated_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        // Generate membership number if not set
        if (!$this->membership->membership_number) {
            $this->membership->update([
                'membership_number' => 'NRAPA-' . date('Y') . '-' . str_pad($this->membership->id, 5, '0', STR_PAD_LEFT),
            ]);
        }

        // Create certificates based on membership type entitlements
        $this->issueCertificates();

        // Issue welcome letter and membership card
        $this->issueWelcomeLetterAndCard($admin);

        // Send welcome email
        $this->sendApprovalEmail();

        // Log the action
        AuditLog::log(
            'membership_approved',
            $this->membership,
            ['status' => 'applied'],
            ['status' => 'active'],
            $admin
        );

        session()->flash('success', 'Membership approved, welcome letter issued, and welcome email sent!');

        $this->redirect(route('admin.approvals.index'), navigate: true);
    }

    public function reject(): void
    {
        if ($this->membership->status !== 'applied') {
            session()->flash('error', 'This application has already been processed.');
            return;
        }

        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:10'],
        ], [
            'rejectionReason.required' => 'Please provide a reason for rejection.',
            'rejectionReason.min' => 'The rejection reason must be at least 10 characters.',
        ]);

        $admin = Auth::user();

        $this->membership->update([
            'status' => 'revoked',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'suspension_reason' => $this->rejectionReason,
        ]);

        // Send rejection email
        $this->sendRejectionEmail();

        // Log the action
        AuditLog::log(
            'membership_rejected',
            $this->membership,
            ['status' => 'applied'],
            ['status' => 'revoked', 'reason' => $this->rejectionReason],
            $admin
        );

        session()->flash('success', 'Membership application rejected and member notified.');

        $this->redirect(route('admin.approvals.index'), navigate: true);
    }

    protected function issueCertificates(): void
    {
        $user = $this->membership->user;
        $admin = Auth::user();
        $service = app(\App\Services\CertificateIssueService::class);

        // Issue membership certificate (skip terms/standing checks — admin is approving)
        // Note: ID and Proof of Address documents are always required; if missing,
        // the certificate will be issued later once the member uploads them.
        try {
            $service->issueMembershipCertificate($user, $admin, skipChecks: true);
        } catch (\Exception $e) {
            Log::info('Membership certificate not issued at approval time', [
                'user_id' => $user->id,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    protected function issueWelcomeLetterAndCard($admin): void
    {
        $user = $this->membership->user;

        try {
            $service = app(\App\Services\CertificateIssueService::class);

            // Issue welcome letter (skip terms check — admin is approving)
            try {
                $service->issueWelcomeLetter($user, $admin);
            } catch (\Exception $e) {
                // If terms check fails, create a basic welcome letter certificate directly
                $certType = \App\Models\CertificateType::firstOrCreate(
                    ['slug' => 'welcome-letter'],
                    [
                        'name' => 'Welcome Letter',
                        'description' => 'Welcome letter for new members',
                        'template' => 'documents.welcome-letter',
                        'is_active' => true,
                        'sort_order' => 14,
                    ]
                );
                Certificate::create([
                    'user_id' => $user->id,
                    'membership_id' => $this->membership->id,
                    'certificate_type_id' => $certType->id,
                    'certificate_number' => 'WEL-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'qr_code' => bin2hex(random_bytes(16)),
                    'issued_at' => now(),
                    'valid_from' => now(),
                    'issued_by' => $admin->id,
                ]);
                Log::info('Welcome letter created via fallback', ['user_id' => $user->id, 'reason' => $e->getMessage()]);
            }

            // Issue membership card
            try {
                $service->issueMembershipCard($user, $admin);
            } catch (\Exception $e) {
                // Fallback: create card certificate directly
                $certType = \App\Models\CertificateType::firstOrCreate(
                    ['slug' => 'membership-card'],
                    [
                        'name' => 'Membership Card',
                        'description' => 'NRAPA membership identification card',
                        'template' => 'documents.membership-card',
                        'is_active' => true,
                        'sort_order' => 13,
                    ]
                );
                Certificate::create([
                    'user_id' => $user->id,
                    'membership_id' => $this->membership->id,
                    'certificate_type_id' => $certType->id,
                    'certificate_number' => 'CARD-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'qr_code' => bin2hex(random_bytes(16)),
                    'issued_at' => now(),
                    'valid_from' => now(),
                    'valid_until' => $this->membership->expires_at,
                    'issued_by' => $admin->id,
                ]);
                Log::info('Membership card created via fallback', ['user_id' => $user->id, 'reason' => $e->getMessage()]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to issue welcome letter/card on approval', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendApprovalEmail(): void
    {
        try {
            $user = $this->membership->user;
            $cardUrl = route('card');

            Mail::to($user->email)->send(new MembershipApproved(
                membership: $this->membership,
                cardUrl: $cardUrl,
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send membership approval email', [
                'user_id' => $this->membership->user_id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the approval if email fails
        }
    }

    protected function sendRejectionEmail(): void
    {
        try {
            $user = $this->membership->user;

            Mail::to($user->email)->queue(new MembershipRejected(
                membership: $this->membership->load('type', 'user'),
                reason: $this->rejectionReason,
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send membership rejection email', [
                'user_id' => $this->membership->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.approvals.index') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Review Application</h1>
                <p class="text-zinc-500 dark:text-zinc-400">{{ $this->membership->membership_number ?? 'Pending' }}</p>
            </div>
        </div>
        <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
            Pending Review
        </span>
    </div>

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    {{-- Deleted user: show dismiss option --}}
    @if(!$this->membership->user)
    <div class="rounded-xl border-2 border-red-300 bg-red-50 p-8 dark:border-red-700 dark:bg-red-900/20">
        <div class="flex flex-col items-center gap-4 text-center">
            <div class="flex size-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <svg class="size-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-red-800 dark:text-red-200">Member Account Deleted</h2>
            <p class="text-sm text-red-700 dark:text-red-300 max-w-md">
                The member associated with this application (ID: {{ $this->membership->user_id }}) has been deleted. 
                This application can no longer be processed. You can dismiss it to remove it from the pending list.
            </p>
            <div class="flex gap-3 mt-2">
                <button
                    wire:click="dismiss"
                    wire:confirm="Dismiss this orphaned application? It will be marked as revoked."
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition-colors"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Dismiss Application
                </button>
                <a href="{{ route('admin.approvals.index') }}" wire:navigate
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-6 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                    Back to Approvals
                </a>
            </div>
        </div>
    </div>
    @else

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Applicant Information --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Applicant Information</h2>
            </div>
            <div class="p-6">
                <div class="mb-6 flex items-center gap-4">
                    <div class="flex size-16 items-center justify-center rounded-full bg-emerald-100 text-xl font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                        {{ $this->membership->user->initials() }}
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $this->membership->user->name }}</h3>
                        <p class="text-zinc-500 dark:text-zinc-400">{{ $this->membership->user->email }}</p>
                    </div>
                </div>

                <dl class="space-y-4">
                    @if($this->membership->user->phone)
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Phone</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->membership->user->phone }}</dd>
                    </div>
                    @endif
                    @if($this->membership->user->date_of_birth)
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Date of Birth</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->membership->user->date_of_birth->format('d M Y') }}</dd>
                    </div>
                    @endif
                    @if($this->membership->user->physical_address)
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Physical Address</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $this->membership->user->physical_address }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Account Created</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">{{ $this->membership->user->created_at->format('d M Y \a\t H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Email Verified</dt>
                        <dd class="mt-1">
                            @if($this->membership->user->email_verified_at)
                            <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Verified
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                                Not Verified
                            </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Membership Details --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Membership Details</h2>
            </div>
            <div class="p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Membership Type</dt>
                        <dd class="mt-1">
                            <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->membership->type->name }}</span>
                            @if($this->membership->type->isLifetime())
                            <span class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Lifetime</span>
                            @endif
                        </dd>
                    </div>
                    @if($this->membership->isAffiliatedClubMembership() && $this->membership->affiliatedClub)
                    <div class="rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 p-3">
                        <dt class="text-sm font-medium text-purple-800 dark:text-purple-200">Affiliated Club</dt>
                        <dd class="mt-1">
                            <span class="font-semibold text-purple-900 dark:text-purple-100">{{ $this->membership->affiliatedClub->name }}</span>
                            <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $this->membership->affiliatedClub->dedicated_type === 'both' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : ($this->membership->affiliatedClub->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                {{ $this->membership->affiliatedClub->dedicated_type_label }}
                            </span>
                        </dd>
                        <div class="mt-2 text-xs text-purple-700 dark:text-purple-300 space-y-1">
                            @if($this->membership->affiliatedClub->requires_competency)
                            <p>Requires: SAPS Firearm Competency Certificate</p>
                            @endif
                            @if($this->membership->affiliatedClub->required_activities_per_year > 0)
                            <p>Requires: {{ $this->membership->affiliatedClub->required_activities_per_year }} activities/year</p>
                            @endif
                        </div>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Description</dt>
                        <dd class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $this->membership->type->description }}</dd>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Price</dt>
                            <dd class="mt-1 text-lg font-semibold text-emerald-600 dark:text-emerald-400">R{{ number_format($this->membership->amount_due, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Duration</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">
                                @if($this->membership->type->isLifetime())
                                    Lifetime
                                @else
                                    {{ $this->membership->type->duration_months }} months
                                @endif
                            </dd>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Renewal Required</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->membership->type->requires_renewal ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Dedicated Status</dt>
                            <dd class="mt-1">
                                @if($this->membership->type->allows_dedicated_status)
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Eligible</span>
                                @else
                                    <span class="text-zinc-500">Not Available</span>
                                @endif
                            </dd>
                        </div>
                    </div>
                    @if($this->membership->type->requires_knowledge_test)
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Knowledge Test</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">Required</span>
                        </dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Applied On</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->membership->applied_at->format('d M Y \a\t H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    {{-- Payment Reference Card --}}
    @if($this->membership->payment_reference)
    <div class="rounded-xl border-2 border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex size-10 items-center justify-center rounded-lg bg-amber-200 dark:bg-amber-800">
                    <svg class="size-5 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Payment Reference</h2>
                    <p class="text-sm text-amber-600 dark:text-amber-400">Match this reference on bank statement to verify payment</p>
                </div>
            </div>
            
            <div class="flex items-center justify-between gap-4 p-4 bg-white dark:bg-zinc-800 rounded-lg border-2 border-dashed border-amber-400 dark:border-amber-600">
                <span class="text-2xl font-mono font-bold text-zinc-900 dark:text-white tracking-wider">{{ $this->membership->payment_reference }}</span>
                <button 
                    type="button"
                    x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText('{{ $this->membership->payment_reference }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center gap-2 rounded-lg bg-amber-600 hover:bg-amber-700 px-4 py-2 text-sm font-medium text-white transition-colors"
                >
                    <svg x-show="!copied" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg x-show="copied" x-cloak class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
            </div>

            <div class="mt-4 flex items-center gap-4 text-sm">
                <div>
                    <span class="text-amber-700 dark:text-amber-300">Amount Due:</span>
                    <span class="font-bold text-amber-800 dark:text-amber-200">R{{ number_format($this->membership->amount_due, 2) }}</span>
                </div>
                @if($this->membership->payment_email_sent_at)
                <div class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>Payment email sent {{ $this->membership->payment_email_sent_at->diffForHumans() }}</span>
                </div>
                @else
                <div class="flex items-center gap-1 text-zinc-500 dark:text-zinc-400">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Payment email not sent</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Action Buttons --}}
    @if($this->membership->status === 'applied')
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Decision</h3>

        @if(!$showRejectModal)
        <div class="flex flex-col gap-4 sm:flex-row">
            <button
                wire:click="approve"
                wire:confirm="Are you sure you want to approve this membership application?"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-nrapa-blue px-6 py-3 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors"
            >
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Approve Application
            </button>
            <button
                wire:click="$set('showRejectModal', true)"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-300 bg-white px-6 py-3 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:bg-zinc-800 dark:text-red-400 dark:hover:bg-red-950/20"
            >
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                Reject Application
            </button>
        </div>
        @else
        <div class="space-y-4">
            <div>
                <label for="rejectionReason" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Reason for Rejection</label>
                <textarea
                    wire:model="rejectionReason"
                    id="rejectionReason"
                    rows="3"
                    placeholder="Please provide a reason for rejecting this application..."
                    class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400"
                ></textarea>
                @error('rejectionReason')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-3">
                <button
                    wire:click="reject"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-6 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600"
                >
                    Confirm Rejection
                </button>
                <button
                    wire:click="$set('showRejectModal', false)"
                    class="inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-6 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600"
                >
                    Cancel
                </button>
            </div>
        </div>
        @endif
    </div>
    @endif

    @endif {{-- end @else for user exists --}}
</div>
