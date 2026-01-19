<?php

use App\Models\MemberDocument;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] class extends Component {
    public MemberDocument $document;

    public function mount(MemberDocument $document): void
    {
        // Ensure user can only view their own documents
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }
        
        $this->document = $document->load('documentType', 'verifier');
    }

    public function download(): mixed
    {
        return Storage::disk('s3')->download(
            $this->document->file_path,
            $this->document->original_filename
        );
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

    public function getPreviewUrl(): ?string
    {
        try {
            return Storage::disk('s3')->temporaryUrl($this->document->file_path, now()->addMinutes(15));
        } catch (\Exception $e) {
            return null;
        }
    }
}; ?>

<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <a href="{{ route('documents.index') }}" class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 flex items-center gap-1 mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Documents
            </a>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $document->documentType->name }}</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $document->original_filename }}</p>
        </div>
        <button wire:click="download"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
        {{-- Document Preview --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 flex flex-col h-full">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Preview</h2>
            <div class="bg-zinc-100 dark:bg-zinc-900 rounded-lg overflow-hidden flex-1 flex items-center justify-center" style="min-height: 500px;">
                @php $previewUrl = $this->getPreviewUrl(); @endphp
                @if($previewUrl && str_contains($document->mime_type, 'image'))
                    <img src="{{ $previewUrl }}" alt="Document preview" class="w-full h-full object-contain p-4">
                @elseif($previewUrl && str_contains($document->mime_type, 'pdf'))
                    <div class="w-full h-full relative" style="min-height: 500px;">
                        <iframe 
                            src="{{ $previewUrl }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH"
                            class="w-full h-full border-0"
                            style="min-height: 500px;"
                            title="PDF Preview">
                        </iframe>
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
                    <div class="flex flex-col items-center justify-center p-8">
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
        </div>

        {{-- Document Details --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 flex flex-col h-full">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Details</h2>
            
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusBadgeClass($document->status) }}">
                            {{ ucfirst($document->status) }}
                        </span>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Document Type</dt>
                    <dd class="mt-1 text-base text-zinc-900 dark:text-white">{{ $document->documentType->name }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">File Size</dt>
                    <dd class="mt-1 text-base text-zinc-900 dark:text-white">{{ number_format($document->file_size / 1024, 1) }} KB</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Uploaded</dt>
                    <dd class="mt-1 text-base text-zinc-900 dark:text-white">{{ $document->uploaded_at->format('d M Y H:i') }}</dd>
                </div>
                
                @if($document->expires_at)
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Expires</dt>
                        <dd class="mt-1 text-base text-zinc-900 dark:text-white">
                            {{ $document->expires_at->format('d M Y') }}
                            @if($document->expires_at->isPast())
                                <span class="text-red-500 text-sm ml-2">(Expired)</span>
                            @elseif($document->expires_at->diffInDays(now()) < 30)
                                <span class="text-amber-500 text-sm ml-2">(Expiring soon)</span>
                            @endif
                        </dd>
                    </div>
                @endif
                
                @if($document->verified_at)
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Verified</dt>
                        <dd class="mt-1 text-base text-zinc-900 dark:text-white">
                            {{ $document->verified_at->format('d M Y H:i') }}
                            @if($document->verifier)
                                <span class="text-zinc-500 dark:text-zinc-400 text-sm"> by {{ $document->verifier->name }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
            
            @if($document->rejection_reason)
                <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">Rejection Reason</p>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $document->rejection_reason }}</p>
                </div>
            @endif
            
            @if($document->documentType->description)
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Document Requirements</p>
                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">{{ $document->documentType->description }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
