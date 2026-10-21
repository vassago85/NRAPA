{{-- Evidence for an EndorsementRequest approval. Data: request, acknowledgements, branch, eligibility --}}
@php
    $firearm = $request->firearm;
    $make = $firearm?->make ?? $request->firearm_make;
    $model = $firearm?->model ?? $request->firearm_model;
    $calibre = $firearm?->calibre_manual ?? $request->firearm_calibre;
    $serial = $firearm?->serial_number ?? $firearm?->barrel_serial_number ?? $request->firearm_serial;
    $accepted = $acknowledgements->where('accepted', true);
@endphp

@php
    $templateLabel = match ($branch) {
        \App\Models\EndorsementRequest::ENDORSEMENT_TYPE_SELF_DEFENCE => 'Self-defence supporting letter',
        \App\Models\EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL => 'Occasional (Section 15) letter',
        default => 'Dedicated status endorsement',
    };
@endphp

<dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Template</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">
            {{ $templateLabel }}
            · {{ $request->request_type === \App\Models\EndorsementRequest::TYPE_RENEWAL ? 'Renewal' : 'New' }}
        </dd>
    </div>
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Firearm</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">{{ trim(($make ?? '').' '.($model ?? '')) ?: '—' }}@if($calibre) · {{ $calibre }}@endif</dd>
    </div>
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Serial</dt>
        <dd class="font-mono text-zinc-900 dark:text-white">{{ $serial ?: '—' }}</dd>
    </div>
    @if($branch !== \App\Models\EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL)
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Dedicated category</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">{{ $request->dedicated_category ? str($request->dedicated_category)->replace('_', ' ')->title() : 'Not set' }}</dd>
    </div>
    @else
    <div>
        <dt class="text-zinc-500 dark:text-zinc-400">Requirement</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">Member in good standing only</dd>
    </div>
    @endif
    @if($request->motivation_note || $request->member_notes)
        <div class="sm:col-span-2">
            <dt class="text-zinc-500 dark:text-zinc-400">Member's note</dt>
            <dd class="text-zinc-800 dark:text-zinc-200 whitespace-pre-line">{{ $request->motivation_note ?: $request->member_notes }}</dd>
        </div>
    @endif
</dl>

@if($acknowledgements->isNotEmpty())
    <div class="mt-5">
        <div class="flex items-center justify-between">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Acknowledgements</p>
            <span @class([
                'rounded-full px-2 py-0.5 text-xs font-medium',
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $accepted->count() === $acknowledgements->count(),
                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' => $accepted->count() !== $acknowledgements->count(),
            ])>{{ $accepted->count() }} of {{ $acknowledgements->count() }} ticked</span>
        </div>
        <ul class="mt-2">
            @foreach($acknowledgements as $ack)
                <li class="flex gap-3 border-b border-zinc-50 py-2.5 last:border-b-0 dark:border-zinc-800">
                    @if($ack->accepted)
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7"/></svg>
                    @else
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                    @endif
                    <span class="text-[13px] leading-5 text-zinc-700 dark:text-zinc-300">
                        {{ $ack->clause_text }}
                        <span class="block text-xs text-zinc-400">{{ $ack->accepted_at?->format('d M Y, H:i') ?? 'Not accepted' }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
        @php $times = $accepted->pluck('accepted_at')->filter()->sort()->values(); @endphp
        @if($times->isNotEmpty())
            <p class="mt-2 border-t border-zinc-100 pt-2 text-xs text-zinc-400 dark:border-zinc-800">
                Ticked individually on {{ $times->first()->format('d M Y') }} between {{ $times->first()->format('H:i') }} and {{ $times->last()->format('H:i') }} · recorded with timestamp and IP
            </p>
        @endif
    </div>
@elseif($branch === \App\Models\EndorsementRequest::ENDORSEMENT_TYPE_SELF_DEFENCE)
    <p class="mt-5 text-sm text-amber-700 dark:text-amber-300">No acknowledgements were recorded for this request.</p>
@elseif($branch === \App\Models\EndorsementRequest::ENDORSEMENT_TYPE_OCCASIONAL)
    <p class="mt-5 text-xs text-zinc-500 dark:text-zinc-400">
        Section 15 occasional letters do not use a per-clause acknowledgement list &mdash; a single declaration (recorded at
        {{ optional($request->declaration_accepted_at)->format('d M Y H:i') ?? 'submission time' }}) is sufficient.
    </p>
@endif

@if($eligibility && ! empty($eligibility['errors']))
    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-800 dark:bg-amber-900/20">
        <p class="font-medium text-amber-800 dark:text-amber-200">Eligibility notes</p>
        <ul class="mt-1 list-disc pl-5 text-amber-700 dark:text-amber-300">
            @foreach($eligibility['errors'] as $error)
                <li>{{ is_array($error) ? ($error['message'] ?? '') : $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
