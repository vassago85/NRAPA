<?php

use App\Jobs\SyncMembershipToSage;
use App\Models\Membership;
use App\Models\Certificate;
use App\Models\AuditLog;
use App\Mail\MembershipApproved;
use App\Mail\MembershipRejected;
use App\Mail\PaymentInstructions;
use App\Mail\PaymentReceived;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Review Application - Admin')] class extends Component {
    public Membership $membership;
    public string $rejectionReason = '';
    public bool $showRejectModal = false;
    public ?float $changeAmount = null;

    #[Computed]
    public function proofOfPaymentUrl(): ?string
    {
        if (! $this->membership->proof_of_payment_path) {
            return null;
        }

        return route('admin.approvals.proof-of-payment', $this->membership);
    }

    #[Computed]
    public function isChangeRequest(): bool
    {
        return in_array($this->membership->status, ['pending_change', 'pending_payment']);
    }

    #[Computed]
    public function previousMembership()
    {
        return $this->membership->previousMembership?->load('type');
    }

    public function mount(Membership $membership): void
    {
        $this->membership = $membership->load(['user', 'type', 'affiliatedClub', 'previousMembership.type']);

        // Pre-fill change amount with upgrade price
        if ($this->membership->status === 'pending_change') {
            $this->changeAmount = (float) ($this->membership->type->upgrade_price ?? $this->membership->type->initial_price ?? 0);
        }

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

        // Generate membership number from user's permanent member number
        if (!$this->membership->membership_number) {
            $user = $this->membership->user;
            $this->membership->update([
                'membership_number' => $user->formatted_member_number,
            ]);
        }

        // Create certificates based on membership type entitlements
        $this->issueCertificates();

        // Issue welcome letter and membership card
        $this->issueWelcomeLetterAndCard($admin);

        // Queue welcome email and payment instructions
        $this->sendApprovalEmail();
        $this->sendPaymentEmail();

        // Log the action
        AuditLog::log(
            'membership_approved',
            $this->membership,
            ['status' => 'applied'],
            ['status' => 'active'],
            $admin
        );

        SyncMembershipToSage::dispatch($this->membership)->afterCommit();

        session()->flash('success', 'Membership approved, welcome letter issued, and emails queued!');

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

    public function setChangeAmount(): void
    {
        if ($this->membership->status !== 'pending_change') {
            session()->flash('error', 'This request is not awaiting an amount.');
            return;
        }

        $this->validate([
            'changeAmount' => ['required', 'numeric', 'min:0'],
        ], [
            'changeAmount.required' => 'Please enter the amount the member must pay.',
            'changeAmount.min' => 'Amount cannot be negative.',
        ]);

        $this->membership->update([
            'change_amount' => $this->changeAmount,
            'status' => 'pending_payment',
        ]);

        // Generate payment reference if not set
        if (!$this->membership->payment_reference) {
            $this->membership->update([
                'payment_reference' => Membership::generatePaymentReference($this->membership),
            ]);
        }

        $admin = Auth::user();

        AuditLog::log(
            'change_request_amount_set',
            $this->membership,
            ['status' => 'pending_change'],
            ['status' => 'pending_payment', 'change_amount' => $this->changeAmount],
            $admin
        );

        // Send payment instructions email
        try {
            if ($this->membership->user?->email) {
                $bankAccount = \App\Models\SystemSetting::getBankAccount();
                Mail::to($this->membership->user->email)->send(
                    new \App\Mail\PaymentInstructions(
                        membership: $this->membership,
                        bankAccount: $bankAccount,
                        reference: $this->membership->payment_reference,
                    )
                );
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send change request payment email', ['error' => $e->getMessage()]);
        }

        session()->flash('success', 'Amount set to R' . number_format($this->changeAmount, 2) . '. Member has been notified to make payment.');
    }

    public function approveChange(): void
    {
        if ($this->membership->status !== 'pending_payment') {
            session()->flash('error', 'This change request is not ready for approval.');
            return;
        }

        $admin = Auth::user();
        $previousMembership = $this->membership->previousMembership;

        // Expire the old membership
        if ($previousMembership && $previousMembership->status === 'active') {
            $previousMembership->update([
                'status' => 'expired',
                'expires_at' => now(),
            ]);
        }

        // Calculate expiry date based on new membership type
        $expiresAt = $this->membership->type->calculateExpiryDate(now());

        // Activate the new membership
        $this->membership->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'activated_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        // Always use the user's permanent member number
        if (!$this->membership->membership_number) {
            $user = $this->membership->user;
            $this->membership->update([
                'membership_number' => $user->formatted_member_number,
            ]);
        }

        // Issue certificates for the new type
        $this->issueCertificates();
        $this->issueWelcomeLetterAndCard($admin);

        // Clean up proof of payment
        if ($this->membership->proof_of_payment_path) {
            try {
                Storage::disk('r2')->delete($this->membership->proof_of_payment_path);
            } catch (\Exception $e) {}
            $this->membership->update(['proof_of_payment_path' => null]);
        }

        // Send payment confirmation and approval email
        $this->sendPaymentReceivedEmail();
        $this->sendApprovalEmail();

        AuditLog::log(
            'change_request_approved',
            $this->membership,
            ['status' => 'pending_payment', 'previous_type' => $previousMembership?->type?->name],
            ['status' => 'active', 'new_type' => $this->membership->type?->name],
            $admin
        );

        SyncMembershipToSage::dispatch($this->membership)->afterCommit();

        session()->flash('success', 'Membership type change approved and member notified!');
        $this->redirect(route('admin.approvals.index'), navigate: true);
    }

    #[Computed]
    public function isAwaitingPayment(): bool
    {
        $m = $this->membership;

        if ($m->status === 'applied' && in_array($m->source, ['web', 'admin']) && !$m->proof_of_payment_path && !$m->payment_confirmed_at) {
            return true;
        }

        if ($m->status === 'pending_payment' && !$m->proof_of_payment_path && !$m->payment_confirmed_at) {
            return true;
        }

        return false;
    }

    public function confirmPayment(): void
    {
        $admin = Auth::user();

        $this->membership->update([
            'payment_confirmed_at' => now(),
            'payment_confirmed_by' => $admin->id,
        ]);

        AuditLog::log(
            'payment_confirmed_by_admin',
            $this->membership,
            ['payment_confirmed_at' => null],
            ['payment_confirmed_at' => now()->toDateTimeString(), 'confirmed_by' => $admin->name],
            $admin
        );

        session()->flash('success', 'Payment confirmed. This application is now ready for approval.');
    }

    public function rejectChange(): void
    {
        if (!in_array($this->membership->status, ['pending_change', 'pending_payment'])) {
            session()->flash('error', 'This request has already been processed.');
            return;
        }

        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:10'],
        ], [
            'rejectionReason.required' => 'Please provide a reason for rejection.',
        ]);

        $admin = Auth::user();
        $oldStatus = $this->membership->status;

        $this->membership->update([
            'status' => 'revoked',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'suspension_reason' => $this->rejectionReason,
        ]);

        // Clean up proof of payment if any
        if ($this->membership->proof_of_payment_path) {
            try {
                Storage::disk('r2')->delete($this->membership->proof_of_payment_path);
            } catch (\Exception $e) {}
            $this->membership->update(['proof_of_payment_path' => null]);
        }

        $this->sendRejectionEmail();

        AuditLog::log(
            'change_request_rejected',
            $this->membership,
            ['status' => $oldStatus],
            ['status' => 'revoked', 'reason' => $this->rejectionReason],
            $admin
        );

        session()->flash('success', 'Type change request rejected and member notified.');
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
        if ($this->membership->welcome_email_sent_at) {
            return;
        }

        try {
            $user = $this->membership->user;
            $cardUrl = route('card');

            Mail::to($user->email)->send(new MembershipApproved(
                membership: $this->membership,
                cardUrl: $cardUrl,
            ));

            $this->membership->update(['welcome_email_sent_at' => now()]);
        } catch (\Exception $e) {
            Log::warning('Failed to send membership approval email', [
                'user_id' => $this->membership->user_id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the approval if email fails
        }
    }

    protected function sendPaymentEmail(): void
    {
        if ($this->membership->source === 'import') {
            return;
        }

        try {
            $bankAccount = SystemSetting::getBankAccount();
            $user = $this->membership->user;

            Mail::to($user->email)->send(new PaymentInstructions(
                $this->membership->load('type', 'user', 'affiliatedClub'),
                $bankAccount,
                $this->membership->payment_reference,
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send payment instructions on approval', [
                'membership_id' => $this->membership->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendPaymentReceivedEmail(): void
    {
        try {
            $user = $this->membership->user;

            Mail::to($user->email)->send(new PaymentReceived(
                membership: $this->membership->load('type', 'user'),
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send payment received email', [
                'membership_id' => $this->membership->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendRejectionEmail(): void
    {
        try {
            $user = $this->membership->user;

            Mail::to($user->email)->send(new MembershipRejected(
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

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Application Review</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review membership application details</p>
    </x-slot>

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
        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
            {{ match($this->membership->status) {
                'pending_change' => 'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200',
                'pending_payment' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                default => 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
            } }}">
            {{ match($this->membership->status) {
                'pending_change' => 'Type Change Request',
                'pending_payment' => 'Awaiting Payment',
                default => 'Pending Review',
            } }}
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
    <div class="rounded-2xl shadow-sm border-2 border-red-300 bg-red-50 p-8 dark:border-red-700 dark:bg-red-900/20">
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
        <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-800">
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
        <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-800">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Membership Details</h2>
            </div>
            <div class="p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Membership Type</dt>
                        <dd class="mt-1">
                            <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->membership->type->name }}</span>
                            @if($this->membership->type->isLifetime())
                            <span class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Lifetime</span>
                            @endif
                        </dd>
                    </div>
                    @if($this->membership->isAffiliatedClubMembership() && $this->membership->affiliatedClub)
                    <div class="rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 p-3">
                        <dt class="text-sm font-medium text-purple-800 dark:text-purple-200">Affiliated Club</dt>
                        <dd class="mt-1">
                            <span class="font-semibold text-purple-900 dark:text-purple-100">{{ $this->membership->affiliatedClub->name }}</span>
                            <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $this->membership->affiliatedClub->dedicated_type === 'both' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : ($this->membership->affiliatedClub->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200') }}">
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
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">Eligible</span>
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
    <div class="rounded-2xl shadow-sm border-2 border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20">
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
            
            <div class="flex items-center justify-between gap-4 p-4 bg-white dark:bg-zinc-900 rounded-lg border-2 border-dashed border-amber-400 dark:border-amber-600">
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
                @if($this->membership->sage_invoice_id)
                <div class="flex items-center gap-1 text-indigo-600 dark:text-indigo-400">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span>Sage invoice synced</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Proof of Payment --}}
    @if($this->membership->proof_of_payment_path)
    <div class="rounded-2xl shadow-sm border border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-200 dark:bg-emerald-800">
                    <svg class="size-5 text-emerald-700 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-emerald-800 dark:text-emerald-200">Proof of Payment</h2>
                    <p class="text-sm text-emerald-600 dark:text-emerald-400">Uploaded by member — review before approving</p>
                </div>
            </div>

            @php
                $popPath = $this->membership->proof_of_payment_path;
                $ext = strtolower(pathinfo($popPath, PATHINFO_EXTENSION));
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
            @endphp

            @if($isImage)
                <div class="rounded-lg overflow-hidden border border-emerald-200 dark:border-emerald-700 bg-white dark:bg-zinc-800">
                    <img src="{{ $this->proofOfPaymentUrl }}" alt="Proof of Payment" class="max-w-full max-h-[500px] mx-auto">
                </div>
            @else
                <div class="flex items-center justify-between gap-4 p-4 bg-white dark:bg-zinc-800 rounded-lg border border-emerald-200 dark:border-emerald-700">
                    <div class="flex items-center gap-3">
                        <svg class="size-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ basename($popPath) }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">PDF document</p>
                        </div>
                    </div>
                    <a href="{{ $this->proofOfPaymentUrl }}" target="_blank"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition-colors">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        View PDF
                    </a>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Mark as Paid (for awaiting-payment memberships) --}}
    @if($this->isAwaitingPayment)
    <div class="rounded-2xl shadow-sm border border-amber-200 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-900/20">
        <div class="flex items-start gap-4">
            <div class="flex size-10 items-center justify-center rounded-lg bg-amber-200 dark:bg-amber-800 flex-shrink-0">
                <svg class="size-5 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-amber-800 dark:text-amber-200">Awaiting Payment</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    This member has not yet uploaded proof of payment. If you have confirmed payment through other means (e.g. bank statement), you can mark it as paid.
                </p>
                <div class="mt-3">
                    <button
                        wire:click="confirmPayment"
                        wire:confirm="Confirm payment received for this member? The application will become ready for approval."
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Mark as Paid
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Payment Confirmed indicator --}}
    @if($this->membership->payment_confirmed_at && !$this->membership->proof_of_payment_path)
    <div class="rounded-2xl shadow-sm border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">
                Payment confirmed by admin on {{ $this->membership->payment_confirmed_at->format('d M Y \a\t H:i') }}.
            </p>
        </div>
    </div>
    @endif

    {{-- Action Buttons --}}
    @if($this->membership->status === 'applied')
    <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Decision</h3>

        @if(!$showRejectModal)
        <div class="flex flex-col gap-4 sm:flex-row">
            <button
                wire:click="approve"
                wire:loading.attr="disabled"
                wire:target="approve"
                wire:confirm="Are you sure you want to approve this membership application?"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-nrapa-blue px-6 py-3 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span wire:loading.remove wire:target="approve">Approve Application</span>
                <span wire:loading wire:target="approve">Processing...</span>
            </button>
            <button
                wire:click="$set('showRejectModal', true)"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-300 bg-white px-6 py-3 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:bg-zinc-800 dark:text-red-400 dark:hover:bg-red-950/20 transition-colors"
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
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-6 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 transition-colors"
                >
                    Confirm Rejection
                </button>
                <button
                    wire:click="$set('showRejectModal', false)"
                    class="inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-6 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                >
                    Cancel
                </button>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Change Request Actions --}}
    @if($this->membership->status === 'pending_change')
    <div class="rounded-2xl shadow-sm border border-violet-200 bg-white p-6 dark:border-violet-700 dark:bg-zinc-900">
        <h3 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-white">Type Change Request</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">This member has requested to change their membership type. Set the amount they must pay to proceed.</p>

        @if($this->previousMembership)
        <div class="flex items-center gap-3 mb-4 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50">
            <div class="text-sm">
                <span class="text-zinc-500">From:</span>
                <span class="font-semibold text-zinc-900 dark:text-white">{{ $this->previousMembership->type?->name ?? 'Unknown' }}</span>
            </div>
            <svg class="size-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            <div class="text-sm">
                <span class="text-zinc-500">To:</span>
                <span class="font-semibold text-emerald-700 dark:text-emerald-400">{{ $this->membership->type?->name ?? 'Unknown' }}</span>
            </div>
        </div>
        @endif

        @if($this->membership->notes)
        <div class="mb-4 p-3 rounded-lg border border-zinc-200 dark:border-zinc-800">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Member's Reason</p>
            <p class="text-sm text-zinc-900 dark:text-white whitespace-pre-line">{{ $this->membership->notes }}</p>
        </div>
        @endif

        @if(!$showRejectModal)
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Amount to Pay (R)</label>
                <input type="number" step="0.01" min="0" wire:model="changeAmount"
                    class="w-full max-w-xs rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                @error('changeAmount') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Pre-filled with the upgrade price. Adjust if needed (set to 0 for no charge).</p>
            </div>
            <div class="flex gap-3">
                <button
                    wire:click="setChangeAmount"
                    wire:loading.attr="disabled"
                    wire:confirm="Set payment amount to R{{ number_format($changeAmount ?? 0, 2) }}? The member will be notified to make payment."
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-nrapa-blue px-6 py-2.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Set Amount & Notify Member
                </button>
                <button
                    wire:click="$set('showRejectModal', true)"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-300 bg-white px-6 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:bg-zinc-800 dark:text-red-400 transition-colors"
                >
                    Reject Request
                </button>
            </div>
        </div>
        @else
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Reason for Rejection</label>
                <textarea wire:model="rejectionReason" rows="3" placeholder="Please provide a reason..."
                    class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                @error('rejectionReason') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3">
                <button wire:click="rejectChange" class="rounded-lg bg-red-600 px-6 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors">Confirm Rejection</button>
                <button wire:click="$set('showRejectModal', false)" class="rounded-lg border border-zinc-300 bg-white px-6 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 transition-colors">Cancel</button>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Pending Payment: Approve after POP --}}
    @if($this->membership->status === 'pending_payment')
    <div class="rounded-2xl shadow-sm border border-indigo-200 bg-white p-6 dark:border-indigo-700 dark:bg-zinc-900">
        <h3 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-white">Type Change - Awaiting Payment</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
            Amount set to <strong class="text-zinc-900 dark:text-white">R{{ number_format($this->membership->change_amount ?? 0, 2) }}</strong>.
            Payment ref: <strong class="text-zinc-900 dark:text-white">{{ $this->membership->payment_reference }}</strong>.
        </p>

        @if($this->previousMembership)
        <div class="flex items-center gap-3 mb-4 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50">
            <div class="text-sm">
                <span class="text-zinc-500">From:</span>
                <span class="font-semibold text-zinc-900 dark:text-white">{{ $this->previousMembership->type?->name ?? 'Unknown' }}</span>
            </div>
            <svg class="size-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            <div class="text-sm">
                <span class="text-zinc-500">To:</span>
                <span class="font-semibold text-emerald-700 dark:text-emerald-400">{{ $this->membership->type?->name ?? 'Unknown' }}</span>
            </div>
        </div>
        @endif

        {{-- POP Preview --}}
        @if($this->proofOfPaymentUrl)
        <div class="mb-4 rounded-lg border border-zinc-200 dark:border-zinc-800 overflow-hidden">
                <div class="bg-zinc-50 dark:bg-zinc-900/50 px-4 py-2 border-b border-zinc-200 dark:border-zinc-800">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Proof of Payment</p>
            </div>
            <div class="p-4">
                @php $popExt = pathinfo($this->membership->proof_of_payment_path, PATHINFO_EXTENSION); @endphp
                @if(in_array(strtolower($popExt), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                    <img src="{{ $this->proofOfPaymentUrl }}" alt="Proof of Payment" class="max-w-full rounded-lg max-h-96">
                @else
                    <a href="{{ $this->proofOfPaymentUrl }}" target="_blank" class="inline-flex items-center gap-2 text-sm text-nrapa-blue hover:underline">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        View PDF Document
                    </a>
                @endif
            </div>
        </div>
        @else
        <div class="mb-4 p-4 rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20">
            <p class="text-sm text-amber-700 dark:text-amber-300">No proof of payment uploaded yet. The member has been notified to make payment.</p>
            @if(!$this->membership->payment_confirmed_at)
            <div class="mt-3">
                <button
                    wire:click="confirmPayment"
                    wire:confirm="Confirm payment received? The application will become ready for final approval."
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 transition-colors"
                >
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Mark as Paid
                </button>
            </div>
            @else
            <p class="mt-2 text-xs text-emerald-600 dark:text-emerald-400">Payment confirmed by admin on {{ $this->membership->payment_confirmed_at->format('d M Y \a\t H:i') }}.</p>
            @endif
        </div>
        @endif

        @if(!$showRejectModal)
        <div class="flex gap-3">
            <button
                wire:click="approveChange"
                wire:loading.attr="disabled"
                wire:confirm="Approve this type change? The member's old membership will be expired and the new type activated."
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-nrapa-blue px-6 py-2.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"/></svg>
                Approve Type Change
            </button>
            <button
                wire:click="$set('showRejectModal', true)"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-300 bg-white px-6 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:bg-zinc-800 dark:text-red-400 transition-colors"
            >
                Reject Request
            </button>
        </div>
        @else
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Reason for Rejection</label>
                <textarea wire:model="rejectionReason" rows="3" placeholder="Please provide a reason..."
                    class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                @error('rejectionReason') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3">
                <button wire:click="rejectChange" class="rounded-lg bg-red-600 px-6 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors">Confirm Rejection</button>
                <button wire:click="$set('showRejectModal', false)" class="rounded-lg border border-zinc-300 bg-white px-6 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 transition-colors">Cancel</button>
            </div>
        </div>
        @endif
    </div>
    @endif

    @endif {{-- end @else for user exists --}}
</div>
