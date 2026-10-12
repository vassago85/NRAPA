{{-- Evidence for a CalibreRequest approval. Data: request, exactMatch, matches --}}
<dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Requested calibre</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->name }}</dd>
    </div>
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Category</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">{{ ucfirst((string) $request->category) ?: '—' }}@if($request->ignition_type) · {{ ucfirst($request->ignition_type) }}@endif</dd>
    </div>
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">SAPS code</dt>
        <dd class="font-mono text-zinc-900 dark:text-white">{{ $request->saps_code ?: '—' }}</dd>
    </div>
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Aliases</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">{{ collect($request->aliases)->filter()->implode(', ') ?: '—' }}</dd>
    </div>
    @if($request->reason)
        <div class="sm:col-span-2">
            <dt class="text-zinc-500 dark:text-zinc-400">Member's reason</dt>
            <dd class="text-zinc-800 dark:text-zinc-200 whitespace-pre-line">{{ $request->reason }}</dd>
        </div>
    @endif
</dl>

<div class="mt-5">
    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Existing calibres</p>
    @if($exactMatch)
        <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
            {{ $exactMatch->name }} already exists. Approving links the request to it instead of adding a duplicate.
        </p>
    @endif
    @if($matches->isEmpty())
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">No similar calibres on the list.</p>
    @else
        <ul class="mt-2 divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($matches as $match)
                <li class="flex items-center justify-between py-2 text-sm">
                    <span class="text-zinc-800 dark:text-zinc-200">{{ $match->name }}</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ ucfirst((string) $match->category) }}@if($match->saps_code) · {{ $match->saps_code }}@endif</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
