<?php

use App\Models\EndorsementRequest;
use App\Models\MembershipType;
use App\Models\ShootingActivity;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Dedicated Status')] class extends Component {
    
    public string $statusFilter = '';

    public function trackRanyatiClick(): void
    {
        $data = SystemSetting::get('ranyati_click_stats') ?: [];
        $data['total'] = ($data['total'] ?? 0) + 1;
        $userId = (string) auth()->id();
        $data['users'][$userId] = ($data['users'][$userId] ?? 0) + 1;
        $data['last_at'] = now()->toIso8601String();
        SystemSetting::set('ranyati_click_stats', $data, 'json', 'analytics');
    }

    public function updatedStatusFilter(): void
    {
        // Reset any pagination if needed
    }

    #[Computed]
    public function requests()
    {
        $query = EndorsementRequest::where('user_id', auth()->id())
            ->with(['firearm', 'firearm.firearmCalibre', 'firearm.firearmMake', 'firearm.firearmModel', 'documents'])
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query->get();
    }

    #[Computed]
    public function stats()
    {
        $userId = auth()->id();
        return [
            'pending' => EndorsementRequest::where('user_id', $userId)
                ->whereIn('status', ['submitted', 'under_review', 'pending_documents'])
                ->count(),
            'approved' => EndorsementRequest::where('user_id', $userId)
                ->where('status', 'approved')
                ->count(),
            'issued' => EndorsementRequest::where('user_id', $userId)
                ->where('status', 'issued')
                ->count(),
            'total' => EndorsementRequest::where('user_id', $userId)->count(),
        ];
    }

    #[Computed]
    public function hasDraft()
    {
        return EndorsementRequest::where('user_id', auth()->id())
            ->where('status', EndorsementRequest::STATUS_DRAFT)
            ->exists();
    }

    #[Computed]
    public function eligibility()
    {
        return EndorsementRequest::getEligibilitySummary(auth()->user());
    }

    #[Computed]
    public function membership()
    {
        return auth()->user()->activeMembership;
    }

    #[Computed]
    public function dedicatedType()
    {
        return $this->membership?->type?->dedicated_type;
    }

    #[Computed]
    public function dedicatedTypeLabel()
    {
        return match($this->dedicatedType) {
            MembershipType::DEDICATED_TYPE_SPORT => 'Dedicated Sport Shooter',
            MembershipType::DEDICATED_TYPE_HUNTER => 'Dedicated Hunter',
            MembershipType::DEDICATED_TYPE_BOTH => 'Dedicated Sport Shooter & Hunter',
            default => 'Dedicated Member',
        };
    }

    #[Computed]
    public function activityYear()
    {
        $now = now();
        return $now->month >= 11 ? $now->year + 1 : $now->year;
    }

    #[Computed]
    public function activityPeriod()
    {
        $year = $this->activityYear;
        return [
            'start' => Carbon::create($year, 1, 1)->startOfDay(),
            'end' => Carbon::create($year, 9, 30)->endOfDay(),
            'nrapa_deadline' => Carbon::create($year, 10, 31),
            'saps_report' => Carbon::create($year, 12, 1),
            'label' => "1 Jan {$year} - 30 Sep {$year}",
        ];
    }

    #[Computed]
    public function complianceStatus()
    {
        $user = auth()->user();
        $period = $this->activityPeriod;

        // NRAPA rule: 2 approved activities in the PREVIOUS activity year qualify the member
        // for the CURRENT year. Current-year activities bank compliance for NEXT year.
        $required = 2;
        $summary = ShootingActivity::complianceSummary($user, $required);

        $approvedActivities = $summary['qualifying_year']['total'];
        $bankingActivities = $summary['banking_year']['total'];
        $isCompliant = $summary['is_compliant_now'];

        $daysUntilDeadline = (int) now()->diffInDays($period['nrapa_deadline'], false);
        $isPastDeadline = $daysUntilDeadline < 0;

        return [
            'approved_count' => $approvedActivities,
            'banking_count' => $bankingActivities,
            'qualifying_year' => $summary['qualifying_year']['year'],
            'banking_year' => $summary['banking_year']['year'],
            'required' => $required,
            'required_sport' => $required,
            'required_hunter' => $required,
            'sport_compliant' => $isCompliant,
            'hunter_compliant' => $isCompliant,
            'is_compliant' => $isCompliant,
            'days_until_deadline' => max(0, $daysUntilDeadline),
            'is_past_deadline' => $isPastDeadline,
            'deadline_date' => $period['nrapa_deadline']->format('d M Y'),
            'period_label' => $period['label'],
        ];
    }

    public function deleteRequest(EndorsementRequest $request): void
    {
        if ($request->user_id !== auth()->id()) {
            return;
        }

        if (!$request->isDraft()) {
            session()->flash('error', 'Only draft requests can be deleted.');
            return;
        }

        // Delete related records
        $request->documents()->delete();
        $request->components()->delete();
        $request->firearm?->delete();
        $request->delete();

        session()->flash('success', 'Draft request deleted successfully.');
    }

    public function cancelRequest(EndorsementRequest $request): void
    {
        if ($request->user_id !== auth()->id()) {
            return;
        }

        if (!$request->isSubmitted()) {
            session()->flash('error', 'This request cannot be cancelled.');
            return;
        }

        $request->cancel();
        session()->flash('success', 'Request cancelled successfully.');
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Dedicated Status</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage your dedicated status and request endorsement letters for firearm applications.</p>
            </div>
            @if($this->dedicatedType)
                <a href="{{ route('member.endorsements.create') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Request Endorsement
                </a>
            @endif
        </div>
    </x-slot>

    {{-- Dedicated Status Compliance Bar --}}
    <div class="mb-8">

        {{-- Compliance Status Bar --}}
        @if(!$this->dedicatedType)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-amber-300 dark:border-amber-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-full shrink-0">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Dedicated Membership Required</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            Endorsement letters are only available for <strong>Dedicated Hunter</strong> or <strong>Dedicated Sport Shooter</strong> members.
                            To request endorsement letters for Section 16 firearm applications, you need to upgrade your membership to a dedicated membership type.
                        </p>
                        <a href="{{ route('membership.apply') }}" wire:navigate
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                            </svg>
                            Upgrade Membership
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            {{-- Status Header --}}
            <div class="px-6 py-4 bg-gradient-to-r from-nrapa-blue to-nrapa-blue-dark">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        {{-- Status Icon --}}
                        <div class="p-2 bg-white/20 rounded-lg">
                            @if($this->dedicatedType === 'sport')
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            @elseif($this->dedicatedType === 'hunter')
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">{{ $this->dedicatedTypeLabel }}</h2>
                            <p class="text-sm text-white/80">Activity Period: {{ $this->complianceStatus['period_label'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($this->complianceStatus['is_compliant'])
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold bg-white/20 text-white">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Compliant
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold bg-white/20 text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Action Required
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Activity Progress (single total; NRAPA requires 2 approved activities per year regardless of category) --}}
            <div class="p-6">
                <div class="p-4 rounded-lg border {{ $this->complianceStatus['is_compliant'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 {{ $this->complianceStatus['is_compliant'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span class="font-semibold text-zinc-900 dark:text-white">Approved Activities from {{ $this->complianceStatus['qualifying_year'] }}</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">(qualify you for {{ now()->year }} — hunting or sport)</span>
                        </div>
                        @if($this->complianceStatus['is_compliant'])
                            <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Complete</span>
                        @else
                            <span class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ max(0, $this->complianceStatus['required'] - $this->complianceStatus['approved_count']) }} more needed</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
                            <div class="h-3 rounded-full transition-all {{ $this->complianceStatus['is_compliant'] ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                style="width: {{ min(100, ($this->complianceStatus['approved_count'] / max(1, $this->complianceStatus['required'])) * 100) }}%"></div>
                        </div>
                        <span class="text-sm font-bold {{ $this->complianceStatus['is_compliant'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $this->complianceStatus['approved_count'] }}/{{ $this->complianceStatus['required'] }}
                        </span>
                    </div>
                </div>

                {{-- Deadline Notice --}}
                <div class="mt-4 flex items-center justify-between pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>NRAPA Submission Deadline: <strong>{{ $this->complianceStatus['deadline_date'] }}</strong></span>
                    </div>
                    @if(!$this->complianceStatus['is_past_deadline'])
                        <span class="text-sm {{ $this->complianceStatus['days_until_deadline'] <= 30 ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-zinc-500 dark:text-zinc-400' }}">
                            {{ $this->complianceStatus['days_until_deadline'] }} days remaining
                        </span>
                    @else
                        <span class="text-sm text-red-600 dark:text-red-400 font-semibold">
                            Deadline passed
                        </span>
                    @endif
                </div>

                @if(!$this->complianceStatus['is_compliant'])
                    <div class="mt-4">
                        <a href="{{ route('activities.index') }}" wire:navigate
                            class="inline-flex items-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Submit Activities
                        </a>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Compliance Status Card --}}
    <div class="mb-8 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Compliance Status</h2>
            @if($this->eligibility['eligible'])
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Fully Compliant
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Not Fully Compliant
                </span>
            @endif
        </div>
        <div class="p-6">
            <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-4">
                {{-- Knowledge Test --}}
                <div class="p-4 rounded-xl border {{ $this->eligibility['knowledge_test_passed'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' }}">
                    <div class="flex items-center gap-3 mb-2">
                        @if($this->eligibility['knowledge_test_passed'])
                            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @else
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                        @endif
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Knowledge Test</h3>
                    </div>
                    @if($this->eligibility['knowledge_test_passed'])
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">Completed (once-off requirement)</p>
                    @else
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">Required once-off to proceed</p>
                        <a href="{{ route('knowledge-test.index') }}" wire:navigate
                            class="inline-flex items-center gap-1 text-sm font-medium text-blue-700 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                            Take the knowledge test
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @endif
                </div>

                {{-- Documents --}}
                <div class="p-4 rounded-xl border {{ $this->eligibility['documents_complete'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' }}">
                    <div class="flex items-center gap-3 mb-2">
                        @if($this->eligibility['documents_complete'])
                            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @else
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                        @endif
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Required Documents</h3>
                    </div>
                    @if($this->eligibility['documents_complete'])
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">All documents verified</p>
                    @else
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                            <p class="mb-1">Still needed:</p>
                            <ul class="list-disc list-inside mb-2">
                                @foreach($this->eligibility['missing_documents'] as $doc)
                                    <li>{{ $doc['name'] }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ route('documents.index') }}" wire:navigate
                                class="inline-flex items-center gap-1 font-medium text-blue-700 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                Upload documents
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Activities --}}
                <div class="p-4 rounded-xl border {{ $this->eligibility['activities_met'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' }}">
                    <div class="flex items-center gap-3 mb-2">
                        @if($this->eligibility['activities_met'])
                            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @else
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                        @endif
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Approved Activities</h3>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $this->eligibility['activity_details']['approved_count'] }} / {{ $this->eligibility['activity_details']['required'] }} required
                        <span class="text-xs">({{ $this->eligibility['activity_details']['period'] ?? now()->year }} activity year)</span>
                    </p>
                    @if(!$this->eligibility['activities_met'])
                        <a href="{{ route('activities.index') }}" wire:navigate
                            class="inline-flex items-center gap-1 text-sm font-medium text-blue-700 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mt-1">
                            Submit activities
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-300 dark:border-emerald-700 rounded-xl text-emerald-800 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats Cards --}}
    @if($this->stats['total'] > 0)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Pending</p>
                        <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['pending'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Approved</p>
                        <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['approved'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Issued</p>
                        <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['issued'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-nrapa-blue/10 dark:bg-nrapa-blue/20 rounded-lg">
                        <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Total</p>
                        <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['total'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Ranyati Motivations Recommendation --}}
    @if($this->stats['approved'] + $this->stats['issued'] > 0)
    @php
        $motivationsUrl = 'https://motivations.ranyati.co.za/enquire?' . http_build_query([
            'name' => auth()->user()->getIdName(),
            'email' => auth()->user()->email,
            'membership' => $this->membership?->membership_number,
        ]);
    @endphp
    <div class="mb-6 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm">
        <div class="flex flex-col sm:flex-row items-stretch">
            <a href="{{ $motivationsUrl }}" target="_blank" wire:click="trackRanyatiClick"
                class="flex-shrink-0 flex items-center justify-center px-6 py-4 bg-[#1b2a4a]">
                <img src="{{ asset('logo-ranyati_motivations-white-text.png') }}" alt="Ranyati Motivations" class="h-12 w-auto" />
            </a>
            <div class="flex flex-col sm:flex-row flex-1 items-center gap-4 p-5 bg-gradient-to-r from-orange-50 via-white to-orange-50 dark:from-zinc-800 dark:via-zinc-800 dark:to-zinc-800">
                <div class="flex-1 text-center sm:text-left">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Need a Professional Firearm Motivation?</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Your endorsement is ready &mdash; now let Ranyati Motivations draft your Section 16 motivation letter for SAPS. Professional, compliant, and tailored to your application.
                    </p>
                </div>
                <a href="{{ $motivationsUrl }}" target="_blank" wire:click="trackRanyatiClick"
                    class="flex-shrink-0 inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#F58220] to-[#d46f16] hover:from-[#d46f16] hover:to-[#c06010] shadow-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Get a Motivation
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Filter --}}
    @if($this->stats['total'] > 0)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full md:w-64">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Filter by Status</label>
                    <select wire:model.live="statusFilter" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        <option value="">All Statuses</option>
                        @foreach(App\Models\EndorsementRequest::getStatusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    @endif

    {{-- Requests List --}}
    @if($this->requests->count() > 0)
        <div class="space-y-4">
            @foreach($this->requests as $request)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-start gap-4">
                                {{-- Icon --}}
                                <div class="p-3 rounded-lg {{ $request->isRenewal() ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-blue-100 dark:bg-blue-900/30' }}">
                                    @if($request->isRenewal())
                                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    @endif
                                </div>

                                <div>
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                                            {{ $request->request_type_label }}
                                        </h3>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $request->status_badge_class }}">
                                            {{ $request->status_label }}
                                        </span>
                                    </div>

                                    @if($request->firearm)
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $request->firearm->summary }}
                                        </p>
                                    @endif

                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                                        Created {{ $request->created_at->diffForHumans() }}
                                        @if($request->submitted_at)
                                            · Submitted {{ $request->submitted_at->diffForHumans() }}
                                        @endif
                                        @if($request->issued_at)
                                            · Issued {{ $request->issued_at->format('d M Y') }}
                                        @endif
                                    </p>

                                    @if($request->letter_reference)
                                        <p class="mt-1 text-sm font-mono text-emerald-600 dark:text-emerald-400">
                                            Ref: {{ $request->letter_reference }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($request->canEdit())
                                    <a href="{{ route('member.endorsements.edit', $request) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-emerald-700 bg-emerald-100 hover:bg-emerald-200 dark:text-emerald-300 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Continue
                                    </a>
                                    <button wire:click="deleteRequest('{{ $request->uuid }}')"
                                        wire:confirm="Are you sure you want to delete this draft?"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Delete
                                    </button>
                                @elseif($request->isApproved())
                                    <a href="{{ route('member.endorsements.show', $request) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-emerald-700 bg-emerald-100 hover:bg-emerald-200 dark:text-emerald-300 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View Details
                                    </a>
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                                        Ready for letter generation
                                    </span>
                                @elseif($request->isSubmitted())
                                    <a href="{{ route('member.endorsements.show', $request) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:text-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                    <button wire:click="cancelRequest('{{ $request->uuid }}')"
                                        wire:confirm="Are you sure you want to cancel this request?"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                        Cancel
                                    </button>
                                @elseif($request->isIssued())
                                    <a href="{{ route('member.endorsements.show', $request) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:text-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                    @if($request->letter_file_path)
                                    <a href="{{ route('member.endorsements.letter', $request) }}"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-nrapa-blue hover:bg-nrapa-blue-dark rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                        </svg>
                                        Download PDF
                                    </a>
                                    @endif
                                @else
                                    <a href="{{ route('member.endorsements.show', $request) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:text-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                                        View Details
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Documents Progress (for draft/submitted) --}}
                        @if($request->isDraft() || $request->status === 'pending_documents')
                            @php
                                $totalDocs = $request->documents->where('is_required', true)->count();
                                $uploadedDocs = $request->documents->where('is_required', true)->whereIn('status', ['uploaded', 'verified', 'system_verified'])->count();
                            @endphp
                            @if($totalDocs > 0)
                                <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-zinc-600 dark:text-zinc-400">Documents</span>
                                        <span class="text-zinc-900 dark:text-white font-medium">{{ $uploadedDocs }}/{{ $totalDocs }} uploaded</span>
                                    </div>
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                        <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ $totalDocs > 0 ? ($uploadedDocs / $totalDocs * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-zinc-900 dark:text-white">No Endorsement Requests</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 max-w-md mx-auto">
                You haven't submitted any endorsement letter requests yet. Start a new request to get an endorsement letter for your dedicated status firearms.
            </p>
            @if($this->dedicatedType)
                <a href="{{ route('member.endorsements.create') }}" wire:navigate
                    class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Request Endorsement Letter
                </a>
            @else
                <a href="{{ route('membership.apply') }}" wire:navigate
                    class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    Upgrade to Dedicated Membership
                </a>
            @endif
        </div>
    @endif

    {{-- Info Card --}}
    <div class="mt-8 p-6 bg-nrapa-blue/5 dark:bg-nrapa-blue/10 border border-nrapa-blue/20 dark:border-nrapa-blue/30 rounded-xl">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-nrapa-blue dark:text-nrapa-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-sm text-zinc-700 dark:text-zinc-300">
                <h4 class="font-semibold mb-1 text-zinc-900 dark:text-white">About Endorsement Letters</h4>
                <p>
                    An endorsement letter is supporting documentation from NRAPA confirming that a specific firearm or component is fit for purpose
                    for dedicated sport shooting or dedicated hunting. It serves as motivation when applying for a Section 16 licence &mdash;
                    for example, a 9mm pistol would be endorsed for sport shooting but not for hunting, while a bolt-action rifle in a hunting calibre
                    would be endorsed for hunting purposes.
                </p>
                <p class="mt-2">
                    You can request a <strong>New Endorsement</strong> for first-time licence applications or a <strong>Renewal Endorsement</strong>
                    for existing firearms (which can also include component requests such as main firearm components or actions).
                </p>
            </div>
        </div>
    </div>
</div>
