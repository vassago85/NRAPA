<?php

use App\Models\EndorsementRequest;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Review Endorsement Request - Admin')] class extends Component {
    public EndorsementRequest $request;

    public bool $showRejectModal = false;
    public string $rejectionReason = '';
    public string $adminNotes = '';
    public string $approvalReason = ''; // Required when approving non-compliant member

    public function mount(EndorsementRequest $request): void
    {
        $this->request = $request->load([
            'user', 
            'firearm', 
            'firearm.calibre', 
            'components', 
            'components.calibre',
            'documents',
            'comments',
            'reviewer',
            'issuer',
        ]);
        $this->adminNotes = $request->admin_notes ?? '';
    }

    #[Computed]
    public function memberEligibility(): array
    {
        return EndorsementRequest::getEligibilitySummary($this->request->user);
    }

    #[Computed]
    public function isFullyCompliant(): bool
    {
        return $this->memberEligibility['eligible'] ?? false;
    }

    public function markUnderReview(): void
    {
        if (!$this->request->isSubmitted() || $this->request->status !== 'submitted') {
            session()->flash('error', 'This request cannot be marked as under review.');
            return;
        }

        $this->request->markUnderReview(auth()->user());
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'endorsement_under_review',
            'auditable_type' => EndorsementRequest::class,
            'auditable_id' => $this->request->id,
            'old_values' => ['status' => 'submitted'],
            'new_values' => ['status' => 'under_review'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        session()->flash('success', 'Request marked as under review.');
        $this->request->refresh();
    }

    public function requestMoreDocuments(): void
    {
        $this->validate([
            'adminNotes' => 'required|min:10',
        ], [
            'adminNotes.required' => 'Please specify which documents are needed.',
            'adminNotes.min' => 'Please provide more detail about the required documents.',
        ]);

        $this->request->markPendingDocuments(auth()->user(), $this->adminNotes);
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'endorsement_pending_documents',
            'auditable_type' => EndorsementRequest::class,
            'auditable_id' => $this->request->id,
            'old_values' => ['status' => $this->request->status],
            'new_values' => ['status' => 'pending_documents', 'notes' => $this->adminNotes],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        session()->flash('success', 'Request marked as pending additional documents.');
        $this->request->refresh();
    }

    public function approveEndorsement(): void
    {
        // If member is NOT fully compliant, require a reason/comment
        if (!$this->isFullyCompliant) {
            $this->validate([
                'approvalReason' => 'required|min:20',
            ], [
                'approvalReason.required' => 'You must provide a reason for approving this request since the member is not fully compliant.',
                'approvalReason.min' => 'Please provide a more detailed reason (at least 20 characters).',
            ]);
        }

        // Approve the request (allows letter generation)
        $this->request->approve(auth()->user(), $this->adminNotes);

        // Build admin notes with compliance info if non-compliant
        $notes = $this->adminNotes;
        if (!$this->isFullyCompliant && $this->approvalReason) {
            $complianceNote = "[APPROVED DESPITE NON-COMPLIANCE] Reason: " . $this->approvalReason;
            $notes = $notes ? $notes . "\n\n" . $complianceNote : $complianceNote;
            $this->request->update(['admin_notes' => $notes]);
        }
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'endorsement_approved',
            'auditable_type' => EndorsementRequest::class,
            'auditable_id' => $this->request->id,
            'old_values' => ['status' => $this->request->getOriginal('status')],
            'new_values' => [
                'status' => 'approved',
                'fully_compliant' => $this->isFullyCompliant,
                'approval_reason' => $this->approvalReason ?: null,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        session()->flash('success', 'Endorsement request approved. You can now generate the letter.');
        $this->request->refresh();
    }

    public function issueEndorsement(): void
    {
        // Can only issue if request is approved
        if (!$this->request->isApproved()) {
            session()->flash('error', 'Request must be approved before letter can be issued.');
            return;
        }

        try {
            // Generate letter reference
            $letterReference = EndorsementRequest::generateLetterReference();
            
            // Generate endorsement letter using DocumentRenderer
            $renderer = app(\App\Contracts\DocumentRenderer::class);
            $letterPath = $renderer->renderEndorsementLetter($this->request, 'documents.endorsement-letter');

            $this->request->issue(auth()->user(), $letterReference, $letterPath);
        
            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'endorsement_issued',
                'auditable_type' => EndorsementRequest::class,
                'auditable_id' => $this->request->id,
                'old_values' => ['status' => 'approved'],
                'new_values' => [
                    'status' => 'issued', 
                    'letter_reference' => $letterReference,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            session()->flash('success', 'Endorsement letter issued successfully! Reference: ' . $letterReference);
            $this->request->refresh();
        } catch (\Exception $e) {
            Log::error('Failed to generate endorsement letter', [
                'request_id' => $this->request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to generate endorsement letter: ' . $e->getMessage());
        }
    }

    public function rejectRequest(): void
    {
        $this->validate([
            'rejectionReason' => 'required|min:10',
        ], [
            'rejectionReason.required' => 'Please provide a reason for rejection.',
            'rejectionReason.min' => 'The rejection reason must be at least 10 characters.',
        ]);

        $this->request->reject(auth()->user(), $this->rejectionReason);
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'endorsement_rejected',
            'auditable_type' => EndorsementRequest::class,
            'auditable_id' => $this->request->id,
            'old_values' => ['status' => $this->request->status],
            'new_values' => ['status' => 'rejected', 'reason' => $this->rejectionReason],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->showRejectModal = false;
        session()->flash('success', 'Request rejected.');
        $this->request->refresh();
    }

    public function verifyDocument(int $documentId): void
    {
        $document = $this->request->documents()->find($documentId);
        if ($document) {
            $document->verify(auth()->user());
            session()->flash('success', 'Document verified.');
            $this->request->refresh();
        }
    }

    public function rejectDocument(int $documentId, string $reason): void
    {
        $document = $this->request->documents()->find($documentId);
        if ($document && $reason) {
            $document->reject(auth()->user(), $reason);
            session()->flash('success', 'Document rejected.');
            $this->request->refresh();
        }
    }
}; ?>

<div>
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.endorsements.index') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Back
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Review Endorsement Request</h1>
                <p class="text-zinc-500 dark:text-zinc-400">{{ $request->request_type_label }} - {{ $request->user->name }}</p>
            </div>
        </div>
        <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium {{ $request->status_badge_class }}">
            {{ $request->status_label }}
        </span>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Member Info --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Member Information</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ $request->user->initials() }}
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $request->user->name }}</h3>
                            <p class="text-zinc-500 dark:text-zinc-400">{{ $request->user->email }}</p>
                        </div>
                    </div>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        @if($request->user->phone)
                            <div>
                                <dt class="text-zinc-500">Phone</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->user->phone }}</dd>
                            </div>
                        @endif
                        @if($request->user->id_number)
                            <div>
                                <dt class="text-zinc-500">ID Number</dt>
                                <dd class="font-mono font-medium text-zinc-900 dark:text-white">{{ $request->user->id_number }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Compliance Status Card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border {{ $this->isFullyCompliant ? 'border-emerald-300 dark:border-emerald-700' : 'border-amber-300 dark:border-amber-700' }} overflow-hidden">
                <div class="px-6 py-4 border-b {{ $this->isFullyCompliant ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20' : 'border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20' }}">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold {{ $this->isFullyCompliant ? 'text-emerald-800 dark:text-emerald-200' : 'text-amber-800 dark:text-amber-200' }}">
                            Member Compliance Status
                        </h2>
                        @if($this->isFullyCompliant)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Fully Compliant
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Not Fully Compliant
                            </span>
                        @endif
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid gap-4 md:grid-cols-3">
                        {{-- Knowledge Test --}}
                        <div class="p-4 rounded-lg border {{ $this->memberEligibility['knowledge_test_passed'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' }}">
                            <div class="flex items-center gap-3 mb-2">
                                @if($this->memberEligibility['knowledge_test_passed'])
                                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-full">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                @endif
                                <h3 class="font-semibold text-zinc-900 dark:text-white">Knowledge Test</h3>
                            </div>
                            <p class="text-sm {{ $this->memberEligibility['knowledge_test_passed'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                                {{ $this->memberEligibility['knowledge_test_passed'] ? 'Passed' : 'Not Completed' }}
                            </p>
                        </div>

                        {{-- Documents --}}
                        <div class="p-4 rounded-lg border {{ $this->memberEligibility['documents_complete'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' }}">
                            <div class="flex items-center gap-3 mb-2">
                                @if($this->memberEligibility['documents_complete'])
                                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-full">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                @endif
                                <h3 class="font-semibold text-zinc-900 dark:text-white">Documents</h3>
                            </div>
                            @if($this->memberEligibility['documents_complete'])
                                <p class="text-sm text-emerald-700 dark:text-emerald-300">All Required</p>
                            @else
                                <p class="text-sm text-amber-700 dark:text-amber-300">
                                    Missing {{ count($this->memberEligibility['missing_documents']) }} doc(s)
                                </p>
                            @endif
                        </div>

                        {{-- Activities --}}
                        <div class="p-4 rounded-lg border {{ $this->memberEligibility['activities_met'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' }}">
                            <div class="flex items-center gap-3 mb-2">
                                @if($this->memberEligibility['activities_met'])
                                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-full">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                @endif
                                <h3 class="font-semibold text-zinc-900 dark:text-white">Activities</h3>
                            </div>
                            <p class="text-sm {{ $this->memberEligibility['activities_met'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                                {{ $this->memberEligibility['activity_details']['approved_count'] ?? 0 }} / {{ $this->memberEligibility['activity_details']['required'] ?? 2 }} required
                            </p>
                        </div>
                    </div>

                    @if(!$this->isFullyCompliant && count($this->memberEligibility['errors'] ?? []) > 0)
                        <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">Issues:</h4>
                            <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1">
                                @foreach($this->memberEligibility['errors'] as $error)
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Request Details --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Request Details</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-zinc-500">Request Type</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->request_type_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">Purpose</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->purpose_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">Submitted</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                {{ $request->submitted_at ? $request->submitted_at->format('d M Y H:i') : 'Not yet submitted' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">Declaration</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                @if($request->declaration_accepted_at)
                                    <span class="text-green-600 dark:text-green-400">Accepted {{ $request->declaration_accepted_at->format('d M Y H:i') }}</span>
                                @else
                                    <span class="text-red-600">Not accepted</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                    @if($request->member_notes)
                        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <dt class="text-sm text-zinc-500 mb-1">Member Notes</dt>
                            <dd class="text-sm text-zinc-900 dark:text-white">{{ $request->member_notes }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Firearm Details --}}
            @if($request->firearm)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Firearm Details</h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-zinc-500">Category</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->firearm->category_label }}</dd>
                            </div>
                            @if($request->firearm->calibre_display)
                                <div>
                                    <dt class="text-zinc-500">Calibre</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->firearm->calibre_display }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->ignition_type)
                                <div>
                                    <dt class="text-zinc-500">Ignition</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->firearm->ignition_type_label }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->action_type)
                                <div>
                                    <dt class="text-zinc-500">Action</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->firearm->action_type_label }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->make || $request->firearm->model)
                                <div>
                                    <dt class="text-zinc-500">Make / Model</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->firearm->make }} {{ $request->firearm->model }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->serial_number)
                                <div>
                                    <dt class="text-zinc-500">Serial Number</dt>
                                    <dd class="font-mono font-medium text-zinc-900 dark:text-white">{{ $request->firearm->serial_number }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->licence_section)
                                <div>
                                    <dt class="text-zinc-500">Licence Section</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->firearm->licence_section_label }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->saps_reference)
                                <div>
                                    <dt class="text-zinc-500">SAPS Reference</dt>
                                    <dd class="font-mono font-medium text-zinc-900 dark:text-white">{{ $request->firearm->saps_reference }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            @endif

            {{-- Components --}}
            @if($request->components->count() > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Component Endorsements</h2>
                    </div>
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($request->components as $component)
                            <div class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-zinc-900 dark:text-white">{{ $component->component_type_label }}</h4>
                                        @if($component->component_make || $component->component_model)
                                            <p class="text-sm text-zinc-500">{{ $component->component_make }} {{ $component->component_model }}</p>
                                        @endif
                                        @if($component->calibre_display)
                                            <p class="text-sm text-zinc-500">Calibre: {{ $component->calibre_display }}</p>
                                        @endif
                                        @if($component->component_serial)
                                            <p class="text-sm font-mono text-zinc-500">S/N: {{ $component->component_serial }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Documents --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Documents</h2>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($request->documents as $doc)
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    @if($doc->isVerified())
                                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @elseif($doc->isUploaded())
                                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="p-2 bg-zinc-100 dark:bg-zinc-700 rounded-lg">
                                            <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="font-medium text-zinc-900 dark:text-white">
                                            {{ $doc->document_type_label }}
                                            @if($doc->is_required)
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </h4>
                                        @if($doc->original_filename)
                                            <p class="text-sm text-zinc-500 truncate max-w-sm">{{ $doc->original_filename }}</p>
                                        @endif
                                        @if($doc->isActivityProof() && $doc->activity_date)
                                            <p class="text-xs text-zinc-400">
                                                {{ $doc->activity_type_label }} - {{ $doc->activity_date->format('d M Y') }}
                                                @if($doc->activity_venue) · {{ $doc->activity_venue }} @endif
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $doc->status_badge_class }}">
                                        {{ $doc->status_label }}
                                    </span>

                                    @if($doc->isUploaded() && !$doc->isVerified())
                                        <button wire:click="verifyDocument({{ $doc->id }})"
                                            class="p-1.5 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors"
                                            title="Verify">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar - Actions --}}
        <div class="space-y-6">
            {{-- Actions Card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Actions</h3>
                </div>
                <div class="p-6 space-y-4">
                    @if($request->status === 'submitted')
                        <button wire:click="markUnderReview"
                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            Mark Under Review
                        </button>
                    @endif

                    @if(in_array($request->status, ['submitted', 'under_review', 'pending_documents']))
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Admin Notes</label>
                            <textarea wire:model="adminNotes" rows="3"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm resize-none"
                                placeholder="Add notes for the member or internal use..."></textarea>
                        </div>

                        {{-- Non-compliant approval warning --}}
                        @if(!$this->isFullyCompliant)
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <div>
                                        <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                            Member Not Fully Compliant
                                        </h4>
                                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                            You can still approve this request at your discretion, but you must provide a reason explaining why.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-sm font-medium text-amber-800 dark:text-amber-200 mb-1">
                                        Reason for Approval <span class="text-red-500">*</span>
                                    </label>
                                    <textarea wire:model="approvalReason" rows="3"
                                        class="w-full px-3 py-2 border border-amber-300 dark:border-amber-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm resize-none"
                                        placeholder="Explain why you are approving this despite non-compliance (e.g., documents pending verification, recent membership, exceptional circumstances...)"></textarea>
                                    @error('approvalReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif

                        {{-- Show Approve button if under review or pending documents --}}
                        @if(in_array($request->status, ['under_review', 'pending_documents']))
                            <button wire:click="approveEndorsement"
                                wire:confirm="Are you sure you want to approve this endorsement request?{{ !$this->isFullyCompliant ? ' The member is NOT fully compliant.' : '' }}"
                                class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2 mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Approve Request
                            </button>
                        @endif

                        {{-- Show Issue button only if approved --}}
                        @if($request->isApproved())
                            <button wire:click="issueEndorsement"
                                wire:confirm="Are you sure you want to generate and issue this endorsement letter?"
                                class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2 mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Generate & Issue Letter
                            </button>
                        @endif

                        {{-- Show other action buttons only if not approved/issued --}}
                        @if(!$request->isApproved() && !$request->isIssued())
                            <button wire:click="requestMoreDocuments"
                                class="w-full px-4 py-2 border border-amber-500 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors mb-2">
                                Request More Documents
                            </button>

                            <button wire:click="$set('showRejectModal', true)"
                                class="w-full px-4 py-2 border border-red-500 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                Reject Request
                            </button>
                        @endif
                    @endif

                    {{-- Show approved status --}}
                    @if($request->isApproved() && !$request->isIssued())
                        <div class="text-center py-4 border-t border-zinc-200 dark:border-zinc-700 mt-4">
                            <div class="w-16 h-16 mx-auto bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-emerald-600 dark:text-emerald-400 font-semibold">Request Approved</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Ready for letter generation</p>
                        </div>
                    @endif

                    @if($request->isIssued())
                        <div class="text-center py-4">
                            <div class="w-16 h-16 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-green-600 dark:text-green-400 font-semibold">Endorsement Issued</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Endorsement letter has been generated</p>
                            
                            @if($request->isIssued())
                                <div class="flex gap-2 mt-4">
                                    <a href="{{ route('admin.endorsements.preview', $request) }}" target="_blank"
                                        class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        </svg>
                                        View Letter
                                    </a>
                                    <a href="{{ route('admin.endorsements.preview', $request) }}?print=1" target="_blank"
                                        onclick="window.open(this.href, '_blank', 'width=800,height=600'); setTimeout(() => { window.print(); }, 500); return false;"
                                        class="flex-1 px-4 py-2 border border-emerald-600 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Print
                                    </a>
                                </div>
                                @if($request->letter_reference)
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                                        Reference: {{ $request->letter_reference }}
                                    </p>
                                @endif
                            @endif
                        </div>
                    @endif

                    @if($request->isRejected())
                        <div class="text-center py-4">
                            <div class="w-16 h-16 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-red-600 dark:text-red-400 font-semibold">Request Rejected</p>
                            @if($request->rejection_reason)
                                <p class="text-sm text-zinc-500 mt-2">{{ $request->rejection_reason }}</p>
                            @endif
                        </div>
                    @endif

                    @if($request->isDraft())
                        <div class="text-center py-4">
                            <div class="w-16 h-16 mx-auto bg-zinc-100 dark:bg-zinc-700 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <p class="text-zinc-500 font-semibold">Draft</p>
                            <p class="text-sm text-zinc-400 mt-1">Not yet submitted by member</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Request Info --}}
            @if($request->letter_reference || $request->reviewer || $request->issuer)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Processing Info</h3>
                    </div>
                    <div class="p-6 space-y-3 text-sm">
                        @if($request->letter_reference)
                            <div>
                                <span class="text-zinc-500">Reference:</span>
                                <span class="font-mono font-medium text-zinc-900 dark:text-white ml-2">{{ $request->letter_reference }}</span>
                            </div>
                        @endif
                        @if($request->reviewer)
                            <div>
                                <span class="text-zinc-500">Reviewed by:</span>
                                <span class="font-medium text-zinc-900 dark:text-white ml-2">{{ $request->reviewer->name }}</span>
                            </div>
                        @endif
                        @if($request->issuer)
                            <div>
                                <span class="text-zinc-500">Issued by:</span>
                                <span class="font-medium text-zinc-900 dark:text-white ml-2">{{ $request->issuer->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Reject Modal --}}
    @if($showRejectModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showRejectModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Reject Request</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reason for Rejection <span class="text-red-500">*</span></label>
                        <textarea wire:model="rejectionReason" rows="4"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white resize-none"
                            placeholder="Provide a clear reason for the rejection..."></textarea>
                        @error('rejectionReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showRejectModal', false)"
                            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button wire:click="rejectRequest"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                            Reject Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
