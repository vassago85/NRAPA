<?php

use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ShootingActivity;
use App\Models\ActivityType;
use App\Models\UserDeletionRequest;
use App\Models\AccountResetLog;
use App\Models\UserSecurityQuestion;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use App\Mail\AccountDeleted;
use App\Mail\ImportWelcome;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Member Details - Admin')] class extends Component {
    public User $user;
    public bool $showDeleteModal = false;
    public bool $showRequestDeleteModal = false;
    public string $deleteReason = '';
    public bool $showDeleteCertificateModal = false;
    public ?int $certificateToDelete = null;
    
    // Account reset properties
    public bool $showResetPasswordModal = false;
    public bool $showReset2FAModal = false;
    public string $resetNotes = '';
    
    // Knowledge test manual completion
    public bool $showMarkKnowledgeTestModal = false;
    public ?int $selectedKnowledgeTestId = null;
    public string $knowledgeTestNotes = '';
    
    // Reset test attempts
    public ?int $testToResetId = null;
    
    // 2FA verification for members
    public array $securityAnswers = [];
    public bool $verificationPassed = false;
    public ?string $verificationError = null;

    // Edit profile properties
    public bool $showEditProfileModal = false;
    public string $editName = '';
    public string $editEmail = '';
    public string $editIdNumber = '';
    public string $editPhone = '';
    public string $editDateOfBirth = '';
    public string $editPhysicalAddress = '';
    public string $editPostalAddress = '';

    // Edit membership properties
    public bool $showEditMembershipModal = false;
    public ?int $editMembershipId = null;
    public string $editMembershipTypeId = '';
    public string $editMembershipStatus = '';
    public string $editMembershipExpiresAt = '';
    public string $editMembershipActivatedAt = '';
    public string $editMembershipNotes = '';

    // Add activity properties
    public bool $showAddActivityModal = false;
    public string $addActivityTrack = 'hunting';
    public string $addActivityTypeId = '';
    public string $addActivityDate = '';
    public string $addActivityDescription = '';

    public function mount(User $user): void
    {
        $this->user = $user->load([
            'memberships.type',
            'memberships.approver',
            'certificates.certificateType',
            'documents.documentType',
            'documents.verifier',
            'dedicatedStatusApplications',
            'knowledgeTestAttempts.knowledgeTest',
            'deletionRequests',
        ]);
    }

    #[Computed]
    public function endorsementRequests()
    {
        return \App\Models\EndorsementRequest::where('user_id', $this->user->id)
            ->with(['firearm.firearmCalibre', 'firearm.firearmMake', 'firearm.firearmModel'])
            ->latest('updated_at')
            ->get();
    }

    #[Computed]
    public function memberDocuments()
    {
        return $this->user->documents->sortByDesc('created_at');
    }

    #[Computed]
    public function activeMembership()
    {
        return $this->user->memberships->firstWhere('status', 'active');
    }

    #[Computed]
    public function isLifetimeMember(): bool
    {
        return $this->activeMembership?->type?->isLifetime() ?? false;
    }

    #[Computed]
    public function memberActivities()
    {
        return ShootingActivity::where('user_id', $this->user->id)
            ->with(['activityType', 'verifier'])
            ->latest('activity_date')
            ->get();
    }

    #[Computed]
    public function activitySummary(): array
    {
        $period = ShootingActivity::getActivityPeriod($this->user);
        $currentYear = ShootingActivity::where('user_id', $this->user->id)
            ->approved()
            ->withinActivityYear($this->user)
            ->get();

        return [
            'total' => $currentYear->count(),
            'hunting' => $currentYear->where('track', 'hunting')->count(),
            'sport' => $currentYear->where('track', 'sport')->count(),
            'period_label' => $period['label'],
        ];
    }

    #[Computed]
    public function activityTypes()
    {
        return ActivityType::active()->orderBy('name')->get();
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

    public function resendWelcomeEmail(): void
    {
        $membership = $this->user->memberships()->latest()->first();

        if (!$membership) {
            session()->flash('error', 'No membership found for this member.');
            return;
        }

        try {
            Mail::to($this->user->email)->queue(new ImportWelcome(
                $this->user,
                $membership,
                'Use the password provided during import',
            ));
            session()->flash('success', "Welcome email queued for {$this->user->name} ({$this->user->email}).");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to queue welcome email resend', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', "Failed to send email: {$e->getMessage()}");
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
        $userEmail = $this->user->email;

        // Send notification email before deletion (email will be modified on delete)
        try {
            Mail::to($userEmail)->send(new AccountDeleted(
                userName: $userName,
                userEmail: $userEmail,
                reason: $this->deleteReason,
                deletedBy: auth()->user()->name
            ));
        } catch (\Exception $e) {
            // Log error but continue with deletion
            \Illuminate\Support\Facades\Log::warning('Failed to send account deletion email', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Create a deletion record for audit purposes
        UserDeletionRequest::create([
            'user_id' => $this->user->id,
            'requested_by' => auth()->id(),
            'actioned_by' => auth()->id(),
            'status' => UserDeletionRequest::STATUS_APPROVED,
            'reason' => $this->deleteReason,
            'actioned_at' => now(),
        ]);

        $this->user->forceDelete();

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
                'dedicated-both' => $issueService->issueDedicatedBothCertificate($this->user, $issuer),
                'occasional-hunter' => $issueService->issueOccasionalCertificate($this->user, $issuer, 'hunter'),
                'occasional-sport' => $issueService->issueOccasionalCertificate($this->user, $issuer, 'sport'),
                'membership-certificate' => $issueService->issueMembershipCertificate($this->user, $issuer),
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

    public function deleteCertificate(): void
    {
        if (!$this->certificateToDelete) {
            session()->flash('error', 'No certificate selected for deletion.');
            return;
        }

        try {
            $certificate = \App\Models\Certificate::find($this->certificateToDelete);
            
            if (!$certificate) {
                session()->flash('error', 'Certificate not found.');
                return;
            }

            // Verify this certificate belongs to the user we're viewing
            if ($certificate->user_id !== $this->user->id) {
                session()->flash('error', 'Certificate does not belong to this member.');
                return;
            }

            // Log the deletion
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'certificate_deleted',
                'auditable_type' => \App\Models\Certificate::class,
                'auditable_id' => $certificate->id,
                'old_values' => [
                    'certificate_number' => $certificate->certificate_number,
                    'certificate_type' => $certificate->certificateType->name ?? null,
                    'user_id' => $certificate->user_id,
                ],
                'new_values' => ['deleted' => true],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Delete associated file if exists
            if ($certificate->file_path) {
                $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';
                try {
                    \Illuminate\Support\Facades\Storage::disk($disk)->delete($certificate->file_path);
                } catch (\Exception $e) {
                    // Log but don't fail deletion if file doesn't exist
                    \Illuminate\Support\Facades\Log::warning('Failed to delete certificate file', [
                        'file_path' => $certificate->file_path,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $certificateNumber = $certificate->certificate_number;
            $certificate->delete();

            // Refresh user to update certificates list
            $this->user->refresh();
            $this->user->load(['certificates.certificateType']);

            $this->showDeleteCertificateModal = false;
            $this->certificateToDelete = null;

            session()->flash('success', "Certificate {$certificateNumber} has been deleted.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete certificate', [
                'certificate_id' => $this->certificateToDelete,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to delete certificate: ' . $e->getMessage());
        }
    }

    public function openMarkKnowledgeTestModal(): void
    {
        $this->knowledgeTestNotes = '';
        $this->selectedKnowledgeTestId = null;
        $this->showMarkKnowledgeTestModal = true;
    }

    public function markKnowledgeTestComplete(): void
    {
        $this->validate([
            'selectedKnowledgeTestId' => 'required|exists:knowledge_tests,id',
            'knowledgeTestNotes' => 'nullable|string|max:500',
        ]);

        // Check if user already has a passed test
        if ($this->user->hasPassedKnowledgeTest()) {
            session()->flash('error', 'User has already passed a knowledge test.');
            $this->showMarkKnowledgeTestModal = false;
            return;
        }

        $knowledgeTest = KnowledgeTest::findOrFail($this->selectedKnowledgeTestId);

        // Get total points for the test
        $totalPoints = $knowledgeTest->total_points ?? 100;
        
        // Create a passed attempt
        $attempt = KnowledgeTestAttempt::create([
            'user_id' => $this->user->id,
            'knowledge_test_id' => $knowledgeTest->id,
            'started_at' => now(),
            'submitted_at' => now(),
            'auto_score' => $totalPoints,
            'manual_score' => 0,
            'total_score' => $totalPoints,
            'passed' => true,
            'marked_at' => now(),
            'marked_by' => auth()->id(),
            'marker_notes' => $this->knowledgeTestNotes ?: 'Manually marked as complete by admin',
        ]);

        // Log the action
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'knowledge_test_manually_completed',
            'auditable_type' => KnowledgeTestAttempt::class,
            'auditable_id' => $attempt->id,
            'old_values' => null,
            'new_values' => [
                'user_id' => $this->user->id,
                'knowledge_test_id' => $knowledgeTest->id,
                'passed' => true,
                'notes' => $this->knowledgeTestNotes,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Refresh user to show updated status
        $this->user->refresh();
        $this->user->load(['knowledgeTestAttempts.knowledgeTest']);

        $this->showMarkKnowledgeTestModal = false;
        $this->knowledgeTestNotes = '';
        $this->selectedKnowledgeTestId = null;

        session()->flash('success', 'Knowledge test marked as complete successfully.');
    }

    public function resetTestAttempts(int $testId): void
    {
        // Get test name for logging
        $test = KnowledgeTest::find($testId);
        $testName = $test?->name ?? 'Unknown';

        // Delete all attempts for this user and test
        $deletedCount = KnowledgeTestAttempt::where('user_id', $this->user->id)
            ->where('knowledge_test_id', $testId)
            ->delete();

        // Log the action
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'reset_test_attempts',
            'subject_type' => User::class,
            'subject_id' => $this->user->id,
            'changes' => [
                'test_id' => $testId,
                'test_name' => $testName,
                'attempts_deleted' => $deletedCount,
                'reason' => 'Admin reset test attempts',
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Refresh user data
        $this->user->refresh();
        $this->user->load(['knowledgeTestAttempts.knowledgeTest']);

        session()->flash('success', "Reset {$deletedCount} attempt(s) for {$testName}. Member can now retake the test.");
    }

    #[Computed]
    public function testAttemptSummary()
    {
        // Group attempts by test and count them
        $summary = [];
        foreach ($this->user->knowledgeTestAttempts as $attempt) {
            $testId = $attempt->knowledge_test_id;
            if (!isset($summary[$testId])) {
                $summary[$testId] = [
                    'test' => $attempt->knowledgeTest,
                    'count' => 0,
                    'max' => $attempt->knowledgeTest->max_attempts ?? 3,
                    'passed' => false,
                ];
            }
            $summary[$testId]['count']++;
            if ($attempt->passed) {
                $summary[$testId]['passed'] = true;
            }
        }
        return $summary;
    }

    #[Computed]
    public function availableKnowledgeTests()
    {
        return KnowledgeTest::active()->orderBy('name')->get();
    }

    #[Computed]
    public function membershipTypes()
    {
        return MembershipType::where('is_active', true)->orderBy('name')->get();
    }

    public function openEditProfileModal(): void
    {
        $this->editName = $this->user->name;
        $this->editEmail = $this->user->email;
        $this->editIdNumber = $this->user->id_number ?? '';
        $this->editPhone = $this->user->phone ?? '';
        $this->editDateOfBirth = $this->user->date_of_birth?->format('Y-m-d') ?? '';
        $this->editPhysicalAddress = $this->user->physical_address ?? '';
        $this->editPostalAddress = $this->user->postal_address ?? '';
        $this->showEditProfileModal = true;
    }

    public function saveProfile(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editEmail' => 'required|email|max:255|unique:users,email,' . $this->user->id,
            'editIdNumber' => 'nullable|string|max:13',
            'editPhone' => 'nullable|string|max:20',
            'editDateOfBirth' => 'nullable|date',
            'editPhysicalAddress' => 'nullable|string|max:500',
            'editPostalAddress' => 'nullable|string|max:500',
        ]);

        $this->user->update([
            'name' => $this->editName,
            'email' => $this->editEmail,
            'id_number' => $this->editIdNumber ?: null,
            'phone' => $this->editPhone ?: null,
            'date_of_birth' => $this->editDateOfBirth ?: null,
            'physical_address' => $this->editPhysicalAddress ?: null,
            'postal_address' => $this->editPostalAddress ?: null,
        ]);

        $this->user->refresh();
        $this->showEditProfileModal = false;
        session()->flash('success', 'Member profile updated successfully.');
    }

    public function openAssignMembershipModal(): void
    {
        $this->editMembershipId = null;
        $this->editMembershipTypeId = '';
        $this->editMembershipStatus = 'active';
        $this->editMembershipExpiresAt = '';
        $this->editMembershipActivatedAt = now()->format('Y-m-d');
        $this->editMembershipNotes = '';
        $this->showEditMembershipModal = true;
    }

    public function openEditMembershipModal(?int $membershipId = null): void
    {
        $membership = $membershipId
            ? $this->user->memberships->firstWhere('id', $membershipId)
            : $this->user->memberships->first();

        if (!$membership) {
            session()->flash('error', 'No membership found to edit.');
            return;
        }

        $this->editMembershipId = $membership->id;
        $this->editMembershipTypeId = (string) $membership->membership_type_id;
        $this->editMembershipStatus = $membership->status;
        $this->editMembershipExpiresAt = $membership->expires_at?->format('Y-m-d') ?? '';
        $this->editMembershipActivatedAt = $membership->activated_at?->format('Y-m-d') ?? '';
        $this->editMembershipNotes = $membership->notes ?? '';
        $this->showEditMembershipModal = true;
    }

    public function saveMembership(): void
    {
        $this->validate([
            'editMembershipTypeId' => 'required|exists:membership_types,id',
            'editMembershipStatus' => 'required|in:applied,approved,active,suspended,revoked,expired',
            'editMembershipExpiresAt' => 'nullable|date',
            'editMembershipActivatedAt' => 'nullable|date',
            'editMembershipNotes' => 'nullable|string|max:1000',
        ]);

        if ($this->editMembershipId) {
            $membership = Membership::findOrFail($this->editMembershipId);
            $oldStatus = $membership->status;

            $membership->update([
                'membership_type_id' => $this->editMembershipTypeId,
                'status' => $this->editMembershipStatus,
                'expires_at' => $this->editMembershipExpiresAt ?: null,
                'activated_at' => $this->editMembershipActivatedAt ?: null,
                'notes' => $this->editMembershipNotes ?: null,
            ]);

            if ($this->editMembershipStatus === 'active' && $oldStatus !== 'active' && !$membership->approved_at) {
                $membership->update([
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ]);
            }

            $message = 'Membership updated successfully.';
        } else {
            $membershipType = MembershipType::findOrFail($this->editMembershipTypeId);

            $expiresAt = $this->editMembershipExpiresAt ?: null;
            if (!$expiresAt && $membershipType->requires_renewal && $membershipType->duration_months) {
                $activatedAt = $this->editMembershipActivatedAt ? \Carbon\Carbon::parse($this->editMembershipActivatedAt) : now();
                $expiresAt = $activatedAt->copy()->addMonths($membershipType->duration_months)->format('Y-m-d');
            }

            Membership::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'user_id' => $this->user->id,
                'membership_type_id' => $this->editMembershipTypeId,
                'membership_number' => $this->user->formatted_member_number,
                'status' => $this->editMembershipStatus,
                'applied_at' => $this->user->created_at,
                'approved_at' => in_array($this->editMembershipStatus, ['approved', 'active']) ? now() : null,
                'approved_by' => in_array($this->editMembershipStatus, ['approved', 'active']) ? auth()->id() : null,
                'activated_at' => $this->editMembershipActivatedAt ?: null,
                'expires_at' => $expiresAt,
                'notes' => $this->editMembershipNotes ?: null,
                'source' => 'admin',
            ]);

            $message = 'Membership assigned successfully.';
        }

        $this->user->load(['memberships.type', 'memberships.approver', 'activeMembership.type']);
        $this->showEditMembershipModal = false;
        session()->flash('success', $message);
    }

    public function approveActivity(int $activityId): void
    {
        $activity = ShootingActivity::where('user_id', $this->user->id)->findOrFail($activityId);
        $activity->approve(auth()->user());
        unset($this->memberActivities, $this->activitySummary);
        session()->flash('success', 'Activity approved.');
    }

    public function rejectActivity(int $activityId): void
    {
        $activity = ShootingActivity::where('user_id', $this->user->id)->findOrFail($activityId);
        $activity->reject(auth()->user(), 'Rejected by admin');
        unset($this->memberActivities, $this->activitySummary);
        session()->flash('success', 'Activity rejected.');
    }

    public function openAddActivityModal(): void
    {
        $this->addActivityTrack = 'hunting';
        $this->addActivityTypeId = '';
        $this->addActivityDate = now()->format('Y-m-d');
        $this->addActivityDescription = '';
        $this->showAddActivityModal = true;
    }

    public function saveActivity(): void
    {
        $this->validate([
            'addActivityTrack' => 'required|in:hunting,sport',
            'addActivityTypeId' => 'nullable|exists:activity_types,id',
            'addActivityDate' => 'required|date',
            'addActivityDescription' => 'required|string|max:500',
        ]);

        $activityType = $this->addActivityTypeId
            ? ActivityType::find($this->addActivityTypeId)
            : ActivityType::active()->forTrack($this->addActivityTrack)->first();

        ShootingActivity::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user->id,
            'activity_type_id' => $activityType?->id,
            'track' => $this->addActivityTrack,
            'activity_date' => $this->addActivityDate,
            'description' => $this->addActivityDescription,
            'status' => 'approved',
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        unset($this->memberActivities, $this->activitySummary);
        $this->showAddActivityModal = false;
        session()->flash('success', 'Activity added and approved.');
    }

    public function getStatusClasses(string $status): string
    {
        return match($status) {
            'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
            'applied' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'suspended', 'revoked' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'expired' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Member Details</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">View and manage member information</p>
    </x-slot>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-100 p-4 text-emerald-800 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
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
            <a href="{{ route('admin.members.index') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
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
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
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

            <button wire:click="resendWelcomeEmail"
                wire:confirm="Send welcome email to {{ $this->user->name }} ({{ $this->user->email }})?"
                class="inline-flex items-center gap-1 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50 transition-colors">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                Resend Welcome Email
            </button>

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
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Member Information</h2>
                <button wire:click="openEditProfileModal" class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                    Edit
                </button>
            </div>
            <div class="p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Full Name</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Member Number</dt>
                        <dd class="mt-1 font-mono font-medium text-zinc-900 dark:text-white">{{ $this->user->formatted_member_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Email</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">ID Number</dt>
                        <dd class="mt-1 font-mono font-medium text-zinc-900 dark:text-white">{{ $this->user->id_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Phone</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->user->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Date of Birth</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $this->user->date_of_birth?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    @if($this->user->physical_address)
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Physical Address</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $this->user->physical_address }}</dd>
                    </div>
                    @endif
                    @if($this->user->postal_address)
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Postal Address</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $this->user->postal_address }}</dd>
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
                            <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
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
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 lg:col-span-2">
            <div class="flex items-center justify-between border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Current Membership</h2>
                @if($this->activeMembership)
                <button wire:click="openEditMembershipModal({{ $this->activeMembership->id }})" class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                    Edit
                </button>
                @endif
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
                            @if($this->activeMembership->expires_at?->isPast())
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-sm font-medium text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                    Expired
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-sm font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    Active
                                </span>
                            @endif
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
                                @elseif($this->activeMembership->expires_at->lte(now()->addDays(30)))
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
                    <button wire:click="openAssignMembershipModal" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Assign Membership
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Membership History --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
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
                            @php $displayStatus = ($membership->status === 'active' && $membership->expires_at?->isPast()) ? 'expired' : $membership->status; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusClasses($displayStatus) }}">
                                {{ ucfirst(str_replace('_', ' ', $displayStatus)) }}
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
                            <div class="flex items-center gap-2">
                                <button wire:click="openEditMembershipModal({{ $membership->id }})" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors" title="Edit">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                </button>
                                @if($membership->sage_invoice_id)
                                <span title="Sage invoice synced" class="text-indigo-500 dark:text-indigo-400">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </span>
                                @endif
                                @if($membership->status === 'applied')
                                <a href="{{ route('admin.approvals.show', $membership) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                                    Review
                                </a>
                                @endif
                            </div>
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

    {{-- Activities --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center justify-between border-b border-zinc-200 p-6 dark:border-zinc-700">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Activities</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $this->activitySummary['period_label'] }}</p>
            </div>
            <button wire:click="openAddActivityModal" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 transition-colors">
                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Activity
            </button>
        </div>

        {{-- Activity Summary --}}
        <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-3 dark:border-zinc-700 dark:bg-zinc-900/50">
            <div class="flex flex-wrap items-center gap-4 text-sm">
                @if($this->isLifetimeMember)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Lifetime Member — Activities always up to date
                    </span>
                @else
                    <span class="text-zinc-600 dark:text-zinc-400">
                        Approved this year: <strong class="text-zinc-900 dark:text-white">{{ $this->activitySummary['total'] }}</strong>
                    </span>
                    <span class="text-zinc-400 dark:text-zinc-600">|</span>
                    <span class="text-zinc-600 dark:text-zinc-400">
                        Hunting: <strong class="text-zinc-900 dark:text-white">{{ $this->activitySummary['hunting'] }}</strong>
                    </span>
                    <span class="text-zinc-600 dark:text-zinc-400">
                        Sport: <strong class="text-zinc-900 dark:text-white">{{ $this->activitySummary['sport'] }}</strong>
                    </span>
                @endif
            </div>
        </div>

        @if($this->memberActivities->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Track</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->memberActivities as $activity)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-zinc-900 dark:text-white">{{ $activity->activity_date->format('d M Y') }}</td>
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-zinc-900 dark:text-white">{{ $activity->activityType?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-6 py-3 text-sm">
                            @if($activity->track === 'hunting')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-300">Hunting</span>
                            @elseif($activity->track === 'sport')
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Sport</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="max-w-xs truncate px-6 py-3 text-sm text-zinc-500 dark:text-zinc-400" title="{{ $activity->description }}">{{ \Illuminate\Support\Str::limit($activity->description, 60) }}</td>
                        <td class="whitespace-nowrap px-6 py-3 text-sm">
                            @if($activity->status === 'approved')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Approved</span>
                            @elseif($activity->status === 'pending')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending</span>
                            @elseif($activity->status === 'rejected')
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Rejected</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-3 text-sm">
                            @if($activity->status === 'pending')
                            <div class="flex items-center gap-2">
                                <button wire:click="approveActivity({{ $activity->id }})" wire:confirm="Approve this activity?" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors" title="Approve">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <button wire:click="rejectActivity({{ $activity->id }})" wire:confirm="Reject this activity?" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors" title="Reject">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            @elseif($activity->verifier)
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">by {{ $activity->verifier->name }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center">
            <p class="text-zinc-500 dark:text-zinc-400">No activities recorded</p>
        </div>
        @endif
    </div>

    {{-- Uploaded Documents --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Uploaded Documents</h2>
        </div>

        @if($this->memberDocuments->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Document Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Filename</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Uploaded</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Verified By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->memberDocuments as $document)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-zinc-900 dark:text-white">{{ $document->documentType?->name ?? '—' }}</td>
                        <td class="max-w-xs truncate px-6 py-3 text-sm text-zinc-500 dark:text-zinc-400" title="{{ $document->original_filename }}">{{ \Illuminate\Support\Str::limit($document->original_filename, 40) }}</td>
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $document->created_at->format('d M Y') }}</td>
                        <td class="whitespace-nowrap px-6 py-3 text-sm">
                            @if($document->status === 'verified')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Verified</span>
                            @elseif($document->status === 'pending')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending</span>
                            @elseif($document->status === 'rejected')
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Rejected</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ ucfirst($document->status) }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $document->verifier?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-6 py-3 text-sm">
                            <a href="{{ route('admin.documents.show', $document) }}" wire:navigate
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/></svg>
                                Review
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center">
            <p class="text-zinc-500 dark:text-zinc-400">No documents uploaded</p>
        </div>
        @endif
    </div>

    {{-- Document Issuance --}}
    @if($this->activeMembership)
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
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
                    $memberDedicatedType = $this->user->activeMembership?->type?->dedicated_type;
                    $hasHunterStatus = in_array($memberDedicatedType, ['hunter', 'both']);
                    $hasHunterCert = $this->user->certificates()
                        ->whereHas('certificateType', fn($q) => $q->where('slug', 'dedicated-hunter-certificate'))
                        ->whereNull('revoked_at')
                        ->exists();
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Dedicated Hunter Certificate</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Official certificate confirming Dedicated Hunter Status with valid documents and activities up to date</p>
                    @if(!$hasHunterStatus)
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-3">Member does not have approved dedicated hunter status</p>
                    @endif
                    <button wire:click="issueDocument('dedicated-hunter')" 
                            @disabled(!$hasHunterStatus || $hasHunterCert)
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-nrapa-blue hover:bg-nrapa-blue-dark rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        @if($hasHunterCert) Already Issued @else Issue Certificate @endif
                    </button>
                </div>

                {{-- Dedicated Sport Certificate --}}
                @php
                    $hasSportStatus = in_array($memberDedicatedType, ['sport', 'sport_shooter', 'both']);
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
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-nrapa-blue hover:bg-nrapa-blue-dark rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        @if($hasSportCert) Already Issued @else Issue Certificate @endif
                    </button>
                </div>

                {{-- Dedicated Both Certificate --}}
                @php
                    $hasBothStatus = $hasHunterStatus && $hasSportStatus;
                    $hasBothCert = $this->user->certificates()
                        ->whereHas('certificateType', fn($q) => $q->where('slug', 'dedicated-both-certificate'))
                        ->whereNull('revoked_at')
                        ->exists();
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Dedicated Hunter & Sport Shooter (S16)</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Section 16 — Both dedicated statuses required</p>
                    @if(!$hasBothStatus)
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-3">Member does not have both approved dedicated statuses</p>
                    @endif
                    <button wire:click="issueDocument('dedicated-both')"
                            @disabled(!$hasBothStatus || $hasBothCert)
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-nrapa-blue hover:bg-nrapa-blue-dark rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        @if($hasBothCert) Already Issued @else Issue Certificate @endif
                    </button>
                </div>

                {{-- Occasional Hunter Certificate --}}
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Occasional Hunter (S15)</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Section 15 — For members without dedicated hunter status</p>
                    <button wire:click="issueDocument('occasional-hunter')"
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-nrapa-blue hover:bg-nrapa-blue-dark rounded-lg transition-colors">
                        Issue Certificate
                    </button>
                </div>

                {{-- Occasional Sport Shooter Certificate --}}
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Occasional Sport Shooter (S15)</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Section 15 — For members without dedicated sport shooter status</p>
                    <button wire:click="issueDocument('occasional-sport')"
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-nrapa-blue hover:bg-nrapa-blue-dark rounded-lg transition-colors">
                        Issue Certificate
                    </button>
                </div>

                {{-- Membership Certificate --}}
                @php
                    $hasMembershipCert = $this->user->certificates()
                        ->whereHas('certificateType', fn($q) => $q->where('slug', 'membership-certificate'))
                        ->whereNull('revoked_at')
                        ->where(function ($q) {
                            $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                        })
                        ->exists();
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Membership Certificate</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Certificate confirming member is paid-up, active, and in good standing</p>
                    <button wire:click="issueDocument('membership-certificate')" 
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-nrapa-blue hover:bg-nrapa-blue-dark rounded-lg transition-colors">
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
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Simple membership identification card (credit card format, wallet compatible)</p>
                    <button wire:click="issueDocument('membership-card')" 
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-nrapa-blue hover:bg-nrapa-blue-dark rounded-lg transition-colors">
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
                            class="w-full px-3 py-2 text-xs font-medium text-white bg-nrapa-blue hover:bg-nrapa-blue-dark rounded-lg transition-colors">
                        Generate Letter
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Certificates & Endorsement Requests --}}
    @if($this->user->certificates->count() > 0 || $this->endorsementRequests->count() > 0)
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Certificates & Endorsement Requests</h2>
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
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
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
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Valid</span>
                            @elseif($certificate->isRevoked())
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Revoked</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">Expired</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.certificates.show', $certificate) }}" wire:navigate
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/></svg>
                                    View
                                </a>
                                <button wire:click="$set('certificateToDelete', {{ $certificate->id }}); $set('showDeleteCertificateModal', true)"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    {{-- Endorsement Requests --}}
                    @foreach($this->endorsementRequests as $endorsement)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">
                            Endorsement {{ ucfirst($endorsement->request_type) }}
                            @if($endorsement->firearm)
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    {{ $endorsement->firearm->make }} {{ $endorsement->firearm->model }}
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-zinc-900 dark:text-white">
                            {{ $endorsement->letter_reference ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($endorsement->issued_at)
                                {{ $endorsement->issued_at->format('d M Y') }}
                            @elseif($endorsement->submitted_at)
                                {{ $endorsement->submitted_at->format('d M Y') }}
                            @else
                                {{ $endorsement->created_at->format('d M Y') }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($endorsement->expires_at)
                                <span class="{{ $endorsement->is_expired ? 'text-red-600 dark:text-red-400' : ($endorsement->is_expiring_soon ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-600 dark:text-zinc-400') }}">
                                    {{ $endorsement->expires_at->format('d M Y') }}
                                </span>
                                @if($endorsement->is_expired)
                                    <span class="ml-1 text-xs text-red-600 dark:text-red-400">(Expired)</span>
                                @elseif($endorsement->is_expiring_soon)
                                    <span class="ml-1 text-xs text-amber-600 dark:text-amber-400">(Expiring Soon)</span>
                                @endif
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($endorsement->status === 'issued' && $endorsement->is_expired)
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Expired</span>
                            @elseif($endorsement->status === 'issued' && $endorsement->is_expiring_soon)
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Expiring Soon</span>
                            @elseif($endorsement->status === 'issued')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Issued</span>
                            @elseif($endorsement->status === 'approved')
                                <span class="inline-flex items-center rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-800 dark:bg-teal-900/40 dark:text-teal-300">Approved</span>
                            @elseif($endorsement->status === 'submitted')
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Submitted</span>
                            @elseif($endorsement->status === 'under_review')
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">Under Review</span>
                            @elseif($endorsement->status === 'rejected')
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Rejected</span>
                            @elseif($endorsement->status === 'draft')
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Draft</span>
                            @elseif($endorsement->status === 'cancelled')
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Cancelled</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ ucfirst($endorsement->status) }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.endorsements.show', $endorsement->uuid) }}" wire:navigate
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/></svg>
                                    View
                                </a>
                                @if($endorsement->letter_file_path)
                                <a href="{{ route('admin.endorsements.download', $endorsement->uuid) }}"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                    target="_blank">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    Download
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Delete Certificate Modal --}}
    @if($showDeleteCertificateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showDeleteCertificateModal') }" x-show="show" x-cloak>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" x-on:click="show = false"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                            <svg class="size-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Delete Certificate</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">This action cannot be undone</p>
                        </div>
                    </div>
                    <p class="mb-6 text-sm text-zinc-600 dark:text-zinc-400">
                        Are you sure you want to delete this certificate? This will permanently remove the certificate record and associated file.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showDeleteCertificateModal', false); $set('certificateToDelete', null)"
                            class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-200 dark:border-zinc-600 dark:hover:bg-zinc-600 transition-colors">
                            Cancel
                        </button>
                        <button wire:click="deleteCertificate"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                            Delete Certificate
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Knowledge Test Status --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Knowledge Test Status</h2>
                @if(!$this->user->hasPassedKnowledgeTest())
                    <button wire:click="openMarkKnowledgeTestModal" class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Mark as Complete
                    </button>
                @endif
            </div>
        </div>
        <div class="p-6">
            @if($this->user->hasPassedKnowledgeTest())
                <div class="flex items-center gap-3">
                    <svg class="size-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <div>
                        <p class="font-medium text-emerald-800 dark:text-emerald-200">Knowledge Test Passed</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            @php
                                $passedAttempt = $this->user->knowledgeTestAttempts->where('passed', true)->first();
                            @endphp
                            @if($passedAttempt)
                                Passed on {{ $passedAttempt->submitted_at?->format('d M Y') ?? $passedAttempt->marked_at?->format('d M Y') ?? 'N/A' }}
                            @endif
                        </p>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <svg class="size-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-amber-800 dark:text-amber-200">Knowledge Test Not Completed</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Member has not passed the knowledge test yet</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Knowledge Test Attempts Summary --}}
    @if(count($this->testAttemptSummary) > 0)
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Knowledge Test Attempts</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Overview of test attempts by test type</p>
        </div>

        {{-- Summary Cards --}}
        <div class="p-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->testAttemptSummary as $testId => $summary)
            <div class="rounded-lg border {{ $summary['passed'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : ($summary['count'] >= $summary['max'] ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' : 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800') }} p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">{{ $summary['test']->name ?? 'Unknown Test' }}</h3>
                        <p class="mt-1 text-sm">
                            <span class="{{ $summary['count'] >= $summary['max'] && !$summary['passed'] ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-zinc-600 dark:text-zinc-400' }}">
                                {{ $summary['count'] }} / {{ $summary['max'] }} attempts used
                            </span>
                        </p>
                        @if($summary['passed'])
                        <span class="mt-2 inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200">
                            <svg class="mr-1 size-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Passed
                        </span>
                        @elseif($summary['count'] >= $summary['max'])
                        <span class="mt-2 inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-800 dark:text-red-200">
                            Max attempts reached
                        </span>
                        @endif
                    </div>
                    <button 
                        wire:click="openResetAttemptsModal({{ $testId }}, '{{ addslashes($summary['test']->name ?? 'Unknown') }}')"
                        wire:confirm="Are you sure you want to reset all attempts for this test? The member will be able to retake the test."
                        class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium"
                        title="Reset all attempts for this test"
                    >
                        Reset
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Detailed Attempts Table --}}
        <div class="border-t border-zinc-200 dark:border-zinc-700">
            <div class="px-6 py-3 bg-zinc-50 dark:bg-zinc-900/50">
                <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Attempt History</h3>
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
                        @foreach($this->user->knowledgeTestAttempts->sortByDesc('started_at') as $attempt)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $attempt->knowledgeTest->name ?? 'N/A' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $attempt->started_at->format('d M Y H:i') }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">
                                @if($attempt->total_score !== null && $attempt->knowledgeTest)
                                    @php
                                        $percentage = $attempt->knowledgeTest->total_points > 0 
                                            ? round(($attempt->total_score / $attempt->knowledgeTest->total_points) * 100, 1)
                                            : 0;
                                    @endphp
                                    {{ $percentage }}% ({{ $attempt->total_score }}/{{ $attempt->knowledgeTest->total_points }})
                                @else
                                    —
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($attempt->passed === true)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Passed</span>
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
                    Are you sure you want to permanently delete <strong>{{ $this->user->name }}</strong>? All their data, documents, and files will be removed. This cannot be undone.
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reason for deletion *</label>
                    <textarea wire:model="deleteReason" rows="3" placeholder="Please provide a reason for deleting this user..."
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                    @error('deleteReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showDeleteModal', false)" 
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="deleteUser"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors">
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
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="requestDeletion"
                        class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 transition-colors">
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
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="sendPasswordReset"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
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
                                <div class="mb-3 p-2 rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-sm flex items-center gap-2">
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
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="reset2FA"
                        @if($this->requiresVerification && $this->userSecurityQuestions->count() >= \App\Models\UserSecurityQuestion::REQUIRED_QUESTIONS && !$verificationPassed)
                            disabled
                        @endif
                        class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        Reset 2FA
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Mark Knowledge Test Complete Modal --}}
    {{-- Edit Profile Modal --}}
    @if($showEditProfileModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showEditProfileModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Edit Member Profile</h3>
                    <button wire:click="$set('showEditProfileModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="saveProfile" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="editName" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        @error('editName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" wire:model="editEmail" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        @error('editEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">ID Number</label>
                            <input type="text" wire:model="editIdNumber" maxlength="13" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-mono focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('editIdNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Phone</label>
                            <input type="text" wire:model="editPhone" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('editPhone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date of Birth</label>
                        <input type="date" wire:model="editDateOfBirth" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        @error('editDateOfBirth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Physical Address</label>
                        <textarea wire:model="editPhysicalAddress" rows="2" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                        @error('editPhysicalAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Postal Address</label>
                        <textarea wire:model="editPostalAddress" rows="2" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                        @error('editPostalAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="button" wire:click="$set('showEditProfileModal', false)" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-200 dark:border-zinc-600 transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit Membership Modal --}}
    @if($showEditMembershipModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showEditMembershipModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $editMembershipId ? 'Edit Membership' : 'Assign Membership' }}</h3>
                    <button wire:click="$set('showEditMembershipModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="saveMembership" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Membership Type <span class="text-red-500">*</span></label>
                        <select wire:model="editMembershipTypeId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <option value="">Select membership type...</option>
                            @foreach($this->membershipTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('editMembershipTypeId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status <span class="text-red-500">*</span></label>
                        <select wire:model="editMembershipStatus" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <option value="applied">Applied</option>
                            <option value="approved">Approved</option>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="revoked">Revoked</option>
                            <option value="expired">Expired</option>
                        </select>
                        @error('editMembershipStatus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Activated On</label>
                            <input type="date" wire:model="editMembershipActivatedAt" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('editMembershipActivatedAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Expires On</label>
                            <input type="date" wire:model="editMembershipExpiresAt" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Leave blank for lifetime memberships</p>
                            @error('editMembershipExpiresAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Admin Notes</label>
                        <textarea wire:model="editMembershipNotes" rows="3" placeholder="Reason for change..." class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                        @error('editMembershipNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="button" wire:click="$set('showEditMembershipModal', false)" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-200 dark:border-zinc-600 transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">{{ $editMembershipId ? 'Save Changes' : 'Assign Membership' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($showMarkKnowledgeTestModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showMarkKnowledgeTestModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                        <svg class="size-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Mark Knowledge Test Complete</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Manually mark knowledge test as passed</p>
                    </div>
                </div>
                
                <p class="mb-4 text-zinc-600 dark:text-zinc-300">
                    Mark knowledge test as complete for <strong>{{ $this->user->name }}</strong>. This is useful when importing users who have already completed the test.
                </p>

                <div class="mb-4">
                    <label for="selectedKnowledgeTestId" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Knowledge Test <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="selectedKnowledgeTestId" id="selectedKnowledgeTestId" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select a knowledge test...</option>
                        @foreach($this->availableKnowledgeTests as $test)
                            <option value="{{ $test->id }}">{{ $test->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedKnowledgeTestId') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label for="knowledgeTestNotes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Notes (Optional)
                    </label>
                    <textarea wire:model="knowledgeTestNotes" id="knowledgeTestNotes" rows="3" placeholder="e.g., Completed test in previous system, imported user..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    @error('knowledgeTestNotes') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showMarkKnowledgeTestModal', false)" 
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="markKnowledgeTestComplete"
                        class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        Mark as Complete
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Add Activity Modal --}}
    @if($showAddActivityModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showAddActivityModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Add Activity</h3>
                    <button wire:click="$set('showAddActivityModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="saveActivity" class="p-6 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Track <span class="text-red-500">*</span></label>
                            <select wire:model="addActivityTrack" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <option value="hunting">Hunting</option>
                                <option value="sport">Sport Shooting</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Activity Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="addActivityDate" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('addActivityDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Activity Type</label>
                        <select wire:model="addActivityTypeId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <option value="">Auto-select based on track</option>
                            @foreach($this->activityTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->track }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description / Reason <span class="text-red-500">*</span></label>
                        <textarea wire:model="addActivityDescription" rows="3" placeholder="e.g. Letter received — member was overseas, Medical certificate provided..." class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                        @error('addActivityDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                        <p class="text-xs text-blue-700 dark:text-blue-300">This activity will be created as <strong>approved</strong> and credited to the member's current activity year.</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="button" wire:click="$set('showAddActivityModal', false)" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-200 dark:border-zinc-600 transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">Add Activity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
