<?php

use App\Models\Certificate;
use App\Helpers\QrCodeHelper;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Title('My Digital Card')] #[Layout('components.layouts.card')] class extends Component {
    public bool $showAddToHomeInstructions = true;

    public function mount(): void
    {
        // Check if user has dismissed the add-to-home instructions
        $this->showAddToHomeInstructions = !session()->get('card_add_to_home_dismissed', false);
    }

    public function dismissInstructions(): void
    {
        $this->showAddToHomeInstructions = false;
        session()->put('card_add_to_home_dismissed', true);
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function membership()
    {
        return $this->user->activeMembership;
    }

    #[Computed]
    public function membershipCard()
    {
        // Get the membership card certificate for this user
        return Certificate::where('user_id', $this->user->id)
            ->whereHas('certificateType', fn($q) => $q->where('slug', 'membership-card'))
            ->valid()
            ->latest()
            ->first();
    }

    #[Computed]
    public function qrCodeUrl()
    {
        if (!$this->membershipCard) {
            return null;
        }
        
        $verificationUrl = $this->membershipCard->getVerificationUrl();
        return QrCodeHelper::generateUrl($verificationUrl, 250);
    }

    #[Computed]
    public function isExpired()
    {
        if (!$this->membership) {
            return true;
        }
        return $this->membership->isExpired();
    }

    #[Computed]
    public function isLifetime()
    {
        if (!$this->membership) {
            return false;
        }
        return $this->membership->type?->is_lifetime ?? false;
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900 flex flex-col">
    {{-- PWA Meta Tags --}}
    @push('head')
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="NRAPA Card">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#18181b">
    <link rel="apple-touch-icon" href="{{ asset('images/nrapa-icon-180.png') }}">
    @endpush

    {{-- Add to Home Screen Instructions (Dismissible) --}}
    @if($showAddToHomeInstructions)
    <div class="bg-emerald-600 text-white px-4 py-3 text-center text-sm">
        <div class="flex items-center justify-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <span class="hidden sm:inline">Add this card to your home screen for quick access</span>
            <span class="sm:hidden">Tap <strong>Share</strong> → <strong>Add to Home Screen</strong></span>
            <button wire:click="dismissInstructions" class="ml-2 p-1 hover:bg-emerald-700 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    {{-- Main Card Content --}}
    <div class="flex-1 flex items-center justify-center p-4">
        @if($this->membership && $this->membershipCard)
        {{-- Digital Membership Card --}}
        <div class="w-full max-w-sm">
            {{-- Card Container --}}
            <div class="bg-gradient-to-br from-zinc-800 to-zinc-900 rounded-3xl shadow-2xl overflow-hidden border border-zinc-700">
                {{-- Card Header --}}
                <div class="bg-emerald-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            {{-- Logo --}}
                            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center">
                                <img src="{{ asset('images/nrapa-logo.png') }}" alt="NRAPA" class="w-10 h-10 object-contain" onerror="this.style.display='none'">
                            </div>
                            <div>
                                <h1 class="text-white font-bold text-lg tracking-tight">NRAPA</h1>
                                <p class="text-emerald-100 text-xs">Member Card</p>
                            </div>
                        </div>
                        {{-- Status Badge --}}
                        @if($this->isExpired)
                        <span class="px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full uppercase">Expired</span>
                        @else
                        <span class="px-3 py-1 bg-emerald-400 text-emerald-900 text-xs font-bold rounded-full uppercase">Active</span>
                        @endif
                    </div>
                </div>

                {{-- Card Body --}}
                <div class="px-6 py-6 space-y-6">
                    {{-- Member Info --}}
                    <div class="space-y-4">
                        <div>
                            <p class="text-zinc-500 text-xs uppercase tracking-wider">Member Name</p>
                            <p class="text-white text-xl font-semibold">{{ $this->user->name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-zinc-500 text-xs uppercase tracking-wider">Membership No.</p>
                                <p class="text-white font-mono text-sm">{{ $this->membership->membership_number }}</p>
                            </div>
                            <div>
                                <p class="text-zinc-500 text-xs uppercase tracking-wider">Type</p>
                                <p class="text-white text-sm">{{ $this->membership->type?->name ?? 'Member' }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-zinc-500 text-xs uppercase tracking-wider">Valid Until</p>
                            @if($this->isLifetime)
                            <p class="text-emerald-400 font-semibold">Lifetime</p>
                            @else
                            <p class="text-white {{ $this->isExpired ? 'text-red-400' : '' }}">
                                {{ $this->membership->expires_at?->format('d M Y') ?? 'N/A' }}
                            </p>
                            @endif
                        </div>
                    </div>

                    {{-- QR Code --}}
                    <div class="flex flex-col items-center">
                        <div class="bg-white p-3 rounded-2xl shadow-lg">
                            <img src="{{ $this->qrCodeUrl }}" alt="Verification QR Code" class="w-48 h-48" loading="lazy">
                        </div>
                        <p class="text-zinc-500 text-xs mt-3 text-center">Scan to verify membership</p>
                    </div>
                </div>

                {{-- Card Footer --}}
                <div class="px-6 py-3 bg-zinc-900/50 border-t border-zinc-700">
                    <p class="text-zinc-500 text-xs text-center">
                        Certificate: {{ $this->membershipCard->certificate_number }}
                    </p>
                </div>
            </div>

            {{-- Back to Dashboard Link --}}
            <div class="mt-6 text-center">
                <a href="{{ route('dashboard') }}" wire:navigate class="text-zinc-400 hover:text-white text-sm inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>

        @elseif($this->membership && !$this->membershipCard)
        {{-- Membership exists but no card certificate --}}
        <div class="text-center px-6 max-w-sm">
            <div class="w-16 h-16 bg-amber-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h2 class="text-white text-xl font-semibold mb-2">Card Not Available</h2>
            <p class="text-zinc-400 mb-6">Your digital membership card is being generated. Please check back shortly or contact support if this persists.</p>
            <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
        </div>

        @else
        {{-- No active membership --}}
        <div class="text-center px-6 max-w-sm">
            <div class="w-16 h-16 bg-zinc-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                </svg>
            </div>
            <h2 class="text-white text-xl font-semibold mb-2">No Active Membership</h2>
            <p class="text-zinc-400 mb-6">You need an active membership to access your digital card.</p>
            <a href="{{ route('membership.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                View Membership Options
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
        @endif
    </div>
</div>
