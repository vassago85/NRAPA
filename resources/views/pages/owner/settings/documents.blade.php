<?php

use App\Models\SystemSetting;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Helpers\StorageHelper;

new class extends Component {
    use WithFileUploads;

    // Signatory Details
    public string $signatory_name = '';
    public string $signatory_title = '';
    
    // File uploads
    public $signature_file = null;
    public $commissioner_file = null;
    
    // Current file paths (for display)
    public ?string $current_signature_path = null;
    public ?string $current_commissioner_path = null;

    public function mount(): void
    {
        $this->signatory_name = SystemSetting::get('default_signatory_name', 'NRAPA Administration');
        $this->signatory_title = SystemSetting::get('default_signatory_title', 'Authorised Signatory');
        $this->current_signature_path = SystemSetting::get('default_signatory_signature_path', null);
        $this->current_commissioner_path = SystemSetting::get('default_commissioner_oaths_scan_path', null);
    }

    public function saveSignatoryDetails(): void
    {
        $this->validate([
            'signatory_name' => 'required|string|max:255',
            'signatory_title' => 'required|string|max:255',
        ]);

        SystemSetting::set('default_signatory_name', $this->signatory_name, 'string', 'documents', 'Default signatory name');
        SystemSetting::set('default_signatory_title', $this->signatory_title, 'string', 'documents', 'Default signatory title');

        session()->flash('success', 'Signatory details saved successfully.');
    }

    public function uploadSignature(): void
    {
        $this->validate([
            'signature_file' => 'required|image|mimes:png|max:2048', // PNG only, max 2MB
        ], [
            'signature_file.required' => 'Please select a signature file.',
            'signature_file.image' => 'Signature must be an image file.',
            'signature_file.mimes' => 'Signature must be a PNG file (transparent PNG recommended).',
            'signature_file.max' => 'Signature file must not exceed 2MB.',
        ]);

        $disk = app()->environment(['local', 'development', 'testing']) ? 'public' : 'r2_public';
        $path = $this->signature_file->store('signatures', $disk);
        
        // Delete old signature if exists
        if ($this->current_signature_path) {
            try {
                Storage::disk($disk)->delete($this->current_signature_path);
            } catch (\Exception $e) {
                // Ignore deletion errors
            }
        }

        SystemSetting::set('default_signatory_signature_path', $path, 'string', 'documents', 'Default signatory signature path');
        $this->current_signature_path = $path;
        $this->signature_file = null;

        session()->flash('success', 'Signature uploaded successfully.');
    }

    public function uploadCommissionerScan(): void
    {
        $this->validate([
            'commissioner_file' => 'required|mimes:jpg,jpeg,png,pdf|max:10240', // JPG/PNG/PDF, max 10MB
        ], [
            'commissioner_file.required' => 'Please select a commissioner scan file.',
            'commissioner_file.mimes' => 'Commissioner scan must be a JPG, PNG, or PDF file.',
            'commissioner_file.max' => 'Commissioner scan file must not exceed 10MB.',
        ]);

        $disk = app()->environment(['local', 'development', 'testing']) ? 'public' : 'r2_public';
        $path = $this->commissioner_file->store('commissioner-scans', $disk);
        
        // Delete old scan if exists
        if ($this->current_commissioner_path) {
            try {
                Storage::disk($disk)->delete($this->current_commissioner_path);
            } catch (\Exception $e) {
                // Ignore deletion errors
            }
        }

        SystemSetting::set('default_commissioner_oaths_scan_path', $path, 'string', 'documents', 'Default commissioner of oaths scan path');
        $this->current_commissioner_path = $path;
        $this->commissioner_file = null;

        session()->flash('success', 'Commissioner of Oaths scan uploaded successfully.');
    }

    public function deleteSignature(): void
    {
        if ($this->current_signature_path) {
            $disk = app()->environment(['local', 'development', 'testing']) ? 'public' : 'r2_public';
            try {
                Storage::disk($disk)->delete($this->current_signature_path);
            } catch (\Exception $e) {
                // Ignore deletion errors
            }
        }

        SystemSetting::set('default_signatory_signature_path', null, 'string', 'documents', 'Default signatory signature path');
        $this->current_signature_path = null;

        session()->flash('success', 'Signature deleted successfully.');
    }

    public function deleteCommissionerScan(): void
    {
        if ($this->current_commissioner_path) {
            $disk = app()->environment(['local', 'development', 'testing']) ? 'public' : 'r2_public';
            try {
                Storage::disk($disk)->delete($this->current_commissioner_path);
            } catch (\Exception $e) {
                // Ignore deletion errors
            }
        }

        SystemSetting::set('default_commissioner_oaths_scan_path', null, 'string', 'documents', 'Default commissioner of oaths scan path');
        $this->current_commissioner_path = null;

        session()->flash('success', 'Commissioner of Oaths scan deleted successfully.');
    }

    public function getSignatureUrlProperty(): ?string
    {
        if (!$this->current_signature_path) {
            return null;
        }
        return StorageHelper::getUrl($this->current_signature_path);
    }

    public function getCommissionerUrlProperty(): ?string
    {
        if (!$this->current_commissioner_path) {
            return null;
        }
        return StorageHelper::getUrl($this->current_commissioner_path);
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Document Assets</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Manage signatory signature and Commissioner of Oaths documents for certificates and letters.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Settings Navigation -->
        <div class="lg:col-span-1">
            <nav class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">Settings</h2>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('owner.settings.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Bank Account
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('owner.settings.email') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Email / SMTP
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('owner.settings.storage') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                            Storage / R2
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('owner.settings.approvals') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Approval Workflows
                        </a>
                    </li>
                    <li>
                        <a href="#document-assets" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Document Assets
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Document Assets Settings -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Signatory Details -->
            <div id="document-assets" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Authorised Signatory Details</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Name and title displayed on certificates and letters</p>
                    </div>
                </div>

                <form wire:submit="saveSignatoryDetails" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="signatory_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Signatory Name</label>
                            <input type="text" id="signatory_name" wire:model="signatory_name" placeholder="e.g. John Smith"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @error('signatory_name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="signatory_title" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Signatory Title</label>
                            <input type="text" id="signatory_title" wire:model="signatory_title" placeholder="e.g. Authorised Signatory"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @error('signatory_title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
                            Save Signatory Details
                        </button>
                    </div>
                </form>
            </div>

            <!-- Signatory Signature Upload -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Signatory Signature</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Upload a transparent PNG signature image (max 2MB)</p>
                    </div>
                </div>

                @if($this->signatureUrl)
                    <div class="mb-4 p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Current Signature</span>
                            <button wire:click="deleteSignature" wire:confirm="Are you sure you want to delete the signature?" 
                                class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">
                                Delete
                            </button>
                        </div>
                        <div class="bg-white p-4 rounded border border-zinc-200 dark:border-zinc-700">
                            <img src="{{ $this->signatureUrl }}" alt="Signature" class="max-h-32 mx-auto object-contain">
                        </div>
                    </div>
                @endif

                <form wire:submit="uploadSignature" class="space-y-4">
                    <div>
                        <label for="signature_file" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Upload Signature (PNG only)</label>
                        <input type="file" id="signature_file" wire:model="signature_file" accept="image/png"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        @error('signature_file') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        @if($signature_file)
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Selected: {{ $signature_file->getClientOriginalName() }}</p>
                        @endif
                    </div>

                    @if($signature_file)
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
                            Upload Signature
                        </button>
                    @endif
                </form>
            </div>

            <!-- Commissioner of Oaths Scan Upload -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Commissioner of Oaths Scan</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Upload Commissioner of Oaths scan (JPG, PNG, or PDF - max 10MB)</p>
                    </div>
                </div>

                @if($this->commissionerUrl)
                    <div class="mb-4 p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Current Commissioner Scan</span>
                            <button wire:click="deleteCommissionerScan" wire:confirm="Are you sure you want to delete the commissioner scan?" 
                                class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">
                                Delete
                            </button>
                        </div>
                        <div class="bg-white p-4 rounded border border-zinc-200 dark:border-zinc-700">
                            @if(str_ends_with(strtolower($this->commissionerUrl), '.pdf'))
                                <iframe src="{{ $this->commissionerUrl }}" class="w-full h-64 border-0 rounded"></iframe>
                            @else
                                <img src="{{ $this->commissionerUrl }}" alt="Commissioner Scan" class="max-h-64 mx-auto object-contain">
                            @endif
                        </div>
                    </div>
                @endif

                <form wire:submit="uploadCommissionerScan" class="space-y-4">
                    <div>
                        <label for="commissioner_file" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Upload Commissioner Scan (JPG, PNG, or PDF)</label>
                        <input type="file" id="commissioner_file" wire:model="commissioner_file" accept="image/jpeg,image/png,application/pdf"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        @error('commissioner_file') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        @if($commissioner_file)
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Selected: {{ $commissioner_file->getClientOriginalName() }}</p>
                        @endif
                    </div>

                    @if($commissioner_file)
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
                            Upload Commissioner Scan
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
