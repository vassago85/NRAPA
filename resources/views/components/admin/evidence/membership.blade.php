{{-- Evidence for a Membership approval. Data: membership, checklist --}}
@php $user = $membership->user; @endphp

<dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
    <div class="flex justify-between gap-4 sm:block">
        <dt class="text-zinc-500 dark:text-zinc-400">Membership type</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">{{ $membership->type?->name ?? 'Unknown' }}</dd>
    </div>
    @if($membership->status === 'pending_change')
        <div class="flex justify-between gap-4 sm:block">
            <dt class="text-zinc-500 dark:text-zinc-400">Changing from</dt>
            <dd class="font-medium text-zinc-900 dark:text-white">{{ $membership->previousMembership?->type?->name ?? 'Unknown' }}</dd>
        </div>
        <div class="flex justify-between gap-4 sm:block">
            <dt class="text-zinc-500 dark:text-zinc-400">Change fee</dt>
            <dd class="font-medium text-zinc-900 dark:text-white">R{{ number_format($membership->defaultChangeAmount(), 2) }}</dd>
        </div>
    @else
        <div class="flex justify-between gap-4 sm:block">
            <dt class="text-zinc-500 dark:text-zinc-400">Application</dt>
            <dd class="font-medium text-zinc-900 dark:text-white">{{ $membership->isTransfer() ? 'Transfer' : ($membership->isRenewal() ? 'Renewal' : 'New') }} · {{ ucfirst((string) $membership->source) }}</dd>
        </div>
        <div class="flex justify-between gap-4 sm:block">
            <dt class="text-zinc-500 dark:text-zinc-400">Payment</dt>
            <dd class="font-medium text-zinc-900 dark:text-white">
                @if(! $membership->isBillable())
                    Not billable
                @elseif($membership->payment_confirmed_at)
                    Confirmed {{ $membership->payment_confirmed_at->format('d M Y') }}
                @else
                    Not confirmed
                @endif
            </dd>
        </div>
    @endif
    <div class="flex justify-between gap-4 sm:block">
        <dt class="text-zinc-500 dark:text-zinc-400">Applied</dt>
        <dd class="font-medium text-zinc-900 dark:text-white">{{ $membership->applied_at?->format('d M Y, H:i') ?? '—' }}</dd>
    </div>
    @if($membership->affiliatedClub)
        <div class="flex justify-between gap-4 sm:block">
            <dt class="text-zinc-500 dark:text-zinc-400">Affiliated club</dt>
            <dd class="font-medium text-zinc-900 dark:text-white">{{ $membership->affiliatedClub->name }}</dd>
        </div>
    @endif
    @if($user)
        <div class="flex justify-between gap-4 sm:block">
            <dt class="text-zinc-500 dark:text-zinc-400">ID number</dt>
            <dd class="font-medium text-zinc-900 dark:text-white">{{ $user->id_number ?: '—' }}</dd>
        </div>
        <div class="flex justify-between gap-4 sm:block">
            <dt class="text-zinc-500 dark:text-zinc-400">Contact</dt>
            <dd class="font-medium text-zinc-900 dark:text-white">{{ $user->email }}@if($user->phone) · {{ $user->phone }}@endif</dd>
        </div>
    @endif
</dl>

@if($membership->notes)
    <div class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 whitespace-pre-line">{{ $membership->notes }}</div>
@endif

<div class="mt-5">
    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Required documents</p>
    @if($checklist->isEmpty())
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">This membership type requires no documents.</p>
    @else
        <ul class="mt-2 divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($checklist as $row)
                <li class="flex items-center justify-between py-2 text-sm">
                    <span class="text-zinc-800 dark:text-zinc-200">{{ $row['name'] }}</span>
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs font-medium',
                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $row['status'] === 'verified',
                        'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' => $row['status'] === 'pending',
                        'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200' => ! $row['present'],
                    ])>{{ $row['status'] === 'verified' ? 'Verified' : ($row['status'] === 'pending' ? 'Awaiting review' : 'Missing') }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
