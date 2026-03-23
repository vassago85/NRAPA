<?php

use App\Models\Membership;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Membership Details')] class extends Component {
    use WithFileUploads;

    public Membership $membership;
    public $proofOfPayment = null;

    public function mount(Membership $membership): void
    {
        if ($membership->user_id !== Auth::id()) {
            abort(403);
        }

        $this->membership = $membership->load(['type', 'approver', 'certificates.certificateType']);
    }

    #[Computed]
    public function bankAccount(): array
    {
        return SystemSetting::getBankAccount();
    }

    public function uploadProofOfPayment(): void
    {
        $this->validate([
            'proofOfPayment' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $path = $this->proofOfPayment->store(
            "proof-of-payment/{$this->membership->user_id}",
            'r2',
        );

        $this->membership->update(['proof_of_payment_path' => $path]);
        $this->proofOfPayment = null;

        session()->flash('success', 'Proof of payment uploaded successfully. It will be reviewed by an administrator.');
    }

    public function removeProofOfPayment(): void
    {
        if ($this->membership->proof_of_payment_path) {
            Storage::disk('r2')->delete($this->membership->proof_of_payment_path);
            $this->membership->update(['proof_of_payment_path' => null]);
            session()->flash('success', 'Proof of payment removed.');
        }
    }

    public function getStatusClasses(): string
    {
        return match($this->membership->status) {
            'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
            'applied' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'pending_change' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300',
            'pending_payment' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
            'suspended', 'revoked' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'expired' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('membership.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Membership Details</h1>
                    <p class="mt-1 text-sm font-mono text-zinc-500 dark:text-zinc-400">{{ $this->membership->membership_number }}</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium {{ $this->getStatusClasses() }}">
                {{ match($this->membership->status) {
                    'pending_change' => 'Awaiting Review',
                    'pending_payment' => 'Payment Required',
                    default => ucfirst($this->membership->status),
                } }}
            </span>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">

    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    {{-- Payment Reference Card (for pending applications) --}}
    @if($this->membership->status === 'applied' && $this->membership->payment_reference)
    <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-6 dark:border-amber-600 dark:bg-amber-900/20">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex size-10 items-center justify-center rounded-lg bg-amber-200 dark:bg-amber-800">
                <svg class="size-5 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-amber-800 dark:text-amber-200">Payment Required</h3>
                <p class="text-sm text-amber-600 dark:text-amber-400">Make your EFT payment using the reference below</p>
            </div>
        </div>

        {{-- Payment Reference - Prominent --}}
        <div class="mb-4">
            <p class="text-xs font-medium text-amber-700 dark:text-amber-300 mb-2">YOUR PAYMENT REFERENCE (click to copy)</p>
            <div 
                x-data="{ copied: false }"
                x-on:click="navigator.clipboard.writeText('{{ $this->membership->payment_reference }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="cursor-pointer group"
            >
                <div class="flex items-center justify-between gap-4 p-4 bg-white dark:bg-zinc-800 rounded-lg border-2 border-dashed border-amber-400 dark:border-amber-600 hover:border-amber-500 dark:hover:border-amber-500 transition-colors">
                    <span class="text-2xl font-mono font-bold text-zinc-900 dark:text-white tracking-wider">{{ $this->membership->payment_reference }}</span>
                    <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                        <svg x-show="!copied" class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <svg x-show="copied" x-cloak class="size-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="copied ? 'Copied!' : 'Copy'" class="text-sm font-medium"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Amount Due --}}
        <div class="flex items-center justify-between p-3 bg-white/50 dark:bg-zinc-800/50 rounded-lg mb-4">
            <span class="text-amber-700 dark:text-amber-300 font-medium">Amount to Pay:</span>
            <span class="text-xl font-bold text-amber-800 dark:text-amber-200">R{{ number_format($this->membership->amount_due, 2) }}</span>
        </div>

        {{-- Bank Account Details --}}
        <div class="bg-white/50 dark:bg-zinc-800/50 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-3">Bank Account Details</h4>
            <dl class="grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                <div class="flex items-baseline gap-2">
                    <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Bank:</dt>
                    <dd class="font-medium text-amber-800 dark:text-amber-200">{{ $this->bankAccount['bank_name'] ?: 'To be confirmed' }}</dd>
                </div>
                <div class="flex items-baseline gap-2">
                    <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Account Name:</dt>
                    <dd class="font-medium text-amber-800 dark:text-amber-200">{{ $this->bankAccount['account_name'] ?: 'To be confirmed' }}</dd>
                </div>
                <div class="flex items-baseline gap-2">
                    <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Account Number:</dt>
                    <dd class="font-mono font-medium text-amber-800 dark:text-amber-200">{{ $this->bankAccount['account_number'] ?: 'To be confirmed' }}</dd>
                </div>
                <div class="flex items-baseline gap-2">
                    <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Branch Code:</dt>
                    <dd class="font-mono font-medium text-amber-800 dark:text-amber-200">{{ $this->bankAccount['branch_code'] ?: 'To be confirmed' }}</dd>
                </div>
                <div class="flex items-baseline gap-2">
                    <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Account Type:</dt>
                    <dd class="font-medium text-amber-800 dark:text-amber-200">{{ $this->bankAccount['account_type'] ?: 'To be confirmed' }}</dd>
                </div>
            </dl>
        </div>

        <p class="text-sm text-amber-700 dark:text-amber-300">
            <strong>Important:</strong> Payment instructions have been sent to your email. 
            Please use the exact reference above when making your EFT payment. 
            Your membership will be activated once payment is confirmed (1-3 business days).
        </p>

        {{-- Proof of Payment Upload --}}
        <div class="mt-4 border-t border-amber-300 dark:border-amber-600 pt-4">
            <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-3">Upload Proof of Payment</h4>

            @if($this->membership->proof_of_payment_path)
                <div class="flex items-center justify-between gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                    <div class="flex items-center gap-2">
                        <svg class="size-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Proof of payment uploaded — pending admin review</span>
                    </div>
                    <button wire:click="removeProofOfPayment" wire:confirm="Remove the uploaded proof of payment?"
                        class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium">
                        Remove
                    </button>
                </div>
            @else
                <form wire:submit="uploadProofOfPayment" class="space-y-3"
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="dragging = false; $refs.popInput.files = $event.dataTransfer.files; $refs.popInput.dispatchEvent(new Event('change', { bubbles: true }))">
                    <label class="block cursor-pointer">
                        <div class="flex flex-col items-center justify-center gap-2 p-6 bg-white dark:bg-zinc-800 rounded-lg border-2 border-dashed transition-colors"
                            :class="dragging ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-amber-300 dark:border-amber-600 hover:border-amber-400 dark:hover:border-amber-500'">
                            <svg class="size-8 text-amber-400" :class="dragging && 'text-emerald-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            @if($proofOfPayment)
                                <span class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">{{ $proofOfPayment->getClientOriginalName() }}</span>
                            @else
                                <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Drop file here or click to browse</span>
                                <span class="text-xs text-amber-600 dark:text-amber-400">JPG, PNG, or PDF — max 5MB</span>
                            @endif
                        </div>
                        <input x-ref="popInput" wire:model="proofOfPayment" type="file" accept=".jpg,.jpeg,.png,.pdf" class="sr-only">
                    </label>
                    @error('proofOfPayment') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    @if($proofOfPayment)
                    <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                        Upload Proof of Payment
                    </button>
                    @endif
                </form>
            @endif
        </div>
    </div>
    @endif

    {{-- Pending Change Request Banner --}}
    @if($this->membership->status === 'pending_change')
    <div class="rounded-xl border-2 border-violet-300 bg-violet-50 p-6 dark:border-violet-600 dark:bg-violet-900/20">
        <div class="flex items-start gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-violet-200 dark:bg-violet-800">
                <svg class="size-5 text-violet-700 dark:text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-violet-800 dark:text-violet-200">Type Change Request — Awaiting Admin Review</h3>
                <p class="mt-1 text-sm text-violet-700 dark:text-violet-300">
                    Your request to change to <strong>{{ $this->membership->type?->name }}</strong> is being reviewed. 
                    An administrator will set a payment amount shortly and you will be notified.
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Pending Payment for Change Request --}}
    @if($this->membership->status === 'pending_payment')
    <div class="rounded-xl border-2 border-indigo-300 bg-indigo-50 p-6 dark:border-indigo-600 dark:bg-indigo-900/20">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex size-10 items-center justify-center rounded-lg bg-indigo-200 dark:bg-indigo-800">
                <svg class="size-5 text-indigo-700 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-indigo-800 dark:text-indigo-200">Payment Required for Type Change</h3>
                <p class="text-sm text-indigo-600 dark:text-indigo-400">Change to <strong>{{ $this->membership->type?->name }}</strong> — pay via EFT and upload proof</p>
            </div>
        </div>

        {{-- Payment Reference --}}
        @if($this->membership->payment_reference)
        <div class="mb-4">
            <p class="text-xs font-medium text-indigo-700 dark:text-indigo-300 mb-2">YOUR PAYMENT REFERENCE (click to copy)</p>
            <div x-data="{ copied: false }" x-on:click="navigator.clipboard.writeText('{{ $this->membership->payment_reference }}'); copied = true; setTimeout(() => copied = false, 2000)" class="cursor-pointer group">
                <div class="flex items-center justify-between gap-4 p-4 bg-white dark:bg-zinc-800 rounded-lg border-2 border-dashed border-indigo-400 dark:border-indigo-600 hover:border-indigo-500 transition-colors">
                    <span class="text-2xl font-mono font-bold text-zinc-900 dark:text-white tracking-wider">{{ $this->membership->payment_reference }}</span>
                    <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
                        <svg x-show="!copied" class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <svg x-show="copied" x-cloak class="size-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="copied ? 'Copied!' : 'Copy'" class="text-sm font-medium"></span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Amount Due --}}
        <div class="flex items-center justify-between p-3 bg-white/50 dark:bg-zinc-800/50 rounded-lg mb-4">
            <span class="text-indigo-700 dark:text-indigo-300 font-medium">Amount to Pay:</span>
            <span class="text-xl font-bold text-indigo-800 dark:text-indigo-200">R{{ number_format($this->membership->change_amount ?? 0, 2) }}</span>
        </div>

        {{-- Bank Account --}}
        <div class="bg-white/50 dark:bg-zinc-800/50 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-semibold text-indigo-800 dark:text-indigo-200 mb-3">Bank Account Details</h4>
            <dl class="grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                <div class="flex items-baseline gap-2">
                    <dt class="text-indigo-600 dark:text-indigo-400 whitespace-nowrap">Bank:</dt>
                    <dd class="font-medium text-indigo-800 dark:text-indigo-200">{{ $this->bankAccount['bank_name'] ?: 'To be confirmed' }}</dd>
                </div>
                <div class="flex items-baseline gap-2">
                    <dt class="text-indigo-600 dark:text-indigo-400 whitespace-nowrap">Account Name:</dt>
                    <dd class="font-medium text-indigo-800 dark:text-indigo-200">{{ $this->bankAccount['account_name'] ?: 'To be confirmed' }}</dd>
                </div>
                <div class="flex items-baseline gap-2">
                    <dt class="text-indigo-600 dark:text-indigo-400 whitespace-nowrap">Account Number:</dt>
                    <dd class="font-mono font-medium text-indigo-800 dark:text-indigo-200">{{ $this->bankAccount['account_number'] ?: 'To be confirmed' }}</dd>
                </div>
                <div class="flex items-baseline gap-2">
                    <dt class="text-indigo-600 dark:text-indigo-400 whitespace-nowrap">Branch Code:</dt>
                    <dd class="font-mono font-medium text-indigo-800 dark:text-indigo-200">{{ $this->bankAccount['branch_code'] ?: 'To be confirmed' }}</dd>
                </div>
            </dl>
        </div>

        {{-- POP Upload --}}
        <div class="border-t border-indigo-300 dark:border-indigo-600 pt-4">
            <h4 class="text-sm font-semibold text-indigo-800 dark:text-indigo-200 mb-3">Upload Proof of Payment</h4>

            @if($this->membership->proof_of_payment_path)
                <div class="flex items-center justify-between gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                    <div class="flex items-center gap-2">
                        <svg class="size-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Proof of payment uploaded — pending admin review</span>
                    </div>
                    <button wire:click="removeProofOfPayment" wire:confirm="Remove the uploaded proof of payment?"
                        class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 font-medium">Remove</button>
                </div>
            @else
                <form wire:submit="uploadProofOfPayment" class="space-y-3"
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="dragging = false; $refs.popInput2.files = $event.dataTransfer.files; $refs.popInput2.dispatchEvent(new Event('change', { bubbles: true }))">
                    <label class="block cursor-pointer">
                        <div class="flex flex-col items-center justify-center gap-2 p-6 bg-white dark:bg-zinc-800 rounded-lg border-2 border-dashed transition-colors"
                            :class="dragging ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-indigo-300 dark:border-indigo-600 hover:border-indigo-400'">
                            <svg class="size-8 text-indigo-400" :class="dragging && 'text-emerald-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            @if($proofOfPayment)
                                <span class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">{{ $proofOfPayment->getClientOriginalName() }}</span>
                            @else
                                <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Drop file here or click to browse</span>
                                <span class="text-xs text-indigo-600 dark:text-indigo-400">JPG, PNG, or PDF — max 5MB</span>
                            @endif
                        </div>
                        <input x-ref="popInput2" wire:model="proofOfPayment" type="file" accept=".jpg,.jpeg,.png,.pdf" class="sr-only">
                    </label>
                    @error('proofOfPayment') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    @if($proofOfPayment)
                    <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">Upload Proof of Payment</button>
                    @endif
                </form>
            @endif
        </div>
    </div>
    @endif

    {{-- Status Alerts --}}
    @if($this->membership->status === 'applied' && !$this->membership->payment_reference)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
        <div class="flex items-start gap-3">
            <svg class="size-5 flex-shrink-0 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div>
                <h3 class="font-semibold text-amber-800 dark:text-amber-200">Application Under Review</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    Your membership application is being reviewed by our team.
                    You will receive an email notification once it has been processed.
                </p>
            </div>
        </div>
    </div>
    @elseif($this->membership->status === 'approved')
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
        <div class="flex items-start gap-3">
            <svg class="size-5 flex-shrink-0 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
            </svg>
            <div>
                <h3 class="font-semibold text-blue-800 dark:text-blue-200">Application Approved!</h3>
                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    Your membership has been approved. Payment details have been sent to your email.
                    Your membership will be activated once payment is confirmed.
                </p>
            </div>
        </div>
    </div>
    @elseif($this->membership->status === 'suspended')
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-start gap-3">
            <svg class="size-5 flex-shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div>
                <h3 class="font-semibold text-red-800 dark:text-red-200">Membership Suspended</h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                    Your membership has been suspended. Reason: {{ $this->membership->suspension_reason ?? 'Contact support for more information.' }}
                </p>
            </div>
        </div>
    </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Membership Info --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Membership Information</h2>
            </div>

            <div class="p-6">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Type</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $this->membership->type->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Member Number</dt>
                        <dd class="font-mono font-medium text-zinc-900 dark:text-white">{{ $this->membership->membership_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Duration</dt>
                        <dd>
                            @if($this->membership->type->isLifetime())
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Lifetime</span>
                            @else
                                <span class="text-zinc-900 dark:text-white">{{ $this->membership->type->duration_months }} months</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Renewal</dt>
                        <dd class="text-zinc-900 dark:text-white">{{ $this->membership->type->requires_renewal ? 'Required annually' : 'Not required' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Price</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">R{{ number_format($this->membership->amount_due, 2) }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Timeline</h2>
            </div>

            <div class="p-6">
                <div class="space-y-4">
                    {{-- Applied --}}
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="flex size-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-400">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <div class="h-full w-px bg-zinc-200 dark:bg-zinc-700"></div>
                        </div>
                        <div class="pb-4">
                            <p class="font-medium text-zinc-900 dark:text-white">Application Submitted</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->membership->applied_at->format('d M Y \a\t H:i') }}</p>
                        </div>
                    </div>

                    {{-- Approved --}}
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            @if($this->membership->approved_at)
                            <div class="flex size-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-400">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            @else
                            <div class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            @endif
                            <div class="h-full w-px bg-zinc-200 dark:bg-zinc-700"></div>
                        </div>
                        <div class="pb-4">
                            <p class="font-medium {{ !$this->membership->approved_at ? 'text-zinc-400' : 'text-zinc-900 dark:text-white' }}">
                                Application Approved
                            </p>
                            @if($this->membership->approved_at)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $this->membership->approved_at->format('d M Y \a\t H:i') }}
                                @if($this->membership->approver)
                                    by {{ $this->membership->approver->name }}
                                @endif
                            </p>
                            @else
                            <p class="text-sm text-zinc-400">Pending</p>
                            @endif
                        </div>
                    </div>

                    {{-- Activated --}}
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            @if($this->membership->activated_at)
                            <div class="flex size-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-400">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            @else
                            <div class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            @endif
                        </div>
                        <div>
                            <p class="font-medium {{ !$this->membership->activated_at ? 'text-zinc-400' : 'text-zinc-900 dark:text-white' }}">
                                Membership Activated
                            </p>
                            @if($this->membership->activated_at)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->membership->activated_at->format('d M Y \a\t H:i') }}</p>
                            @else
                            <p class="text-sm text-zinc-400">Pending payment confirmation</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Certificates --}}
    @if($this->membership->certificates->count() > 0)
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Certificates</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Certificate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Certificate #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Issued</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Valid Until</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->membership->certificates as $certificate)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $certificate->certificateType->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-zinc-900 dark:text-white">{{ $certificate->certificate_number }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $certificate->issued_at->format('d M Y') }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($certificate->valid_until)
                                {{ $certificate->valid_until->format('d M Y') }}
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Indefinite</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            @if($certificate->isValid())
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Valid</span>
                            @elseif($certificate->isRevoked())
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Revoked</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">Expired</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            <a href="{{ route('certificates.show', $certificate) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    </div>
</div>
