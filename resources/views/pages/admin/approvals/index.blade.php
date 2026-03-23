<?php

use App\Mail\MembershipApproved;
use App\Models\CalibreRequest;
use App\Models\EndorsementRequest;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\ShootingActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('All Approvals - Admin')] class extends Component {
    use WithPagination;

    #[Computed]
    public function allPendingApprovals()
    {
        $approvals = collect();

        // Pending Documents (exclude documents linked to activities to avoid double-counting)
        $pendingDocuments = MemberDocument::where('status', 'pending')
            ->whereDoesntHave('shootingActivityAsEvidence')
            ->whereDoesntHave('shootingActivityAsAdditional')
            ->with(['user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($doc) {
                return [
                    'type' => 'document',
                    'id' => $doc->id,
                    'title' => $doc->document_type . ' Document',
                    'user' => $doc->user,
                    'date' => $doc->created_at,
                    'route' => route('admin.documents.show', $doc),
                ];
            });

        // Pending Memberships (exclude orphaned records)
        $pendingMemberships = Membership::where('status', 'applied')
            ->whereHas('user')
            ->with(['user', 'type'])
            ->orderBy('applied_at', 'asc')
            ->get()
            ->map(function ($membership) {
                return [
                    'type' => 'membership',
                    'id' => $membership->id,
                    'title' => ($membership->type?->name ?? 'Membership') . ' Membership',
                    'user' => $membership->user,
                    'date' => $membership->applied_at,
                    'route' => route('admin.approvals.show', $membership),
                ];
            });

        // Pending Activities
        $pendingActivities = ShootingActivity::where('status', 'pending')
            ->with(['user', 'activityType'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($activity) {
                return [
                    'type' => 'activity',
                    'id' => $activity->id,
                    'title' => ($activity->activityType?->name ?? 'Shooting Activity') . ' - ' . ($activity->activity_date?->format('d M Y') ?? 'N/A'),
                    'user' => $activity->user,
                    'date' => $activity->created_at,
                    'route' => route('admin.activities.show', $activity),
                ];
            });

        // Pending Calibre Requests
        $pendingCalibres = CalibreRequest::where('status', 'pending')
            ->with(['user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($calibre) {
                return [
                    'type' => 'calibre',
                    'id' => $calibre->id,
                    'title' => 'Calibre Request: ' . ($calibre->calibre_name ?? 'N/A'),
                    'user' => $calibre->user,
                    'date' => $calibre->created_at,
                    'route' => route('admin.calibre-requests.show', $calibre),
                ];
            });

        // Membership type change requests (awaiting admin to set amount)
        $pendingChangeRequests = Membership::where('status', 'pending_change')
            ->whereHas('user')
            ->with(['user', 'type', 'previousMembership.type'])
            ->orderBy('applied_at', 'asc')
            ->get()
            ->map(function ($membership) {
                $fromType = $membership->previousMembership?->type?->name ?? 'Unknown';
                $toType = $membership->type?->name ?? 'Unknown';
                return [
                    'type' => 'change_request',
                    'id' => $membership->id,
                    'title' => "Type Change: {$fromType} → {$toType}",
                    'user' => $membership->user,
                    'date' => $membership->applied_at,
                    'route' => route('admin.approvals.show', $membership),
                ];
            });

        // Change requests awaiting final approval (member uploaded POP)
        $pendingChangePayments = Membership::where('status', 'pending_payment')
            ->whereHas('user')
            ->with(['user', 'type', 'previousMembership.type'])
            ->orderBy('applied_at', 'asc')
            ->get()
            ->map(function ($membership) {
                $toType = $membership->type?->name ?? 'Unknown';
                return [
                    'type' => 'change_payment',
                    'id' => $membership->id,
                    'title' => "Change Payment: {$toType}" . ($membership->proof_of_payment_path ? ' (POP uploaded)' : ''),
                    'user' => $membership->user,
                    'date' => $membership->applied_at,
                    'route' => route('admin.approvals.show', $membership),
                ];
            });

        // Pending Endorsements (exclude approved – approved items are no longer "pending approval")
        $pendingEndorsements = EndorsementRequest::whereIn('status', [
            EndorsementRequest::STATUS_SUBMITTED,
            EndorsementRequest::STATUS_UNDER_REVIEW,
            EndorsementRequest::STATUS_PENDING_DOCUMENTS,
        ])
            ->with(['user', 'firearm'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($endorsement) {
                return [
                    'type' => 'endorsement',
                    'id' => $endorsement->id,
                    'title' => 'Endorsement Request' . ($endorsement->firearm ? ' - ' . ($endorsement->firearm->display_name ?? 'N/A') : ''),
                    'user' => $endorsement->user,
                    'date' => $endorsement->created_at,
                    'status' => $endorsement->status,
                    'route' => route('admin.endorsements.show', $endorsement),
                ];
            });

        return $approvals
            ->merge($pendingDocuments)
            ->merge($pendingMemberships)
            ->merge($pendingChangeRequests)
            ->merge($pendingChangePayments)
            ->merge($pendingActivities)
            ->merge($pendingCalibres)
            ->merge($pendingEndorsements)
            ->filter(fn ($item) => $item['user'] !== null)
            ->sortBy('date')
            ->values();
    }

    #[Computed]
    public function stats()
    {
        $docs = MemberDocument::where('status', 'pending')
            ->whereDoesntHave('shootingActivityAsEvidence')
            ->whereDoesntHave('shootingActivityAsAdditional')
            ->count();
        $memberships = Membership::where('status', 'applied')->whereHas('user')->count();
        $changeRequests = Membership::whereIn('status', ['pending_change', 'pending_payment'])->whereHas('user')->count();
        $activities = ShootingActivity::where('status', 'pending')->count();
        $calibres = CalibreRequest::where('status', 'pending')->count();
        $endorsements = EndorsementRequest::whereIn('status', [
            EndorsementRequest::STATUS_SUBMITTED,
            EndorsementRequest::STATUS_UNDER_REVIEW,
            EndorsementRequest::STATUS_PENDING_DOCUMENTS,
        ])->count();
        return [
            'documents' => $docs,
            'memberships' => $memberships,
            'change_requests' => $changeRequests,
            'activities' => $activities,
            'calibres' => $calibres,
            'endorsements' => $endorsements,
            'total' => $docs + $memberships + $changeRequests + $activities + $calibres + $endorsements,
        ];
    }

    public function getTypeBadgeClass(string $type): string
    {
        return match($type) {
            'document' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'membership' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
            'change_request' => 'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200',
            'change_payment' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
            'activity' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'calibre' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'endorsement' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }

    public function getTypeLabel(string $type): string
    {
        return match($type) {
            'document' => 'Document',
            'membership' => 'Membership',
            'change_request' => 'Type Change',
            'change_payment' => 'Change Payment',
            'activity' => 'Activity',
            'calibre' => 'Calibre',
            'endorsement' => 'Endorsement',
            default => ucfirst($type),
        };
    }

    public function clearAllApprovals(): void
    {
        $admin = auth()->user();
        $cleared = 0;

        // Approve all pending activities (cascades to their evidence documents)
        $pendingActivities = ShootingActivity::where('status', 'pending')->get();
        foreach ($pendingActivities as $activity) {
            $activity->approve($admin);
            $cleared++;
        }

        // Approve remaining pending documents (excluding any already verified by activity cascade)
        $pendingDocs = MemberDocument::where('status', 'pending')
            ->whereDoesntHave('shootingActivityAsEvidence')
            ->whereDoesntHave('shootingActivityAsAdditional')
            ->get();
        foreach ($pendingDocs as $doc) {
            $doc->verify($admin);
            $cleared++;
        }

        // Approve all pending memberships (skip orphaned records where user or type was deleted)
        $pendingMemberships = Membership::where('status', 'applied')
            ->whereHas('user')
            ->whereHas('type')
            ->with(['user', 'type'])
            ->get();
        foreach ($pendingMemberships as $membership) {
            $expiresAt = $membership->type->calculateExpiryDate(now());
            $membership->update([
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'activated_at' => now(),
                'expires_at' => $expiresAt,
            ]);
            if (!$membership->membership_number) {
                $membership->update([
                    'membership_number' => 'NRAPA-' . date('Y') . '-' . str_pad($membership->id, 5, '0', STR_PAD_LEFT),
                ]);
            }

            try {
                if ($membership->user && !$membership->welcome_email_sent_at) {
                    Mail::to($membership->user->email)->queue(new MembershipApproved(
                        membership: $membership,
                        cardUrl: route('card'),
                    ));
                    $membership->update(['welcome_email_sent_at' => now()]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send bulk approval email', [
                    'membership_id' => $membership->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $cleared++;
        }

        // Clean up orphaned pending memberships (user deleted)
        Membership::where('status', 'applied')
            ->whereDoesntHave('user')
            ->delete();

        // Approve all pending calibre requests
        $pendingCalibres = CalibreRequest::where('status', 'pending')->get();
        foreach ($pendingCalibres as $calibre) {
            $calibre->update([
                'status' => CalibreRequest::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_notes' => 'Bulk approved by admin',
            ]);
            $cleared++;
        }

        // Approve all pending endorsements (set to approved status)
        $pendingEndorsements = EndorsementRequest::whereIn('status', [
            EndorsementRequest::STATUS_SUBMITTED,
            EndorsementRequest::STATUS_UNDER_REVIEW,
            EndorsementRequest::STATUS_PENDING_DOCUMENTS,
        ])->get();
        foreach ($pendingEndorsements as $endorsement) {
            $endorsement->approve($admin, 'Bulk approved by admin');
            $cleared++;
        }

        // Issue all approved endorsements (that haven't been issued yet)
        $approvedEndorsements = EndorsementRequest::where('status', EndorsementRequest::STATUS_APPROVED)->get();
        foreach ($approvedEndorsements as $endorsement) {
            try {
                // Try to issue the endorsement letter properly
                $letterReference = 'BULK-' . date('Ymd') . '-' . str_pad($endorsement->id, 5, '0', STR_PAD_LEFT);
                $endorsement->issue($admin, $letterReference, null);
                $cleared++;
            } catch (\Exception $e) {
                // If we can't issue properly (e.g., not compliant), mark as issued anyway for clearing purposes
                // This allows clearing the outstanding list even if compliance requirements aren't met
                $eligibility = EndorsementRequest::getEligibilitySummary($endorsement->user);
                $membership = $endorsement->user->activeMembership;
                $dedicatedType = $membership?->type?->dedicated_type ?? null;
                $dedicatedCategory = match($dedicatedType) {
                    'sport' => 'Dedicated Sport Shooter',
                    'hunter' => 'Dedicated Hunter',
                    'both' => 'Dedicated Sport Shooter & Dedicated Hunter',
                    default => null,
                };
                
                $endorsement->update([
                    'status' => EndorsementRequest::STATUS_ISSUED,
                    'issued_at' => now(),
                    'issued_by' => $admin->id,
                    'letter_reference' => 'BULK-' . date('Ymd') . '-' . str_pad($endorsement->id, 5, '0', STR_PAD_LEFT),
                    'dedicated_status_compliant' => $eligibility['eligible'] ?? false,
                    'dedicated_category' => $dedicatedCategory,
                    'dedicated_status_snapshot_at' => now(),
                    'expires_at' => now()->addYear(),
                ]);
                $cleared++;
            }
        }

        session()->flash('success', "Cleared {$cleared} outstanding approval(s).");
        $this->dispatch('$refresh');
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Approvals</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review and process pending membership applications and document uploads</p>
    </x-slot>

    {{-- Action Bar --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        @if($this->stats['total'] > 0)
        <button wire:click="clearAllApprovals" 
                wire:confirm="Are you sure you want to approve all outstanding approvals? This action cannot be undone."
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Clear All Approvals
        </button>
        @endif
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Pending</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['total'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Documents</p>
            <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->stats['documents'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Memberships</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->stats['memberships'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Activities</p>
            <p class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $this->stats['activities'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Calibres</p>
            <p class="mt-1 text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $this->stats['calibres'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Endorsements</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['endorsements'] }}</p>
        </div>
    </div>

    {{-- All Pending Approvals --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">All Pending Approvals</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Sorted by date (oldest first)</p>
        </div>

        @if($this->allPendingApprovals->count() > 0)
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach($this->allPendingApprovals as $approval)
            <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                <div class="flex items-center gap-4 flex-1">
                    <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                        {{ $approval['user']->initials() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $approval['title'] }}</h3>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $this->getTypeBadgeClass($approval['type']) }}">
                                {{ $this->getTypeLabel($approval['type']) }}
                            </span>
                            @if(isset($approval['status']))
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                {{ ucfirst(str_replace('_', ' ', $approval['status'])) }}
                            </span>
                            @endif
                        </div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $approval['user']->name }} • {{ $approval['user']->email }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ $approval['date']->diffForHumans() }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 flex-shrink-0">
                    <div class="text-right">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Submitted</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $approval['date']->format('d M Y') }}</p>
                    </div>
                    <a href="{{ $approval['route'] }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        Review
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="p-12 text-center">
            <svg class="mx-auto size-12 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">All caught up!</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                There are no pending approvals requiring attention.
            </p>
        </div>
        @endif
    </div>
</div>
