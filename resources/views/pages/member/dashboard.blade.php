<?php

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\MemberDocument;
use App\Models\EndorsementRequest;
use App\Models\Certificate;
use App\Models\NotificationDismissal;
use App\Models\ShootingActivity;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public bool $showDismissedNotifications = false;
    public bool $showWelcomeLetterModal = false;

    /**
     * Dismiss all currently visible rejected activities.
     */
    public function dismissRejectedActivitiesAlert(): void
    {
        $ids = $this->rejectedActivities->pluck('id')->toArray();
        if (!empty($ids)) {
            NotificationDismissal::dismissMany(auth()->id(), ShootingActivity::class, $ids);
        }
    }

    /**
     * Dismiss all currently visible rejected documents.
     */
    public function dismissRejectedDocumentsAlert(): void
    {
        $ids = $this->rejectedDocuments->pluck('id')->toArray();
        if (!empty($ids)) {
            NotificationDismissal::dismissMany(auth()->id(), MemberDocument::class, $ids);
        }
    }

    /**
     * Restore a single dismissed notification so it appears in the active alerts again.
     */
    public function restoreNotification(string $type, int $id): void
    {
        NotificationDismissal::restore(auth()->id(), $type, $id);
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function activeMembership()
    {
        return $this->user->activeMembership;
    }

    #[Computed]
    public function latestMembership()
    {
        return $this->user->memberships()->latest()->first();
    }

    #[Computed]
    public function certificates()
    {
        return $this->user->certificates()->valid()->with('certificateType')->latest()->take(3)->get();
    }

    #[Computed]
    public function hasPassedTest()
    {
        return $this->user->hasPassedKnowledgeTest();
    }

    #[Computed]
    public function requiresTest()
    {
        return $this->activeMembership?->type?->requires_knowledge_test ?? false;
    }

    #[Computed]
    public function availableMembershipTypes()
    {
        return MembershipType::active()
            ->displayOnLanding()
            ->ordered()
            ->get();
    }

    #[Computed]
    public function featuredMembershipType()
    {
        return MembershipType::active()->featured()->first();
    }

    #[Computed]
    public function needsMembership(): bool
    {
        return !$this->activeMembership && !$this->latestMembership;
    }

    #[Computed]
    public function expiredMembership(): ?Membership
    {
        $m = $this->activeMembership;
        if (! $m || ! $m->expires_at || $m->type->isLifetime()) {
            return null;
        }

        return $m->expires_at->isPast() ? $m : null;
    }

    #[Computed]
    public function pendingPaymentMembership()
    {
        // Get membership that is awaiting payment (applied status with payment reference)
        return $this->user->memberships()
            ->where('status', 'applied')
            ->whereNotNull('payment_reference')
            ->with('type')
            ->first();
    }

    #[Computed]
    public function bankAccount(): array
    {
        return SystemSetting::getBankAccount();
    }

    #[Computed]
    public function pendingDocuments()
    {
        return MemberDocument::where('user_id', $this->user->id)
            ->pending()
            ->with('documentType')
            ->get();
    }

    /**
     * Activities awaiting admin approval (status=pending only; approved/rejected are excluded).
     */
    #[Computed]
    public function pendingActivities()
    {
        return ShootingActivity::where('user_id', $this->user->id)
            ->where('status', 'pending')
            ->with('activityType')
            ->orderBy('activity_date', 'desc')
            ->get();
    }

    #[Computed]
    public function rejectedDocuments()
    {
        return MemberDocument::where('user_id', $this->user->id)
            ->where('status', 'rejected')
            ->whereNotNull('rejection_reason')
            ->with('documentType')
            ->orderBy('verified_at', 'desc')
            ->get();
    }

    #[Computed]
    public function rejectedActivities()
    {
        return ShootingActivity::where('user_id', $this->user->id)
            ->where('status', 'rejected')
            ->whereNotNull('rejection_reason')
            ->with('activityType')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * IDs that the user has dismissed.
     */
    #[Computed]
    public function dismissedDocumentIds()
    {
        return NotificationDismissal::getDismissedIds(auth()->id(), MemberDocument::class);
    }

    #[Computed]
    public function dismissedActivityIds()
    {
        return NotificationDismissal::getDismissedIds(auth()->id(), ShootingActivity::class);
    }

    /**
     * Active (non-dismissed) rejected items.
     */
    #[Computed]
    public function activeRejectedDocuments()
    {
        return $this->rejectedDocuments->reject(fn ($doc) => in_array($doc->id, $this->dismissedDocumentIds));
    }

    #[Computed]
    public function activeRejectedActivities()
    {
        return $this->rejectedActivities->reject(fn ($a) => in_array($a->id, $this->dismissedActivityIds));
    }

    /**
     * Dismissed rejected items (for the "Dismissed" section).
     */
    #[Computed]
    public function dismissedRejectedDocuments()
    {
        return $this->rejectedDocuments->filter(fn ($doc) => in_array($doc->id, $this->dismissedDocumentIds));
    }

    #[Computed]
    public function dismissedRejectedActivities()
    {
        return $this->rejectedActivities->filter(fn ($a) => in_array($a->id, $this->dismissedActivityIds));
    }

    #[Computed]
    public function hasDedicatedMembership(): bool
    {
        // Check if user has active membership that allows dedicated status
        // Either by allows_dedicated_status flag OR by having a dedicated_type set
        if (!$this->activeMembership) {
            return false;
        }
        
        $type = $this->activeMembership->type;
        if (!$type) {
            return false;
        }
        
        return $type->allows_dedicated_status || !empty($type->dedicated_type);
    }

    #[Computed]
    public function firearmCounts(): array
    {
        $userId = $this->user->id;
        $expired = \App\Models\UserFirearm::forUser($userId)->expired()->count();
        $expiring = \App\Models\UserFirearm::forUser($userId)->expiringSoon(90)->count();
        $renewal = \App\Models\UserFirearm::forUser($userId)->where('license_status', 'renewal_pending')->count();
        $total = \App\Models\UserFirearm::forUser($userId)->count();

        return [
            'expired' => $expired,
            'expiring' => $expiring,
            'renewal' => $renewal,
            'active' => max(0, $total - $expired - $expiring - $renewal),
            'total' => $total,
            'attention' => $expired + $expiring > 0,
        ];
    }

    #[Computed]
    public function activityCompliance(): array
    {
        $user = $this->user;
        $required = 2;

        // Compliance for the CURRENT year is proven by LAST year's approved activities.
        // This year's activities bank compliance for NEXT year.
        $summary = ShootingActivity::complianceSummary($user, $required);

        return [
            'approved' => $summary['qualifying_year']['total'],
            'required' => $required,
            'met' => $summary['is_compliant_now'],
            'banking' => $summary['banking_year']['total'],
            'qualifying_year' => $summary['qualifying_year']['year'],
            'banking_year' => $summary['banking_year']['year'],
        ];
    }

    #[Computed]
    public function endorsementEligibility()
    {
        // Only show endorsement eligibility for active members with dedicated status
        if (!$this->hasDedicatedMembership) {
            return null;
        }

        return EndorsementRequest::getEligibilitySummary($this->user);
    }

    #[Computed]
    public function showEndorsementStatus(): bool
    {
        // Show endorsement status section for all users with dedicated status membership
        return $this->hasDedicatedMembership;
    }

    #[Computed]
    public function allRequirementsComplete(): bool
    {
        if (!$this->endorsementEligibility) {
            return false;
        }
        
        return $this->endorsementEligibility['knowledge_test_passed'] 
            && $this->endorsementEligibility['documents_complete'] 
            && $this->endorsementEligibility['activities_met'];
    }

    /**
     * Check if the member is newly approved (approved within last 30 days).
     */
    #[Computed]
    public function isNewlyApproved(): bool
    {
        if (!$this->activeMembership) {
            return false;
        }

        return $this->activeMembership->approved_at
            && $this->activeMembership->approved_at->isAfter(now()->subDays(30));
    }

    /**
     * Get the membership certificate (good standing / membership type entitlement).
     * This excludes welcome letters and membership cards.
     */
    #[Computed]
    public function membershipCertificate()
    {
        return $this->user->certificates()
            ->valid()
            ->with('certificateType')
            ->whereHas('certificateType', fn ($q) => $q->whereNotIn('slug', ['welcome-letter', 'membership-card']))
            ->latest()
            ->first();
    }

    /**
     * Get the welcome letter certificate.
     */
    #[Computed]
    public function welcomeLetterCertificate()
    {
        return $this->user->certificates()
            ->with('certificateType')
            ->whereHas('certificateType', fn ($q) => $q->where('slug', 'welcome-letter'))
            ->latest()
            ->first();
    }

    /**
     * Get the QR code image URL for the member's digital card.
     */
    #[Computed]
    public function cardQrCodeUrl(): ?string
    {
        $membership = $this->activeMembership;
        if (!$membership) return null;

        $card = $this->user->certificates()
            ->whereHas('certificateType', fn ($q) => $q->where('slug', 'membership-card'))
            ->valid()
            ->latest()
            ->first();

        if (!$card || !$card->qr_code) return null;

        $verifyUrl = route('certificates.verify', ['qr_code' => $card->qr_code]);
        return \App\Helpers\QrCodeHelper::generateUrl($verifyUrl, 200);
    }

    /**
     * Check if the welcome letter prompt should be shown.
     */
    #[Computed]
    public function shouldShowWelcomeLetterPrompt(): bool
    {
        return $this->activeMembership
            && $this->welcomeLetterCertificate
            && is_null($this->user->welcome_letter_seen_at);
    }

    /**
     * Dismiss the welcome letter prompt.
     */
    public function dismissWelcomeLetterPrompt(): void
    {
        $this->user->update(['welcome_letter_seen_at' => now()]);
        $this->showWelcomeLetterModal = false;
    }

    /**
     * Open the welcome letter modal.
     */
    public function openWelcomeLetterModal(): void
    {
        $this->showWelcomeLetterModal = true;
    }

    /**
     * View and dismiss the welcome letter.
     */
    public function viewWelcomeLetter(): void
    {
        $this->user->update(['welcome_letter_seen_at' => now()]);
        $this->showWelcomeLetterModal = false;
        
        if ($this->welcomeLetterCertificate) {
            $this->redirect(route('certificates.show', $this->welcomeLetterCertificate), navigate: true);
        }
    }

    #[Computed]
    public function nextStep(): ?array
    {
        if ($this->needsMembership) {
            return [
                'key' => 'apply',
                'title' => 'Apply for Membership',
                'description' => 'Join NRAPA to access all member features including the Virtual Safe, Learning Center, and dedicated status support.',
                'action' => 'Browse Memberships',
                'route' => 'membership.apply',
                'color' => 'blue',
            ];
        }

        if ($this->pendingPaymentMembership) {
            return null;
        }

        if (! $this->activeMembership && $this->latestMembership?->status === 'applied') {
            return [
                'key' => 'awaiting_review',
                'title' => 'Application Under Review',
                'description' => 'Your membership application is being reviewed. You\'ll be notified once it\'s approved.',
                'action' => 'View Application',
                'route' => 'membership.index',
                'color' => 'amber',
                'waiting' => true,
            ];
        }

        if ($this->expiredMembership) {
            return null;
        }

        if ($this->activeMembership && ! $this->hasDedicatedMembership) {
            return [
                'key' => 'explore',
                'title' => 'Your Membership is Active',
                'description' => 'Explore the member portal — upload documents, manage your Virtual Safe, and access the Learning Center.',
                'action' => 'Explore',
                'route' => 'armoury.index',
                'color' => 'emerald',
            ];
        }

        if (! $this->hasDedicatedMembership) {
            return null;
        }

        if ($this->activeRejectedDocuments->count() > 0 || $this->activeRejectedActivities->count() > 0) {
            $total = $this->activeRejectedDocuments->count() + $this->activeRejectedActivities->count();
            return [
                'key' => 'fix_rejected',
                'title' => 'Action Required: Rejected Items',
                'description' => "You have {$total} rejected " . ($total === 1 ? 'item' : 'items') . " that need your attention before you can proceed. Review the details below and re-submit.",
                'action' => null,
                'route' => null,
                'color' => 'red',
            ];
        }

        $eligibility = $this->endorsementEligibility;
        if (! $eligibility) {
            return null;
        }

        if (! ($eligibility['knowledge_test_passed'] ?? false)) {
            return [
                'key' => 'knowledge_test',
                'title' => 'Complete the Knowledge Test',
                'description' => 'Pass the dedicated status knowledge test to unlock endorsement letters. This is a one-time requirement.',
                'action' => 'Take the Test',
                'route' => 'knowledge-test.index',
                'color' => 'blue',
            ];
        }

        if (! ($eligibility['documents_complete'] ?? false)) {
            $missing = $eligibility['missing_documents'] ?? [];
            $docNames = array_column($missing, 'name');
            $docList = count($docNames) > 0 ? implode(', ', $docNames) : 'required documents';
            return [
                'key' => 'documents',
                'title' => 'Upload Required Documents',
                'description' => "Missing: {$docList}. Documents must be verified before endorsement requests can be approved.",
                'action' => 'Upload Documents',
                'route' => 'documents.index',
                'color' => 'blue',
            ];
        }

        if (! ($eligibility['activities_met'] ?? false)) {
            $approved = $eligibility['activity_details']['approved_count'] ?? 0;
            $required = $eligibility['activity_details']['required'] ?? 2;
            $remaining = max(0, $required - $approved);
            return [
                'key' => 'activities',
                'title' => 'Log Shooting Activities',
                'description' => "You need {$remaining} more approved " . ($remaining === 1 ? 'activity' : 'activities') . " this year to maintain your dedicated status ({$approved}/{$required} completed).",
                'action' => 'Submit Activity',
                'route' => 'activities.submit',
                'color' => 'blue',
            ];
        }

        return [
            'key' => 'compliant',
            'title' => 'You\'re Fully Compliant',
            'description' => 'All dedicated status requirements are met. You can request endorsement letters for Section 16 firearm licence applications.',
            'action' => 'Request Endorsement',
            'route' => 'member.endorsements.create',
            'color' => 'emerald',
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Welcome back, {{ $this->user->name }}!</h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Manage your NRAPA membership, certificates, and compliance requirements.
                </p>
            </div>
            @if(auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN))
            <a 
                href="{{ route('admin.dashboard') }}" 
                wire:navigate
                class="px-4 py-2 text-sm font-medium text-white bg-nrapa-blue rounded-lg hover:bg-nrapa-blue-dark transition-colors inline-flex items-center gap-2 whitespace-nowrap self-start sm:self-center flex-shrink-0"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                Admin Dashboard
            </a>
            @endif
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">

    {{-- Welcome Letter Prompt (first login after approval) --}}
    @if($this->shouldShowWelcomeLetterPrompt)
    <div class="rounded-xl border-2 border-emerald-300 bg-gradient-to-r from-emerald-50 to-teal-50 p-6 dark:border-emerald-600 dark:from-emerald-900/20 dark:to-teal-900/20">
        <div class="flex items-start gap-4">
            <div class="flex size-14 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-200 dark:bg-emerald-800">
                <svg class="size-7 text-emerald-700 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-emerald-800 dark:text-emerald-200">Welcome to NRAPA!</h3>
                <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                    Your membership has been approved. A personalised welcome letter has been prepared for you with your membership details and important information.
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button wire:click="openWelcomeLetterModal"
                        class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-5 py-2.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        View Welcome Letter
                    </button>
                    <button wire:click="dismissWelcomeLetterPrompt"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-900 dark:text-emerald-300 dark:hover:text-emerald-100 transition-colors">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Dismiss
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Missing Phone Number Prompt --}}
    @if(empty($this->user->phone))
    <div class="rounded-xl border-2 border-amber-300 dark:border-amber-600 bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 p-6">
        <div class="flex items-start gap-4">
            <div class="flex size-14 flex-shrink-0 items-center justify-center rounded-xl bg-amber-200 dark:bg-amber-800">
                <svg class="size-7 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-amber-800 dark:text-amber-200">Phone Number Required</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    Please add your phone number to your profile. We need a contact number on file for all members.
                </p>
                <div class="mt-4">
                    <a href="{{ route('profile.edit') }}" wire:navigate
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-600 hover:bg-amber-700 px-5 py-2.5 text-sm font-medium text-white transition-colors">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                        Update Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Expired Membership Alert --}}
    @if($this->expiredMembership)
    @php $expMembership = $this->expiredMembership; @endphp
    <div class="rounded-xl border-2 border-red-300 dark:border-red-700 bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 p-6">
        <div class="flex items-start gap-4">
            <div class="flex size-14 flex-shrink-0 items-center justify-center rounded-xl bg-red-200 dark:bg-red-800">
                <svg class="size-7 text-red-700 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-red-800 dark:text-red-200">Your Membership Has Expired</h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                    Your <strong>{{ $expMembership->type->name }}</strong> membership expired on
                    <strong>{{ $expMembership->expires_at->format('d M Y') }}</strong>.
                    Renew now to keep your membership active and retain access to endorsements, certificates, and all member benefits.
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <a href="{{ route('membership.index') }}" wire:navigate
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 px-5 py-2.5 text-sm font-medium text-white transition-colors">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Renew Membership
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Welcome Letter Modal --}}
    @if($showWelcomeLetterModal && $this->welcomeLetterCertificate)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-init="document.body.classList.add('overflow-hidden')" x-on:remove="document.body.classList.remove('overflow-hidden')">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="dismissWelcomeLetterPrompt"></div>
        
        {{-- Modal --}}
        <div class="relative w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl dark:bg-zinc-800">
            {{-- Close button --}}
            <button wire:click="dismissWelcomeLetterPrompt" class="absolute right-4 top-4 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Content --}}
            <div class="text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                    <svg class="size-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Welcome to NRAPA!</h2>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Congratulations, <span class="font-semibold">{{ $this->user->name }}</span>! Your membership has been approved.
                </p>

                @if($this->activeMembership)
                <div class="mt-6 rounded-xl bg-zinc-50 dark:bg-zinc-700/50 p-4 text-left">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">Member Number</span>
                            <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $this->activeMembership->membership_number }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">Membership Type</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $this->activeMembership->type->name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">Status</span>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                        </div>
                    </div>
                </div>
                @endif

                <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                    Your personalised welcome letter contains important details about your membership, including your rights, responsibilities, and how to get the most out of your NRAPA membership.
                </p>

                <div class="mt-6 flex flex-col gap-3">
                    <button wire:click="viewWelcomeLetter"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-nrapa-blue px-5 py-3 text-sm font-semibold text-white hover:bg-nrapa-blue-dark transition-colors">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12"/>
                        </svg>
                        View My Welcome Letter
                    </button>
                    <button wire:click="dismissWelcomeLetterPrompt"
                        class="w-full inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-5 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                        Maybe Later
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Payment Awaiting Confirmation Banner --}}
    @if($this->pendingPaymentMembership)
    <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-6 dark:border-amber-600 dark:bg-amber-900/20">
        <div class="flex items-start gap-4">
            <div class="flex size-12 flex-shrink-0 items-center justify-center rounded-lg bg-amber-200 dark:bg-amber-800">
                <svg class="size-6 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Payment Awaiting Confirmation</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    Your {{ $this->pendingPaymentMembership->type->name }} membership application is pending payment confirmation.
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    {{-- Payment Reference --}}
                    <div>
                        <p class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-1">YOUR PAYMENT REFERENCE</p>
                        <div 
                            x-data="{ copied: false }"
                            x-on:click="navigator.clipboard.writeText('{{ $this->pendingPaymentMembership->payment_reference }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="cursor-pointer"
                        >
                            <div class="flex items-center justify-between gap-2 p-3 bg-white dark:bg-zinc-800 rounded-lg border-2 border-dashed border-amber-400 dark:border-amber-600 hover:border-amber-500 transition-colors">
                                <span class="text-lg font-mono font-bold text-zinc-900 dark:text-white">{{ $this->pendingPaymentMembership->payment_reference }}</span>
                                <div class="flex items-center gap-1 text-amber-600 dark:text-amber-400">
                                    <svg x-show="!copied" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <svg x-show="copied" x-cloak class="size-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="copied ? 'Copied!' : 'Copy'" class="text-xs font-medium"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Amount Due --}}
                    <div>
                        <p class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-1">AMOUNT TO PAY</p>
                        <div class="p-3 bg-white dark:bg-zinc-800 rounded-lg border border-amber-200 dark:border-amber-700">
                            <span class="text-lg font-bold text-amber-800 dark:text-amber-200">R{{ number_format($this->pendingPaymentMembership->amount_due, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Bank Account Details --}}
                <div class="mt-4 bg-white/50 dark:bg-zinc-800/50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-3">Bank Account Details</h4>
                    <dl class="grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                        <div class="flex items-baseline gap-2">
                            <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Bank:</dt>
                            <dd class="font-medium text-amber-800 dark:text-amber-200">{{ $this->bankAccount['bank_name'] ?: 'To be confirmed' }}</dd>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Account Name:</dt>
                            <dd class="font-medium text-amber-800 dark:text-amber-200">{{ $this->bankAccount['account_name'] ?: 'To be confirmed' }}</dd>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Account Number:</dt>
                            <dd class="font-mono font-medium text-amber-800 dark:text-amber-200">{{ $this->bankAccount['account_number'] ?: 'To be confirmed' }}</dd>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Branch Code:</dt>
                            <dd class="font-mono font-medium text-amber-800 dark:text-amber-200">{{ $this->bankAccount['branch_code'] ?: 'To be confirmed' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <a href="{{ route('membership.show', $this->pendingPaymentMembership) }}" wire:navigate 
                       class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 transition-colors">
                        View Full Details
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <p class="text-xs text-amber-600 dark:text-amber-400">
                        Your membership will be activated once payment is confirmed (1-3 business days).
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- YOUR NEXT STEP — Primary CTA --}}
    @if($this->nextStep)
    @php $step = $this->nextStep; @endphp
    <div class="rounded-xl border-2 overflow-hidden
        {{ $step['color'] === 'emerald' ? 'border-emerald-300 dark:border-emerald-700' : '' }}
        {{ $step['color'] === 'blue' ? 'border-nrapa-blue/40 dark:border-nrapa-blue/50' : '' }}
        {{ $step['color'] === 'amber' ? 'border-amber-300 dark:border-amber-700' : '' }}
        {{ $step['color'] === 'red' ? 'border-red-300 dark:border-red-700' : '' }}
    ">
        <div class="px-5 py-2.5
            {{ $step['color'] === 'emerald' ? 'bg-emerald-600 dark:bg-emerald-700' : '' }}
            {{ $step['color'] === 'blue' ? 'bg-nrapa-blue dark:bg-nrapa-blue-dark' : '' }}
            {{ $step['color'] === 'amber' ? 'bg-amber-500 dark:bg-amber-600' : '' }}
            {{ $step['color'] === 'red' ? 'bg-red-600 dark:bg-red-700' : '' }}
        ">
            <p class="text-xs font-bold uppercase tracking-widest text-white/90">Your Next Step</p>
        </div>
        <div class="p-5 sm:p-6
            {{ $step['color'] === 'emerald' ? 'bg-emerald-50 dark:bg-emerald-900/20' : '' }}
            {{ $step['color'] === 'blue' ? 'bg-blue-50 dark:bg-blue-900/15' : '' }}
            {{ $step['color'] === 'amber' ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}
            {{ $step['color'] === 'red' ? 'bg-red-50 dark:bg-red-900/20' : '' }}
        ">
            <div class="flex items-start gap-4">
                <div class="flex size-12 flex-shrink-0 items-center justify-center rounded-xl
                    {{ $step['color'] === 'emerald' ? 'bg-emerald-200 dark:bg-emerald-800' : '' }}
                    {{ $step['color'] === 'blue' ? 'bg-blue-200 dark:bg-blue-800' : '' }}
                    {{ $step['color'] === 'amber' ? 'bg-amber-200 dark:bg-amber-800' : '' }}
                    {{ $step['color'] === 'red' ? 'bg-red-200 dark:bg-red-800' : '' }}
                ">
                    @if($step['key'] === 'compliant' || $step['key'] === 'explore')
                        <svg class="size-6 {{ $step['color'] === 'emerald' ? 'text-emerald-700 dark:text-emerald-300' : 'text-blue-700 dark:text-blue-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @elseif($step['key'] === 'knowledge_test')
                        <svg class="size-6 text-blue-700 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342"/>
                        </svg>
                    @elseif($step['key'] === 'documents')
                        <svg class="size-6 text-blue-700 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    @elseif($step['key'] === 'activities')
                        <svg class="size-6 text-blue-700 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    @elseif($step['key'] === 'fix_rejected')
                        <svg class="size-6 text-red-700 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    @elseif($step['key'] === 'awaiting_review')
                        <svg class="size-6 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @else
                        <svg class="size-6 text-blue-700 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    @endif
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold
                        {{ $step['color'] === 'emerald' ? 'text-emerald-800 dark:text-emerald-200' : '' }}
                        {{ $step['color'] === 'blue' ? 'text-zinc-900 dark:text-white' : '' }}
                        {{ $step['color'] === 'amber' ? 'text-amber-800 dark:text-amber-200' : '' }}
                        {{ $step['color'] === 'red' ? 'text-red-800 dark:text-red-200' : '' }}
                    ">{{ $step['title'] }}</h3>
                    <p class="mt-1 text-sm
                        {{ $step['color'] === 'emerald' ? 'text-emerald-700 dark:text-emerald-300' : '' }}
                        {{ $step['color'] === 'blue' ? 'text-zinc-600 dark:text-zinc-400' : '' }}
                        {{ $step['color'] === 'amber' ? 'text-amber-700 dark:text-amber-300' : '' }}
                        {{ $step['color'] === 'red' ? 'text-red-700 dark:text-red-300' : '' }}
                    ">{{ $step['description'] }}</p>
                    @if($step['action'] ?? null)
                    <div class="mt-4">
                        <a href="{{ route($step['route']) }}" wire:navigate
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition-colors
                                {{ $step['color'] === 'emerald' ? 'bg-emerald-600 hover:bg-emerald-700' : '' }}
                                {{ $step['color'] === 'blue' ? 'bg-nrapa-blue hover:bg-nrapa-blue-dark' : '' }}
                                {{ $step['color'] === 'amber' ? 'bg-amber-600 hover:bg-amber-700' : '' }}
                            ">
                            {{ $step['action'] }}
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Action Required Notifications --}}
    @php
        $activeActivities = $this->activeRejectedActivities;
        $activeDocuments = $this->activeRejectedDocuments;
        $dismissedActivities = $this->dismissedRejectedActivities;
        $dismissedDocuments = $this->dismissedRejectedDocuments;
        $totalDismissed = $dismissedActivities->count() + $dismissedDocuments->count();
    @endphp

    @if($this->pendingDocuments->count() > 0 || $this->pendingActivities->count() > 0 || $activeDocuments->count() > 0 || $activeActivities->count() > 0 || $this->showEndorsementStatus || $totalDismissed > 0)
    <div class="space-y-4">
        {{-- Rejected Activities Alert (active / not dismissed) --}}
        @if($activeActivities->count() > 0)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20 relative">
            <button wire:click="dismissRejectedActivitiesAlert" 
                class="absolute top-3 right-3 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 transition-colors"
                title="Dismiss this alert">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="flex items-start gap-4 pr-8">
                <div class="flex size-10 flex-shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/50">
                    <svg class="size-5 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-800 dark:text-red-200">Activities Rejected</h3>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                        You have {{ $activeActivities->count() }} activit{{ $activeActivities->count() > 1 ? 'ies' : 'y' }} that {{ $activeActivities->count() > 1 ? 'were' : 'was' }} rejected and need attention:
                    </p>
                    <ul class="mt-2 space-y-2 text-sm text-red-700 dark:text-red-300">
                        @foreach($activeActivities->take(3) as $activity)
                            <li class="flex flex-col gap-1 rounded-lg bg-red-100/50 dark:bg-red-900/30 p-2">
                                <div class="flex items-center gap-2 font-medium">
                                    <svg class="size-3" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3"/>
                                    </svg>
                                    {{ $activity->activityType?->name ?? 'Activity' }} - {{ $activity->activity_date->format('d M Y') }}
                                    <span class="text-xs text-red-600 dark:text-red-400 font-normal">(rejected {{ $activity->updated_at->diffForHumans() }})</span>
                                </div>
                                @if($activity->rejection_reason)
                                    <p class="ml-5 text-xs text-red-600 dark:text-red-400 italic">
                                        "{{ Str::limit($activity->rejection_reason, 100) }}"
                                    </p>
                                @endif
                            </li>
                        @endforeach
                        @if($activeActivities->count() > 3)
                            <li class="text-xs italic">and {{ $activeActivities->count() - 3 }} more...</li>
                        @endif
                    </ul>
                    <a href="{{ route('activities.index') }}" wire:navigate 
                        class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-red-800 hover:text-red-900 dark:text-red-200 dark:hover:text-red-100">
                        View All Activities
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Rejected Documents Alert (active / not dismissed) --}}
        @if($activeDocuments->count() > 0)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20 relative">
            <button wire:click="dismissRejectedDocumentsAlert" 
                class="absolute top-3 right-3 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 transition-colors"
                title="Dismiss this alert">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="flex items-start gap-4 pr-8">
                <div class="flex size-10 flex-shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/50">
                    <svg class="size-5 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-800 dark:text-red-200">Documents Rejected</h3>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                        You have {{ $activeDocuments->count() }} document{{ $activeDocuments->count() > 1 ? 's' : '' }} that {{ $activeDocuments->count() > 1 ? 'were' : 'was' }} rejected and need attention:
                    </p>
                    <ul class="mt-2 space-y-2 text-sm text-red-700 dark:text-red-300">
                        @foreach($activeDocuments->take(3) as $doc)
                            <li class="flex flex-col gap-1 rounded-lg bg-red-100/50 dark:bg-red-900/30 p-2">
                                <div class="flex items-center gap-2 font-medium">
                                    <svg class="size-3" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3"/>
                                    </svg>
                                    {{ $doc->documentType?->name ?? 'Document' }}
                                    <span class="text-xs text-red-600 dark:text-red-400 font-normal">(rejected {{ $doc->verified_at?->diffForHumans() ?? 'recently' }})</span>
                                </div>
                                @if($doc->rejection_reason)
                                    <p class="ml-5 text-xs text-red-600 dark:text-red-400 italic">
                                        "{{ Str::limit($doc->rejection_reason, 100) }}"
                                    </p>
                                @endif
                            </li>
                        @endforeach
                        @if($activeDocuments->count() > 3)
                            <li class="text-xs italic">and {{ $activeDocuments->count() - 3 }} more...</li>
                        @endif
                    </ul>
                    <a href="{{ route('documents.index') }}" wire:navigate 
                        class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-red-800 hover:text-red-900 dark:text-red-200 dark:hover:text-red-100">
                        View All Documents
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Pending Activities Alert (waiting for admin approval; approved activities are excluded) --}}
        @if($this->pendingActivities->count() > 0)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-start gap-4">
                <div class="flex size-10 flex-shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/50">
                    <svg class="size-5 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-amber-800 dark:text-amber-200">Activities Waiting for Approval</h3>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                        You have {{ $this->pendingActivities->count() }} activit{{ $this->pendingActivities->count() > 1 ? 'ies' : 'y' }} waiting for admin verification:
                    </p>
                    <ul class="mt-2 space-y-1 text-sm text-amber-700 dark:text-amber-300">
                        @foreach($this->pendingActivities->take(3) as $activity)
                            <li class="flex items-center gap-2">
                                <svg class="size-3" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3"/>
                                </svg>
                                {{ $activity->activityType?->name ?? 'Activity' }} - {{ $activity->activity_date->format('d M Y') }}
                                <span class="text-xs text-amber-600 dark:text-amber-400">(submitted {{ $activity->created_at->diffForHumans() }})</span>
                            </li>
                        @endforeach
                        @if($this->pendingActivities->count() > 3)
                            <li class="text-xs italic">and {{ $this->pendingActivities->count() - 3 }} more...</li>
                        @endif
                    </ul>
                    <a href="{{ route('activities.index') }}" wire:navigate 
                        class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-amber-800 hover:text-amber-900 dark:text-amber-200 dark:hover:text-amber-100">
                        View Activities
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Pending Documents Alert --}}
        @if($this->pendingDocuments->count() > 0)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-start gap-4">
                <div class="flex size-10 flex-shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/50">
                    <svg class="size-5 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-amber-800 dark:text-amber-200">Documents Pending Approval</h3>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                        You have {{ $this->pendingDocuments->count() }} document{{ $this->pendingDocuments->count() > 1 ? 's' : '' }} waiting for admin verification:
                    </p>
                    <ul class="mt-2 space-y-1 text-sm text-amber-700 dark:text-amber-300">
                        @foreach($this->pendingDocuments->take(3) as $doc)
                            <li class="flex items-center gap-2">
                                <svg class="size-3" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3"/>
                                </svg>
                                {{ $doc->documentType?->name ?? 'Document' }}
                                <span class="text-xs text-amber-600 dark:text-amber-400">(uploaded {{ $doc->created_at->diffForHumans() }})</span>
                            </li>
                        @endforeach
                        @if($this->pendingDocuments->count() > 3)
                            <li class="text-xs italic">and {{ $this->pendingDocuments->count() - 3 }} more...</li>
                        @endif
                    </ul>
                    <a href="{{ route('documents.index') }}" wire:navigate 
                        class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-amber-800 hover:text-amber-900 dark:text-amber-200 dark:hover:text-amber-100">
                        View Documents
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Dedicated Status Progress Card --}}
        @if($this->showEndorsementStatus)
        @php
            $eligibility = $this->endorsementEligibility;
            $knowledgeTestPassed = $eligibility['knowledge_test_passed'] ?? false;
            $documentsComplete = $eligibility['documents_complete'] ?? false;
            $activitiesMet = $eligibility['activities_met'] ?? false;
            $approvedCount = $eligibility['activity_details']['approved_count'] ?? 0;
            $requiredCount = $eligibility['activity_details']['required'] ?? 2;
        @endphp
        <div class="rounded-xl border border-zinc-200 bg-white p-5 sm:p-6 dark:border-zinc-700 dark:bg-zinc-800">
            {{-- Header --}}
            <div class="flex items-start gap-3 mb-4">
                <div class="flex size-10 flex-shrink-0 items-center justify-center rounded-lg bg-nrapa-orange/10 dark:bg-nrapa-orange/20">
                    <svg class="size-5 text-nrapa-orange dark:text-nrapa-orange" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Dedicated Status Requirements</h3>
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                        @if($this->allRequirementsComplete)
                            All requirements met - your endorsement requests can be approved
                        @else
                            Endorsement letters will not be approved until all criteria below are met
                        @endif
                    </p>
                </div>
            </div>

            {{-- All Complete Banner --}}
            @if($this->allRequirementsComplete)
            <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3">
                <div class="flex items-center gap-2">
                    <svg class="size-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">All requirements met - eligible for endorsement approval</span>
                </div>
            </div>
            @endif

            {{-- Requirements List --}}
            <div class="space-y-3">
                {{-- Knowledge Test --}}
                <div class="flex items-start sm:items-center justify-between gap-2 py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-start sm:items-center gap-2 sm:gap-3 min-w-0">
                        <svg class="size-5 text-zinc-400 dark:text-zinc-500 flex-shrink-0 mt-0.5 sm:mt-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                        <div class="min-w-0">
                            @if($knowledgeTestPassed)
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Knowledge Test</span>
                            @else
                                <a href="{{ route('knowledge-test.index') }}" wire:navigate class="text-sm text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:underline">
                                    Knowledge Test
                                </a>
                            @endif
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">One-time requirement</p>
                        </div>
                    </div>
                    @if($knowledgeTestPassed)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                            <svg class="size-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Complete
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 dark:bg-amber-900/40 px-2.5 py-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                            <svg class="size-3" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3"/>
                            </svg>
                            Required
                        </span>
                    @endif
                </div>

                {{-- Documents --}}
                @php
                    $idSlugs = ['identity-document', 'id-document'];
                    $idTypeIds = \App\Models\DocumentType::whereIn('slug', $idSlugs)->pluck('id');
                    $idDoc = $idTypeIds->isNotEmpty()
                        ? \App\Models\MemberDocument::where('user_id', $this->user->id)
                            ->whereIn('document_type_id', $idTypeIds)
                            ->orderByRaw("FIELD(status, 'verified', 'pending', 'rejected') ASC")
                            ->first()
                        : null;

                    $docStatusLabel = function ($doc) {
                        if (!$doc) return 'Not uploaded';
                        return match($doc->status) {
                            'verified' => 'Verified',
                            'pending'  => 'Uploaded, pending verification',
                            'rejected' => 'Rejected',
                            default    => ucfirst($doc->status),
                        };
                    };
                @endphp
                <div class="flex items-start sm:items-center justify-between gap-2 py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-start sm:items-center gap-2 sm:gap-3 min-w-0">
                        <svg class="size-5 text-zinc-400 dark:text-zinc-500 flex-shrink-0 mt-0.5 sm:mt-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <div class="min-w-0">
                            @if($documentsComplete)
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Required Documents</span>
                            @else
                                <a href="{{ route('documents.index') }}" wire:navigate class="text-sm text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:underline">
                                    Required Documents
                                </a>
                            @endif
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                ID: {{ $docStatusLabel($idDoc) }}
                            </p>
                        </div>
                    </div>
                    @if($documentsComplete)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                            <svg class="size-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Valid
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 dark:bg-amber-900/40 px-2.5 py-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                            <svg class="size-3" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3"/>
                            </svg>
                            Update Required
                        </span>
                    @endif
                </div>

                {{-- Activities --}}
                <div class="flex items-start sm:items-center justify-between gap-2 py-2">
                    <div class="flex items-start sm:items-center gap-2 sm:gap-3 min-w-0">
                        <svg class="size-5 text-zinc-400 dark:text-zinc-500 flex-shrink-0 mt-0.5 sm:mt-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <div class="min-w-0">
                            @if($activitiesMet)
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Shooting Activities</span>
                            @else
                                <a href="{{ route('activities.index') }}" wire:navigate class="text-sm text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:underline">
                                    Shooting Activities
                                </a>
                            @endif
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $requiredCount }} approved per year</p>
                        </div>
                    </div>
                    @if($activitiesMet)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                            <svg class="size-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ $approvedCount }}/{{ $requiredCount }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 dark:bg-amber-900/40 px-2.5 py-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                            <svg class="size-3" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3"/>
                            </svg>
                            {{ $approvedCount }}/{{ $requiredCount }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Warning if not all complete --}}
            @if(!$this->allRequirementsComplete)
            <div class="mt-4 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <div class="flex items-start gap-2">
                    <svg class="size-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Requirements Not Met</p>
                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                            @if(!$knowledgeTestPassed)
                                Complete the knowledge test (one-time requirement).
                            @elseif(!$documentsComplete)
                                @php
                                    $missingDocs = $eligibility['missing_documents'] ?? [];
                                    $missingDocNames = array_column($missingDocs, 'name');
                                @endphp
                                @if(count($missingDocNames) > 0)
                                    Missing required documents: {{ implode(', ', $missingDocNames) }}.
                                    Documents must be verified by an admin before they count toward requirements.
                                @endif
                            @elseif(!$activitiesMet)
                                Submit {{ $requiredCount }} activities per year to maintain dedicated status. You have {{ $approvedCount }} approved.
                            @endif
                        </p>
                        @if(!$activitiesMet)
                            <div class="mt-3">
                                <a href="{{ route('activities.submit') }}" wire:navigate 
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white text-sm font-medium rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Submit Activity Now
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Action Buttons --}}
            <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-700 flex flex-col sm:flex-row gap-3">
                @if(in_array($this->activeMembership?->type?->dedicated_type, ['hunter', 'sport', 'both']))
                <a href="{{ route('member.endorsements.create') }}" wire:navigate 
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-nrapa-blue hover:bg-nrapa-blue-dark text-white text-sm font-medium transition-colors">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Request Endorsement
                </a>
                @endif
                <a href="{{ route('member.endorsements.index') }}" wire:navigate 
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    Manage Dedicated Status
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
        @endif

        {{-- Dismissed Notifications Toggle --}}
        @if($totalDismissed > 0)
        <div>
            <button wire:click="$toggle('showDismissedNotifications')"
                class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors">
                <svg class="size-4 transition-transform {{ $showDismissedNotifications ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
                {{ $totalDismissed }} dismissed notification{{ $totalDismissed > 1 ? 's' : '' }}
            </button>

            @if($showDismissedNotifications)
            <div class="mt-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-3">Dismissed Notifications</h4>
                <div class="space-y-2">
                    @foreach($dismissedActivities as $activity)
                        <div class="flex items-center justify-between rounded-lg bg-white dark:bg-zinc-800 px-4 py-2.5 border border-zinc-100 dark:border-zinc-700">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:text-red-300 whitespace-nowrap">Activity</span>
                                <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">
                                    {{ $activity->activityType?->name ?? 'Activity' }} - {{ $activity->activity_date->format('d M Y') }}
                                </span>
                                @if($activity->rejection_reason)
                                    <span class="text-xs text-zinc-400 italic truncate hidden sm:inline">"{{ Str::limit($activity->rejection_reason, 60) }}"</span>
                                @endif
                            </div>
                            <button wire:click="restoreNotification('{{ addslashes(App\Models\ShootingActivity::class) }}', {{ $activity->id }})"
                                class="ml-2 text-xs text-nrapa-blue hover:text-nrapa-blue-dark font-medium whitespace-nowrap">
                                Show Again
                            </button>
                        </div>
                    @endforeach

                    @foreach($dismissedDocuments as $doc)
                        <div class="flex items-center justify-between rounded-lg bg-white dark:bg-zinc-800 px-4 py-2.5 border border-zinc-100 dark:border-zinc-700">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:text-red-300 whitespace-nowrap">Document</span>
                                <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">
                                    {{ $doc->documentType?->name ?? 'Document' }}
                                </span>
                                @if($doc->rejection_reason)
                                    <span class="text-xs text-zinc-400 italic truncate hidden sm:inline">"{{ Str::limit($doc->rejection_reason, 60) }}"</span>
                                @endif
                            </div>
                            <button wire:click="restoreNotification('{{ addslashes(App\Models\MemberDocument::class) }}', {{ $doc->id }})"
                                class="ml-2 text-xs text-nrapa-blue hover:text-nrapa-blue-dark font-medium whitespace-nowrap">
                                Show Again
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- Status Summary Row --}}
    @if($this->activeMembership)
    @php
        $fc = $this->firearmCounts;
        $ac = $this->activityCompliance;
        $isLifetime = $this->activeMembership->type->isLifetime();
        $memberPaidUp = $this->activeMembership && ($isLifetime || !$this->activeMembership->expires_at || $this->activeMembership->expires_at->isFuture());
    @endphp
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <!-- Membership Status -->
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs uppercase tracking-wider font-semibold text-zinc-500">Membership Status</h3>
                <span class="text-xs px-2 py-1 rounded-full font-medium {{ $memberPaidUp ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                    {{ $memberPaidUp ? 'Paid-up' : 'Expired' }}
                </span>
            </div>
            <div class="space-y-2">
                <div>
                    <span class="text-xs uppercase tracking-wider text-zinc-500">Paid-up till</span>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $isLifetime ? 'Lifetime' : ($this->activeMembership->expires_at?->format('M d, Y') ?? 'N/A') }}</p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wider text-zinc-500">Member since</span>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $this->activeMembership->approved_at?->format('M d, Y') ?? $this->activeMembership->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Firearm Status -->
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs uppercase tracking-wider font-semibold text-zinc-500">Firearm Status</h3>
                @if($fc['attention'])
                    <span class="text-xs px-2 py-1 rounded-full font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Attention</span>
                @endif
            </div>
            <div class="grid grid-cols-4 gap-2 text-center">
                <div>
                    <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ $fc['expired'] }}</p>
                    <p class="text-[10px] uppercase tracking-wider text-zinc-500">Expired</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $fc['expiring'] }}</p>
                    <p class="text-[10px] uppercase tracking-wider text-zinc-500">Expiring</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $fc['renewal'] }}</p>
                    <p class="text-[10px] uppercase tracking-wider text-zinc-500">Renewal</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $fc['active'] }}</p>
                    <p class="text-[10px] uppercase tracking-wider text-zinc-500">Active</p>
                </div>
            </div>
        </div>

        <!-- Compliance Status -->
        @if($this->hasDedicatedMembership)
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs uppercase tracking-wider font-semibold text-zinc-500">Compliance Status</h3>
                <span class="text-xs px-2 py-1 rounded-full font-medium {{ $ac['met'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                    {{ $ac['met'] ? 'Complete' : 'Incomplete' }}
                </span>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold {{ $ac['met'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $ac['approved'] }} / {{ $ac['required'] }}</p>
                <p class="text-xs uppercase tracking-wider text-zinc-500 mt-1">Sport Activities OCT {{ now()->year }}</p>
            </div>
        </div>
        @else
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs uppercase tracking-wider font-semibold text-zinc-500">Quick Stats</h3>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-nrapa-blue dark:text-nrapa-blue-light">{{ $fc['total'] }}</p>
                <p class="text-xs uppercase tracking-wider text-zinc-500 mt-1">Firearms in Safe</p>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Quick Links --}}
    @if($this->activeMembership)
    <div>
        <h2 class="text-xs uppercase tracking-wider font-semibold text-zinc-500 mb-3">Quick Links</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <a href="{{ route('certificates.index') }}" wire:navigate
               class="group rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md hover:border-nrapa-blue/30 dark:hover:border-nrapa-blue/30 transition p-4 text-center">
                <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-xl bg-nrapa-blue/10 dark:bg-nrapa-blue/20 group-hover:bg-nrapa-blue/20 transition">
                    <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                </div>
                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Membership Card</span>
            </a>

            <a href="{{ route('profile.edit') }}" wire:navigate
               class="group relative rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md hover:border-nrapa-blue/30 dark:hover:border-nrapa-blue/30 transition p-4 text-center">
                @if(empty($this->user->phone))
                    <span class="absolute -top-1 -right-1 flex size-3"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex size-3 rounded-full bg-amber-500"></span></span>
                @endif
                <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-xl bg-nrapa-blue/10 dark:bg-nrapa-blue/20 group-hover:bg-nrapa-blue/20 transition">
                    <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">My Profile</span>
            </a>

            <a href="{{ route('armoury.index') }}" wire:navigate
               class="group relative rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md hover:border-nrapa-blue/30 dark:hover:border-nrapa-blue/30 transition p-4 text-center">
                @if($this->firearmCounts['total'] > 0)
                    <span class="absolute -top-1.5 -right-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-nrapa-blue text-white">{{ $this->firearmCounts['total'] }}</span>
                @endif
                <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-xl bg-nrapa-blue/10 dark:bg-nrapa-blue/20 group-hover:bg-nrapa-blue/20 transition">
                    <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Virtual Safe</span>
            </a>

            @if($this->hasDedicatedMembership)
            <a href="{{ route('activities.index') }}" wire:navigate
               class="group relative rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md hover:border-nrapa-blue/30 dark:hover:border-nrapa-blue/30 transition p-4 text-center">
                <span class="absolute -top-1.5 -right-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $this->activityCompliance['met'] ? 'bg-emerald-500' : 'bg-amber-500' }} text-white">{{ $this->activityCompliance['approved'] }}/{{ $this->activityCompliance['required'] }}</span>
                <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-xl bg-nrapa-blue/10 dark:bg-nrapa-blue/20 group-hover:bg-nrapa-blue/20 transition">
                    <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Activities</span>
            </a>

            <a href="{{ route('member.endorsements.index') }}" wire:navigate
               class="group rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md hover:border-nrapa-blue/30 dark:hover:border-nrapa-blue/30 transition p-4 text-center">
                <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-xl bg-nrapa-blue/10 dark:bg-nrapa-blue/20 group-hover:bg-nrapa-blue/20 transition">
                    <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Endorsements</span>
            </a>
            @endif

            <a href="{{ route('documents.index') }}" wire:navigate
               class="group rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md hover:border-nrapa-blue/30 dark:hover:border-nrapa-blue/30 transition p-4 text-center">
                <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-xl bg-nrapa-blue/10 dark:bg-nrapa-blue/20 group-hover:bg-nrapa-blue/20 transition">
                    <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Documents</span>
            </a>

            <a href="{{ route('learning.index') }}" wire:navigate
               class="group rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md hover:border-nrapa-blue/30 dark:hover:border-nrapa-blue/30 transition p-4 text-center">
                <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-xl bg-nrapa-blue/10 dark:bg-nrapa-blue/20 group-hover:bg-nrapa-blue/20 transition">
                    <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Learning Center</span>
            </a>

            <a href="{{ route('certificates.index') }}" wire:navigate
               class="group relative rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md hover:border-nrapa-blue/30 dark:hover:border-nrapa-blue/30 transition p-4 text-center">
                @if($this->certificates->count() > 0)
                    <span class="absolute -top-1.5 -right-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-500 text-white">{{ $this->certificates->count() }}</span>
                @endif
                <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-xl bg-nrapa-blue/10 dark:bg-nrapa-blue/20 group-hover:bg-nrapa-blue/20 transition">
                    <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12"/></svg>
                </div>
                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Certificates</span>
            </a>

            <a href="{{ route('membership.index') }}" wire:navigate
               class="group rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md hover:border-nrapa-blue/30 dark:hover:border-nrapa-blue/30 transition p-4 text-center">
                <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-xl bg-nrapa-blue/10 dark:bg-nrapa-blue/20 group-hover:bg-nrapa-blue/20 transition">
                    <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Payments</span>
            </a>
        </div>
    </div>
    @endif

    {{-- Newly Approved: Prominent Certificate Banner --}}
    @if($this->isNewlyApproved && $this->certificates->count() > 0)
    <div class="rounded-xl border-2 border-nrapa-blue/30 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 dark:border-nrapa-blue/40 dark:from-blue-900/10 dark:to-indigo-900/10">
        <div class="flex items-start gap-4">
            <div class="flex size-12 flex-shrink-0 items-center justify-center rounded-xl bg-nrapa-blue/15 dark:bg-nrapa-blue/25">
                <svg class="size-6 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Your Certificates Are Ready</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Your membership certificates have been issued and are available for viewing and download.
                </p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($this->certificates as $certificate)
                    <a href="{{ route('certificates.show', $certificate) }}" wire:navigate
                        class="group flex items-center gap-3 rounded-lg border border-zinc-200 bg-white p-3 hover:border-nrapa-blue/50 hover:shadow-sm transition-all dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/40">
                        <div class="flex size-9 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                            <svg class="size-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate group-hover:text-nrapa-blue dark:group-hover:text-nrapa-blue-light transition-colors">{{ $certificate->certificateType->name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Issued {{ $certificate->issued_at->format('d M Y') }}</p>
                        </div>
                        <svg class="size-4 ml-auto flex-shrink-0 text-zinc-400 group-hover:text-nrapa-blue transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    @endforeach
                </div>
                <div class="mt-3">
                    <a href="{{ route('certificates.index') }}" wire:navigate 
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-nrapa-blue hover:text-nrapa-blue-dark dark:text-nrapa-blue-light dark:hover:text-white transition-colors">
                        View All Certificates
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Membership Details --}}
    @if($this->activeMembership)
    <div x-data="{ showCard: false }">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-3 font-semibold text-zinc-900 dark:text-white">Membership Details</h3>
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">Status</span>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">Type</span>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $this->activeMembership->type->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">Member #</span>
                    <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $this->activeMembership->membership_number }}</span>
                </div>
                @if($this->activeMembership->type->isLifetime())
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">Expires</span>
                    <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Never (Lifetime)</span>
                </div>
                @elseif($this->activeMembership->expires_at)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">Expires</span>
                    <span class="text-sm text-zinc-900 dark:text-white">{{ $this->activeMembership->expires_at->format('d M Y') }}</span>
                </div>
                @endif
            </div>
            <div class="mt-4 flex flex-col gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <button @click="showCard = true" class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#0B4EA2] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#0a3d80] transition-colors cursor-pointer">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    Open Fullscreen Card
                </button>
                <a href="{{ route('membership.index') }}" wire:navigate class="block w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-center text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                    Membership Details
                </a>
            </div>
        </div>

        {{-- Fullscreen Card Overlay --}}
        <div x-show="showCard"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-gradient-to-br from-zinc-900/95 to-zinc-800/95 backdrop-blur-sm"
             @keydown.escape.window="showCard = false">
            {{-- Close Button --}}
            <button @click="showCard = false" class="absolute top-4 right-4 z-10 flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-medium text-white hover:bg-white/20 transition-colors cursor-pointer">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Close
            </button>

            {{-- Card --}}
            <div class="w-full max-w-xs overflow-hidden rounded-2xl shadow-2xl" @click.outside="showCard = false">
                {{-- Blue Header --}}
                <div class="flex items-center justify-between bg-gradient-to-br from-[#0B4EA2] to-[#0a3d80] px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-lg bg-white/90">
                            @php $logoUrl = \App\Helpers\DocumentHelper::getLogoUrl(); @endphp
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="NRAPA" class="size-7 object-contain" />
                            @else
                                <span class="text-[10px] font-extrabold text-[#0B4EA2]">NRAPA</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-extrabold text-white tracking-wide">NRAPA</div>
                            <div class="text-[10px] font-semibold text-white/80">Member Card</div>
                        </div>
                    </div>
                    <span class="rounded-full bg-[#F58220] px-2.5 py-0.5 text-[10px] font-bold uppercase text-white tracking-wide">Active</span>
                </div>
                {{-- Orange Stripe --}}
                <div class="h-1 bg-gradient-to-r from-[#F58220] via-[#f9a825] to-[#F58220]"></div>
                {{-- Card Body --}}
                <div class="bg-white px-5 py-4">
                    <div class="mb-3">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Member Name</div>
                        <div class="text-base font-bold text-zinc-900">{{ $this->user->name }}</div>
                    </div>
                    <div class="mb-3 flex gap-4">
                        <div class="flex-1">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Membership No.</div>
                            <div class="font-mono text-xs font-semibold text-zinc-800">{{ $this->activeMembership->membership_number }}</div>
                        </div>
                        <div class="flex-1">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Type</div>
                            <div class="text-xs font-semibold text-zinc-800">{{ $this->activeMembership->type->name }}</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Valid Until</div>
                        @if($this->activeMembership->type->isLifetime())
                            <div class="text-xs font-bold text-[#0B4EA2]">Lifetime</div>
                        @else
                            <div class="text-xs font-semibold text-zinc-800">{{ $this->activeMembership->expires_at?->format('d M Y') ?? 'N/A' }}</div>
                        @endif
                    </div>
                    @if($this->cardQrCodeUrl)
                    <div class="flex flex-col items-center pt-2">
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-1.5">
                            <img src="{{ $this->cardQrCodeUrl }}" alt="QR Code" class="size-24 rounded" loading="lazy" />
                        </div>
                        <span class="mt-1.5 text-[10px] text-zinc-500">Scan to verify membership</span>
                    </div>
                    @endif
                </div>
                {{-- Blue Footer --}}
                <div class="bg-[#0B4EA2] px-5 py-2 text-center">
                    <div class="text-[10px] font-semibold text-white/90">{{ $this->activeMembership->membership_number }}</div>
                </div>
            </div>
        </div>
    </div>
    @elseif($this->latestMembership)
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-nrapa-blue/10 dark:bg-nrapa-blue/20">
                <svg class="size-5 text-nrapa-blue dark:text-nrapa-blue-light" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                </svg>
            </div>
            <h3 class="font-semibold text-zinc-900 dark:text-white">Membership Status</h3>
        </div>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">Application</span>
                @switch($this->latestMembership->status)
                    @case('applied')
                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending Review</span>
                        @break
                    @case('approved')
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Approved</span>
                        @break
                    @case('suspended')
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Suspended</span>
                        @break
                    @case('revoked')
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Revoked</span>
                        @break
                    @case('expired')
                        <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">Expired</span>
                        @break
                    @default
                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">{{ ucfirst($this->latestMembership->status) }}</span>
                @endswitch
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Applied on {{ $this->latestMembership->applied_at->format('d M Y') }}
            </p>
        </div>
    </div>
    @else
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            You don't have an active membership yet. Select a membership below to get started.
        </p>
        <a href="{{ route('membership.apply') }}" wire:navigate class="mt-3 block w-full rounded-lg bg-nrapa-blue px-4 py-2 text-center text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
            View All Memberships
        </a>
    </div>
    @endif

    {{-- Membership Status Cards --}}
    <div class="grid gap-4 sm:gap-6 md:grid-cols-2 lg:grid-cols-3">

        {{-- Knowledge Test Card --}}
        @if($this->requiresTest)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-nrapa-blue/10 dark:bg-nrapa-blue/20">
                    <svg class="size-5 text-nrapa-blue dark:text-nrapa-blue-light" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                </div>
                <h3 class="font-semibold text-zinc-900 dark:text-white">Knowledge Test</h3>
            </div>

            @if($this->hasPassedTest)
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <svg class="size-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="font-medium text-emerald-600 dark:text-emerald-400">Test Passed</span>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        You have successfully completed the knowledge test requirement.
                    </p>
                </div>
            @else
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <svg class="size-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <span class="font-medium text-amber-600 dark:text-amber-400">Test Required</span>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Complete the knowledge test to finalize your membership.
                    </p>
                    <a href="{{ route('knowledge-test.index') }}" wire:navigate class="block w-full rounded-lg bg-nrapa-blue px-4 py-2 text-center text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        Take Test
                    </a>
                </div>
            @endif
        </div>
        @endif

        {{-- Certificates & Endorsements Card --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-nrapa-blue/10 dark:bg-nrapa-blue/20">
                    <svg class="size-5 text-nrapa-blue dark:text-nrapa-blue-light" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" />
                    </svg>
                </div>
                <h3 class="font-semibold text-zinc-900 dark:text-white">Certificates & Endorsements</h3>
            </div>

            @if($this->certificates->count() > 0)
                <div class="space-y-3">
                    @foreach($this->certificates as $certificate)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $certificate->certificateType->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Issued {{ $certificate->issued_at->format('d M Y') }}
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Valid</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    @if($this->activeMembership)
                        No certificates or endorsements issued yet. These will be available once approved and requirements have been met.
                    @else
                        Apply for membership to receive your certificates and endorsements.
                    @endif
                </p>
            @endif

            <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <a href="{{ route('certificates.index') }}" wire:navigate class="block w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-center text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                    View All Certificates & Endorsements
                </a>
            </div>
        </div>
    </div>

    {{-- Available Memberships Section (for users without membership) --}}
    @if($this->needsMembership && $this->availableMembershipTypes->count() > 0)
    <div class="mt-2">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Choose Your Membership</h2>
            <a href="{{ route('membership.apply') }}" wire:navigate class="text-sm font-medium text-nrapa-blue hover:text-nrapa-blue-dark dark:text-nrapa-blue-light dark:hover:text-white">
                View all options &rarr;
            </a>
        </div>
        <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
            Select a membership to unlock full access to the NRAPA member portal, including the Virtual Safe, Learning Center, and more.
        </p>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($this->availableMembershipTypes as $type)
                <div class="relative rounded-xl border {{ $type->is_featured ? 'border-nrapa-blue ring-2 ring-nrapa-blue' : 'border-zinc-200 dark:border-zinc-700' }} bg-white p-6 dark:bg-zinc-800">
                    @if($type->is_featured)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 transform">
                            <span class="inline-flex items-center rounded-full bg-nrapa-orange px-3 py-1 text-xs font-semibold text-white">
                                Recommended
                            </span>
                        </div>
                    @endif
                    
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $type->name }}</h3>
                        @if($type->dedicated_type)
                            <span class="mt-1 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ ucfirst($type->dedicated_type === 'both' ? 'Hunter & Sport Shooter' : ($type->dedicated_type === 'sport' ? 'Sport Shooter' : 'Hunter')) }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="mb-4">
                        @if($type->hasUpgradeFee())
                        @php $basicType = $this->availableMembershipTypes->firstWhere('slug', 'basic'); @endphp
                        @php $totalSignup = ($basicType?->initial_price ?? 0) + ($type->upgrade_price ?? 0); @endphp
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-zinc-900 dark:text-white">R{{ number_format($totalSignup, 0) }}</span>
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">sign-up</span>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Renewal: R{{ number_format($type->renewal_price, 0) }}/year</p>
                        @else
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-zinc-900 dark:text-white">R{{ number_format($type->initial_price, 0) }}</span>
                            @if($type->duration_type === 'annual')
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">/year</span>
                            @elseif($type->duration_type === 'lifetime')
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">once-off</span>
                            @elseif($type->duration_months)
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">/{{ $type->duration_months }}mo</span>
                            @endif
                        </div>
                        @if($type->renewal_price > 0 && $type->renewal_price != $type->initial_price)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Renewal: R{{ number_format($type->renewal_price, 0) }}/year</p>
                        @endif
                        @endif
                    </div>
                    
                    @if($type->description)
                        <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400 line-clamp-3">{{ $type->description }}</p>
                    @endif
                    
                    <ul class="mb-6 space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 text-nrapa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Virtual Safe
                        </li>
                        @if($type->allows_dedicated_status)
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 text-nrapa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Virtual Loading Bench
                        </li>
                        @endif
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 text-nrapa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Learning Center
                        </li>
                        @if($type->allows_dedicated_status)
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 text-nrapa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Dedicated Status support
                        </li>
                        @endif
                    </ul>
                    
                    <a href="{{ route('membership.apply', ['type' => $type->slug]) }}" wire:navigate class="block w-full rounded-lg {{ $type->is_featured ? 'bg-nrapa-blue text-white hover:bg-nrapa-blue-dark' : 'border border-nrapa-blue text-nrapa-blue hover:bg-nrapa-blue/5 dark:border-nrapa-blue-light dark:text-nrapa-blue-light dark:hover:bg-nrapa-blue/10' }} px-4 py-2.5 text-center text-sm font-semibold transition-colors">
                        Select Membership
                    </a>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- (Quick Actions moved to header tabs) --}}
    </div>
</div>
