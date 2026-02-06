<?php

use App\Models\Membership;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public int $selectedYear;
    public int $selectedMonth;
    
    // Stats for the selected period
    public int $newMembers = 0;
    public int $renewals = 0;
    public int $totalBillable = 0;

    // Bulk selection
    public array $selected = [];
    public bool $selectAll = false;
    public string $bulkAction = '';
    
    public function mount(): void
    {
        $this->selectedYear = now()->year;
        $this->selectedMonth = now()->month;
        $this->loadStats();
    }
    
    public function updatedSelectedYear(): void
    {
        $this->resetPage();
        $this->loadStats();
        $this->resetSelection();
    }
    
    public function updatedSelectedMonth(): void
    {
        $this->resetPage();
        $this->loadStats();
        $this->resetSelection();
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            // Select all visible membership IDs on current page
            try {
                $this->selected = Membership::billable()
                    ->approvedInMonth($this->selectedYear, $this->selectedMonth)
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->toArray();
            } catch (\Exception $e) {
                $this->selected = [];
            }
        } else {
            $this->selected = [];
        }
    }

    protected function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->bulkAction = '';
    }

    public function executeBulkAction(): void
    {
        if (empty($this->selected)) {
            session()->flash('error', 'No items selected.');
            return;
        }

        $count = count($this->selected);
        $ids = array_map('intval', $this->selected);

        if ($this->bulkAction === 'remove_from_billing') {
            // Mark as import so they no longer count as billable
            Membership::whereIn('id', $ids)->update(['source' => 'import']);

            AuditLog::log(
                'billing_bulk_remove',
                null,
                ['membership_ids' => $ids],
                ['source' => 'import'],
                Auth::user()
            );

            session()->flash('success', "{$count} item(s) removed from billing (marked as import).");
        } elseif ($this->bulkAction === 'delete') {
            // Permanently delete memberships (for test data cleanup)
            $memberships = Membership::whereIn('id', $ids)->get();
            foreach ($memberships as $m) {
                // Delete related certificates
                try { $m->certificates()->delete(); } catch (\Exception $e) {}
                $m->forceDelete();
            }

            AuditLog::log(
                'billing_bulk_delete',
                null,
                ['membership_ids' => $ids, 'count' => $count],
                [],
                Auth::user()
            );

            session()->flash('success', "{$count} membership(s) permanently deleted.");
        } else {
            session()->flash('error', 'Please select an action.');
            return;
        }

        $this->resetSelection();
        $this->loadStats();
    }
    
    protected function loadStats(): void
    {
        try {
            $this->newMembers = Membership::billable()
                ->newInMonth($this->selectedYear, $this->selectedMonth)
                ->count();
            
            $this->renewals = Membership::billable()
                ->renewalsInMonth($this->selectedYear, $this->selectedMonth)
                ->count();
            
            $this->totalBillable = $this->newMembers + $this->renewals;
        } catch (\Exception $e) {
            // source column might not exist yet
            $this->newMembers = 0;
            $this->renewals = 0;
            $this->totalBillable = 0;
        }
    }
    
    public function with(): array
    {
        try {
            $memberships = Membership::billable()
                ->approvedInMonth($this->selectedYear, $this->selectedMonth)
                ->with(['user', 'type', 'previousMembership.type'])
                ->orderBy('approved_at', 'desc')
                ->paginate(20);
        } catch (\Exception $e) {
            $memberships = collect()->paginate(20);
        }
        
        // Generate year options (current year and 5 years back)
        $years = range(now()->year, now()->year - 5);
        
        // Month names
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = date('F', mktime(0, 0, 0, $i, 1));
        }
        
        // Get annual summary
        $annualSummary = $this->getAnnualSummary();
        
        return [
            'memberships' => $memberships,
            'years' => $years,
            'months' => $months,
            'annualSummary' => $annualSummary,
        ];
    }
    
    protected function getAnnualSummary(): array
    {
        $summary = [];
        
        for ($month = 1; $month <= 12; $month++) {
            try {
                $new = Membership::billable()
                    ->newInMonth($this->selectedYear, $month)
                    ->count();
                
                $renewals = Membership::billable()
                    ->renewalsInMonth($this->selectedYear, $month)
                    ->count();
                
                $summary[$month] = [
                    'new' => $new,
                    'renewals' => $renewals,
                    'total' => $new + $renewals,
                ];
            } catch (\Exception $e) {
                $summary[$month] = ['new' => 0, 'renewals' => 0, 'total' => 0];
            }
        }
        
        return $summary;
    }
    
    public function exportCsv(): void
    {
        $memberships = Membership::billable()
            ->approvedInMonth($this->selectedYear, $this->selectedMonth)
            ->with(['user', 'type', 'previousMembership.type'])
            ->orderBy('approved_at', 'desc')
            ->get();
        
        $monthName = date('F', mktime(0, 0, 0, $this->selectedMonth, 1));
        $filename = "billing-report-{$monthName}-{$this->selectedYear}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function () use ($memberships) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Date Approved',
                'Membership Number',
                'Member Name',
                'Email',
                'Membership Type',
                'Type (New/Renewal)',
                'Previous Membership',
                'Source',
            ]);
            
            foreach ($memberships as $membership) {
                fputcsv($file, [
                    $membership->approved_at?->format('Y-m-d H:i:s') ?? '',
                    $membership->membership_number,
                    $membership->user->name ?? '',
                    $membership->user->email ?? '',
                    $membership->type->name ?? '',
                    $membership->isRenewal() ? 'Renewal' : 'New',
                    $membership->previousMembership?->type?->name ?? 'N/A',
                    ucfirst($membership->source ?? 'web'),
                ]);
            }
            
            fclose($file);
        };
        
        // Stream the response
        $this->dispatch('download-csv', [
            'data' => $memberships->map(function ($m) {
                return [
                    $m->approved_at?->format('Y-m-d H:i:s') ?? '',
                    $m->membership_number,
                    $m->user->name ?? '',
                    $m->user->email ?? '',
                    $m->type->name ?? '',
                    $m->isRenewal() ? 'Renewal' : 'New',
                    $m->previousMembership?->type?->name ?? 'N/A',
                    ucfirst($m->source ?? 'web'),
                ];
            })->toArray(),
            'filename' => $filename,
            'headers' => ['Date Approved', 'Membership Number', 'Member Name', 'Email', 'Membership Type', 'Type (New/Renewal)', 'Previous Membership', 'Source'],
        ]);
    }
}; ?>

<div x-data="{
    downloadCsv(event) {
        const data = event.detail[0].data;
        const filename = event.detail[0].filename;
        const headers = event.detail[0].headers;
        
        let csv = headers.join(',') + '\n';
        data.forEach(row => {
            csv += row.map(cell => {
                if (cell && (cell.includes(',') || cell.includes('\"') || cell.includes('\n'))) {
                    return '\"' + cell.replace(/\"/g, '\"\"') + '\"';
                }
                return cell;
            }).join(',') + '\n';
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('href', url);
        a.setAttribute('download', filename);
        a.click();
        window.URL.revokeObjectURL(url);
    }
}" @download-csv.window="downloadCsv($event)">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Billing Reports</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Track new members and renewals for NRAPA invoicing.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">
            ← Back to Dashboard
        </a>
    </div>

    {{-- Period Selector --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Period:</label>
                <select wire:model.live="selectedMonth"
                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="selectedYear"
                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    @foreach($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1"></div>
            <button wire:click="exportCsv" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export CSV
            </button>
        </div>
    </div>

    {{-- Stats for Selected Period --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">New Members</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $newMembers }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">First-time memberships</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Renewals</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $renewals }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Membership renewals</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Billable</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $totalBillable }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">For NRAPA invoicing</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Annual Summary --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $selectedYear }} Annual Summary</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="text-left py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">Month</th>
                        <th class="text-right py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">New</th>
                        <th class="text-right py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">Renewals</th>
                        <th class="text-right py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $yearlyNew = 0; $yearlyRenewals = 0; $yearlyTotal = 0; @endphp
                    @foreach($annualSummary as $month => $data)
                        @php
                            $yearlyNew += $data['new'];
                            $yearlyRenewals += $data['renewals'];
                            $yearlyTotal += $data['total'];
                            $isCurrentSelection = $month == $selectedMonth;
                        @endphp
                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50 {{ $isCurrentSelection ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                            <td class="py-2 px-3 {{ $isCurrentSelection ? 'font-medium text-indigo-700 dark:text-indigo-300' : 'text-zinc-700 dark:text-zinc-300' }}">
                                {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                            </td>
                            <td class="py-2 px-3 text-right {{ $isCurrentSelection ? 'text-indigo-700 dark:text-indigo-300' : 'text-zinc-600 dark:text-zinc-400' }}">{{ $data['new'] }}</td>
                            <td class="py-2 px-3 text-right {{ $isCurrentSelection ? 'text-indigo-700 dark:text-indigo-300' : 'text-zinc-600 dark:text-zinc-400' }}">{{ $data['renewals'] }}</td>
                            <td class="py-2 px-3 text-right font-medium {{ $isCurrentSelection ? 'text-indigo-700 dark:text-indigo-300' : 'text-zinc-900 dark:text-white' }}">{{ $data['total'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-zinc-50 dark:bg-zinc-700/30 font-semibold">
                        <td class="py-3 px-3 text-zinc-900 dark:text-white">Total</td>
                        <td class="py-3 px-3 text-right text-emerald-600 dark:text-emerald-400">{{ $yearlyNew }}</td>
                        <td class="py-3 px-3 text-right text-blue-600 dark:text-blue-400">{{ $yearlyRenewals }}</td>
                        <td class="py-3 px-3 text-right text-indigo-600 dark:text-indigo-400">{{ $yearlyTotal }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bulk Action Bar --}}
    @if(count($selected) > 0)
    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-4 mb-4 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex items-center gap-2 text-sm font-medium text-indigo-800 dark:text-indigo-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ count($selected) }} item(s) selected
        </div>
        <div class="flex items-center gap-3 flex-1">
            <select wire:model="bulkAction" class="px-3 py-1.5 text-sm border border-indigo-300 dark:border-indigo-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white">
                <option value="">Choose action...</option>
                <option value="remove_from_billing">Remove from billing (mark as import)</option>
                <option value="delete">Permanently delete memberships</option>
            </select>
            <button
                wire:click="executeBulkAction"
                wire:confirm="{{ $bulkAction === 'delete' ? 'Are you sure? This will PERMANENTLY delete the selected memberships and their certificates. This cannot be undone.' : 'Remove selected items from billing? They will be marked as imported and excluded from billing counts.' }}"
                @disabled(empty($bulkAction))
                class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed
                    {{ $bulkAction === 'delete' ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Apply
            </button>
            <button wire:click="$set('selected', []); $set('selectAll', false)" class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                Clear selection
            </button>
        </div>
    </div>
    @endif

    {{-- Detailed List --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Billable Memberships - {{ $months[$selectedMonth] }} {{ $selectedYear }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAll"
                                class="w-4 h-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-700">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Member</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Membership #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($memberships as $membership)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 {{ in_array((string) $membership->id, $selected) ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : '' }}">
                            <td class="px-4 py-4">
                                <input type="checkbox" wire:model.live="selected" value="{{ $membership->id }}"
                                    class="w-4 h-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-700">
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $membership->approved_at?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $membership->user?->name ?? 'Deleted User' }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $membership->user?->email ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $membership->membership_number }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $membership->type?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($membership->isRenewal())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        Renewal
                                    </span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-1">
                                        from {{ $membership->previousMembership?->type?->name ?? 'N/A' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">
                                        New
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($membership->source === 'web')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                                        Web
                                    </span>
                                @elseif($membership->source === 'admin')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                        Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                        {{ ucfirst($membership->source ?? 'Unknown') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                No billable memberships for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($memberships->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $memberships->links() }}
            </div>
        @endif
    </div>

    {{-- Note about imports --}}
    <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">About Billing Tracking</p>
                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                    This report only includes <strong>billable</strong> memberships (web applications and admin-created). 
                    Initially imported members are excluded from billing counts. Only memberships with an <strong>approved date</strong> 
                    within the selected period are counted.
                </p>
            </div>
        </div>
    </div>
</div>
