<?php

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\MemberDocument;
use App\Models\EndorsementRequest;
use App\Models\Certificate;
use App\Models\ShootingActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public bool $rejectedDocumentsAlertDismissed = false;
    public bool $rejectedActivitiesAlertDismissed = false;

    public function mount(): void
    {
        // Check if user has dismissed the rejected documents alert in this session
        $this->rejectedDocumentsAlertDismissed = session()->get('rejected_documents_alert_dismissed', false);
        // Check if user has dismissed the rejected activities alert in this session
        $this->rejectedActivitiesAlertDismissed = session()->get('rejected_activities_alert_dismissed', false);
    }

    public function dismissRejectedDocumentsAlert(): void
    {
        $this->rejectedDocumentsAlertDismissed = true;
        session()->put('rejected_documents_alert_dismissed', true);
    }

    public function dismissRejectedActivitiesAlert(): void
    {
        $this->rejectedActivitiesAlertDismissed = true;
        session()->put('rejected_activities_alert_dismissed', true);
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
    public function pendingDocuments()
    {
        return MemberDocument::where('user_id', $this->user->id)
            ->pending()
            ->with('documentType')
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
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Welcome Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Welcome back, {{ $this->user->name }}!</h1>
            <p class="text-zinc-500 dark:text-zinc-400">
                Manage your NRAPA membership, certificates, and compliance requirements.
            </p>
        </div>
        @if(auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN))
        <a 
            href="{{ route('admin.dashboard') }}" 
            wire:navigate
            class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors flex items-center gap-2 whitespace-nowrap"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Admin Dashboard
        </a>
        @endif
    </div>

    {{-- Action Required Notifications --}}
    @if($this->pendingDocuments->count() > 0 || $this->rejectedDocuments->count() > 0 || $this->rejectedActivities->count() > 0 || $this->showEndorsementStatus)
    <div class="space-y-4">
        {{-- Rejected Activities Alert --}}
        @if($this->rejectedActivities->count() > 0 && !$this->rejectedActivitiesAlertDismissed)
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
                        You have {{ $this->rejectedActivities->count() }} activit{{ $this->rejectedActivities->count() > 1 ? 'ies' : 'y' }} that {{ $this->rejectedActivities->count() > 1 ? 'were' : 'was' }} rejected and need attention:
                    </p>
                    <ul class="mt-2 space-y-2 text-sm text-red-700 dark:text-red-300">
                        @foreach($this->rejectedActivities->take(3) as $activity)
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
                        @if($this->rejectedActivities->count() > 3)
                            <li class="text-xs italic">and {{ $this->rejectedActivities->count() - 3 }} more...</li>
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

        {{-- Rejected Documents Alert --}}
        @if($this->rejectedDocuments->count() > 0 && !$this->rejectedDocumentsAlertDismissed)
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
                        You have {{ $this->rejectedDocuments->count() }} document{{ $this->rejectedDocuments->count() > 1 ? 's' : '' }} that {{ $this->rejectedDocuments->count() > 1 ? 'were' : 'was' }} rejected and need attention:
                    </p>
                    <ul class="mt-2 space-y-2 text-sm text-red-700 dark:text-red-300">
                        @foreach($this->rejectedDocuments->take(3) as $doc)
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
                        @if($this->rejectedDocuments->count() > 3)
                            <li class="text-xs italic">and {{ $this->rejectedDocuments->count() - 3 }} more...</li>
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
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            {{-- Header --}}
            <div class="flex items-start gap-3 mb-4">
                <div class="flex size-10 flex-shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                    <svg class="size-5 text-zinc-600 dark:text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                    </svg>
                </div>
                <div class="flex-1">
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
                <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        <svg class="size-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                        <div>
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
                <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        <svg class="size-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <div>
                            @if($documentsComplete)
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Required Documents</span>
                            @else
                                <a href="{{ route('documents.index') }}" wire:navigate class="text-sm text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:underline">
                                    Required Documents
                                </a>
                            @endif
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">ID & Proof of Address (valid within 3 months)</p>
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
                <div class="flex items-center justify-between py-2">
                    <div class="flex items-center gap-3">
                        <svg class="size-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <div>
                            @if($activitiesMet)
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Shooting Activities</span>
                            @else
                                <a href="{{ route('activities.index') }}" wire:navigate class="text-sm text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:underline">
                                    Shooting Activities
                                </a>
                            @endif
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $requiredCount }} approved activities per year to maintain status</p>
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
                                    @if(in_array('Proof of Address', $missingDocNames))
                                        @php
                                            $poaPending = \App\Models\MemberDocument::where('user_id', $this->user->id)
                                                ->whereHas('documentType', fn($q) => $q->where('slug', 'proof-of-address'))
                                                ->where('status', 'pending')
                                                ->exists();
                                        @endphp
                                        @if($poaPending)
                                            Your Proof of Address is pending admin verification.
                                        @else
                                            Upload and verify your Proof of Address (must be valid within 3 months).
                                        @endif
                                    @else
                                        Documents must be verified by an admin before they count toward requirements.
                                    @endif
                                @else
                                    Update your proof of address - must be valid within 3 months for endorsement requests.
                                @endif
                            @elseif(!$activitiesMet)
                                Submit {{ $requiredCount }} activities per year to maintain dedicated status. You have {{ $approvedCount }} approved.
                            @endif
                        </p>
                        @if(!$activitiesMet)
                            <div class="mt-3">
                                <a href="{{ route('activities.submit') }}" wire:navigate 
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
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
                <a href="{{ route('member.endorsements.create') }}" wire:navigate 
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Request Endorsement
                </a>
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
    </div>
    @endif

    {{-- Membership Status Cards --}}
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {{-- Membership Card --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900">
                    <svg class="size-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-zinc-900 dark:text-white">Membership Status</h3>
            </div>

            @if($this->activeMembership)
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Type</span>
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">{{ $this->activeMembership->type->name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Member #</span>
                        <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $this->activeMembership->membership_number }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Status</span>
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                    </div>
                    @if($this->activeMembership->expires_at)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Expires</span>
                        <span class="text-sm text-zinc-900 dark:text-white">{{ $this->activeMembership->expires_at->format('d M Y') }}</span>
                    </div>
                    @else
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Validity</span>
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Lifetime</span>
                    </div>
                    @endif
                </div>
                <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <a href="{{ route('membership.index') }}" wire:navigate class="block w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-center text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                        View Details
                    </a>
                </div>
            @elseif($this->latestMembership)
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Application</span>
                        @switch($this->latestMembership->status)
                            @case('applied')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending Review</span>
                                @break
                            @case('approved')
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Approved - Awaiting Activation</span>
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
            @else
                <div class="space-y-3">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        You don't have an active membership yet. Select a membership below to get started.
                    </p>
                    <a href="{{ route('membership.apply') }}" wire:navigate class="block w-full rounded-lg bg-emerald-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                        View All Memberships
                    </a>
                </div>
            @endif
        </div>

        {{-- Knowledge Test Card --}}
        @if($this->requiresTest)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                    <svg class="size-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                </div>
                <h3 class="font-semibold text-zinc-900 dark:text-white">Knowledge Test</h3>
            </div>

            @if($this->hasPassedTest)
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <svg class="size-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="font-medium text-green-600 dark:text-green-400">Test Passed</span>
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
                    <a href="{{ route('knowledge-test.index') }}" wire:navigate class="block w-full rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                        Take Test
                    </a>
                </div>
            @endif
        </div>
        @endif

        {{-- Certificates & Endorsements Card --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                    <svg class="size-5 text-purple-600 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
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
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Valid</span>
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
            <a href="{{ route('membership.apply') }}" wire:navigate class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                View all options &rarr;
            </a>
        </div>
        <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
            Select a membership to unlock full access to the NRAPA member portal, including the Virtual Safe, Virtual Loading Bench, Learning Center, and more.
        </p>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($this->availableMembershipTypes as $type)
                <div class="relative rounded-xl border {{ $type->is_featured ? 'border-emerald-500 ring-2 ring-emerald-500' : 'border-zinc-200 dark:border-zinc-700' }} bg-white p-6 shadow-sm dark:bg-zinc-800">
                    @if($type->is_featured)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 transform">
                            <span class="inline-flex items-center rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white">
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
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-zinc-900 dark:text-white">R{{ number_format($type->price, 0) }}</span>
                            @if($type->duration_type === 'annual')
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">/year</span>
                            @elseif($type->duration_type === 'lifetime')
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">once-off</span>
                            @elseif($type->duration_months)
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">/{{ $type->duration_months }}mo</span>
                            @endif
                        </div>
                    </div>
                    
                    @if($type->description)
                        <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400 line-clamp-3">{{ $type->description }}</p>
                    @endif
                    
                    <ul class="mb-6 space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Virtual Safe access
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Virtual Loading Bench
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Learning Center
                        </li>
                        @if($type->allows_dedicated_status)
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Dedicated Status support
                            </li>
                        @endif
                    </ul>
                    
                    <a href="{{ route('membership.apply', ['type' => $type->slug]) }}" wire:navigate class="block w-full rounded-lg {{ $type->is_featured ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'border border-emerald-600 text-emerald-600 hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-400 dark:hover:bg-emerald-900/20' }} px-4 py-2.5 text-center text-sm font-semibold transition-colors">
                        Select Membership
                    </a>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Quick Actions --}}
    @if($this->activeMembership)
    <div class="mt-4">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Quick Actions</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('certificates.index') }}" wire:navigate class="flex items-center gap-3 rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Download Certificate
            </a>
            <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-3 rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                Update Profile
            </a>
            @if($this->activeMembership->requiresRenewal() && $this->activeMembership->isRenewable())
            <a href="{{ route('membership.apply') }}" wire:navigate class="flex items-center gap-3 rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Renew Membership
            </a>
            @endif
            @if($this->activeMembership->allowsDedicatedStatus())
            <a href="{{ route('member.endorsements.index') }}" wire:navigate class="flex items-center gap-3 rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                </svg>
                Dedicated Status
            </a>
            @endif
        </div>
    </div>
    @endif
</div>
