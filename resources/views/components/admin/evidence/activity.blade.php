{{-- Evidence for a ShootingActivity approval. Data: activity, withinPeriod, recent, compliance --}}
<dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Activity</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">{{ $activity->activityType?->name ?? 'Activity' }}@if($activity->track) · {{ ucfirst($activity->track) }}@endif</dd>
    </div>
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Date</dt>
        <dd @class(['font-medium', 'text-zinc-900 dark:text-white' => $withinPeriod, 'text-amber-700 dark:text-amber-300' => ! $withinPeriod])>
            {{ $activity->activity_date?->format('d M Y') ?? 'Not given' }}
            @unless($withinPeriod)
                <span class="block text-xs font-normal">Outside the membership period</span>
            @endunless
        </dd>
    </div>
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Location</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">{{ $activity->full_location ?: '—' }}</dd>
    </div>
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Firearm</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">{{ $activity->calibre_name ?? $activity->firearmCalibre?->name ?? '—' }}@if($activity->rounds_fired) · {{ $activity->rounds_fired }} rounds @endif</dd>
    </div>
    @if($activity->description)
        <div class="sm:col-span-2">
            <dt class="text-zinc-500 dark:text-zinc-400">Description</dt>
            <dd class="text-zinc-800 dark:text-zinc-200 whitespace-pre-line">{{ $activity->description }}</dd>
        </div>
    @endif
</dl>

<div class="mt-5 grid gap-3 sm:grid-cols-2">
    @foreach(['Evidence' => $activity->evidenceDocument, 'Additional document' => $activity->additionalDocument] as $label => $doc)
        @if($doc)
            <a href="{{ route('documents.preview', $doc) }}" target="_blank" rel="noopener"
               class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-3 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                <span class="text-zinc-800 dark:text-zinc-200">{{ $label }}: {{ $doc->original_filename ?: 'file' }}</span>
                <span class="font-medium text-nrapa-blue">Open</span>
            </a>
        @endif
    @endforeach
</div>

@if($compliance)
    <p class="mt-5 text-sm text-zinc-600 dark:text-zinc-400">
        {{ $compliance['qualifying_year']['total'] }} approved in {{ $compliance['qualifying_year']['year'] }},
        {{ $compliance['banking_year']['total'] }} so far in {{ $compliance['banking_year']['year'] }}. {{ $compliance['explainer'] }}
    </p>
@endif

@if($recent->isNotEmpty())
    <div class="mt-5">
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Other recent activities</p>
        <ul class="mt-2 divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($recent as $other)
                <li class="flex items-center justify-between py-2 text-sm">
                    <span class="text-zinc-800 dark:text-zinc-200">{{ $other->activity_date?->format('d M Y') }} · {{ $other->activityType?->name ?? 'Activity' }}</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ ucfirst($other->status) }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
