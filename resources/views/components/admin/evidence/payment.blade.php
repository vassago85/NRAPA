{{-- Evidence for a Payment approval. Data: payment, membership, proofUrl, isPdf, reference, referenceMatches, pattern --}}
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Amount due</p>
        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ $payment->formattedAmount() }}</p>
    </div>
    <div>
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Reference</p>
        <p class="mt-1 font-mono text-sm text-zinc-900 dark:text-white">{{ $reference ?? 'None issued' }}</p>
        <p @class(['mt-0.5 text-xs', 'text-emerald-700 dark:text-emerald-300' => $referenceMatches, 'text-amber-700 dark:text-amber-300' => ! $referenceMatches])>
            {{ $referenceMatches ? 'Matches the expected format' : 'Does not match '.$pattern }}
        </p>
    </div>
    <div>
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">For</p>
        <p class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $membership?->type?->name ?? 'Membership' }}</p>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
            {{ $membership?->status === 'pending_payment' ? 'Type change' : ($membership?->isRenewal() ? 'Renewal' : 'New application') }}
            @if($payment->submitted_at) · uploaded {{ $payment->submitted_at->format('d M Y, H:i') }} @endif
        </p>
    </div>
</div>

<div class="mt-5">
    @if($proofUrl)
        @if($isPdf)
            {{-- PDF Open Parameters: collapse the thumbnail panel + fit page width. Without this, a POP PDF in a narrow preview pane shows a horizontal scrollbar and a tiny document. --}}
            <iframe src="{{ $proofUrl }}#toolbar=1&navpanes=0&view=FitH" class="h-[min(72vh,760px)] min-h-[520px] w-full rounded-lg border border-zinc-200 dark:border-zinc-700" title="Proof of payment"></iframe>
        @else
            <img src="{{ $proofUrl }}" alt="Proof of payment" class="w-full max-h-[min(72vh,760px)] rounded-lg border border-zinc-200 object-contain dark:border-zinc-700">
        @endif
        <a href="{{ $proofUrl }}" target="_blank" rel="noopener" class="mt-2 inline-block text-xs text-zinc-500 hover:text-nrapa-blue hover:underline dark:text-zinc-400">Open full-size in a new tab ↗</a>
    @else
        <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            No proof of payment file to show.
        </div>
    @endif
</div>
