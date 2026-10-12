{{-- Evidence for a MemberDocument approval. Data: document, fileUrl, isPdf, previousRejection --}}
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Type</p>
        <p class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $document->documentType?->name ?? 'Document' }}</p>
    </div>
    <div>
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Expiry</p>
        <p @class(['mt-1 text-sm', 'text-red-700 dark:text-red-300' => $document->expires_at?->isPast(), 'text-zinc-900 dark:text-white' => ! $document->expires_at?->isPast()])>
            {{ $document->expires_at?->format('d M Y') ?? 'Does not expire' }}
        </p>
    </div>
    <div>
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Uploaded</p>
        <p class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $document->uploaded_at?->format('d M Y, H:i') ?? $document->created_at?->format('d M Y, H:i') }}</p>
        @if($document->original_filename)
            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $document->original_filename }}</p>
        @endif
    </div>
</div>

@if($previousRejection)
    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-800 dark:bg-amber-900/20">
        <p class="font-medium text-amber-800 dark:text-amber-200">Previously rejected {{ $previousRejection->verified_at?->format('d M Y') }}</p>
        <p class="mt-0.5 text-amber-700 dark:text-amber-300">{{ $previousRejection->rejection_reason ?: 'No reason recorded' }}</p>
    </div>
@endif

<div class="mt-5">
    @if($fileUrl)
        @if($isPdf)
            <iframe src="{{ $fileUrl }}" class="h-[560px] w-full rounded-lg border border-zinc-200 dark:border-zinc-700" title="Document"></iframe>
        @else
            <a href="{{ $fileUrl }}" target="_blank" rel="noopener">
                <img src="{{ $fileUrl }}" alt="{{ $document->documentType?->name }}" class="max-h-[560px] rounded-lg border border-zinc-200 object-contain dark:border-zinc-700">
            </a>
        @endif
        <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-medium text-nrapa-blue hover:underline">Open in a new tab</a>
    @else
        <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            The file is no longer available.
        </div>
    @endif
</div>
