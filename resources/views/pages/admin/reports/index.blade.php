<?php

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new #[Title('Membership Reports - Admin')] class extends Component {
    #[Url]
    public string $statusFilter = 'active';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Computed]
    public function summaryStats(): array
    {
        $totalWithMembership = User::whereHas('memberships')->count();

        $activeCount = Membership::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();

        $lifetimeActiveCount = Membership::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereHas('type', fn ($q) => $q->where('duration_type', 'lifetime'))
            ->count();

        $expiredCount = Membership::where(function ($q) {
            $q->where('status', 'expired')
              ->orWhere(function ($sq) {
                  $sq->where('status', 'active')
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
              });
        })->count();

        $pendingCount = Membership::where('status', 'applied')->count();

        $lifetimePercent = $activeCount > 0
            ? round(($lifetimeActiveCount / $activeCount) * 100, 1)
            : 0;

        $expectedRevenue = Membership::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereHas('type', fn ($q) => $q->where('requires_renewal', true))
            ->join('membership_types', 'memberships.membership_type_id', '=', 'membership_types.id')
            ->sum('membership_types.renewal_price');

        return [
            'total' => $totalWithMembership,
            'active' => $activeCount,
            'expired' => $expiredCount,
            'pending' => $pendingCount,
            'lifetime' => $lifetimeActiveCount,
            'lifetime_percent' => $lifetimePercent,
            'expected_revenue' => (float) $expectedRevenue,
        ];
    }

    #[Computed]
    public function typeBreakdown(): array
    {
        $types = MembershipType::orderBy('sort_order')->orderBy('name')->get();

        $baseQuery = fn () => Membership::query()
            ->when($this->dateFrom, fn ($q) => $q->where('activated_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->where('activated_at', '<=', $this->dateTo . ' 23:59:59'));

        $activeCounts = (clone $baseQuery)()
            ->selectRaw('membership_type_id, count(*) as total')
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->groupBy('membership_type_id')
            ->pluck('total', 'membership_type_id');

        $expiredCounts = (clone $baseQuery)()
            ->selectRaw('membership_type_id, count(*) as total')
            ->where(function ($q) {
                $q->where('status', 'expired')
                  ->orWhere(function ($sq) {
                      $sq->where('status', 'active')
                         ->whereNotNull('expires_at')
                         ->where('expires_at', '<=', now());
                  });
            })
            ->groupBy('membership_type_id')
            ->pluck('total', 'membership_type_id');

        $pendingCounts = (clone $baseQuery)()
            ->selectRaw('membership_type_id, count(*) as total')
            ->where('status', 'applied')
            ->groupBy('membership_type_id')
            ->pluck('total', 'membership_type_id');

        $totalActive = $activeCounts->sum();
        $rows = [];

        foreach ($types as $type) {
            $active = $activeCounts[$type->id] ?? 0;
            $expired = $expiredCounts[$type->id] ?? 0;
            $pending = $pendingCounts[$type->id] ?? 0;
            $total = $active + $expired + $pending;

            if ($this->statusFilter === 'active' && $active === 0) continue;
            if ($this->statusFilter === 'expired' && $expired === 0) continue;
            if ($this->statusFilter === 'pending' && $pending === 0) continue;

            $renewalPrice = (float) ($type->renewal_price ?? 0);
            $initialPrice = (float) ($type->initial_price ?? 0);
            $upgradePrice = (float) ($type->upgrade_price ?? 0);
            $signupFee = $initialPrice > 0 ? $initialPrice : $upgradePrice;
            $requiresRenewal = (bool) $type->requires_renewal;
            $expectedRevenue = $requiresRenewal ? $active * $renewalPrice : 0;

            $rows[] = [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'dedicated_type' => $type->dedicated_type,
                'duration_type' => $type->duration_type,
                'requires_renewal' => $requiresRenewal,
                'active' => $active,
                'expired' => $expired,
                'pending' => $pending,
                'total' => $total,
                'active_percent' => $totalActive > 0 ? round(($active / $totalActive) * 100, 1) : 0,
                'renewal_price' => $renewalPrice,
                'signup_fee' => $signupFee,
                'expected_revenue' => $expectedRevenue,
            ];
        }

        return $rows;
    }

    #[Computed]
    public function sourceBreakdown(): array
    {
        return Membership::query()
            ->selectRaw("COALESCE(source, 'unknown') as source, count(*) as total")
            ->when($this->dateFrom, fn ($q) => $q->where('activated_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->where('activated_at', '<=', $this->dateTo . ' 23:59:59'))
            ->groupBy('source')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'source' => match ($row->source) {
                    'web' => 'Self-Registered',
                    'import' => 'Imported',
                    'admin' => 'Admin-Created',
                    'renewal' => 'Renewal',
                    default => ucfirst($row->source),
                },
                'raw' => $row->source,
                'total' => $row->total,
            ])
            ->toArray();
    }

    public function resetFilters(): void
    {
        $this->statusFilter = 'active';
        $this->dateFrom = '';
        $this->dateTo = '';
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = $this->typeBreakdown;
        $filename = 'membership-report-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Membership Type', 'Dedicated Type', 'Duration', 'Active', 'Expired', 'Pending', 'Total', '% of Active', 'Sign-up Price', 'Renewal Price', 'Est. Annual Revenue']);

            $totalRevenue = 0;
            foreach ($rows as $row) {
                $totalRevenue += $row['expected_revenue'];
                fputcsv($handle, [
                    $row['name'],
                    $row['dedicated_type'] ? ucfirst($row['dedicated_type']) : 'None',
                    ucfirst($row['duration_type'] ?? 'Annual'),
                    (string) $row['active'],
                    (string) $row['expired'],
                    (string) $row['pending'],
                    (string) $row['total'],
                    $row['active_percent'] . '%',
                    $row['signup_fee'] ? 'R' . number_format($row['signup_fee'], 2) : 'N/A',
                    $row['requires_renewal'] ? 'R' . number_format($row['renewal_price'], 2) : 'N/A',
                    $row['requires_renewal'] ? 'R' . number_format($row['expected_revenue'], 2) : 'N/A',
                ]);
            }
            fputcsv($handle, ['TOTAL', '', '', '', '', '', '', '', '', '', 'R' . number_format($totalRevenue, 2)]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Membership Reports</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Breakdown of members by type, status, and source</p>
            </div>
            <button wire:click="exportCsv" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </button>
        </div>
    </x-slot>

    {{-- Summary Cards --}}
    @php $stats = $this->summaryStats; @endphp
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Members</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Active</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-orange-600 dark:text-orange-400">Expired</p>
            <p class="mt-2 text-3xl font-bold text-orange-700 dark:text-orange-300">{{ number_format($stats['expired']) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-amber-600 dark:text-amber-400">Pending</p>
            <p class="mt-2 text-3xl font-bold text-amber-700 dark:text-amber-300">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-blue-600 dark:text-blue-400">Lifetime Members</p>
            <p class="mt-2 text-3xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($stats['lifetime']) }}</p>
        </div>
        <div class="rounded-2xl border-2 {{ $stats['lifetime_percent'] > 30 ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/20' : ($stats['lifetime_percent'] > 15 ? 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20' : 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/20') }} p-5">
            <p class="text-xs font-medium uppercase tracking-wider {{ $stats['lifetime_percent'] > 30 ? 'text-red-600 dark:text-red-400' : ($stats['lifetime_percent'] > 15 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                Lifetime %
            </p>
            <p class="mt-2 text-3xl font-bold {{ $stats['lifetime_percent'] > 30 ? 'text-red-700 dark:text-red-300' : ($stats['lifetime_percent'] > 15 ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300') }}">
                {{ $stats['lifetime_percent'] }}%
            </p>
            <p class="mt-1 text-xs {{ $stats['lifetime_percent'] > 30 ? 'text-red-600 dark:text-red-400' : ($stats['lifetime_percent'] > 15 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                of active members
            </p>
        </div>
        <div class="rounded-2xl border-2 border-emerald-300 bg-emerald-50 p-5 dark:border-emerald-700 dark:bg-emerald-900/20">
            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Expected Annual Revenue</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">R{{ number_format($stats['expected_revenue'], 0) }}</p>
            <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">if all active renew</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-end gap-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Status</label>
            <select wire:model.live="statusFilter" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                <option value="all">All</option>
                <option value="active">Active Only</option>
                <option value="expired">Expired Only</option>
                <option value="pending">Pending Only</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Activated From</label>
            <input type="date" wire:model.live="dateFrom" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
        </div>
        <div>
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Activated To</label>
            <input type="date" wire:model.live="dateTo" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
        </div>
        <button wire:click="resetFilters" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 transition-colors">
            Reset
        </button>
    </div>

    {{-- Breakdown by Type --}}
    <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-800">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Breakdown by Membership Type</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Category</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Active</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-orange-600 dark:text-orange-400">Expired</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-amber-600 dark:text-amber-400">Pending</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">% of Active</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Sign-up</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Renewal</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Est. Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @php
                        $rows = $this->typeBreakdown;
                        $totals = ['active' => 0, 'expired' => 0, 'pending' => 0, 'total' => 0, 'revenue' => 0];
                    @endphp
                    @forelse($rows as $row)
                    @php
                        $totals['active'] += $row['active'];
                        $totals['expired'] += $row['expired'];
                        $totals['pending'] += $row['pending'];
                        $totals['total'] += $row['total'];
                        $totals['revenue'] += $row['expected_revenue'];
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $row['name'] }}
                            @if($row['duration_type'] === 'lifetime')
                                <span class="ml-1.5 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Lifetime</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($row['dedicated_type'])
                                {{ ucfirst($row['dedicated_type']) }}
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">Standard</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format($row['active']) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-orange-600 dark:text-orange-400">{{ number_format($row['expired']) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-amber-600 dark:text-amber-400">{{ number_format($row['pending']) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium text-zinc-900 dark:text-white">{{ number_format($row['total']) }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                    <div class="h-full rounded-full {{ $row['duration_type'] === 'lifetime' ? 'bg-blue-500' : 'bg-emerald-500' }}" style="width: {{ min($row['active_percent'], 100) }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 w-12 text-right">{{ $row['active_percent'] }}%</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-zinc-600 dark:text-zinc-400">
                            @if($row['signup_fee']) R{{ number_format($row['signup_fee'], 0) }} @else <span class="text-zinc-400 dark:text-zinc-500">—</span> @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-zinc-600 dark:text-zinc-400">
                            @if($row['requires_renewal'])
                                @if($row['renewal_price']) R{{ number_format($row['renewal_price'], 0) }} @else <span class="text-zinc-400 dark:text-zinc-500">R0</span> @endif
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium {{ $row['expected_revenue'] > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-zinc-400 dark:text-zinc-500' }}">
                            @if($row['requires_renewal'])
                                R{{ number_format($row['expected_revenue'], 0) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No memberships match the current filters.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($rows) > 0)
                <tfoot class="border-t-2 border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900/50">
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-zinc-900 dark:text-white">TOTALS</td>
                        <td></td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($totals['active']) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-bold text-orange-600 dark:text-orange-400">{{ number_format($totals['expired']) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-bold text-amber-600 dark:text-amber-400">{{ number_format($totals['pending']) }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-bold text-zinc-900 dark:text-white">{{ number_format($totals['total']) }}</td>
                        <td class="px-6 py-4 text-right text-xs font-bold text-zinc-600 dark:text-zinc-400">100%</td>
                        <td></td>
                        <td></td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-bold text-emerald-700 dark:text-emerald-300">R{{ number_format($totals['revenue'], 0) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Source Breakdown --}}
    <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-800">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Membership Source</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">How members joined the platform</p>
        </div>
        <div class="p-6">
            @php
                $sources = $this->sourceBreakdown;
                $sourceTotal = collect($sources)->sum('total');
            @endphp
            @if(count($sources) > 0)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($sources as $source)
                @php
                    $pct = $sourceTotal > 0 ? round(($source['total'] / $sourceTotal) * 100, 1) : 0;
                    $colors = match($source['raw']) {
                        'import' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-700 dark:text-purple-300', 'light' => 'bg-purple-100 dark:bg-purple-900/30', 'border' => 'border-purple-200 dark:border-purple-800'],
                        'web' => ['bg' => 'bg-emerald-500', 'text' => 'text-emerald-700 dark:text-emerald-300', 'light' => 'bg-emerald-100 dark:bg-emerald-900/30', 'border' => 'border-emerald-200 dark:border-emerald-800'],
                        'admin' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-700 dark:text-blue-300', 'light' => 'bg-blue-100 dark:bg-blue-900/30', 'border' => 'border-blue-200 dark:border-blue-800'],
                        'renewal' => ['bg' => 'bg-amber-500', 'text' => 'text-amber-700 dark:text-amber-300', 'light' => 'bg-amber-100 dark:bg-amber-900/30', 'border' => 'border-amber-200 dark:border-amber-800'],
                        default => ['bg' => 'bg-zinc-500', 'text' => 'text-zinc-700 dark:text-zinc-300', 'light' => 'bg-zinc-100 dark:bg-zinc-900/30', 'border' => 'border-zinc-200 dark:border-zinc-800'],
                    };
                @endphp
                <div class="rounded-lg border {{ $colors['border'] }} {{ $colors['light'] }} p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium {{ $colors['text'] }}">{{ $source['source'] }}</p>
                        <span class="text-xs font-semibold {{ $colors['text'] }}">{{ $pct }}%</span>
                    </div>
                    <p class="mt-2 text-2xl font-bold {{ $colors['text'] }}">{{ number_format($source['total']) }}</p>
                    <div class="mt-2 h-1.5 w-full rounded-full bg-white/50 dark:bg-zinc-800/50 overflow-hidden">
                        <div class="h-full rounded-full {{ $colors['bg'] }}" style="width: {{ min($pct, 100) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-center text-sm text-zinc-500 dark:text-zinc-400 py-4">No membership data available.</p>
            @endif
        </div>
    </div>
</div>
