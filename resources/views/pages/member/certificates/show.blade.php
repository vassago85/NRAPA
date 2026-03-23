<?php

use App\Models\Certificate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] class extends Component {
    public Certificate $certificate;

    public function mount(Certificate $certificate): void
    {
        $user = Auth::user();
        
        // Allow dev, owner, and admin to view any certificate
        // Members can only view their own certificates
        if (!$user->isDeveloper() && !$user->isOwner() && !$user->isAdmin()) {
            if ($certificate->user_id !== $user->id) {
                abort(403);
            }
        }

        $this->certificate = $certificate->load(['certificateType', 'membership.type', 'issuer', 'user']);
    }

    #[Computed]
    public function verificationUrl()
    {
        return route('certificates.verify', ['qr_code' => $this->certificate->qr_code]);
    }

    #[Computed]
    public function qrCodeImageUrl()
    {
        return \App\Helpers\QrCodeHelper::generateUrl($this->verificationUrl, 200);
    }

    #[Computed]
    public function previewUrl()
    {
        $user = auth()->user();
        if ($user->isDeveloper()) {
            return route('developer.certificates.preview', $this->certificate);
        } elseif ($user->isOwner()) {
            // Owner uses admin routes for certificates
            return route('admin.certificates.preview', $this->certificate);
        } elseif ($user->isAdmin()) {
            return route('admin.certificates.preview', $this->certificate);
        }
        return route('certificates.preview', $this->certificate);
    }

    #[Computed]
    public function downloadUrl()
    {
        $user = auth()->user();
        if ($user->isDeveloper()) {
            return route('developer.certificates.download', $this->certificate);
        } elseif ($user->isOwner() || $user->isAdmin()) {
            return route('admin.certificates.download', $this->certificate);
        }
        return route('certificates.download', $this->certificate);
    }

    #[Computed]
    public function isMembershipCard(): bool
    {
        return $this->certificate->certificateType->slug === 'membership-card';
    }

    #[Computed]
    public function walletEnabled(): bool
    {
        $walletService = app(\App\Services\WalletPassService::class);
        return $walletService->isEnabled();
    }

    #[Computed]
    public function appleWalletEnabled(): bool
    {
        $walletService = app(\App\Services\WalletPassService::class);
        return $walletService->isAppleEnabled();
    }

    #[Computed]
    public function googleWalletEnabled(): bool
    {
        $walletService = app(\App\Services\WalletPassService::class);
        return $walletService->isGoogleEnabled();
    }

    #[Computed]
    public function appleWalletUrl()
    {
        $user = auth()->user();
        if ($user->isDeveloper()) {
            return route('developer.certificates.wallet.apple', $this->certificate);
        } elseif ($user->isOwner() || $user->isAdmin()) {
            return route('admin.certificates.wallet.apple', $this->certificate);
        }
        return route('certificates.wallet.apple', $this->certificate);
    }

    #[Computed]
    public function googleWalletUrl()
    {
        $user = auth()->user();
        if ($user->isDeveloper()) {
            return route('developer.certificates.wallet.google', $this->certificate);
        } elseif ($user->isOwner() || $user->isAdmin()) {
            return route('admin.certificates.wallet.google', $this->certificate);
        }
        return route('certificates.wallet.google', $this->certificate);
    }

    public bool $showDeleteModal = false;

    public function deleteCertificate(): void
    {
        $user = auth()->user();
        
        // Only admins, owners, and developers can delete
        if (!$user->isAdmin() && !$user->isOwner() && !$user->isDeveloper()) {
            session()->flash('error', 'You do not have permission to delete certificates.');
            return;
        }

        try {
            // Log the deletion
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'certificate_deleted',
                'auditable_type' => \App\Models\Certificate::class,
                'auditable_id' => $this->certificate->id,
                'old_values' => [
                    'certificate_number' => $this->certificate->certificate_number,
                    'certificate_type' => $this->certificate->certificateType->name ?? null,
                    'user_id' => $this->certificate->user_id,
                ],
                'new_values' => ['deleted' => true],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Delete associated file if exists
            if ($this->certificate->file_path) {
                $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';
                \Illuminate\Support\Facades\Storage::disk($disk)->delete($this->certificate->file_path);
            }

            $certificateNumber = $this->certificate->certificate_number;
            $this->certificate->delete();

            session()->flash('success', "Certificate {$certificateNumber} has been deleted.");
            
            // Redirect to certificates index
            $indexRoute = 'certificates.index';
            if ($user->isDeveloper()) {
                $indexRoute = 'developer.certificates.index';
            } elseif ($user->isOwner() || $user->isAdmin()) {
                $indexRoute = 'admin.certificates.index';
            }
            
            $this->redirect(route($indexRoute), navigate: true);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete certificate', [
                'certificate_id' => $this->certificate->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to delete certificate: ' . $e->getMessage());
        }
    }
}; ?>

<div class="flex flex-col gap-6">

    {{-- Header with blue accent --}}
    @php
        $backRoute = 'certificates.index';
        if (auth()->user()->isDeveloper()) {
            $backRoute = 'developer.certificates.index';
        } elseif (auth()->user()->isOwner() || auth()->user()->isAdmin()) {
            $backRoute = 'admin.certificates.index';
        }
    @endphp
    <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
        {{-- Blue header bar --}}
        <div class="flex items-center justify-between bg-gradient-to-br from-[#0B4EA2] to-[#0a3d80] px-5 py-4 sm:px-6">
            <div class="flex items-center gap-4 min-w-0">
                <a href="{{ route($backRoute) }}" wire:navigate
                    class="flex size-8 flex-shrink-0 items-center justify-center rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-xl font-bold text-white break-words">{{ $this->certificate->certificateType->name }}</h1>
                    <p class="font-mono text-xs text-white/70 break-all">{{ $this->certificate->certificate_number }}</p>
                </div>
            </div>
            @if($this->certificate->isValid())
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500 px-3 py-1 text-xs font-bold text-white flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                    Valid
                </span>
            @elseif($this->certificate->isRevoked())
                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500 px-3 py-1 text-xs font-bold text-white flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Revoked
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#F58220] px-3 py-1 text-xs font-bold text-white flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>
                    Expired
                </span>
            @endif
        </div>
        {{-- Orange accent stripe --}}
        <div class="h-1 bg-gradient-to-r from-[#F58220] via-[#f9a825] to-[#F58220]"></div>
        {{-- Admin info bar (if applicable) --}}
        @if((auth()->user()->isDeveloper() || auth()->user()->isOwner() || auth()->user()->isAdmin()) && $this->certificate->user)
        <div class="bg-zinc-50 dark:bg-zinc-800/50 px-5 py-3 sm:px-6 flex flex-wrap gap-x-6 gap-y-1 text-sm border-b border-zinc-200 dark:border-zinc-700">
            <span><span class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Member</span> <span class="font-medium text-zinc-900 dark:text-white">{{ $this->certificate->user->name }}</span></span>
            @if($this->certificate->membership)
                <span><span class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Type</span> <span class="font-medium text-zinc-900 dark:text-white">{{ $this->certificate->membership->type->name ?? 'N/A' }}</span></span>
                <span><span class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">No.</span> <span class="font-mono font-medium text-zinc-900 dark:text-white">{{ $this->certificate->membership->membership_number ?? 'N/A' }}</span></span>
            @endif
        </div>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Certificate Preview --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-[#0B4EA2]">Document Preview</h2>
                    <div class="flex gap-2">
                        <a href="{{ $this->previewUrl }}" target="_blank" title="Full Screen"
                            class="flex size-8 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Preview iframe --}}
                @php $isCard = ($certificate->certificateType->slug ?? '') === 'membership-card'; @endphp
                @if($isCard)
                <div class="bg-zinc-50 dark:bg-zinc-900 flex items-center justify-center" style="min-height: 320px;">
                    <iframe 
                        src="{{ $this->previewUrl }}?print=0"
                        class="border-0"
                        style="width: 380px; height: 280px;"
                        title="Certificate Preview">
                    </iframe>
                </div>
                @else
                <div class="bg-zinc-50 dark:bg-zinc-900 overflow-hidden"
                     x-data="{ scale: 1 }"
                     x-init="$nextTick(() => { scale = Math.min(1, $el.offsetWidth / 794); })"
                     x-on:resize.window.debounce.150ms="scale = Math.min(1, $el.offsetWidth / 794)"
                     :style="'height: ' + Math.ceil(1123 * scale) + 'px'">
                    <iframe 
                        src="{{ $this->previewUrl }}?print=0"
                        class="border-0"
                        style="width: 794px; height: 1123px;"
                        :style="'width: 794px; height: 1123px; transform: scale(' + scale + '); transform-origin: top left;'"
                        title="Certificate Preview">
                    </iframe>
                </div>
                @endif

                {{-- Action buttons --}}
                <div class="px-5 py-3.5 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $this->previewUrl }}" target="_blank" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-[#0B4EA2] hover:bg-[#0a3d80] text-white rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/></svg>
                            View
                        </a>
                        <a href="{{ $this->downloadUrl }}" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-[#F58220] hover:bg-[#e0741b] text-white rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            Download PDF
                        </a>
                        <a href="{{ $this->previewUrl }}" target="_blank"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Print
                        </a>
                        @if(auth()->user()->isAdmin() || auth()->user()->isOwner() || auth()->user()->isDeveloper())
                            <button wire:click="$set('showDeleteModal', true)" 
                                class="inline-flex items-center gap-2 px-4 py-2 border border-red-300 dark:border-red-600 text-red-700 dark:text-red-300 rounded-lg text-sm hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                        @endif
                    </div>
                    @if($this->isMembershipCard && $this->walletEnabled)
                    <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2] mb-2">Add to Wallet</p>
                        <div class="flex gap-2">
                            @if($this->appleWalletEnabled)
                            <a href="{{ $this->appleWalletUrl }}" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-black hover:bg-zinc-800 text-white rounded-lg text-sm font-medium transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.96-3.24-.96-1.15 0-1.36.93-2.85.93-1.5 0-2.14-.91-3.27-1.97-1.13-1.05-2.14-2.98-2.14-5.05 0-3.11 2.04-4.85 4.11-4.85 1.02 0 1.84.37 2.49.37.63 0 1.62-.38 2.74-.38 2.35 0 3.98 1.35 3.98 3.89 0 .78-.13 1.56-.38 2.32-.25.75-.56 1.5-.99 2.18zM12.03 3.5c.57-1.29 1.28-2.35 2.31-3.18.99-.81 2.4-1.27 3.44-1.05.11.6.41 1.17.89 1.67.48.5 1.05.86 1.66 1.11.6.25 1.25.38 1.88.38-.08 1.3-.41 2.54-1.03 3.66-.61 1.11-1.45 2.07-2.47 2.83-1.01.75-2.18 1.28-3.4 1.57-.12-.6-.41-1.17-.89-1.67-.48-.5-1.05-.86-1.66-1.11-.6-.25-1.25-.38-1.88-.38.08-1.3.41-2.54 1.03-3.66.61-1.11 1.45-2.07 2.47-2.83z"/>
                                </svg>
                                Apple Wallet
                            </a>
                            @endif
                            @if($this->googleWalletEnabled)
                            <a href="{{ $this->googleWalletUrl }}" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-zinc-50 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg text-sm font-medium transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                                Google Wallet
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            {{-- Certificate Details Card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Certificate Details</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Type</div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white break-words">{{ $this->certificate->certificateType->name }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Certificate Number</div>
                        <div class="font-mono text-sm font-semibold text-zinc-900 dark:text-white break-all">{{ $this->certificate->certificate_number }}</div>
                    </div>
                    @if($this->certificate->membership)
                    <div class="pt-3 border-t border-zinc-100 dark:border-zinc-700 space-y-3">
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Member Name</div>
                            <div class="text-sm font-semibold text-zinc-900 dark:text-white break-words">{{ $this->certificate->user->name ?? 'N/A' }}</div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Type</div>
                                <div class="text-sm text-zinc-900 dark:text-white break-words">{{ $this->certificate->membership->type->name ?? 'N/A' }}</div>
                            </div>
                            <div class="flex-1">
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Number</div>
                                <div class="font-mono text-sm text-zinc-900 dark:text-white break-all">{{ $this->certificate->membership->membership_number ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="pt-3 border-t border-zinc-100 dark:border-zinc-700 space-y-3">
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Issued</div>
                                <div class="text-sm text-zinc-900 dark:text-white">{{ $this->certificate->issued_at->format('d M Y') }}</div>
                            </div>
                            <div class="flex-1">
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Valid From</div>
                                <div class="text-sm text-zinc-900 dark:text-white">{{ $this->certificate->valid_from->format('d M Y') }}</div>
                            </div>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Valid Until</div>
                            <div class="text-sm text-zinc-900 dark:text-white">
                                @if($this->certificate->valid_until)
                                    {{ $this->certificate->valid_until->format('d M Y') }}
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[#F58220]/10 text-[#F58220]">Indefinite</span>
                                @endif
                            </div>
                        </div>
                        @if($this->certificate->issuer)
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Issued By</div>
                            <div class="text-sm text-zinc-900 dark:text-white">{{ $this->certificate->issuer->name }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- QR Verification Card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-[10px] font-semibold uppercase tracking-wider text-[#0B4EA2]">Verification</h2>
                </div>
                <div class="p-5">
                    <div class="flex flex-col items-center gap-3">
                        <div class="flex items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 p-2.5 dark:border-zinc-600 dark:bg-zinc-700">
                            <img src="{{ $this->qrCodeImageUrl }}" alt="QR Code" class="size-28 rounded" loading="lazy" />
                        </div>
                        <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                            Scan to verify certificate authenticity
                        </p>
                        <div class="w-full relative">
                            <input type="text" readonly value="{{ $this->verificationUrl }}"
                                class="w-full px-3 py-2 font-mono text-[10px] border border-zinc-200 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-700 text-zinc-900 dark:text-white pr-8">
                            <button onclick="navigator.clipboard.writeText('{{ $this->verificationUrl }}')" title="Copy URL"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-[#0B4EA2] transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Revocation Info --}}
            @if($this->certificate->isRevoked())
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border-2 border-red-300 dark:border-red-700 overflow-hidden">
                <div class="px-5 py-3 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-700">
                    <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <h2 class="text-sm font-bold uppercase tracking-wider">Revoked</h2>
                    </div>
                </div>
                <div class="p-5 space-y-3">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-red-500">Revoked On</div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $this->certificate->revoked_at->format('d F Y') }}</div>
                    </div>
                    @if($this->certificate->revocation_reason)
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-red-500">Reason</div>
                        <div class="text-sm text-zinc-900 dark:text-white">{{ $this->certificate->revocation_reason }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" x-data="{ show: @entangle('showDeleteModal') }" x-show="show" x-cloak>
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl max-w-md w-full mx-4 overflow-hidden" @click.away="show = false">
            <div class="bg-red-50 dark:bg-red-900/20 px-6 py-4 border-b border-red-200 dark:border-red-700">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-red-800 dark:text-red-200">Delete Certificate</h3>
                </div>
            </div>
            <div class="p-6">
                <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                    Are you sure you want to delete certificate <strong class="font-mono">{{ $this->certificate->certificate_number }}</strong>? 
                    This action cannot be undone and will permanently remove the certificate and its associated file.
                </p>
                <div class="flex gap-3 justify-end">
                    <button wire:click="$set('showDeleteModal', false)" 
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="deleteCertificate" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                        Delete Certificate
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
