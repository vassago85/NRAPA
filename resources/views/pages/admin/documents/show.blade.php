<?php

use App\Mail\DocumentCorrected;
use App\Models\MemberDocument;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] class extends Component {
    public MemberDocument $document;
    public string $rejectionReason = '';

    public bool $editingMetadata = false;
    public string $editSurname = '';
    public string $editNames = '';
    public string $editIdNumber = '';
    public string $editSex = '';
    public string $editDateOfBirth = '';

    public function mount(MemberDocument $document): void
    {
        $this->document = $document->load('documentType', 'verifier', 'user');
    }

    public function openEditMetadata(): void
    {
        $meta = $this->document->metadata ?? [];
        $this->editSurname = $meta['surname'] ?? '';
        $this->editNames = $meta['names'] ?? '';
        $this->editIdNumber = $meta['identity_number'] ?? '';
        $this->editSex = $meta['sex'] ?? '';
        $this->editDateOfBirth = $meta['date_of_birth'] ?? '';
        $this->editingMetadata = true;
    }

    public function cancelEditMetadata(): void
    {
        $this->editingMetadata = false;
    }

    public function saveCorrections(): void
    {
        $this->validate([
            'editSurname' => 'required|string|max:100',
            'editNames' => 'required|string|max:100',
            'editIdNumber' => 'required|string|max:20',
            'editSex' => 'required|in:male,female',
            'editDateOfBirth' => 'required|date',
        ]);

        $oldMeta = $this->document->metadata ?? [];
        $changes = [];

        $fieldMap = [
            'surname' => ['prop' => 'editSurname', 'label' => 'Surname'],
            'names' => ['prop' => 'editNames', 'label' => 'Names'],
            'identity_number' => ['prop' => 'editIdNumber', 'label' => 'ID Number'],
            'sex' => ['prop' => 'editSex', 'label' => 'Sex'],
            'date_of_birth' => ['prop' => 'editDateOfBirth', 'label' => 'Date of Birth'],
        ];

        foreach ($fieldMap as $key => $info) {
            $oldVal = $oldMeta[$key] ?? '';
            $newVal = $this->{$info['prop']};
            if ((string) $oldVal !== (string) $newVal) {
                $displayOld = $oldVal;
                $displayNew = $newVal;
                if ($key === 'sex') {
                    $displayOld = $oldVal ? ucfirst($oldVal) : '';
                    $displayNew = ucfirst($newVal);
                }
                if ($key === 'date_of_birth') {
                    $displayOld = $oldVal ? \Carbon\Carbon::parse($oldVal)->format('d F Y') : '';
                    $displayNew = \Carbon\Carbon::parse($newVal)->format('d F Y');
                }
                $changes[] = ['label' => $info['label'], 'old' => $displayOld, 'new' => $displayNew];
            }
        }

        if (empty($changes)) {
            $this->editingMetadata = false;
            session()->flash('info', 'No changes were made.');
            return;
        }

        $newMeta = array_merge($oldMeta, [
            'surname' => $this->editSurname,
            'names' => $this->editNames,
            'identity_number' => $this->editIdNumber,
            'sex' => $this->editSex,
            'date_of_birth' => $this->editDateOfBirth,
        ]);
        $this->document->update(['metadata' => $newMeta]);

        $user = $this->document->user;
        if ($user) {
            $userUpdates = [];
            if ($this->editSurname !== ($oldMeta['surname'] ?? '') || $this->editNames !== ($oldMeta['names'] ?? '')) {
                $userUpdates['name'] = trim($this->editNames . ' ' . $this->editSurname);
            }
            if ($this->editIdNumber !== ($oldMeta['identity_number'] ?? '')) {
                $duplicate = User::where('id_number', $this->editIdNumber)
                    ->where('id', '!=', $user->id)
                    ->exists();
                if (!$duplicate) {
                    $userUpdates['id_number'] = $this->editIdNumber;
                } else {
                    Log::warning('Skipped syncing corrected ID number: duplicate exists', [
                        'id_number' => $this->editIdNumber,
                        'user_id' => $user->id,
                    ]);
                }
            }
            if ($this->editDateOfBirth !== ($oldMeta['date_of_birth'] ?? '')) {
                $userUpdates['date_of_birth'] = $this->editDateOfBirth;
            }
            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }
        }

        try {
            Mail::to($user->email)->queue(new DocumentCorrected(
                $this->document,
                $changes,
                auth()->user()->name,
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send document correction email', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->document->refresh();
        $this->editingMetadata = false;
        $changeCount = count($changes);
        session()->flash('success', "{$changeCount} correction(s) saved. The member has been notified by email.");
    }

    protected function getPrivateDisk(): string
    {
        // Use local storage for local/development/testing environments
        if (app()->environment(['local', 'development', 'testing'])) {
            return 'local';
        }
        // Use R2 if configured, otherwise fall back to s3 (Minio)
        if (config('filesystems.disks.r2.key')) {
            return 'r2';
        }
        return 's3';
    }

    public function download(): mixed
    {
        return Storage::disk($this->getPrivateDisk())->download(
            $this->document->file_path,
            $this->document->original_filename
        );
    }

    public function verifyDocument(): void
    {
        $this->document->verify(auth()->user());
        
        session()->flash('success', "Document verified successfully.");
        $this->redirect(route('admin.documents.index'), navigate: true);
    }

    public function rejectDocument(): void
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:10|max:500',
        ]);

        $this->document->reject(auth()->user(), $this->rejectionReason);
        
        session()->flash('info', "Document rejected. Member will be notified.");
        $this->redirect(route('admin.documents.index'), navigate: true);
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match($status) {
            'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            'verified' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            'expired' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300',
            'archived' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300',
        };
    }

    public function getPreviewUrl(): ?string
    {
        // Use Laravel proxy route to stream file (bypasses R2 signed URL issues)
        return route('admin.documents.preview', $this->document);
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Document Review</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review member document submission</p>
    </x-slot>
    
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('admin.documents.index') }}" class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 flex items-center gap-1 mb-2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Documents
            </a>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $document->documentType->name }}</h2>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                {{ $document->original_filename }} • 
                <a href="{{ route('admin.members.show', $document->user) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    {{ $document->user->name }}
                </a>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="download"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
        {{-- Document Preview --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6 flex flex-col h-full">
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

        {{-- Document Details & Actions --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6 flex flex-col h-full">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Details</h2>
            
            <dl class="space-y-4 flex-1">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Member</dt>
                    <dd class="mt-1">
                        <a href="{{ route('admin.members.show', $document->user) }}" class="text-base text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                            {{ $document->user->name }}
                        </a>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $document->user->email }}</p>
                    </dd>
                </div>
                
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
                            @elseif($document->expires_at->lte(now()->addDays(30)))
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
            
            {{-- ID Document Metadata --}}
            @if($document->metadata && isset($document->metadata['identity_number']))
                <div class="mt-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-indigo-800 dark:text-indigo-200 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            ID Document Details (Entered by Member)
                        </h4>
                        @if(!$editingMetadata)
                        <button wire:click="openEditMetadata" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit
                        </button>
                        @endif
                    </div>

                    @if($editingMetadata)
                    <form wire:submit="saveCorrections" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-indigo-700 dark:text-indigo-300 mb-1">Surname</label>
                                <input type="text" wire:model="editSurname" class="w-full rounded-md border border-indigo-300 bg-white px-2.5 py-1.5 text-sm text-zinc-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-indigo-600 dark:bg-zinc-800 dark:text-white">
                                @error('editSurname') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-indigo-700 dark:text-indigo-300 mb-1">Names</label>
                                <input type="text" wire:model="editNames" class="w-full rounded-md border border-indigo-300 bg-white px-2.5 py-1.5 text-sm text-zinc-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-indigo-600 dark:bg-zinc-800 dark:text-white">
                                @error('editNames') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-indigo-700 dark:text-indigo-300 mb-1">ID Number</label>
                                <input type="text" wire:model="editIdNumber" class="w-full rounded-md border border-indigo-300 bg-white px-2.5 py-1.5 text-sm font-mono text-zinc-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-indigo-600 dark:bg-zinc-800 dark:text-white">
                                @error('editIdNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-indigo-700 dark:text-indigo-300 mb-1">Sex</label>
                                <select wire:model="editSex" class="w-full rounded-md border border-indigo-300 bg-white px-2.5 py-1.5 text-sm text-zinc-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-indigo-600 dark:bg-zinc-800 dark:text-white">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                @error('editSex') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-indigo-700 dark:text-indigo-300 mb-1">Date of Birth</label>
                                <input type="date" wire:model="editDateOfBirth" class="w-full rounded-md border border-indigo-300 bg-white px-2.5 py-1.5 text-sm text-zinc-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-indigo-600 dark:bg-zinc-800 dark:text-white">
                                @error('editDateOfBirth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <button type="submit" class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Save & Notify Member
                            </button>
                            <button type="button" wire:click="cancelEditMetadata" class="inline-flex items-center gap-1 rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                                Cancel
                            </button>
                        </div>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400">Changes will be saved to the document and synced to the member's profile. The member will receive an email detailing what was changed.</p>
                    </form>
                    @else
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">Surname:</span>
                            <span class="text-indigo-900 dark:text-indigo-100 ml-1">{{ $document->metadata['surname'] ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">Names:</span>
                            <span class="text-indigo-900 dark:text-indigo-100 ml-1">{{ $document->metadata['names'] ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">ID Number:</span>
                            <span class="text-indigo-900 dark:text-indigo-100 ml-1 font-mono">{{ $document->metadata['identity_number'] ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">Sex:</span>
                            <span class="text-indigo-900 dark:text-indigo-100 ml-1 capitalize">{{ $document->metadata['sex'] ?? '-' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">Date of Birth:</span>
                            <span class="text-indigo-900 dark:text-indigo-100 ml-1">
                                @if(isset($document->metadata['date_of_birth']))
                                    {{ \Carbon\Carbon::parse($document->metadata['date_of_birth'])->format('d F Y') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            @endif

            {{-- Address Metadata --}}
            @if($document->metadata && isset($document->metadata['street_address']))
                <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Address Details (Entered by Member)
                    </h4>
                    <div class="text-sm text-amber-900 dark:text-amber-100 space-y-1">
                        <p>{{ $document->metadata['street_address'] ?? '' }}</p>
                        @if(!empty($document->metadata['suburb']))
                            <p>{{ $document->metadata['suburb'] }}</p>
                        @endif
                        <p>
                            {{ $document->metadata['city'] ?? '' }}{{ !empty($document->metadata['postal_code']) ? ', ' . $document->metadata['postal_code'] : '' }}
                        </p>
                        <p>{{ $document->metadata['province'] ?? '' }}</p>
                    </div>
                </div>
            @endif
            
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

            {{-- Admin Actions --}}
            @if($document->isPending())
                <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Rejection Reason (if rejecting)</label>
                        <textarea wire:model="rejectionReason" 
                            rows="3"
                            placeholder="Enter reason for rejection (required if rejecting)..."
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"></textarea>
                        @error('rejectionReason') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                        @enderror
                    </div>
                    
                    <div class="flex gap-3">
                        <button wire:click="verifyDocument"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Verify Document
                        </button>
                        <button wire:click="rejectDocument"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Reject Document
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
