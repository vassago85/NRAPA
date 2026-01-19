<?php

use App\Models\DocumentType;
use App\Models\MemberDocument;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app.sidebar')] class extends Component {
    use WithPagination;

    public string $statusFilter = 'pending';
    public string $typeFilter = '';
    public string $search = '';
    
    public bool $showReviewModal = false;
    public ?MemberDocument $reviewingDocument = null;
    public string $rejectionReason = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = MemberDocument::with(['user', 'documentType', 'verifier'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn($q) => $q->where('document_type_id', $this->typeFilter))
            ->when($this->search, function ($q) {
                $q->whereHas('user', function ($userQuery) {
                    $userQuery->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                })->orWhere('original_filename', 'like', "%{$this->search}%");
            })
            ->orderBy('created_at', 'desc');

        $pendingCount = MemberDocument::where('status', 'pending')->count();
        $documentTypes = DocumentType::where('is_active', true)->orderBy('sort_order')->get();

        return [
            'documents' => $query->paginate(15),
            'pendingCount' => $pendingCount,
            'documentTypes' => $documentTypes,
        ];
    }

    public function reviewDocument(MemberDocument $document): void
    {
        $this->reviewingDocument = $document;
        $this->rejectionReason = '';
        $this->showReviewModal = true;
    }

    public function verifyDocument(): void
    {
        if (!$this->reviewingDocument) {
            return;
        }

        $this->reviewingDocument->verify(auth()->user());
        
        session()->flash('success', "Document verified successfully.");
        $this->reset(['showReviewModal', 'reviewingDocument', 'rejectionReason']);
    }

    public function rejectDocument(): void
    {
        if (!$this->reviewingDocument) {
            return;
        }

        $this->validate([
            'rejectionReason' => 'required|string|min:10|max:500',
        ]);

        $this->reviewingDocument->reject(auth()->user(), $this->rejectionReason);
        
        session()->flash('info', "Document rejected. Member will be notified.");
        $this->reset(['showReviewModal', 'reviewingDocument', 'rejectionReason']);
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

    public function getPreviewUrl(MemberDocument $document): ?string
    {
        try {
            $disk = config('filesystems.disks.r2.key') ? 'r2' : config('filesystems.default');
            
            // Check if disk supports temporary URLs (S3/R2 do, local doesn't)
            if (method_exists(Storage::disk($disk)->getDriver(), 'temporaryUrl') || 
                in_array($disk, ['s3', 'r2'])) {
                return Storage::disk($disk)->temporaryUrl($document->file_path, now()->addMinutes(15));
            }
            
            // For local disk, return regular URL
            return Storage::disk($disk)->url($document->file_path);
        } catch (\Exception $e) {
            return null;
        }
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Document Verification</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Review and verify member-uploaded documents.</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="mb-6 p-4 bg-blue-100 dark:bg-blue-900/30 border border-blue-300 dark:border-blue-700 rounded-lg text-blue-800 dark:text-blue-200">
            {{ session('info') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pending Review</p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $pendingCount }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Verified Today</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ \App\Models\MemberDocument::where('status', 'verified')->whereDate('verified_at', today())->count() }}
            </p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Rejected Today</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                {{ \App\Models\MemberDocument::where('status', 'rejected')->whereDate('verified_at', today())->count() }}
            </p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Documents</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">
                {{ \App\Models\MemberDocument::count() }}
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by member name, email, or filename..."
                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <select wire:model.live="statusFilter"
            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="verified">Verified</option>
            <option value="rejected">Rejected</option>
            <option value="expired">Expired</option>
        </select>
        <select wire:model.live="typeFilter"
            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Document Types</option>
            @foreach($documentTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Documents Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Member</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Document Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Filename</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Uploaded</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($documents as $document)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 bg-zinc-200 dark:bg-zinc-700 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $document->user->initials() }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $document->user->name }}</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $document->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-zinc-900 dark:text-white">{{ $document->documentType->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-zinc-900 dark:text-white truncate max-w-xs" title="{{ $document->original_filename }}">
                                {{ $document->original_filename }}
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ number_format($document->file_size / 1024, 1) }} KB
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $document->uploaded_at->format('d M Y') }}
                            <br>
                            <span class="text-xs">{{ $document->uploaded_at->diffForHumans() }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusBadgeClass($document->status) }}">
                                {{ ucfirst($document->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="reviewDocument('{{ $document->uuid }}')"
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                Review
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            No documents found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($documents->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $documents->links() }}
            </div>
        @endif
    </div>

    {{-- Review Modal --}}
    @if($showReviewModal && $reviewingDocument)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showReviewModal', false)" class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-4xl p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">Review Document</h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Document Preview --}}
                        <div class="bg-zinc-100 dark:bg-zinc-900 rounded-lg overflow-hidden min-h-96">
                            @php $previewUrl = $this->getPreviewUrl($reviewingDocument); @endphp
                            @if($previewUrl && str_contains($reviewingDocument->mime_type, 'image'))
                                <div class="p-4">
                                    <img src="{{ $previewUrl }}" alt="Document preview" class="max-w-full max-h-[500px] mx-auto rounded-lg object-contain">
                                </div>
                            @elseif($previewUrl && str_contains($reviewingDocument->mime_type, 'pdf'))
                                <div class="relative h-[500px]">
                                    {{-- Embedded PDF Viewer --}}
                                    <iframe 
                                        src="{{ $previewUrl }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH"
                                        class="w-full h-full border-0"
                                        title="PDF Preview">
                                    </iframe>
                                    {{-- Fallback link if iframe doesn't work --}}
                                    <div class="absolute bottom-2 right-2">
                                        <a href="{{ $previewUrl }}" target="_blank" 
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg shadow-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                            Open Full Screen
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center h-96 p-4">
                                    <svg class="w-24 h-24 text-zinc-400 mb-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                    <p class="text-zinc-600 dark:text-zinc-400">Preview not available</p>
                                    @if($previewUrl)
                                        <a href="{{ $previewUrl }}" target="_blank" class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                            Download File
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                        
                        {{-- Document Details --}}
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Member</p>
                                    <p class="text-base text-zinc-900 dark:text-white">{{ $reviewingDocument->user->name }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $reviewingDocument->user->email }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Document Type</p>
                                    <p class="text-base text-zinc-900 dark:text-white">{{ $reviewingDocument->documentType->name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Filename</p>
                                    <p class="text-base text-zinc-900 dark:text-white truncate" title="{{ $reviewingDocument->original_filename }}">{{ $reviewingDocument->original_filename }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">File Size</p>
                                    <p class="text-base text-zinc-900 dark:text-white">{{ number_format($reviewingDocument->file_size / 1024, 1) }} KB</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Uploaded</p>
                                    <p class="text-base text-zinc-900 dark:text-white">{{ $reviewingDocument->uploaded_at->format('d M Y H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</p>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusBadgeClass($reviewingDocument->status) }}">
                                        {{ ucfirst($reviewingDocument->status) }}
                                    </span>
                                </div>
                            </div>
                            
                            @if($reviewingDocument->documentType->description)
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Document Requirements</p>
                                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">{{ $reviewingDocument->documentType->description }}</p>
                                </div>
                            @endif

                            {{-- ID Document Metadata --}}
                            @if($reviewingDocument->metadata && isset($reviewingDocument->metadata['identity_number']))
                                <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                                    <h4 class="text-sm font-semibold text-indigo-800 dark:text-indigo-200 mb-3 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                        ID Document Details (Entered by Member)
                                    </h4>
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">Surname:</span>
                                            <span class="text-indigo-900 dark:text-indigo-100 ml-1">{{ $reviewingDocument->metadata['surname'] ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">Names:</span>
                                            <span class="text-indigo-900 dark:text-indigo-100 ml-1">{{ $reviewingDocument->metadata['names'] ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">ID Number:</span>
                                            <span class="text-indigo-900 dark:text-indigo-100 ml-1 font-mono">{{ $reviewingDocument->metadata['identity_number'] ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">Sex:</span>
                                            <span class="text-indigo-900 dark:text-indigo-100 ml-1 capitalize">{{ $reviewingDocument->metadata['sex'] ?? '-' }}</span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">Date of Birth:</span>
                                            <span class="text-indigo-900 dark:text-indigo-100 ml-1">
                                                @if(isset($reviewingDocument->metadata['date_of_birth']))
                                                    {{ \Carbon\Carbon::parse($reviewingDocument->metadata['date_of_birth'])->format('d F Y') }}
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Address Metadata --}}
                            @if($reviewingDocument->metadata && isset($reviewingDocument->metadata['street_address']))
                                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                                    <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-3 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Address Details (Entered by Member)
                                    </h4>
                                    <div class="text-sm text-amber-900 dark:text-amber-100 space-y-1">
                                        <p>{{ $reviewingDocument->metadata['street_address'] ?? '' }}</p>
                                        @if(!empty($reviewingDocument->metadata['suburb']))
                                            <p>{{ $reviewingDocument->metadata['suburb'] }}</p>
                                        @endif
                                        <p>
                                            {{ $reviewingDocument->metadata['city'] ?? '' }}{{ !empty($reviewingDocument->metadata['postal_code']) ? ', ' . $reviewingDocument->metadata['postal_code'] : '' }}
                                        </p>
                                        <p>{{ $reviewingDocument->metadata['province'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endif
                            
                            @if($reviewingDocument->isPending())
                                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                            Rejection Reason (required if rejecting)
                                        </label>
                                        <textarea wire:model="rejectionReason" rows="3"
                                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Explain why this document is being rejected..."></textarea>
                                        @error('rejectionReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    
                                    <div class="flex justify-end gap-3">
                                        <button wire:click="rejectDocument"
                                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                                            Reject
                                        </button>
                                        <button wire:click="verifyDocument"
                                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                                            Verify
                                        </button>
                                    </div>
                                </div>
                            @else
                                @if($reviewingDocument->rejection_reason)
                                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                        <p class="text-sm font-medium text-red-800 dark:text-red-200">Rejection Reason</p>
                                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $reviewingDocument->rejection_reason }}</p>
                                    </div>
                                @endif
                                
                                @if($reviewingDocument->verified_at)
                                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                        <p class="text-sm font-medium text-green-800 dark:text-green-200">Verified</p>
                                        <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                                            {{ $reviewingDocument->verified_at->format('d M Y H:i') }} by {{ $reviewingDocument->verifier?->name ?? 'System' }}
                                        </p>
                                    </div>
                                @endif
                                
                                <div class="flex justify-end">
                                    <button wire:click="$set('showReviewModal', false)"
                                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                        Close
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
