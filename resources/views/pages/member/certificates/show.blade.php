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
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                @php
                    $backRoute = 'certificates.index';
                    if (auth()->user()->isDeveloper()) {
                        $backRoute = 'developer.certificates.index';
                    } elseif (auth()->user()->isOwner()) {
                        $backRoute = 'owner.certificates.index';
                    } elseif (auth()->user()->isAdmin()) {
                        $backRoute = 'admin.certificates.index';
                    }
                @endphp
                <a href="{{ route($backRoute) }}" wire:navigate
                   class="inline-flex items-center gap-1 text-sm text-zinc-600 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->certificate->certificateType->name }}</h1>
                    <p class="font-mono text-zinc-500">{{ $this->certificate->certificate_number }}</p>
                    @if((auth()->user()->isDeveloper() || auth()->user()->isOwner() || auth()->user()->isAdmin()) && $this->certificate->user)
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Member: {{ $this->certificate->user->name }}</p>
                    @endif
                </div>
            </div>
            @if($this->certificate->isValid())
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Valid
                </span>
            @elseif($this->certificate->isRevoked())
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Revoked
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Expired
                </span>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Certificate Preview --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Certificate Preview</h2>
                    </div>

                    <div class="p-6">
                        {{-- Certificate Preview iframe --}}
                        <div class="bg-zinc-100 dark:bg-zinc-900 rounded-lg overflow-hidden" style="min-height: 600px;">
                            <iframe 
                                src="{{ $this->previewUrl }}?print=0"
                                class="w-full h-full border-0"
                                style="min-height: 600px;"
                                title="Certificate Preview">
                            </iframe>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ $this->previewUrl }}" target="_blank" 
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/></svg>
                                View Full Screen
                            </a>
                            <a href="{{ $this->previewUrl }}" target="_blank" 
                                class="inline-flex items-center gap-2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print
                            </a>
                            
                            @if($this->isMembershipCard && $this->walletEnabled)
                                <div class="flex-1 border-l border-zinc-300 dark:border-zinc-600 pl-3">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Add to Wallet:</p>
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
            </div>

            {{-- Certificate Details --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Certificate Details</h2>
                    </div>

                    <div class="p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm text-zinc-500">Type</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $this->certificate->certificateType->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-zinc-500">Certificate Number</dt>
                                <dd class="font-mono font-medium text-zinc-900 dark:text-white">{{ $this->certificate->certificate_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-zinc-500">Issue Date</dt>
                                <dd class="text-zinc-900 dark:text-white">{{ $this->certificate->issued_at->format('d F Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-zinc-500">Valid From</dt>
                                <dd class="text-zinc-900 dark:text-white">{{ $this->certificate->valid_from->format('d F Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-zinc-500">Valid Until</dt>
                                <dd class="text-zinc-900 dark:text-white">
                                    @if($this->certificate->valid_until)
                                        {{ $this->certificate->valid_until->format('d F Y') }}
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Indefinite</span>
                                    @endif
                                </dd>
                            </div>
                            @if($this->certificate->issuer)
                            <div>
                                <dt class="text-sm text-zinc-500">Issued By</dt>
                                <dd class="text-zinc-900 dark:text-white">{{ $this->certificate->issuer->name }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- QR Verification --}}
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">QR Verification</h2>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-center">
                                <div class="flex size-32 items-center justify-center rounded-xl bg-white p-2 dark:bg-zinc-700">
                                    {{-- QR Code would be generated here --}}
                                    <svg class="w-24 h-24 text-zinc-800 dark:text-zinc-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                </div>
                            </div>
                            <p class="text-center text-sm text-zinc-500">
                                Scan this QR code to verify the certificate authenticity.
                            </p>
                            <input type="text" readonly value="{{ $this->verificationUrl }}"
                                   class="w-full px-4 py-2 font-mono text-xs border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        </div>
                    </div>
                </div>

                {{-- Revocation Info --}}
                @if($this->certificate->isRevoked())
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-red-200 dark:border-red-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-red-200 dark:border-red-800">
                        <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <h2 class="text-lg font-semibold">Revoked</h2>
                        </div>
                    </div>

                    <div class="p-6">
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm text-zinc-500">Revoked On</dt>
                                <dd class="text-zinc-900 dark:text-white">{{ $this->certificate->revoked_at->format('d F Y') }}</dd>
                            </div>
                            @if($this->certificate->revocation_reason)
                            <div>
                                <dt class="text-sm text-zinc-500">Reason</dt>
                                <dd class="text-zinc-900 dark:text-white">{{ $this->certificate->revocation_reason }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
