<?php

use App\Models\DocumentType;
use App\Models\MemberDocument;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app.sidebar')] class extends Component {
    use WithFileUploads;

    public $selectedDocumentType = '';
    public $uploadFile;
    public $uploadProgress = 0;
    public bool $showUploadModal = false;
    public bool $showViewModal = false;
    public ?MemberDocument $viewingDocument = null;

    // ID Document metadata fields
    public string $idSurname = '';
    public string $idNames = '';
    public string $idSex = '';
    public string $idNumber = '';
    public string $idDateOfBirth = '';

    // Proof of Address metadata fields
    public string $addressStreet = '';
    public string $addressSuburb = '';
    public string $addressCity = '';
    public string $addressProvince = '';
    public string $addressPostalCode = '';

    protected function rules(): array
    {
        $rules = [
            'selectedDocumentType' => 'required|exists:document_types,id',
            'uploadFile' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp', // 10MB max
        ];

        // Add conditional validation based on document type
        if ($this->selectedDocumentType) {
            $docType = DocumentType::find($this->selectedDocumentType);
            if ($docType) {
                if (in_array($docType->slug, MemberDocument::ID_DOCUMENT_SLUGS)) {
                    $rules['idSurname'] = 'required|string|max:100';
                    $rules['idNames'] = 'required|string|max:100';
                    $rules['idSex'] = 'required|in:male,female';
                    $rules['idNumber'] = 'required|string|size:13';
                }
                if (in_array($docType->slug, MemberDocument::ADDRESS_DOCUMENT_SLUGS)) {
                    $rules['addressStreet'] = 'required|string|max:255';
                    $rules['addressSuburb'] = 'nullable|string|max:100';
                    $rules['addressCity'] = 'required|string|max:100';
                    $rules['addressProvince'] = 'required|string|max:100';
                    $rules['addressPostalCode'] = 'required|string|max:10';
                }
            }
        }

        return $rules;
    }

    protected $messages = [
        'idNumber.size' => 'The ID number must be exactly 13 digits.',
    ];

    /**
     * When ID number changes, auto-populate DOB and sex.
     */
    public function updatedIdNumber($value): void
    {
        $parsed = MemberDocument::parseSaIdNumber($value);
        if ($parsed) {
            $this->idDateOfBirth = $parsed['date_of_birth'];
            $this->idSex = $parsed['sex'];
        }
    }

    /**
     * Check if selected document type requires ID metadata.
     */
    public function requiresIdMetadata(): bool
    {
        if (!$this->selectedDocumentType) return false;
        $docType = DocumentType::find($this->selectedDocumentType);
        return $docType && in_array($docType->slug, MemberDocument::ID_DOCUMENT_SLUGS);
    }

    /**
     * Check if selected document type requires address metadata.
     */
    public function requiresAddressMetadata(): bool
    {
        if (!$this->selectedDocumentType) return false;
        $docType = DocumentType::find($this->selectedDocumentType);
        return $docType && in_array($docType->slug, MemberDocument::ADDRESS_DOCUMENT_SLUGS);
    }

    public function with(): array
    {
        $user = auth()->user();
        
        // Get all document types
        $documentTypes = DocumentType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        // Get user's documents grouped by type
        $userDocuments = MemberDocument::where('user_id', $user->id)
            ->with('documentType', 'verifier')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('document_type_id');
        
        // Get required document types for user's membership (if any)
        $requiredDocTypes = [];
        $activeMembership = $user->activeMembership;
        if ($activeMembership && $activeMembership->membershipType) {
            $requiredDocTypes = $activeMembership->membershipType
                ->documentTypes()
                ->wherePivot('is_required', true)
                ->pluck('document_types.id')
                ->toArray();
        }
        
        return [
            'documentTypes' => $documentTypes,
            'userDocuments' => $userDocuments,
            'requiredDocTypes' => $requiredDocTypes,
        ];
    }

    public function openUploadModal(int $documentTypeId = null): void
    {
        $this->selectedDocumentType = $documentTypeId ?? '';
        $this->uploadFile = null;
        $this->reset([
            'idSurname', 'idNames', 'idSex', 'idNumber', 'idDateOfBirth',
            'addressStreet', 'addressSuburb', 'addressCity', 'addressProvince', 'addressPostalCode',
        ]);
        $this->showUploadModal = true;
    }

    public function uploadDocument(): void
    {
        $this->validate();

        $user = auth()->user();
        $documentType = DocumentType::findOrFail($this->selectedDocumentType);

        // Store the file - use R2 if configured, otherwise use default disk
        $disk = config('filesystems.disks.r2.key') ? 'r2' : config('filesystems.default');
        $path = $this->uploadFile->store(
            "documents/{$user->uuid}/{$documentType->slug}",
            $disk
        );

        // Build metadata based on document type
        $metadata = null;
        if (in_array($documentType->slug, MemberDocument::ID_DOCUMENT_SLUGS)) {
            $metadata = [
                'surname' => $this->idSurname,
                'names' => $this->idNames,
                'sex' => $this->idSex,
                'identity_number' => $this->idNumber,
                'date_of_birth' => $this->idDateOfBirth,
            ];
        } elseif (in_array($documentType->slug, MemberDocument::ADDRESS_DOCUMENT_SLUGS)) {
            $metadata = [
                'street_address' => $this->addressStreet,
                'suburb' => $this->addressSuburb,
                'city' => $this->addressCity,
                'province' => $this->addressProvince,
                'postal_code' => $this->addressPostalCode,
            ];
        }

        // Create the document record
        MemberDocument::create([
            'user_id' => $user->id,
            'document_type_id' => $documentType->id,
            'file_path' => $path,
            'original_filename' => $this->uploadFile->getClientOriginalName(),
            'mime_type' => $this->uploadFile->getMimeType(),
            'file_size' => $this->uploadFile->getSize(),
            'metadata' => $metadata,
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->reset([
            'selectedDocumentType', 'uploadFile', 'showUploadModal',
            'idSurname', 'idNames', 'idSex', 'idNumber', 'idDateOfBirth',
            'addressStreet', 'addressSuburb', 'addressCity', 'addressProvince', 'addressPostalCode',
        ]);
        session()->flash('success', 'Document uploaded successfully. It will be reviewed by an administrator.');
    }

    public function viewDocument(MemberDocument $document): void
    {
        // Ensure user can only view their own documents
        if ($document->user_id !== auth()->id()) {
            return;
        }
        
        $this->viewingDocument = $document;
        $this->showViewModal = true;
    }

    public function downloadDocument(MemberDocument $document): mixed
    {
        // Ensure user can only download their own documents
        if ($document->user_id !== auth()->id()) {
            return null;
        }

        $disk = config('filesystems.disks.r2.key') ? 'r2' : config('filesystems.default');
        return Storage::disk($disk)->download(
            $document->file_path,
            $document->original_filename
        );
    }

    public function deleteDocument(MemberDocument $document): void
    {
        // Ensure user can only delete their own documents
        if ($document->user_id !== auth()->id()) {
            session()->flash('error', 'You can only delete your own documents.');
            return;
        }

        // Delete file from storage
        $disk = config('filesystems.disks.r2.key') ? 'r2' : config('filesystems.default');
        try {
            Storage::disk($disk)->delete($document->file_path);
        } catch (\Exception $e) {
            // Log error but continue with deletion - file might already be gone
        }
        
        // Delete record (soft delete)
        $document->delete();
        
        session()->flash('success', 'Document deleted successfully.');
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match($status) {
            'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            'verified' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            'expired' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300',
            'archived' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300',
        };
    }
}; ?>

<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">My Documents</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Upload and manage your documents for membership and firearm applications.</p>
        </div>
        <button wire:click="openUploadModal()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Upload Document
        </button>
    </div>

    {{-- Flash Messages --}}
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

    {{-- Document Categories --}}
    <div class="space-y-8">
        @foreach($documentTypes->groupBy(fn($dt) => explode('-', $dt->slug)[0]) as $category => $types)
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white capitalize">
                        {{ str_replace('-', ' ', $category) }} Documents
                    </h2>
                </div>
                
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($types as $docType)
                        @php
                            $documents = $userDocuments->get($docType->id, collect());
                            $latestDoc = $documents->first();
                            $isRequired = in_array($docType->id, $requiredDocTypes);
                            $hasValid = $documents->where('status', 'verified')->whereNull('expires_at')->count() > 0 
                                || $documents->where('status', 'verified')->where('expires_at', '>', now())->count() > 0;
                        @endphp
                        <div class="px-6 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-medium text-zinc-900 dark:text-white">
                                            {{ $docType->name }}
                                        </h3>
                                        @if($isRequired)
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                Required
                                            </span>
                                        @endif
                                        @if($hasValid)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                Valid
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $docType->description }}</p>
                                    
                                    @if($docType->expiry_months)
                                        <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                                            Expires after {{ $docType->expiry_months }} months
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                                            Does not expire
                                        </p>
                                    @endif
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    @if($latestDoc)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusBadgeClass($latestDoc->status) }}">
                                            {{ ucfirst($latestDoc->status) }}
                                        </span>
                                    @endif
                                    <button wire:click="openUploadModal({{ $docType->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 border border-emerald-300 dark:border-emerald-700 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                        Upload
                                    </button>
                                </div>
                            </div>
                            
                            {{-- Document History --}}
                            @if($documents->count() > 0)
                                <div class="mt-4 space-y-2">
                                    @foreach($documents->take(3) as $doc)
                                        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 bg-white dark:bg-zinc-800 rounded-lg">
                                                    @if(str_contains($doc->mime_type, 'pdf'))
                                                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                                    @else
                                                        <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate max-w-xs">
                                                        {{ $doc->original_filename }}
                                                    </p>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                        Uploaded {{ $doc->uploaded_at->diffForHumans() }}
                                                        @if($doc->expires_at)
                                                            · Expires {{ $doc->expires_at->format('d M Y') }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $this->getStatusBadgeClass($doc->status) }}">
                                                    {{ ucfirst($doc->status) }}
                                                </span>
                                                <button wire:click="viewDocument('{{ $doc->uuid }}')" class="p-1.5 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" title="View details">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>
                                                <button wire:click="deleteDocument('{{ $doc->uuid }}')" wire:confirm="Are you sure you want to delete this document? This action cannot be undone." class="p-1.5 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" title="Delete document">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    @if($documents->count() > 3)
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-2">
                                            + {{ $documents->count() - 3 }} more document(s)
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Upload Modal --}}
    @if($showUploadModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showUploadModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">Upload Document</h2>
                    
                    <form wire:submit="uploadDocument" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Document Type</label>
                            <select wire:model.live="selectedDocumentType"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="">Select document type...</option>
                                @foreach($documentTypes as $docType)
                                    <option value="{{ $docType->id }}">{{ $docType->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedDocumentType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- ID Document Metadata Fields --}}
                        @if($this->requiresIdMetadata())
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 space-y-4">
                                <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                    ID Document Details
                                </h3>
                                <p class="text-xs text-blue-700 dark:text-blue-300">Please enter the details exactly as they appear on your ID document.</p>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Surname <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="idSurname" placeholder="e.g. Smith"
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent capitalize">
                                        @error('idSurname') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">First Names <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="idNames" placeholder="e.g. John William"
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent capitalize">
                                        @error('idNames') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">ID Number <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.live.debounce.500ms="idNumber" placeholder="e.g. 8507026265088" maxlength="13"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono tracking-wider">
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">13-digit South African ID number</p>
                                    @error('idNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sex <span class="text-red-500">*</span></label>
                                        <select wire:model="idSex"
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                            <option value="">Select...</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                        @error('idSex') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date of Birth</label>
                                        <input type="date" wire:model="idDateOfBirth" readonly
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-100 dark:bg-zinc-600 text-zinc-900 dark:text-white cursor-not-allowed">
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Auto-calculated from ID number</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Proof of Address Metadata Fields --}}
                        @if($this->requiresAddressMetadata())
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800 space-y-4">
                                <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Address Details
                                </h3>
                                <p class="text-xs text-amber-700 dark:text-amber-300">Please enter your residential address as shown on the document.</p>
                                
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Street Address <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="addressStreet" placeholder="e.g. 123 Main Road"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    @error('addressStreet') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Suburb</label>
                                        <input type="text" wire:model="addressSuburb" placeholder="e.g. Sandton"
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                        @error('addressSuburb') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">City/Town <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="addressCity" placeholder="e.g. Johannesburg"
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                        @error('addressCity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Province <span class="text-red-500">*</span></label>
                                        <select wire:model="addressProvince"
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                            <option value="">Select province...</option>
                                            <option value="Eastern Cape">Eastern Cape</option>
                                            <option value="Free State">Free State</option>
                                            <option value="Gauteng">Gauteng</option>
                                            <option value="KwaZulu-Natal">KwaZulu-Natal</option>
                                            <option value="Limpopo">Limpopo</option>
                                            <option value="Mpumalanga">Mpumalanga</option>
                                            <option value="North West">North West</option>
                                            <option value="Northern Cape">Northern Cape</option>
                                            <option value="Western Cape">Western Cape</option>
                                        </select>
                                        @error('addressProvince') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Postal Code <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="addressPostalCode" placeholder="e.g. 2196" maxlength="10"
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                        @error('addressPostalCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <div x-data="{ 
                                dragging: false,
                                handleDrop(e) {
                                    this.dragging = false;
                                    const files = e.dataTransfer.files;
                                    if (files.length > 0) {
                                        // Use Livewire's upload method directly
                                        @this.upload('uploadFile', files[0], 
                                            (uploadedFilename) => { console.log('Upload complete:', uploadedFilename); },
                                            (error) => { console.error('Upload error:', error); },
                                            (event) => { console.log('Upload progress:', event.detail.progress); }
                                        );
                                    }
                                }
                            }">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">File</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-zinc-300 dark:border-zinc-600 border-dashed rounded-lg hover:border-emerald-400 dark:hover:border-emerald-500 transition-colors cursor-pointer"
                                x-on:dragover.prevent="dragging = true"
                                x-on:dragleave.prevent="dragging = false"
                                x-on:drop.prevent="handleDrop($event)"
                                x-on:click="$refs.fileInput.click()"
                                :class="{ 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/20': dragging }">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-zinc-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-zinc-600 dark:text-zinc-400 justify-center">
                                        <span class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">Upload a file</span>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500">PDF, PNG, JPG up to 10MB</p>
                                    
                                    {{-- Loading indicator --}}
                                    <div wire:loading wire:target="uploadFile" class="mt-2">
                                        <div class="flex items-center justify-center gap-2 text-emerald-600 dark:text-emerald-400">
                                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span class="text-sm">Uploading...</span>
                                        </div>
                                    </div>
                                </div>
                                <input x-ref="fileInput" wire:model="uploadFile" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
                            </div>
                            @error('uploadFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            
                            @if($uploadFile)
                                <div class="mt-3 p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg flex items-center gap-3">
                                    <svg class="w-8 h-8 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $uploadFile->getClientOriginalName() }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($uploadFile->getSize() / 1024, 1) }} KB</p>
                                    </div>
                                    <button type="button" wire:click="$set('uploadFile', null)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" wire:click="$set('showUploadModal', false)"
                                class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg disabled:opacity-50"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="uploadDocument">Upload</span>
                                <span wire:loading wire:target="uploadDocument">Uploading...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- View Document Modal --}}
    @if($showViewModal && $viewingDocument)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showViewModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-2xl p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">Document Details</h2>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Document Type</p>
                                <p class="text-base text-zinc-900 dark:text-white">{{ $viewingDocument->documentType->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</p>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusBadgeClass($viewingDocument->status) }}">
                                    {{ ucfirst($viewingDocument->status) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Filename</p>
                                <p class="text-base text-zinc-900 dark:text-white truncate">{{ $viewingDocument->original_filename }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">File Size</p>
                                <p class="text-base text-zinc-900 dark:text-white">{{ number_format($viewingDocument->file_size / 1024, 1) }} KB</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Uploaded</p>
                                <p class="text-base text-zinc-900 dark:text-white">{{ $viewingDocument->uploaded_at->format('d M Y H:i') }}</p>
                            </div>
                            @if($viewingDocument->expires_at)
                                <div>
                                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Expires</p>
                                    <p class="text-base text-zinc-900 dark:text-white">{{ $viewingDocument->expires_at->format('d M Y') }}</p>
                                </div>
                            @endif
                            @if($viewingDocument->verified_at)
                                <div>
                                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Verified</p>
                                    <p class="text-base text-zinc-900 dark:text-white">{{ $viewingDocument->verified_at->format('d M Y H:i') }} by {{ $viewingDocument->verifier?->name ?? 'System' }}</p>
                                </div>
                            @endif
                        </div>
                        
                        @if($viewingDocument->rejection_reason)
                            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <p class="text-sm font-medium text-red-800 dark:text-red-200">Rejection Reason</p>
                                <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $viewingDocument->rejection_reason }}</p>
                            </div>
                        @endif

                        {{-- ID Document Metadata --}}
                        @if($viewingDocument->metadata && isset($viewingDocument->metadata['identity_number']))
                            <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                                <h4 class="text-sm font-semibold text-indigo-800 dark:text-indigo-200 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                    ID Document Details
                                </h4>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">Surname:</span>
                                        <span class="text-indigo-900 dark:text-indigo-100 ml-1">{{ $viewingDocument->metadata['surname'] ?? '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">Names:</span>
                                        <span class="text-indigo-900 dark:text-indigo-100 ml-1">{{ $viewingDocument->metadata['names'] ?? '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">ID Number:</span>
                                        <span class="text-indigo-900 dark:text-indigo-100 ml-1 font-mono">{{ $viewingDocument->metadata['identity_number'] ?? '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">Sex:</span>
                                        <span class="text-indigo-900 dark:text-indigo-100 ml-1 capitalize">{{ $viewingDocument->metadata['sex'] ?? '-' }}</span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">Date of Birth:</span>
                                        <span class="text-indigo-900 dark:text-indigo-100 ml-1">
                                            @if(isset($viewingDocument->metadata['date_of_birth']))
                                                {{ \Carbon\Carbon::parse($viewingDocument->metadata['date_of_birth'])->format('d F Y') }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Address Metadata --}}
                        @if($viewingDocument->metadata && isset($viewingDocument->metadata['street_address']))
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                                <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Address Details
                                </h4>
                                <div class="text-sm text-amber-900 dark:text-amber-100 space-y-1">
                                    <p>{{ $viewingDocument->metadata['street_address'] ?? '' }}</p>
                                    @if(!empty($viewingDocument->metadata['suburb']))
                                        <p>{{ $viewingDocument->metadata['suburb'] }}</p>
                                    @endif
                                    <p>
                                        {{ $viewingDocument->metadata['city'] ?? '' }}{{ !empty($viewingDocument->metadata['postal_code']) ? ', ' . $viewingDocument->metadata['postal_code'] : '' }}
                                    </p>
                                    <p>{{ $viewingDocument->metadata['province'] ?? '' }}</p>
                                </div>
                            </div>
                        @endif
                        
                        <div class="flex justify-between items-center pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <button type="button" 
                                wire:click="deleteDocument('{{ $viewingDocument->uuid }}')" 
                                wire:confirm="Are you sure you want to delete this document? This action cannot be undone."
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                            <div class="flex gap-3">
                                <button type="button" wire:click="$set('showViewModal', false)"
                                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                    Close
                                </button>
                                <a href="{{ route('documents.show', $viewingDocument) }}" target="_blank"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg inline-flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
