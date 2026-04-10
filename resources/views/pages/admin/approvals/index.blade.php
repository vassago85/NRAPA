<?php

use App\Jobs\SyncMembershipToSage;
use App\Mail\MembershipApproved;
use App\Mail\PaymentInstructions;
use App\Mail\PopFollowupReminder;
use App\Models\AuditLog;
use App\Models\CalibreRequest;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\EndorsementRequest;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\ShootingActivity;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('All Approvals - Admin')] class extends Component {

    #[Computed]
    public function pendingApprovals()
    {
        $items = collect();

        // --- Non-membership items (always pending approvals) ---

        $items = $items->merge(
            MemberDocument::where('status', 'pending')
                ->whereDoesntHave('shootingActivityAsEvidence')
                ->whereDoesntHave('shootingActivityAsAdditional')
                ->with(['user'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($doc) => [
                    'type' => 'document',
                    'id' => $doc->id,
                    'title' => $doc->document_type . ' Document',
                    'user' => $doc->user,
                    'date' => $doc->created_at,
                    'route' => route('admin.documents.show', $doc),
                ])
        );

        $items = $items->merge(
            ShootingActivity::where('status', 'pending')
                ->with(['user', 'activityType'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($activity) => [
                    'type' => 'activity',
                    'id' => $activity->id,
                    'title' => ($activity->activityType?->name ?? 'Shooting Activity') . ' - ' . ($activity->activity_date?->format('d M Y') ?? 'N/A'),
                    'user' => $activity->user,
                    'date' => $activity->created_at,
                    'route' => route('admin.activities.show', $activity),
                ])
        );

        $items = $items->merge(
            CalibreRequest::where('status', 'pending')
                ->with(['user'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($calibre) => [
                    'type' => 'calibre',
                    'id' => $calibre->id,
                    'title' => 'Calibre Request: ' . ($calibre->calibre_name ?? 'N/A'),
                    'user' => $calibre->user,
                    'date' => $calibre->created_at,
                    'route' => route('admin.calibre-requests.show', $calibre),
                ])
        );

        $items = $items->merge(
            EndorsementRequest::whereIn('status', [
                EndorsementRequest::STATUS_SUBMITTED,
                EndorsementRequest::STATUS_UNDER_REVIEW,
                EndorsementRequest::STATUS_PENDING_DOCUMENTS,
            ])
                ->with(['user', 'firearm'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($endorsement) => [
                    'type' => 'endorsement',
                    'id' => $endorsement->id,
                    'title' => 'Endorsement Request' . ($endorsement->firearm ? ' - ' . ($endorsement->firearm->display_name ?? 'N/A') : ''),
                    'user' => $endorsement->user,
                    'date' => $endorsement->created_at,
                    'status' => $endorsement->status,
                    'route' => route('admin.endorsements.show', $endorsement),
                ])
        );

        // --- Memberships ready for admin action ---

        // Applied memberships that are either non-billable (imports) OR have POP/payment confirmed
        $items = $items->merge(
            Membership::where('status', 'applied')
                ->whereHas('user')
                ->where(function ($q) {
                    $q->whereNotIn('source', ['web', 'admin'])                  // non-billable (imports)
                        ->orWhereNotNull('proof_of_payment_path')               // POP uploaded
                        ->orWhereNotNull('payment_confirmed_at');               // admin confirmed payment
                })
                ->with(['user', 'type'])
                ->orderBy('applied_at', 'asc')
                ->get()
                ->map(fn ($m) => [
                    'type' => 'membership',
                    'id' => $m->id,
                    'title' => ($m->type?->name ?? 'Membership') . ' Membership',
                    'user' => $m->user,
                    'date' => $m->applied_at,
                    'route' => route('admin.approvals.show', $m),
                ])
        );

        // Type change requests awaiting admin to set amount
        $items = $items->merge(
            Membership::where('status', 'pending_change')
                ->whereHas('user')
                ->with(['user', 'type', 'previousMembership.type'])
                ->orderBy('applied_at', 'asc')
                ->get()
                ->map(function ($m) {
                    $fromType = $m->previousMembership?->type?->name ?? 'Unknown';
                    $toType = $m->type?->name ?? 'Unknown';
                    return [
                        'type' => 'change_request',
                        'id' => $m->id,
                        'title' => "Type Change: {$fromType} → {$toType}",
                        'user' => $m->user,
                        'date' => $m->applied_at,
                        'route' => route('admin.approvals.show', $m),
                    ];
                })
        );

        // Pending payment with POP uploaded or payment confirmed
        $items = $items->merge(
            Membership::where('status', 'pending_payment')
                ->whereHas('user')
                ->where(function ($q) {
                    $q->whereNotNull('proof_of_payment_path')
                        ->orWhereNotNull('payment_confirmed_at');
                })
                ->with(['user', 'type', 'previousMembership.type'])
                ->orderBy('applied_at', 'asc')
                ->get()
                ->map(fn ($m) => [
                    'type' => 'change_payment',
                    'id' => $m->id,
                    'title' => "Change Payment: " . ($m->type?->name ?? 'Unknown') . ($m->proof_of_payment_path ? ' (POP uploaded)' : ' (Payment confirmed)'),
                    'user' => $m->user,
                    'date' => $m->applied_at,
                    'route' => route('admin.approvals.show', $m),
                ])
        );

        return $items
            ->filter(fn ($item) => $item['user'] !== null)
            ->sortBy('date')
            ->values();
    }

    #[Computed]
    public function awaitingPayment()
    {
        $items = collect();

        // Billable applied memberships without POP and no payment confirmation
        $items = $items->merge(
            Membership::where('status', 'applied')
                ->whereHas('user')
                ->whereIn('source', ['web', 'admin'])
                ->whereNull('proof_of_payment_path')
                ->whereNull('payment_confirmed_at')
                ->with(['user', 'type'])
                ->orderBy('applied_at', 'asc')
                ->get()
                ->map(fn ($m) => [
                    'type' => 'membership',
                    'id' => $m->id,
                    'membership_id' => $m->id,
                    'membership_uuid' => $m->uuid,
                    'title' => ($m->type?->name ?? 'Membership') . ($m->isRenewal() ? ' Renewal' : ' Membership'),
                    'is_renewal' => $m->isRenewal(),
                    'user' => $m->user,
                    'date' => $m->applied_at,
                    'payment_reference' => $m->payment_reference,
                    'pop_reminder_sent_at' => $m->pop_reminder_sent_at,
                    'amount_due' => $m->amount_due,
                    'route' => route('admin.approvals.show', $m),
                ])
        );

        // Pending payment without POP and not confirmed
        $items = $items->merge(
            Membership::where('status', 'pending_payment')
                ->whereHas('user')
                ->whereNull('proof_of_payment_path')
                ->whereNull('payment_confirmed_at')
                ->with(['user', 'type', 'previousMembership.type'])
                ->orderBy('applied_at', 'asc')
                ->get()
                ->map(fn ($m) => [
                    'type' => 'change_payment',
                    'id' => $m->id,
                    'membership_id' => $m->id,
                    'membership_uuid' => $m->uuid,
                    'title' => "Change Payment: " . ($m->type?->name ?? 'Unknown'),
                    'user' => $m->user,
                    'date' => $m->applied_at,
                    'payment_reference' => $m->payment_reference,
                    'pop_reminder_sent_at' => $m->pop_reminder_sent_at,
                    'route' => route('admin.approvals.show', $m),
                ])
        );

        return $items
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
        $activities = ShootingActivity::where('status', 'pending')->count();
        $calibres = CalibreRequest::where('status', 'pending')->count();
        $endorsements = EndorsementRequest::whereIn('status', [
            EndorsementRequest::STATUS_SUBMITTED,
            EndorsementRequest::STATUS_UNDER_REVIEW,
            EndorsementRequest::STATUS_PENDING_DOCUMENTS,
        ])->count();

        // Awaiting payment: billable applied without POP/confirmation + pending_payment without POP/confirmation
        $awaitingPaymentCount = Membership::where('status', 'applied')
                ->whereHas('user')
                ->whereIn('source', ['web', 'admin'])
                ->whereNull('proof_of_payment_path')
                ->whereNull('payment_confirmed_at')
                ->count()
            + Membership::where('status', 'pending_payment')
                ->whereHas('user')
                ->whereNull('proof_of_payment_path')
                ->whereNull('payment_confirmed_at')
                ->count();

        // Actionable memberships (those NOT in awaiting payment)
        $actionableMemberships = Membership::where('status', 'applied')
                ->whereHas('user')
                ->where(function ($q) {
                    $q->whereNotIn('source', ['web', 'admin'])
                        ->orWhereNotNull('proof_of_payment_path')
                        ->orWhereNotNull('payment_confirmed_at');
                })
                ->count()
            + Membership::whereIn('status', ['pending_change'])->whereHas('user')->count()
            + Membership::where('status', 'pending_payment')
                ->whereHas('user')
                ->where(function ($q) {
                    $q->whereNotNull('proof_of_payment_path')
                        ->orWhereNotNull('payment_confirmed_at');
                })
                ->count();

        $pendingApprovalCount = $docs + $activities + $calibres + $endorsements + $actionableMemberships;

        return [
            'pending_approvals' => $pendingApprovalCount,
            'awaiting_payment' => $awaitingPaymentCount,
            'total' => $pendingApprovalCount + $awaitingPaymentCount,
            'documents' => $docs,
            'memberships' => $actionableMemberships,
            'activities' => $activities,
            'calibres' => $calibres,
            'endorsements' => $endorsements,
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

    public function confirmPayment(int $membershipId): void
    {
        $membership = Membership::whereHas('user')->findOrFail($membershipId);
        $admin = auth()->user();

        $membership->update([
            'payment_confirmed_at' => now(),
            'payment_confirmed_by' => $admin->id,
        ]);

        AuditLog::log(
            'payment_confirmed_by_admin',
            $membership,
            ['payment_confirmed_at' => null],
            ['payment_confirmed_at' => now()->toDateTimeString(), 'confirmed_by' => $admin->name],
            $admin
        );

        unset($this->pendingApprovals, $this->awaitingPayment, $this->stats);

        session()->flash('success', 'Payment confirmed for ' . $membership->user->name . '. Moved to Pending Approvals.');
    }

    public function sendPaymentReminder(int $membershipId): void
    {
        $membership = Membership::with(['user', 'type'])->whereHas('user')->findOrFail($membershipId);

        if (!$membership->user?->email) {
            session()->flash('error', 'Member has no email address.');
            return;
        }

        try {
            Mail::to($membership->user->email)->queue(new PopFollowupReminder($membership));
            $membership->update(['pop_reminder_sent_at' => now()]);

            unset($this->awaitingPayment);

            session()->flash('success', 'Payment reminder sent to ' . $membership->user->email . '.');
        } catch (\Exception $e) {
            Log::warning('Failed to send payment reminder', [
                'membership_id' => $membership->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to send reminder: ' . $e->getMessage());
        }
    }

    public function approveMembership(int $membershipId): void
    {
        $membership = Membership::with(['user', 'type', 'affiliatedClub'])
            ->whereHas('user')
            ->whereHas('type')
            ->findOrFail($membershipId);

        if ($membership->status !== 'applied') {
            session()->flash('error', 'This membership has already been processed.');
            return;
        }

        $admin = auth()->user();
        $expiresAt = $membership->type->calculateExpiryDate(now());

        $membership->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'activated_at' => now(),
            'expires_at' => $expiresAt,
            'payment_confirmed_at' => $membership->payment_confirmed_at ?? now(),
            'payment_confirmed_by' => $membership->payment_confirmed_by ?? $admin->id,
        ]);

        if (!$membership->membership_number && $membership->user) {
            $membership->update([
                'membership_number' => $membership->user->formatted_member_number,
            ]);
        }

        // Issue certificates
        try {
            $service = app(\App\Services\CertificateIssueService::class);
            $service->issueMembershipCertificate($membership->user, $admin, skipChecks: true);
        } catch (\Exception $e) {
            Log::info('Certificate not issued at inline approval', ['membership_id' => $membership->id, 'reason' => $e->getMessage()]);
        }

        // Issue welcome letter + card
        try {
            $service = app(\App\Services\CertificateIssueService::class);
            try { $service->issueWelcomeLetter($membership->user, $admin); } catch (\Exception $e) {
                $certType = CertificateType::firstOrCreate(['slug' => 'welcome-letter'], ['name' => 'Welcome Letter', 'description' => 'Welcome letter for new members', 'template' => 'documents.welcome-letter', 'is_active' => true, 'sort_order' => 14]);
                Certificate::create(['user_id' => $membership->user->id, 'membership_id' => $membership->id, 'certificate_type_id' => $certType->id, 'certificate_number' => 'WEL-' . strtoupper(substr(md5(uniqid()), 0, 8)), 'qr_code' => bin2hex(random_bytes(16)), 'issued_at' => now(), 'valid_from' => now(), 'issued_by' => $admin->id]);
            }
            try { $service->issueMembershipCard($membership->user, $admin); } catch (\Exception $e) {
                $certType = CertificateType::firstOrCreate(['slug' => 'membership-card'], ['name' => 'Membership Card', 'description' => 'NRAPA membership identification card', 'template' => 'documents.membership-card', 'is_active' => true, 'sort_order' => 13]);
                Certificate::create(['user_id' => $membership->user->id, 'membership_id' => $membership->id, 'certificate_type_id' => $certType->id, 'certificate_number' => 'CARD-' . strtoupper(substr(md5(uniqid()), 0, 8)), 'qr_code' => bin2hex(random_bytes(16)), 'issued_at' => now(), 'valid_from' => now(), 'valid_until' => $membership->expires_at, 'issued_by' => $admin->id]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to issue welcome letter/card on inline approval', ['membership_id' => $membership->id, 'error' => $e->getMessage()]);
        }

        // Send emails
        try {
            if ($membership->user && !$membership->welcome_email_sent_at) {
                Mail::to($membership->user->email)->queue(new MembershipApproved(membership: $membership, cardUrl: route('card')));
                $membership->update(['welcome_email_sent_at' => now()]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send approval email', ['membership_id' => $membership->id, 'error' => $e->getMessage()]);
        }

        if ($membership->source !== 'import' && $membership->user) {
            try {
                $bankAccount = SystemSetting::getBankAccount();
                Mail::to($membership->user->email)->queue(new PaymentInstructions($membership->load('type', 'user', 'affiliatedClub'), $bankAccount, $membership->payment_reference));
            } catch (\Exception $e) {
                Log::warning('Failed to queue payment email on inline approval', ['membership_id' => $membership->id, 'error' => $e->getMessage()]);
            }
        }

        AuditLog::log('membership_approved', $membership, ['status' => 'applied'], ['status' => 'active'], $admin);
        SyncMembershipToSage::dispatch($membership)->afterCommit();

        unset($this->pendingApprovals, $this->awaitingPayment, $this->stats);

        session()->flash('success', $membership->user->name . ' approved and activated!');
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

        // Approve actionable memberships only (skip awaiting-payment items)
        $pendingMemberships = Membership::where('status', 'applied')
            ->whereHas('user')
            ->whereHas('type')
            ->where(function ($q) {
                $q->whereNotIn('source', ['web', 'admin'])
                    ->orWhereNotNull('proof_of_payment_path')
                    ->orWhereNotNull('payment_confirmed_at');
            })
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
            if (!$membership->membership_number && $membership->user) {
                $membership->update([
                    'membership_number' => $membership->user->formatted_member_number,
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

            if ($membership->source !== 'import' && $membership->user) {
                try {
                    $bankAccount = \App\Models\SystemSetting::getBankAccount();
                    Mail::to($membership->user->email)->queue(new PaymentInstructions(
                        $membership->load('type', 'user', 'affiliatedClub'),
                        $bankAccount,
                        $membership->payment_reference,
                    ));
                } catch (\Exception $e) {
                    Log::warning('Failed to queue payment email on bulk approval', [
                        'membership_id' => $membership->id,
                        'error' => $e->getMessage(),
                    ]);
                }
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
                $letterReference = 'BULK-' . date('Ymd') . '-' . str_pad($endorsement->id, 5, '0', STR_PAD_LEFT);
                $endorsement->issue($admin, $letterReference, null);
                $cleared++;
            } catch (\Exception $e) {
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

        session()->flash('success', "Cleared {$cleared} pending approval(s). Items awaiting payment were not affected.");
        $this->dispatch('$refresh');
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Approvals</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review and process pending membership applications and document uploads</p>
    </x-slot>

    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Outstanding</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['total'] }}</p>
        </div>
        <div class="rounded-xl border border-nrapa-blue/30 bg-nrapa-blue/5 p-4 dark:border-nrapa-blue/40 dark:bg-nrapa-blue/10">
            <p class="text-sm text-nrapa-blue dark:text-blue-300">Pending Approvals</p>
            <p class="mt-1 text-2xl font-bold text-nrapa-blue dark:text-blue-400">{{ $this->stats['pending_approvals'] }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $this->stats['documents'] }} docs, {{ $this->stats['memberships'] }} memberships, {{ $this->stats['activities'] }} activities, {{ $this->stats['calibres'] }} calibres, {{ $this->stats['endorsements'] }} endorsements
            </p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
            <p class="text-sm text-amber-700 dark:text-amber-300">Awaiting Payment</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['awaiting_payment'] }}</p>
            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Blocked on member payment</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 flex items-center justify-center">
            @if($this->stats['pending_approvals'] > 0)
            <button wire:click="clearAllApprovals"
                    wire:confirm="Are you sure you want to approve all items in Pending Approvals? Items awaiting payment will not be affected. This action cannot be undone."
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Clear All Approvals
            </button>
            @else
            <p class="text-sm text-zinc-400 dark:text-zinc-500">No pending approvals</p>
            @endif
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- SECTION 1: PENDING APPROVALS                --}}
    {{-- ============================================ --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <div class="flex size-8 items-center justify-center rounded-lg bg-nrapa-blue/10 dark:bg-nrapa-blue/20">
                    <svg class="size-5 text-nrapa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Pending Approvals</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Items ready for admin review — sorted by date (oldest first)</p>
                </div>
                @if($this->pendingApprovals->count() > 0)
                <span class="ml-auto inline-flex items-center rounded-full bg-nrapa-blue/10 px-2.5 py-1 text-xs font-semibold text-nrapa-blue dark:bg-nrapa-blue/20 dark:text-blue-300">
                    {{ $this->pendingApprovals->count() }}
                </span>
                @endif
            </div>
        </div>

        @if($this->pendingApprovals->count() > 0)
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach($this->pendingApprovals as $approval)
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
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $approval['user']->name }} &middot; {{ $approval['user']->email }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ $approval['date']->diffForHumans() }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 flex-shrink-0">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Submitted</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $approval['date']->format('d M Y') }}</p>
                    </div>
                    <a href="{{ $approval['route'] }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        Review
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="p-12 text-center">
            <svg class="mx-auto size-12 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">All caught up!</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                There are no pending approvals requiring attention.
            </p>
        </div>
        @endif
    </div>

    {{-- ============================================ --}}
    {{-- SECTION 2: AWAITING PAYMENT                 --}}
    {{-- ============================================ --}}
    <div class="rounded-xl border border-amber-200 bg-white dark:border-amber-800/50 dark:bg-zinc-800">
        <div class="border-b border-amber-200 dark:border-amber-800/50 p-6 bg-amber-50/50 dark:bg-amber-900/10">
            <div class="flex items-center gap-3">
                <div class="flex size-8 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                    <svg class="size-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Awaiting Payment</h2>
                    <p class="text-sm text-amber-600 dark:text-amber-400">Blocked on member payment / proof of payment outstanding</p>
                </div>
                @if($this->awaitingPayment->count() > 0)
                <span class="ml-auto inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                    {{ $this->awaitingPayment->count() }}
                </span>
                @endif
            </div>
        </div>

        @if($this->awaitingPayment->count() > 0)
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach($this->awaitingPayment as $item)
            <div class="flex flex-col gap-4 p-6 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                <div class="flex items-center gap-4 flex-1">
                    <div class="flex size-12 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                        {{ $item['user']->initials() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $item['title'] }}</h3>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $this->getTypeBadgeClass($item['type']) }}">
                                {{ $this->getTypeLabel($item['type']) }}
                            </span>
                            @if($item['is_renewal'] ?? false)
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-900 dark:text-sky-200">
                                Renewal
                            </span>
                            @endif
                        </div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $item['user']->name }} &middot; {{ $item['user']->email }}</p>
                        <div class="flex items-center gap-3 mt-1 flex-wrap">
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $item['date']->diffForHumans() }}</p>
                            @if($item['payment_reference'])
                            <span class="text-xs font-mono text-zinc-500 dark:text-zinc-400">Ref: {{ $item['payment_reference'] }}</span>
                            @endif
                            @if(isset($item['amount_due']) && $item['amount_due'] > 0)
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">R{{ number_format($item['amount_due'], 2) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap sm:ml-16">
                    @if($item['type'] === 'membership')
                    <button
                        wire:click="approveMembership({{ $item['membership_id'] }})"
                        wire:loading.attr="disabled"
                        wire:target="approveMembership({{ $item['membership_id'] }})"
                        wire:confirm="Approve {{ $item['user']->name }}? This will activate the membership, issue certificates, and send emails."
                        class="inline-flex items-center gap-1.5 rounded-lg bg-nrapa-blue px-3 py-1.5 text-xs font-medium text-white hover:bg-nrapa-blue-dark transition-colors disabled:opacity-50">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        Approve
                    </button>
                    @endif
                    <button
                        wire:click="confirmPayment({{ $item['membership_id'] }})"
                        wire:confirm="Confirm payment received for {{ $item['user']->name }}? This will move the item to Pending Approvals for review."
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 transition-colors">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Mark Paid
                    </button>
                    <button
                        wire:click="sendPaymentReminder({{ $item['membership_id'] }})"
                        wire:confirm="Send a payment reminder email to {{ $item['user']->email }}?"
                        @if($item['pop_reminder_sent_at']) disabled title="Reminder already sent" @endif
                        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:bg-zinc-800 dark:text-amber-400 dark:hover:bg-amber-950/20 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        @if($item['pop_reminder_sent_at'])
                            Reminded
                        @else
                            Remind
                        @endif
                    </button>
                    <a href="{{ $item['route'] }}" wire:navigate
                        class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        View
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="p-8 text-center">
            <svg class="mx-auto size-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
            </svg>
            <h3 class="mt-3 font-semibold text-zinc-900 dark:text-white">No outstanding payments</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                All members have submitted their proof of payment.
            </p>
        </div>
        @endif
    </div>
</div>
