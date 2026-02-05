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
    public bool $showIssueModal = false;
    public string $selectedDedicatedCategory = '';

    public function mount(EndorsementRequest $request): void
    {
        $relationships = [
            'user', 
            'firearm', 
            'firearm.firearmCalibre',
            'firearm.firearmMake',
            'firearm.firearmModel',
            'components', 
            'documents',
            'reviewer',
            'issuer',
        ];
        
        // Only load comments if the table exists
        if (\Illuminate\Support\Facades\Schema::hasTable('comments')) {
            $relationships[] = 'comments';
        }
        
        $this->request = $request->load($relationships);
        $this->adminNotes = $request->admin_notes ?? '';
        
        // Pre-select dedicated category if already set, otherwise determine from membership
        if ($request->dedicated_category) {
            $this->selectedDedicatedCategory = $request->dedicated_category;
        } else {
            $membership = $request->user->activeMembership;
            $dedicatedType = $membership?->type?->dedicated_type ?? null;
            $this->selectedDedicatedCategory = match($dedicatedType) {
                'sport' => 'Dedicated Sport Shooter',
                'hunter' => 'Dedicated Hunter',
                'both' => 'Dedicated Sport Shooter & Dedicated Hunter',
                default => '',
            };
        }
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
        // Approve the request (allows letter generation)
        $this->request->approve(auth()->user(), $this->adminNotes);
        
        // Add note if non-compliant
        if (!$this->isFullyCompliant) {
            $errors = $this->memberEligibility['errors'] ?? [];
            $errorMessages = array_map(fn($error) => is_array($error) ? ($error['message'] ?? '') : $error, $errors);
            $errorList = implode('. ', array_filter($errorMessages));
            
            $complianceNote = "[APPROVED DESPITE NON-COMPLIANCE] Member is not fully compliant: " . $errorList;
            $notes = $this->adminNotes ? $this->adminNotes . "\n\n" . $complianceNote : $complianceNote;
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
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $message = $this->isFullyCompliant 
            ? 'Endorsement request approved. You can now generate the letter.'
            : 'Endorsement request approved despite non-compliance. You can now generate the letter.';
        
        session()->flash('success', $message);
        $this->request->refresh();
    }

    #[Computed]
    public function memberDedicatedStatus(): array
    {
        $user = $this->request->user;
        $eligibility = EndorsementRequest::getEligibilitySummary($user);
        $membership = $user->activeMembership;
        $dedicatedType = $membership?->type?->dedicated_type ?? null;
        
        $category = match($dedicatedType) {
            'sport' => 'Dedicated Sport Shooter',
            'hunter' => 'Dedicated Hunter',
            'both' => 'Dedicated Sport Shooter & Dedicated Hunter',
            default => null,
        };
        
        return [
            'compliant' => $eligibility['eligible'] ?? false,
            'category' => $category,
            'dedicated_type' => $dedicatedType,
        ];
    }

    public function openIssueModal(): void
    {
        if (!$this->request->isApproved()) {
            session()->flash('error', 'Request must be approved before letter can be issued.');
            return;
        }
        
        // Check compliance
        $status = $this->memberDedicatedStatus;
        if (!$status['compliant']) {
            session()->flash('error', 'Dedicated Status is not compliant. Endorsement cannot be issued.');
            return;
        }
        
        // Pre-select category if only one option
        if (empty($this->selectedDedicatedCategory) && $status['category']) {
            $this->selectedDedicatedCategory = $status['category'];
        }
        
        $this->showIssueModal = true;
    }

    public function issueEndorsement(): void
    {
        // Validate dedicated category is selected
        if (empty($this->selectedDedicatedCategory)) {
            session()->flash('error', 'Please select a Dedicated Category.');
            return;
        }
        
        // Can only issue if request is approved
        if (!$this->request->isApproved()) {
            session()->flash('error', 'Request must be approved before letter can be issued.');
            return;
        }

        // Check compliance
        $status = $this->memberDedicatedStatus;
        if (!$status['compliant']) {
            session()->flash('error', 'Dedicated Status is not compliant. Endorsement cannot be issued.');
            return;
        }

        try {
            // Generate letter reference
            $letterReference = EndorsementRequest::generateLetterReference();
            
            // Generate endorsement letter using DocumentRenderer
            $renderer = app(\App\Contracts\DocumentRenderer::class);
            $letterPath = $renderer->renderEndorsementLetter($this->request, 'documents.letters.endorsement');

            // Issue with dedicated status snapshot
            $this->request->issue(
                auth()->user(), 
                $letterReference, 
                $letterPath,
                $status['compliant'],
                $this->selectedDedicatedCategory
            );
        
            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'endorsement_issued',
                'auditable_type' => EndorsementRequest::class,
                'auditable_id' => $this->request->id,
                'old_values' => ['status' => 'approved'],
                'new_values' => [
                    'status' => 'issued', 
                    'letter_reference' => $letterReference,
                    'dedicated_status_compliant' => $status['compliant'],
                    'dedicated_category' => $this->selectedDedicatedCategory,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $this->showIssueModal = false;
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

    public bool $showDeleteModal = false;

    public function deleteEndorsement(): void
    {
        try {
            // Log the deletion
            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'endorsement_deleted',
                'auditable_type' => EndorsementRequest::class,
                'auditable_id' => $this->request->id,
                'old_values' => [
                    'uuid' => $this->request->uuid,
                    'status' => $this->request->status,
                    'user_id' => $this->request->user_id,
                    'request_type' => $this->request->request_type,
                ],
                'new_values' => ['deleted' => true],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Delete associated file if exists
            if ($this->request->letter_file_path) {
                $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';
                \Illuminate\Support\Facades\Storage::disk($disk)->delete($this->request->letter_file_path);
            }

            $requestUuid = $this->request->uuid;
            $this->request->delete(); // Soft delete

            session()->flash('success', "Endorsement request {$requestUuid} has been deleted.");
            $this->redirect(route('admin.endorsements.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Failed to delete endorsement request', [
                'request_id' => $this->request->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to delete endorsement request: ' . $e->getMessage());
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
                                        {{ $error['message'] ?? (is_string($error) ? $error : json_encode($error)) }}
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
                            @if($request->firearm->make_display || $request->firearm->make || $request->firearm->model_display || $request->firearm->model)
                                <div>
                                    <dt class="text-zinc-500">Make / Model</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->firearm->make_display ?? $request->firearm->make }} {{ $request->firearm->model_display ?? $request->firearm->model }}</dd>
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
                                        @if($component->component_type === 'barrel')
                                            @if($component->diameter)
                                                <p class="text-sm text-zinc-500">Diameter: {{ $component->diameter }}</p>
                                            @elseif($component->calibre_display)
                                                <p class="text-sm text-zinc-500">Calibre: {{ $component->calibre_display }}</p>
                                            @endif
                                        @elseif($component->calibre_display)
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
                        @if(!$this->isFullyCompliant && in_array($request->status, ['submitted', 'under_review', 'pending_documents']))
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg mb-3">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                            Member Not Fully Compliant
                                        </h4>
                                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                            The member has not met all prerequisites:
                                        </p>
                                        <ul class="text-xs text-amber-700 dark:text-amber-300 mt-2 space-y-1 list-disc list-inside">
                                            @foreach($this->memberEligibility['errors'] ?? [] as $error)
                                                <li>{{ is_array($error) ? ($error['message'] ?? '') : $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Show Approve button if submitted, under review, or pending documents --}}
                        @if(in_array($request->status, ['submitted', 'under_review', 'pending_documents']))
                            <button wire:click="approveEndorsement"
                                wire:confirm="{{ !$this->isFullyCompliant ? 'WARNING: This member is NOT fully compliant. Are you sure you want to approve this endorsement request despite the missing prerequisites?' : 'Are you sure you want to approve this endorsement request?' }}"
                                wire:loading.attr="disabled"
                                class="w-full px-4 py-2 {{ !$this->isFullyCompliant ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white rounded-lg transition-colors flex items-center justify-center gap-2 mb-3 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="approveEndorsement">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ !$this->isFullyCompliant ? 'Approve (Non-Compliant)' : 'Approve Request' }}
                                </span>
                                <span wire:loading wire:target="approveEndorsement">Processing...</span>
                            </button>
                        @endif

                        {{-- Show Issue button only if approved --}}
                        @if($request->isApproved())
                            @php
                                $dedicatedStatus = $this->memberDedicatedStatus;
                                $isCompliant = $dedicatedStatus['compliant'] ?? false;
                            @endphp
                            
                            @if(!$isCompliant)
                                <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                    <p class="text-sm text-red-800 dark:text-red-200 font-medium">Dedicated Status is not compliant. Endorsement cannot be issued.</p>
                                </div>
                            @else
                                <button wire:click="openIssueModal"
                                    class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2 mb-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Generate & Issue Letter
                                </button>
                            @endif
                        @endif

                        {{-- Show other action buttons only if not approved/issued --}}
                        @if(!$request->isApproved() && !$request->isIssued())
                            <button wire:click="requestMoreDocuments"
                                class="w-full px-4 py-2 border border-amber-500 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors mb-2">
                                Request More Documents
                            </button>

                            <button wire:click="$set('showRejectModal', true)"
                                class="w-full px-4 py-2 border border-red-500 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors mb-2">
                                Reject Request
                            </button>
                        @endif

                        {{-- Delete button (always available for admin) --}}
                        <div class="border-t border-zinc-200 dark:border-zinc-700 mt-4 pt-4">
                            <button wire:click="$set('showDeleteModal', true)"
                                class="w-full px-4 py-2 border border-red-500 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete Request
                            </button>
                        </div>
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
                            @if($request->expires_at)
                                <p class="text-sm mt-2 {{ $request->is_expired ? 'text-red-600 dark:text-red-400' : ($request->is_expiring_soon ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500 dark:text-zinc-400') }}">
                                    Expires: {{ $request->expires_at->format('d M Y') }}
                                    @if($request->is_expired)
                                        <span class="ml-1">(Expired)</span>
                                    @elseif($request->is_expiring_soon)
                                        <span class="ml-1">(Expiring Soon)</span>
                                    @endif
                                </p>
                            @endif
                            
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
                                    <a href="{{ route('admin.endorsements.preview', $request) }}" target="_blank"
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
                        @if($request->expires_at)
                            <div>
                                <span class="text-zinc-500">Expires:</span>
                                <span class="font-medium {{ $request->is_expired ? 'text-red-600 dark:text-red-400' : ($request->is_expiring_soon ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-white') }} ml-2">
                                    {{ $request->expires_at->format('d M Y') }}
                                    @if($request->is_expired)
                                        <span class="text-xs">(Expired)</span>
                                    @elseif($request->is_expiring_soon)
                                        <span class="text-xs">(Expiring Soon)</span>
                                    @endif
                                </span>
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

    {{-- Issue Endorsement Modal --}}
    @if($showIssueModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showIssueModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Issue Endorsement Letter</h3>
                    
                    @php
                        $dedicatedStatus = $this->memberDedicatedStatus;
                    @endphp
                    
                    <div class="mb-4 p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">Member's Current Dedicated Status:</p>
                        <p class="font-medium text-zinc-900 dark:text-white">
                            @if($dedicatedStatus['compliant'])
                                <span class="text-emerald-600 dark:text-emerald-400">Compliant</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">Non-Compliant</span>
                            @endif
                        </p>
                        @if($dedicatedStatus['category'])
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Category: {{ $dedicatedStatus['category'] }}</p>
                        @endif
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Dedicated Category <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="selectedDedicatedCategory" 
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select category...</option>
                            <option value="Dedicated Sport Shooter">Dedicated Sport Shooter</option>
                            <option value="Dedicated Hunter">Dedicated Hunter</option>
                            <option value="Dedicated Sport Shooter & Dedicated Hunter">Dedicated Sport Shooter & Dedicated Hunter</option>
                        </select>
                        @error('selectedDedicatedCategory') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showIssueModal', false)"
                            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button wire:click="issueEndorsement"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg disabled:opacity-50">
                            <span wire:loading.remove wire:target="issueEndorsement">Issue Letter</span>
                            <span wire:loading wire:target="issueEndorsement">Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showDeleteModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Delete Endorsement Request</h3>
                    </div>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                        Are you sure you want to delete this endorsement request? 
                        This action will soft-delete the request and can be restored from the database if needed. 
                        The associated letter file will also be deleted.
                    </p>
                    <div class="flex gap-3 justify-end">
                        <button wire:click="$set('showDeleteModal', false)" 
                            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button wire:click="deleteEndorsement" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                            Delete Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
