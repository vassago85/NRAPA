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

    protected function rules(): array
    {
        return [
            'selectedDocumentType' => 'required|exists:document_types,id',
            'uploadFile' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp', // 10MB max
        ];
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
        $this->showUploadModal = true;
    }

    public function uploadDocument(): void
    {
        $this->validate();

        $user = auth()->user();
        $documentType = DocumentType::findOrFail($this->selectedDocumentType);

        // Store the file
        $path = $this->uploadFile->store(
            "documents/{$user->uuid}/{$documentType->slug}",
            's3'
        );

        // Create the document record
        MemberDocument::create([
            'user_id' => $user->id,
            'document_type_id' => $documentType->id,
            'file_path' => $path,
            'original_filename' => $this->uploadFile->getClientOriginalName(),
            'mime_type' => $this->uploadFile->getMimeType(),
            'file_size' => $this->uploadFile->getSize(),
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->reset(['selectedDocumentType', 'uploadFile', 'showUploadModal']);
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

        return Storage::disk('s3')->download(
            $document->file_path,
            $document->original_filename
        );
    }

    public function deleteDocument(MemberDocument $document): void
    {
        // Ensure user can only delete their own pending documents
        if ($document->user_id !== auth()->id() || !$document->isPending()) {
            session()->flash('error', 'You can only delete pending documents.');
            return;
        }

        // Delete file from storage
        Storage::disk('s3')->delete($document->file_path);
        
        // Delete record
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
                                                <button wire:click="viewDocument('{{ $doc->uuid }}')" class="p-1.5 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>
                                                @if($doc->isPending())
                                                    <button wire:click="deleteDocument('{{ $doc->uuid }}')" wire:confirm="Are you sure you want to delete this document?" class="p-1.5 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                @endif
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
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-lg p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">Upload Document</h2>
                    
                    <form wire:submit="uploadDocument" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Document Type</label>
                            <select wire:model="selectedDocumentType"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="">Select document type...</option>
                                @foreach($documentTypes as $docType)
                                    <option value="{{ $docType->id }}">{{ $docType->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedDocumentType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">File</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-zinc-300 dark:border-zinc-600 border-dashed rounded-lg hover:border-emerald-400 dark:hover:border-emerald-500 transition-colors"
                                x-data="{ dragging: false }"
                                x-on:dragover.prevent="dragging = true"
                                x-on:dragleave.prevent="dragging = false"
                                x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'));"
                                :class="{ 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/20': dragging }">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-zinc-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-zinc-600 dark:text-zinc-400">
                                        <label for="file-upload" class="relative cursor-pointer rounded-md font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">
                                            <span>Upload a file</span>
                                            <input id="file-upload" x-ref="fileInput" wire:model="uploadFile" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500">PDF, PNG, JPG up to 10MB</p>
                                </div>
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
                        
                        <div class="flex justify-end gap-3 pt-4">
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
    @endif
</div>
