<?php

use App\Models\EmailLog;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?string $selectedLog = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = EmailLog::with('user')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('to_email', 'like', "%{$this->search}%")
                        ->orWhere('subject', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($q) {
                            $q->where('name', 'like', "%{$this->search}%")
                                ->orWhere('id_number', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest();

        return [
            'logs' => $query->paginate(20),
            'totalSent' => EmailLog::sent()->count(),
            'totalFailed' => EmailLog::failed()->count(),
        ];
    }

    public function viewLog(string $uuid): void
    {
        $this->selectedLog = $uuid;
    }

    public function closeLog(): void
    {
        $this->selectedLog = null;
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Email Logs</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">View system email delivery history</p>
    </x-slot>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalSent) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Emails Sent</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalFailed) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Failed</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalSent + $totalFailed) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Emails</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <div class="relative flex-1 max-w-md">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by email, subject, name, or ID number..."
                class="w-full px-4 py-2 pl-10 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <svg class="absolute left-3 top-2.5 w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <select wire:model.live="statusFilter"
            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Status</option>
            <option value="sent">Sent</option>
            <option value="failed">Failed</option>
        </select>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Recipient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $log->sent_at?->format('d M Y H:i') ?? $log->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $log->to_name ?? 'Unknown' }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $log->to_email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-zinc-900 dark:text-white max-w-xs truncate">{{ $log->subject }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ class_basename($log->mailable_class) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->status === 'sent')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">
                                        Sent
                                    </span>
                                @elseif($log->status === 'failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                        Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button wire:click="viewLog('{{ $log->uuid }}')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                No email logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- View Log Modal -->
    @if($selectedLog)
        @php $log = \App\Models\EmailLog::where('uuid', $selectedLog)->first(); @endphp
        @if($log)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div wire:click="closeLog" class="fixed inset-0 bg-black/50"></div>
                    <div wire:key="email-detail-{{ $selectedLog }}" class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-4xl p-6 max-h-[90vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Email Details</h2>
                            <button wire:click="closeLog" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div class="col-span-2">
                                    <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">To</label>
                                    <p class="text-zinc-900 dark:text-white">{{ $log->to_name ?? 'Unknown' }} &lt;{{ $log->to_email }}&gt;</p>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">From</label>
                                    <p class="text-zinc-900 dark:text-white">{{ $log->from_name ?? 'Unknown' }} &lt;{{ $log->from_email }}&gt;</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Sent At</label>
                                    <p class="text-zinc-900 dark:text-white">{{ $log->sent_at?->format('d M Y H:i:s') ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</label>
                                    <p class="text-zinc-900 dark:text-white">{{ ucfirst($log->status) }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Subject</label>
                                    <p class="text-zinc-900 dark:text-white">{{ $log->subject }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Type</label>
                                    <p class="text-zinc-900 dark:text-white">{{ class_basename($log->mailable_class) }}</p>
                                </div>
                            </div>

                            @if($log->error_message)
                                <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
                                    <span class="font-medium">Error:</span> {{ $log->error_message }}
                                </div>
                            @endif

                            @if($log->body)
                                <div>
                                    <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1 block">Email Content</label>
                                    @if(str_contains($log->body, '<'))
                                        <div wire:ignore>
                                            <iframe
                                                srcdoc="{!! htmlspecialchars($log->body, ENT_QUOTES, 'UTF-8') !!}"
                                                sandbox="allow-same-origin"
                                                class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white"
                                                style="min-height: 500px;"
                                                onload="try{this.style.height=(this.contentDocument.body.scrollHeight+40)+'px'}catch(e){}"
                                            ></iframe>
                                        </div>
                                    @else
                                        <div class="mt-1 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $log->body }}</div>
                                    @endif
                                </div>
                            @else
                                <div class="p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg text-sm text-zinc-500 dark:text-zinc-400 text-center">
                                    No email content stored.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
