<?php

use App\Models\ShootingActivity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $yearFilter = null;

    // SAPS activity letter (date-range PDF download)
    public bool $sapsLetterOpen = false;
    public string $sapsFrom = '';
    public string $sapsTo = '';

    public function mount(): void
    {
        // Check if user has dedicated status membership
        if (!auth()->user()->activeMembership?->type?->allows_dedicated_status) {
            session()->flash('error', 'Your membership type does not allow dedicated status activities.');
        }

        // Default the SAPS activity letter window to the last 24 months,
        // which matches the typical Section 16 application/renewal lookback.
        $this->sapsTo = now()->toDateString();
        $this->sapsFrom = now()->subYears(2)->toDateString();
    }

    /**
     * Stream a PDF "Activity Letter" for the chosen date range, suitable
     * for attaching to a SAPS firearm motivation. Lists only NRAPA-verified
     * (status = approved) activities so SAPS can rely on the contents.
     *
     * Implementation notes:
     *  - Renders to a temp file via Spatie + DomPDF (forced to match what
     *    PdfDocumentRenderer does in production), then streams via
     *    response()->download()->deleteFileAfterSend(). This is the
     *    bulletproof Livewire download pattern: streaming directly from
     *    Spatie's Pdf::download() inside a Livewire action is fragile
     *    because the response payload has to round-trip through the
     *    Livewire XHR protocol.
     */
    public function downloadSapsLetter()
    {
        $validated = $this->validate([
            'sapsFrom' => ['required', 'date'],
            'sapsTo' => ['required', 'date', 'after_or_equal:sapsFrom'],
        ], [
            'sapsTo.after_or_equal' => 'The "To" date must be on or after the "From" date.',
        ]);

        $user = auth()->user();
        $from = \Carbon\Carbon::parse($validated['sapsFrom'])->startOfDay();
        $to = \Carbon\Carbon::parse($validated['sapsTo'])->endOfDay();

        $activities = ShootingActivity::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('activity_date', [$from, $to])
            ->with([
                'activityType',
                'firearmType',
                'userFirearm.firearmCalibre',
            ])
            ->orderBy('activity_date')
            ->get();

        $safeName = preg_replace('/[^A-Za-z0-9]+/', '-', trim($user->getIdName()));
        $filename = sprintf(
            'NRAPA-Activity-Letter-%s-%s-to-%s.pdf',
            $safeName !== '' ? $safeName : 'Member',
            $from->format('Ymd'),
            $to->format('Ymd'),
        );

        $tempDir = storage_path('app/tmp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'activity-letter-' . uniqid() . '.pdf';

        try {
            // Render via Gotenberg (Chrome) — same rendering pipeline as
            // certificates, so the activity letter looks identical in style.
            // We POST the rendered HTML to the gotenberg sidecar container
            // and write the binary PDF response straight to a local temp
            // file (no R2/S3 round-trip — these PDFs are one-off downloads).
            $html = view('documents.letters.saps-activity-log', [
                'user' => $user,
                'activities' => $activities,
                'from' => $from,
                'to' => $to,
            ])->render();

            $gotenbergUrl = rtrim(env('GOTENBERG_URL', 'http://gotenberg:3000'), '/');

            $response = Http::timeout(30)
                ->asMultipart()
                ->post($gotenbergUrl . '/forms/chromium/convert/html', [
                    ['name' => 'files', 'contents' => $html, 'filename' => 'index.html'],
                    ['name' => 'printBackground', 'contents' => 'true'],
                    ['name' => 'marginTop', 'contents' => '0'],
                    ['name' => 'marginBottom', 'contents' => '0'],
                    ['name' => 'marginLeft', 'contents' => '0'],
                    ['name' => 'marginRight', 'contents' => '0'],
                    ['name' => 'preferCssPageSize', 'contents' => 'true'],
                ]);

            if (! $response->successful() || strlen($response->body()) < 100) {
                throw new \RuntimeException(
                    'Gotenberg returned status ' . $response->status() .
                    ' (' . strlen($response->body()) . ' bytes): ' .
                    substr($response->body(), 0, 200)
                );
            }

            file_put_contents($tempPath, $response->body());

            Log::info('Activity letter generated via Gotenberg', [
                'user_id' => $user->id,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'activity_count' => $activities->count(),
                'bytes' => @filesize($tempPath) ?: 0,
            ]);

            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error('Activity letter generation failed', [
                'user_id' => $user->id,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'activity_count' => $activities->count(),
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
            session()->flash(
                'error',
                'Could not generate the activity letter (' . class_basename($e) . '). The PDF rendering service may be unavailable — please contact admin.'
            );
        }
    }

    public function with(): array
    {
        $user = auth()->user();
        $activityPeriod = ShootingActivity::getActivityPeriod($user);
        $requiredCount = 2;

        $activities = ShootingActivity::where('user_id', $user->id)
            ->with(['activityType', 'tags', 'firearmType', 'userFirearm', 'userFirearm.firearmCalibre', 'country', 'province', 'evidenceDocument', 'additionalDocument'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn($q) => $q->where(function($query) {
                $query->where('location', 'like', '%' . $this->search . '%')
                    ->orWhere('closest_town_city', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            }))
            ->orderBy('activity_date', 'desc')
            ->paginate(12);

        // Use the canonical compliance summary so the member sees:
        //   - Compliance for THIS YEAR = activities approved LAST year (2 required)
        //   - Banking for NEXT YEAR = activities approved THIS year
        $compliance = ShootingActivity::complianceSummary($user, $requiredCount);
        $approvedCount = $compliance['qualifying_year']['total']; // what proves compliance for THIS year
        $bankingCount = $compliance['banking_year']['total'];     // what's being built for NEXT year

        $isPaidUp = $user->activeMembership && (!$user->activeMembership->expires_at || $user->activeMembership->expires_at->isFuture());
        $complianceMet = $compliance['is_compliant_now'];
        $isExempt = $compliance['is_join_year_exempt'] ?? false;

        $currentYear = now()->year;
        $historyStartYear = $currentYear - 5;
        $historyStart = \Carbon\Carbon::create($historyStartYear, 1, 1)->startOfDay();
        $historyEnd = \Carbon\Carbon::create($currentYear - 1, 10, 31)->endOfDay();

        $yearCounts = ShootingActivity::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('activity_date', [$historyStart, $historyEnd])
            ->selectRaw('YEAR(activity_date) as yr, count(*) as cnt')
            ->groupByRaw('YEAR(activity_date)')
            ->pluck('cnt', 'yr');

        $complianceHistory = [];
        for ($year = $currentYear - 1; $year >= $historyStartYear; $year--) {
            $yearApproved = (int) ($yearCounts[$year] ?? 0);
            $complianceHistory[] = [
                'year' => $year,
                'label' => "OCT {$year}",
                'approved' => $yearApproved,
                'required' => $requiredCount,
                'met' => $yearApproved >= $requiredCount,
            ];
        }

        return [
            'activities' => $activities,
            'activityPeriod' => $activityPeriod,
            'approvedCount' => $approvedCount,
            'bankingCount' => $bankingCount,
            'requiredCount' => $requiredCount,
            'complianceMet' => $complianceMet,
            'isExempt' => $isExempt,
            'isPaidUp' => $isPaidUp,
            'complianceHistory' => $complianceHistory,
            'compliance' => $compliance,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">My Activities</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Track and manage your shooting activities for dedicated status</p>
            </div>
            <a href="{{ route('activities.submit') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Submit Activity
            </a>
        </div>
    </x-slot>

    {{-- Activity Letter — date-range PDF download for firearm motivations.
         Lives in the component body (not the header slot) because wire:click
         only binds inside the component's DOM root; slot contents are hoisted
         into the parent layout and lose component scope. --}}
    <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30">
        <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-500/15 text-blue-600 dark:text-blue-400">
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-semibold text-blue-900 dark:text-blue-100">Activity Letter</h2>
                    <p class="text-xs text-blue-700/80 dark:text-blue-300/80">
                        Download a formatted PDF of your <strong>NRAPA-verified</strong> activities for the dates you choose &mdash; suitable for attaching to a SAPS firearm motivation.
                    </p>
                </div>
            </div>
            <button type="button" wire:click="$toggle('sapsLetterOpen')" class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    @if ($sapsLetterOpen)
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    @endif
                </svg>
                {{ $sapsLetterOpen ? 'Close' : 'Generate Letter' }}
            </button>
        </div>

        @if ($sapsLetterOpen)
            <div class="border-t border-blue-200 px-5 py-4 dark:border-blue-800">
                <form wire:submit="downloadSapsLetter" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="sapsFrom" class="block text-xs font-medium text-blue-900 dark:text-blue-100 mb-1">From</label>
                        <input id="sapsFrom" type="date" wire:model="sapsFrom" class="w-full rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:ring-blue-500 dark:border-blue-800 dark:bg-zinc-900 dark:text-white"/>
                        @error('sapsFrom') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sapsTo" class="block text-xs font-medium text-blue-900 dark:text-blue-100 mb-1">To</label>
                        <input id="sapsTo" type="date" wire:model="sapsTo" class="w-full rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:ring-blue-500 dark:border-blue-800 dark:bg-zinc-900 dark:text-white"/>
                        @error('sapsTo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit" wire:loading.attr="disabled" wire:target="downloadSapsLetter" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50 disabled:cursor-wait transition-colors">
                            <svg class="size-4" wire:loading.remove wire:target="downloadSapsLetter" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <svg class="size-4 animate-spin" wire:loading wire:target="downloadSapsLetter" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            <span wire:loading.remove wire:target="downloadSapsLetter">Download PDF</span>
                            <span wire:loading wire:target="downloadSapsLetter">Generating…</span>
                        </button>
                    </div>
                </form>
                @if (session('error'))
                    <p class="mt-3 text-sm text-red-600">{{ session('error') }}</p>
                @endif
            </div>
        @endif
    </div>

    <!-- Compliance Summary + History -->
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Compliance Card -->
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs uppercase tracking-wider font-semibold text-zinc-500">Compliance for {{ now()->year }}</h2>
                @if($isExempt)
                    <span class="text-xs px-2 py-1 rounded-full font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                        Exempt
                    </span>
                @else
                    <span class="text-xs px-2 py-1 rounded-full font-medium {{ $complianceMet ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                        {{ $complianceMet ? 'Compliant' : 'Incomplete' }}
                    </span>
                @endif
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center">
                    @if($isExempt)
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">Exempt</p>
                        <p class="text-xs uppercase tracking-wider text-zinc-500 mt-1">First year</p>
                        <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">no activities required for {{ now()->year }}</p>
                    @else
                        <p class="text-2xl font-bold {{ $complianceMet ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $approvedCount }} / {{ $requiredCount }}
                        </p>
                        <p class="text-xs uppercase tracking-wider text-zinc-500 mt-1">{{ now()->year - 1 }} activities</p>
                        <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">qualify you for {{ now()->year }}</p>
                    @endif
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold {{ $bankingCount >= $requiredCount ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                        {{ $bankingCount }} / {{ $requiredCount }}
                    </p>
                    <p class="text-xs uppercase tracking-wider text-zinc-500 mt-1">{{ now()->year }} activities</p>
                    <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">banking for {{ now()->year + 1 }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold {{ $isPaidUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $isPaidUp ? 'YES' : 'NO' }}
                    </p>
                    <p class="text-xs uppercase tracking-wider text-zinc-500 mt-1">Paid-up</p>
                </div>
            </div>
            <p class="text-xs text-zinc-400 mt-3 text-center">Current window: {{ $activityPeriod['label'] }}</p>
        </div>

        <!-- Compliance History -->
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <h2 class="text-xs uppercase tracking-wider font-semibold text-zinc-500 mb-4">Compliance History</h2>
            <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-none">
                @foreach($complianceHistory as $history)
                    <div class="flex-shrink-0 text-center min-w-[70px]">
                        <p class="text-lg font-bold {{ $history['met'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $history['approved'] }}/{{ $history['required'] }}
                        </p>
                        <p class="text-xs text-zinc-500 mt-1">{{ $history['label'] }}</p>
                        <p class="text-xs uppercase tracking-wider text-zinc-400">Total</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 gap-4">
            <div class="relative flex-1 max-w-xs">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search activities..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-4 py-2 pl-10 text-sm text-zinc-900 dark:text-white placeholder-zinc-500 focus:border-emerald-500 focus:ring-emerald-500">
                <svg class="absolute left-3 top-2.5 size-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <select wire:model.live="statusFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-4 py-2 text-sm text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>

    <!-- Activities Grid -->
    @if($activities->isEmpty())
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-12 text-center">
            <svg class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">No activities submitted yet</p>
            <a href="{{ route('activities.submit') }}" wire:navigate class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-nrapa-blue hover:text-nrapa-blue-dark transition-colors">
                Submit your first activity
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($activities as $activity)
                <a href="{{ route('activities.show', $activity) }}" wire:navigate
                   class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition overflow-hidden block">
                    <div class="p-5">
                        <!-- Header: status + date -->
                        <div class="flex items-center justify-between mb-3">
                            @if($activity->status === 'approved')
                                <span class="text-xs px-2 py-1 rounded-full font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Approved</span>
                            @elseif($activity->status === 'pending')
                                <span class="text-xs px-2 py-1 rounded-full font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Pending</span>
                            @else
                                <span class="text-xs px-2 py-1 rounded-full font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Rejected</span>
                            @endif
                            <span class="text-xs text-zinc-400">Applied: {{ $activity->created_at->format('M d, Y') }}</span>
                        </div>

                        <!-- Category -->
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">
                            {{ $activity->track === 'hunting' ? 'Hunting Related' : 'Sport-Shooting Related' }}
                        </h3>

                        <!-- Details -->
                        <dl class="space-y-2 mb-4">
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-zinc-500">Type of Activity</dt>
                                <dd class="text-sm text-zinc-800 dark:text-zinc-200">{{ $activity->activityType?->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-zinc-500">Date</dt>
                                <dd class="text-sm text-zinc-800 dark:text-zinc-200">{{ $activity->activity_date->format('M d, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-zinc-500">Location</dt>
                                <dd class="text-sm text-zinc-800 dark:text-zinc-200">{{ $activity->full_location ?: 'N/A' }}</dd>
                            </div>
                            @if($activity->userFirearm)
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Firearm</dt>
                                    <dd class="text-sm text-zinc-800 dark:text-zinc-200">
                                        {{ $activity->userFirearm->make_display ?? $activity->userFirearm->make }}{{ $activity->userFirearm->calibre_display ? ', ' . $activity->userFirearm->calibre_display : '' }}
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        <!-- Document thumbnails -->
                        @if($activity->evidenceDocument?->file_path || $activity->additionalDocument?->file_path)
                            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                <p class="text-xs uppercase tracking-wider text-zinc-500 mb-2">Confirmation Document</p>
                                <div class="flex gap-2">
                                    @if($activity->evidenceDocument?->file_path)
                                        @if(Str::endsWith(strtolower($activity->evidenceDocument->file_path), ['.jpg', '.jpeg', '.png', '.webp']))
                                            <img src="{{ Storage::url($activity->evidenceDocument->file_path) }}" alt="Evidence" class="h-12 w-12 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700">
                                        @else
                                            <div class="h-12 w-12 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center bg-zinc-50 dark:bg-zinc-800">
                                                <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                        @endif
                                    @endif
                                    @if($activity->additionalDocument?->file_path)
                                        @if(Str::endsWith(strtolower($activity->additionalDocument->file_path), ['.jpg', '.jpeg', '.png', '.webp']))
                                            <img src="{{ Storage::url($activity->additionalDocument->file_path) }}" alt="Additional" class="h-12 w-12 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700">
                                        @else
                                            <div class="h-12 w-12 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center bg-zinc-50 dark:bg-zinc-800">
                                                <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $activities->links() }}
        </div>
    @endif
</div>
