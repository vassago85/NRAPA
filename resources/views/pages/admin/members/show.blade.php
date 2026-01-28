<?php

use App\Models\User;
use App\Models\Membership;
use App\Models\UserDeletionRequest;
use App\Models\AccountResetLog;
use App\Models\UserSecurityQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Member Details - Admin')] class extends Component {
    public User $user;
    public bool $showDeleteModal = false;
    public bool $showRequestDeleteModal = false;
    public string $deleteReason = '';
    
    // Account reset properties
    public bool $showResetPasswordModal = false;
    public bool $showReset2FAModal = false;
    public string $resetNotes = '';
    
    // 2FA verification for members
    public array $securityAnswers = [];
    public bool $verificationPassed = false;
    public ?string $verificationError = null;

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

    #[Computed]
    public function canResetPassword(): bool
    {
        return auth()->user()->canResetPasswordFor($this->user);
    }

    #[Computed]
    public function canReset2FA(): bool
    {
        return auth()->user()->canReset2FAFor($this->user) && $this->user->has2FAEnabled();
    }

    #[Computed]
    public function requiresVerification(): bool
    {
        // Only regular members require security question verification for 2FA reset
        return $this->user->role === User::ROLE_MEMBER;
    }

    #[Computed]
    public function userSecurityQuestions()
    {
        return $this->user->securityQuestions;
    }

    public function openResetPasswordModal(): void
    {
        $this->resetNotes = '';
        $this->showResetPasswordModal = true;
    }

    public function openReset2FAModal(): void
    {
        $this->resetNotes = '';
        $this->securityAnswers = [];
        $this->verificationPassed = false;
        $this->verificationError = null;
        $this->showReset2FAModal = true;
    }

    public function sendPasswordReset(): void
    {
        if (!$this->canResetPassword) {
            session()->flash('error', 'You do not have permission to reset this user\'s password.');
            return;
        }

        // Send password reset email
        $status = Password::sendResetLink(['email' => $this->user->email]);

        // Log the action
        AccountResetLog::create([
            'user_id' => $this->user->id,
            'reset_by' => auth()->id(),
            'reset_type' => AccountResetLog::TYPE_PASSWORD,
            'verification_passed' => true, // No verification needed for password reset
            'notes' => $this->resetNotes ?: 'Password reset email sent via admin panel.',
            'ip_address' => request()->ip(),
        ]);

        $this->showResetPasswordModal = false;
        $this->resetNotes = '';

        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('success', 'Password reset email has been sent to ' . $this->user->email);
        } else {
            session()->flash('warning', 'Password reset initiated. The user should check their email.');
        }
    }

    public function verifySecurityAnswers(): void
    {
        $this->verificationError = null;
        $questions = $this->userSecurityQuestions;
        
        if ($questions->count() < UserSecurityQuestion::REQUIRED_QUESTIONS) {
            $this->verificationError = 'User has not set up security questions. Cannot verify identity.';
            return;
        }

        $correctAnswers = 0;
        foreach ($questions as $question) {
            $answer = $this->securityAnswers[$question->id] ?? '';
            if ($answer && $question->verifyAnswer($answer)) {
                $correctAnswers++;
            }
        }

        // Require all answers to be correct
        if ($correctAnswers >= $questions->count()) {
            $this->verificationPassed = true;
        } else {
            $this->verificationError = 'Security answers are incorrect. Please ask the user to verify their answers.';
        }
    }

    public function reset2FA(): void
    {
        if (!$this->canReset2FA) {
            session()->flash('error', 'You do not have permission to reset 2FA for this user.');
            return;
        }

        // For members, require verification unless they have no security questions
        if ($this->requiresVerification && $this->userSecurityQuestions->count() >= UserSecurityQuestion::REQUIRED_QUESTIONS) {
            if (!$this->verificationPassed) {
                session()->flash('error', 'You must verify the user\'s identity before resetting 2FA.');
                return;
            }
        }

        // Reset 2FA
        $this->user->reset2FA();

        // Log the action
        AccountResetLog::create([
            'user_id' => $this->user->id,
            'reset_by' => auth()->id(),
            'reset_type' => AccountResetLog::TYPE_2FA,
            'verification_passed' => $this->verificationPassed || !$this->requiresVerification,
            'notes' => $this->resetNotes ?: '2FA reset via admin panel.',
            'ip_address' => request()->ip(),
        ]);

        $this->showReset2FAModal = false;
        $this->resetNotes = '';
        $this->securityAnswers = [];
        $this->verificationPassed = false;
        $this->user->refresh();

        session()->flash('success', '2FA has been reset for ' . $this->user->name . '. They will need to set it up again on their next login.');
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

    public function issueDocument(string $documentType): void
    {
        try {
            $issuer = Auth::user();
            $issueService = app(\App\Services\CertificateIssueService::class);
            
            $certificate = match($documentType) {
                'dedicated-hunter' => $issueService->issueDedicatedHunterCertificate($this->user, $issuer),
                'dedicated-sport' => $issueService->issueDedicatedSportCertificate($this->user, $issuer),
                'paid-up' => $issueService->issuePaidUpCertificate($this->user, $issuer),
                'membership-card' => $issueService->issueMembershipCard($this->user, $issuer),
                'welcome-letter' => $issueService->issueWelcomeLetter($this->user, $issuer),
                default => throw new \Exception('Unknown document type'),
            };
            
            // Log the action
            \App\Models\AuditLog::create([
                'user_id' => $issuer->id,
                'event' => 'issued_certificate',
                'auditable_type' => get_class($certificate),
                'auditable_id' => $certificate->id,
                'old_values' => null,
                'new_values' => [
                    'document_type' => $documentType,
                    'user_id' => $this->user->id,
                    'certificate_id' => $certificate->id,
                    'description' => "Issued {$documentType} certificate for {$this->user->name}",
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            // Refresh user to show new certificate
            $this->user->refresh();
            $this->user->load(['certificates.certificateType']);
            
            session()->flash('success', ucfirst(str_replace('-', ' ', $documentType)) . ' issued successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to issue document: ' . $e->getMessage());
        }
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
        <div class="flex flex-wrap items-center gap-2">
            @if($this->user->is_admin)
            <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-sm font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                Admin
            </span>
            @endif
            
            @if($this->user->has2FAEnabled())
            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                2FA Enabled
            </span>
            @endif
            
            @if($this->pendingDeletionRequest)
            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                Deletion Pending
            </span>
            @endif

            {{-- Account Reset Actions --}}
            @if($this->canResetPassword)
            <button wire:click="openResetPasswordModal" class="inline-flex items-center gap-1 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                Reset Password
            </button>
            @endif

            @if($this->canReset2FA)
            <button wire:click="openReset2FAModal" class="inline-flex items-center gap-1 rounded-lg border border-purple-300 bg-purple-50 px-3 py-1.5 text-sm font-medium text-purple-700 hover:bg-purple-100 dark:border-purple-700 dark:bg-purple-900/30 dark:text-purple-300 dark:hover:bg-purple-900/50 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                </svg>
                Reset 2FA
            </button>
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

    {{-- Document Issuance --}}
    @if($this->activeMembership)
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Document Issuance</h2>
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Issue official certificates and documents for this member</p>
        </div>
        
        <div class="p-6">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {{-- Dedicated Hunter Certificate --}}
                @php
                    $hasHunterStatus = $this->user->dedicatedStatusApplications()
                        ->where('dedicated_type', 'hunter')
                        ->where('status', 'approved')
                        ->where(function ($q) {
                            $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                        })
                        ->exists();
                    $hasHunterCert = $this->user->certificates()
                        ->whereHas('certificateType', fn($q) => $q->where('slug', 'dedicated-hunter-certificate'))
                        ->whereNull('revoked_at')
                        ->exists();
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Dedicated Hunter Certificate</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Official certificate confirming Dedicated Hunter Status</p>
                    @if(!$hasHunterStatus)
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-3">Member does not have approved dedicated hunter status</p>
                    @endif
                    <button wire:click="issueDocument('dedicated-hunter')" 
                            @disabled(!$hasHunterStatus || $hasHunterCert)
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        @if($hasHunterCert) Already Issued @else Issue Certificate @endif
                    </button>
                </div>

                {{-- Dedicated Sport Certificate --}}
                @php
                    $hasSportStatus = $this->user->dedicatedStatusApplications()
                        ->where('dedicated_type', 'sport_shooter')
                        ->where('status', 'approved')
                        ->where(function ($q) {
                            $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                        })
                        ->exists();
                    $hasSportCert = $this->user->certificates()
                        ->whereHas('certificateType', fn($q) => $q->where('slug', 'dedicated-sport-certificate'))
                        ->whereNull('revoked_at')
                        ->exists();
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Dedicated Sport Shooter Certificate</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Official certificate confirming Dedicated Sport Shooter Status</p>
                    @if(!$hasSportStatus)
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-3">Member does not have approved dedicated sport shooter status</p>
                    @endif
                    <button wire:click="issueDocument('dedicated-sport')" 
                            @disabled(!$hasSportStatus || $hasSportCert)
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        @if($hasSportCert) Already Issued @else Issue Certificate @endif
                    </button>
                </div>

                {{-- Paid-Up Certificate --}}
                @php
                    $hasPaidUpCert = $this->user->certificates()
                        ->whereHas('certificateType', fn($q) => $q->where('slug', 'paid-up-certificate'))
                        ->whereNull('revoked_at')
                        ->where(function ($q) {
                            $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                        })
                        ->exists();
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Proof of Paid-Up Membership</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Certificate confirming member is in good standing</p>
                    <button wire:click="issueDocument('paid-up')" 
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                        Issue Certificate
                    </button>
                </div>

                {{-- Membership Card --}}
                @php
                    $hasMembershipCard = $this->user->certificates()
                        ->whereHas('certificateType', fn($q) => $q->where('slug', 'membership-card'))
                        ->whereNull('revoked_at')
                        ->exists();
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Membership Card</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">NRAPA membership identification card</p>
                    <button wire:click="issueDocument('membership-card')" 
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                        Issue Card
                    </button>
                </div>

                {{-- Welcome Letter --}}
                @php
                    $hasWelcomeLetter = $this->user->certificates()
                        ->whereHas('certificateType', fn($q) => $q->where('slug', 'welcome-letter'))
                        ->exists();
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Welcome Letter</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Informational welcome letter for new members</p>
                    <button wire:click="issueDocument('welcome-letter')" 
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                        Generate Letter
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Certificates --}}
    @if($this->user->certificates->count() > 0)
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Issued Certificates & Documents</h2>
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

    {{-- Reset Password Modal --}}
    @if($showResetPasswordModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showResetPasswordModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50">
                        <svg class="size-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Reset Password</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Send password reset email</p>
                    </div>
                </div>
                
                <p class="mb-4 text-zinc-600 dark:text-zinc-300">
                    A password reset email will be sent to <strong>{{ $this->user->email }}</strong>. The user will receive a link to create a new password.
                </p>

                <div class="mb-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Note:</strong> The user must have called in to verify their identity before sending this reset link.
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes (optional)</label>
                    <textarea wire:model="resetNotes" rows="2" placeholder="Reason for password reset..."
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showResetPasswordModal', false)" 
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button wire:click="sendPasswordReset"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Send Reset Email
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Reset 2FA Modal --}}
    @if($showReset2FAModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showReset2FAModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/50">
                        <svg class="size-5 text-purple-600 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Reset Two-Factor Authentication</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Remove 2FA from user account</p>
                    </div>
                </div>
                
                <p class="mb-4 text-zinc-600 dark:text-zinc-300">
                    Resetting 2FA for <strong>{{ $this->user->name }}</strong> will disable their current authenticator. They will need to set up 2FA again.
                </p>

                @if($this->requiresVerification)
                    {{-- Security Questions Verification for Members --}}
                    @if($this->userSecurityQuestions->count() >= \App\Models\UserSecurityQuestion::REQUIRED_QUESTIONS)
                        <div class="mb-4 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                            <h4 class="font-medium text-amber-800 dark:text-amber-200 mb-2">
                                <svg class="inline size-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                Identity Verification Required
                            </h4>
                            <p class="text-sm text-amber-700 dark:text-amber-300 mb-3">
                                Ask the user to answer these security questions over the phone. Enter their answers below.
                            </p>

                            @if($verificationError)
                                <div class="mb-3 p-2 rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
                                    {{ $verificationError }}
                                </div>
                            @endif

                            @if($verificationPassed)
                                <div class="mb-3 p-2 rounded bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm flex items-center gap-2">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    Identity verified successfully!
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach($this->userSecurityQuestions as $question)
                                        <div>
                                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                                {{ $question->question }}
                                            </label>
                                            <input type="text" 
                                                   wire:model="securityAnswers.{{ $question->id }}"
                                                   placeholder="Enter user's answer..."
                                                   class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                        </div>
                                    @endforeach
                                </div>

                                <button wire:click="verifySecurityAnswers" 
                                        class="mt-3 w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                                    Verify Answers
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                            <h4 class="font-medium text-red-800 dark:text-red-200 mb-2">
                                <svg class="inline size-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                No Security Questions Set Up
                            </h4>
                            <p class="text-sm text-red-700 dark:text-red-300">
                                This user has not set up security questions. You may proceed with the reset, but ensure you have verified their identity through other means (e.g., ID document, in-person verification).
                            </p>
                        </div>
                    @endif
                @else
                    {{-- No verification needed for admins/owners --}}
                    <div class="mb-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Note:</strong> This user is an {{ $this->user->role_display_name }}. Security question verification is not required, but ensure you have confirmed their identity.
                        </p>
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes (optional)</label>
                    <textarea wire:model="resetNotes" rows="2" placeholder="Reason for 2FA reset..."
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showReset2FAModal', false)" 
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button wire:click="reset2FA"
                        @if($this->requiresVerification && $this->userSecurityQuestions->count() >= \App\Models\UserSecurityQuestion::REQUIRED_QUESTIONS && !$verificationPassed)
                            disabled
                        @endif
                        class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Reset 2FA
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
