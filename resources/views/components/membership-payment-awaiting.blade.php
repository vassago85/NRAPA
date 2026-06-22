@props(['membership', 'bankAccount', 'proofOfPayment' => null])

@php
    $membership->loadMissing('type', 'previousMembership.type');
    $isUpgrade = $membership->isTypeChange();
@endphp

<div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-6 dark:border-amber-600 dark:bg-amber-900/20">
    <div class="flex items-center gap-3 mb-4">
        <div class="flex size-10 items-center justify-center rounded-lg bg-amber-200 dark:bg-amber-800">
            <svg class="size-5 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <div>
            <h3 class="font-semibold text-amber-800 dark:text-amber-200">
                {{ $isUpgrade ? 'Payment Required for Membership Upgrade' : 'Payment Required' }}
            </h3>
            <p class="text-sm text-amber-600 dark:text-amber-400">
                @if($isUpgrade)
                    Upgrade to <strong>{{ $membership->type?->name }}</strong>
                    @if($membership->previousMembership?->type)
                        <span class="text-amber-500 dark:text-amber-400">(from {{ $membership->previousMembership->type->name }})</span>
                    @endif
                    — pay via EFT and upload your proof of payment below.
                @else
                    Make your EFT payment using the reference below, then upload your proof of payment.
                @endif
            </p>
        </div>
    </div>

    @if($membership->payment_reference)
    <div class="mb-4">
        <p class="text-xs font-medium text-amber-700 dark:text-amber-300 mb-2">YOUR PAYMENT REFERENCE (click to copy)</p>
        <div
            x-data="{ copied: false }"
            x-on:click="navigator.clipboard.writeText('{{ $membership->payment_reference }}'); copied = true; setTimeout(() => copied = false, 2000)"
            class="cursor-pointer group"
        >
            <div class="flex items-center justify-between gap-4 p-4 bg-white dark:bg-zinc-800 rounded-lg border-2 border-dashed border-amber-400 dark:border-amber-600 hover:border-amber-500 dark:hover:border-amber-500 transition-colors">
                <span class="text-2xl font-mono font-bold text-zinc-900 dark:text-white tracking-wider">{{ $membership->payment_reference }}</span>
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
    @endif

    <div class="flex items-center justify-between p-3 bg-white/50 dark:bg-zinc-800/50 rounded-lg mb-4">
        <span class="text-amber-700 dark:text-amber-300 font-medium">
            {{ $isUpgrade ? 'Upgrade fee to pay:' : 'Amount to pay:' }}
        </span>
        <span class="text-xl font-bold text-amber-800 dark:text-amber-200">R{{ number_format($membership->amount_due, 2) }}</span>
    </div>

    <div class="bg-white/50 dark:bg-zinc-800/50 rounded-lg p-4 mb-4">
        <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-3">Bank Account Details</h4>
        <dl class="grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
            <div class="flex items-baseline gap-2">
                <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Bank:</dt>
                <dd class="font-medium text-amber-800 dark:text-amber-200">{{ $bankAccount['bank_name'] ?: 'To be confirmed' }}</dd>
            </div>
            <div class="flex items-baseline gap-2">
                <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Account Name:</dt>
                <dd class="font-medium text-amber-800 dark:text-amber-200">{{ $bankAccount['account_name'] ?: 'To be confirmed' }}</dd>
            </div>
            <div class="flex items-baseline gap-2">
                <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Account Number:</dt>
                <dd class="font-mono font-medium text-amber-800 dark:text-amber-200">{{ $bankAccount['account_number'] ?: 'To be confirmed' }}</dd>
            </div>
            <div class="flex items-baseline gap-2">
                <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Branch Code:</dt>
                <dd class="font-mono font-medium text-amber-800 dark:text-amber-200">{{ $bankAccount['branch_code'] ?: 'To be confirmed' }}</dd>
            </div>
            <div class="flex items-baseline gap-2">
                <dt class="text-amber-600 dark:text-amber-400 whitespace-nowrap">Account Type:</dt>
                <dd class="font-medium text-amber-800 dark:text-amber-200">{{ $bankAccount['account_type'] ?: 'To be confirmed' }}</dd>
            </div>
        </dl>
    </div>

    <p class="text-sm text-amber-700 dark:text-amber-300 mb-4">
        <strong>Important:</strong> Payment instructions have been sent to your email.
        Use the exact reference above when making your EFT payment.
        Your {{ $isUpgrade ? 'upgrade' : 'membership' }} will be activated once payment is confirmed (1–3 business days).
    </p>

    <div class="border-t border-amber-300 dark:border-amber-600 pt-4">
        <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-3">Upload Proof of Payment</h4>

        @if($membership->proof_of_payment_path)
            <div class="flex items-center justify-between gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                <div class="flex items-center gap-2">
                    <svg class="size-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Proof of payment uploaded — pending admin review</span>
                </div>
                <button
                    wire:click="removeProofOfPayment({{ $membership->id }})"
                    wire:confirm="Remove the uploaded proof of payment?"
                    class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium"
                >
                    Remove
                </button>
            </div>
        @else
            <form
                wire:submit="uploadProofOfPayment({{ $membership->id }})"
                class="space-y-3"
                x-data="{ dragging: false }"
                x-on:dragover.prevent="dragging = true"
                x-on:dragleave.prevent="dragging = false"
                x-on:drop.prevent="dragging = false; $refs.popInput.files = $event.dataTransfer.files; $refs.popInput.dispatchEvent(new Event('change', { bubbles: true }))"
            >
                <label class="block cursor-pointer">
                    <div
                        class="flex flex-col items-center justify-center gap-2 p-6 bg-white dark:bg-zinc-800 rounded-lg border-2 border-dashed transition-colors"
                        :class="dragging ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-amber-300 dark:border-amber-600 hover:border-amber-400 dark:hover:border-amber-500'"
                    >
                        <svg class="size-8 text-amber-400" :class="dragging && 'text-emerald-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        @if($proofOfPayment ?? false)
                            <span class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">{{ $proofOfPayment->getClientOriginalName() }}</span>
                        @else
                            <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Drop file here or click to browse</span>
                            <span class="text-xs text-amber-600 dark:text-amber-400">JPG, PNG, or PDF — max 5MB</span>
                        @endif
                    </div>
                    <input x-ref="popInput" wire:model="proofOfPayment" type="file" accept=".jpg,.jpeg,.png,.pdf" class="sr-only">
                </label>
                @error('proofOfPayment') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                @if($proofOfPayment ?? false)
                    <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                        Upload Proof of Payment
                    </button>
                @endif
            </form>
        @endif
    </div>
</div>
