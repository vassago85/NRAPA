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

                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex gap-3">
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
