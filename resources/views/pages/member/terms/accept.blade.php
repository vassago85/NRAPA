<?php

use App\Models\TermsVersion;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app.sidebar')] class extends Component {
    public $accepted = false;
    public $termsVersion;

    public function mount(): void
    {
        $this->termsVersion = TermsVersion::active();
        
        if (!$this->termsVersion) {
            session()->flash('error', 'No active Terms & Conditions found. Please contact support.');
            $this->redirect(route('dashboard'));
        }

        // Check if already accepted
        if (auth()->user()->hasAcceptedActiveTerms()) {
            $this->redirect(route('dashboard'));
        }
    }

    public function accept(): void
    {
        $this->validate([
            'accepted' => 'required|accepted',
        ], [
            'accepted.accepted' => 'You must accept the Terms & Conditions to continue.',
        ]);

        // Create acceptance record
        \App\Models\TermsAcceptance::create([
            'user_id' => auth()->id(),
            'terms_version_id' => $this->termsVersion->id,
            'accepted_at' => now(),
            'accepted_ip' => request()->ip(),
            'accepted_user_agent' => request()->userAgent(),
        ]);

        session()->flash('success', 'Terms & Conditions accepted successfully.');
        $this->redirect(route('dashboard'));
    }

    public function render(): mixed
    {
        return view('pages.member.terms.accept');
    }
};

?>

<div>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Terms & Conditions</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                You must accept the NRAPA Membership Terms & Conditions to continue using the platform.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl">

        @if(session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ $termsVersion->title }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Version: {{ $termsVersion->version }} • Published: {{ $termsVersion->published_at?->format('d F Y') ?? 'N/A' }}
                </p>
            </div>

            <div class="mb-6 max-h-[600px] overflow-y-auto rounded-lg border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900/50">
                {!! $termsVersion->getHtmlContent() !!}
            </div>

            <form wire:submit="accept" class="space-y-4">
                <div class="flex items-start gap-3">
                    <input 
                        type="checkbox" 
                        id="accepted" 
                        wire:model="accepted"
                        class="mt-1 h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700"
                        required
                    >
                    <label for="accepted" class="text-sm text-zinc-700 dark:text-zinc-300">
                        I have read and agree to the <strong>NRAPA Membership Terms & Conditions</strong> (Version {{ $termsVersion->version }})
                    </label>
                </div>

                @error('accepted')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="flex gap-3">
                    <button 
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark focus:outline-none focus:ring-2 focus:ring-nrapa-blue focus:ring-offset-2 disabled:opacity-50 transition-colors"
                        :disabled="!$wire.accepted"
                    >
                        Accept & Continue
                    </button>
                    <a 
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 transition-colors"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
