<?php

use App\Models\EmailLog;
use App\Queries\EmailDeliveryStats;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Email & Delivery - Admin')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $memberFilter = '';
    public ?string $selectedLog = null;

    #[Url]
    public string $delivery = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function filterDelivery(string $delivery): void
    {
        $this->delivery = in_array($delivery, EmailDeliveryStats::DELIVERY_CHIPS, true) ? $delivery : '';
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingMemberFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Manually resolve a queued/failed email without sending it — e.g. the
     * member was contacted another way, has renewed, or the address is dead.
     * Distinct from "sent" on purpose: the mail never went out.
     */
    public function markCompleted(string $uuid): void
    {
        $log = EmailLog::where('uuid', $uuid)->first();

        if (! $log || ! in_array($log->status, ['queued', 'failed'], true)) {
            session()->flash('error', 'Only queued or failed emails can be marked as completed.');
            return;
        }

        $log->update([
            'status' => 'completed',
            'metadata' => array_merge((array) $log->metadata, [
                'completed_by' => auth()->id(),
                'completed_at' => now()->toDateTimeString(),
            ]),
        ]);

        session()->flash('success', "Marked as completed (not sent): {$log->subject} to {$log->to_email}.");
    }

    /**
     * Resend a queued/failed email using the stored body and subject.
     *
     * We can't reconstruct the original mailable (its models/state are gone),
     * but every audit row stores the fully-rendered HTML at dispatch time, so
     * we replay that verbatim. The row is flipped to 'queued' first so the
     * LogSentEmail listener promotes THIS row to 'sent' when the message
     * actually leaves — one row, full history, correct type preserved.
     */
    public function resend(string $uuid): void
    {
        $log = EmailLog::where('uuid', $uuid)->first();

        if (! $log || ! in_array($log->status, ['queued', 'failed'], true)) {
            session()->flash('error', 'Only queued or failed emails can be resent.');
            return;
        }

        if (empty($log->body)) {
            session()->flash('error', 'This log entry has no stored email content, so it cannot be resent.');
            return;
        }

        if ($log->user && $log->user->email === $log->to_email && ! $log->user->canReceiveEmail()) {
            session()->flash('error', "{$log->to_email} has hard-bounced or complained, so it will not be mailed again.");
            return;
        }

        try {
            $log->update([
                'status' => 'queued',
                'error_message' => null,
                'metadata' => array_merge((array) $log->metadata, [
                    'resent_by' => auth()->id(),
                    'resent_at' => now()->toDateTimeString(),
                ]),
            ]);

            Mail::html($log->body, function ($message) use ($log) {
                $message->to($log->to_email, $log->to_name ?: null)
                    ->subject($log->subject);
            });

            // Synchronous send: the listener has already promoted the row.
            // Belt-and-braces in case promotion didn't match.
            $log->refresh();
            if ($log->status === 'queued') {
                $log->update(['status' => 'sent', 'sent_at' => now()]);
            }

            session()->flash('success', "Email resent to {$log->to_email}.");
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => Str::limit($e->getMessage(), 500),
            ]);

            session()->flash('error', 'Resend failed: ' . $e->getMessage());
        }
    }

    public function with(): array
    {
        $query = EmailLog::with(['user' => fn ($q) => $q->withCount('memberships')->with('activeMembership')])
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
            ->when($this->delivery === 'unknown', fn ($q) => $q->whereNull('delivery_status'))
            ->when($this->delivery && $this->delivery !== 'unknown', fn ($q) => $q->where('delivery_status', $this->delivery))
            ->when($this->memberFilter === 'active', function ($q) {
                $q->whereHas('user', fn ($u) => $u->whereHas('memberships', fn ($m) => $m->where('status', 'active')));
            })
            ->when($this->memberFilter === 'expired', function ($q) {
                // Has memberships, but none currently active (expired / suspended / superseded).
                $q->whereHas('user', function ($u) {
                    $u->whereHas('memberships')
                        ->whereDoesntHave('memberships', fn ($m) => $m->where('status', 'active'));
                });
            })
            ->latest();

        $stats = app(EmailDeliveryStats::class)->summary();

        return [
            'logs' => $query->paginate(20),
            'totalSent' => $stats['by_status']['sent'] ?? 0,
            'totalFailed' => $stats['by_status']['failed'] ?? 0,
            'totalEmails' => $stats['total'],
            'deliveryCounts' => $stats['by_delivery'],
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
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Email &amp; Delivery</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Every email the platform sent, and what Mailgun reported back</p>
    </x-slot>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/40 border border-emerald-300 dark:border-emerald-700 rounded-xl text-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 p-4">
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
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 p-4">
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
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalEmails) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Emails</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery status chips -->
    @php
        $deliveryLabels = [
            'delivered' => 'Delivered', 'opened' => 'Opened', 'clicked' => 'Clicked', 'bounced' => 'Bounced',
            'complained' => 'Complained', 'failed' => 'Failed', 'sent' => 'Awaiting report', 'unknown' => 'Not tracked',
        ];
    @endphp
    <div class="mb-6 flex flex-wrap gap-2">
        <button type="button" wire:click="filterDelivery('')" data-delivery-chip="all"
            @class([
                'px-3 py-1 text-xs font-medium rounded-full border transition-colors',
                'border-nrapa-blue bg-nrapa-blue text-white' => $delivery === '',
                'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700' => $delivery !== '',
            ])>
            All <span class="ml-1">{{ number_format($totalEmails) }}</span>
        </button>
        @foreach($deliveryLabels as $key => $label)
            <button type="button" wire:click="filterDelivery('{{ $key }}')" data-delivery-chip="{{ $key }}"
                @class([
                    'px-3 py-1 text-xs font-medium rounded-full border transition-colors',
                    'border-nrapa-blue bg-nrapa-blue text-white' => $delivery === $key,
                    'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700' => $delivery !== $key,
                ])>
                {{ $label }}
                <span @class(['ml-1', 'text-zinc-400' => ($deliveryCounts[$key] ?? 0) === 0 && $delivery !== $key])>{{ number_format($deliveryCounts[$key] ?? 0) }}</span>
            </button>
        @endforeach
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
            <option value="queued">Queued</option>
            <option value="failed">Failed</option>
            <option value="completed">Completed</option>
        </select>
        <select wire:model.live="memberFilter"
            class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Members</option>
            <option value="active">Active Membership</option>
            <option value="expired">Expired / No Active Membership</option>
        </select>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Recipient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($logs as $log)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $log->sent_at?->format('d M Y H:i') ?? $log->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $log->to_name ?? 'Unknown' }}</span>
                                    @if($log->user)
                                        @if($log->user->activeMembership)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">Active</span>
                                        @elseif($log->user->memberships_count > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">Expired</span>
                                        @endif
                                    @endif
                                </div>
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
                                @elseif($log->status === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        Completed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                @endif
                                @if($log->delivery_status && $log->delivery_status !== 'sent')
                                    <span data-delivery-status="{{ $log->delivery_status }}" title="{{ $log->bounce_reason }}"
                                        @class([
                                            'mt-1 flex w-fit items-center px-2 py-0.5 rounded-full text-[11px] font-medium',
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => in_array($log->delivery_status, ['delivered', 'opened', 'clicked'], true),
                                            'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300' => in_array($log->delivery_status, \App\Models\EmailLog::FINAL_DELIVERY_STATUSES, true),
                                        ])>
                                        {{ $deliveryLabels[$log->delivery_status] ?? ucfirst($log->delivery_status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if(in_array($log->status, ['queued', 'failed'], true) && $log->body)
                                        <button wire:click="resend('{{ $log->uuid }}')"
                                            wire:confirm="Resend this email to {{ $log->to_email }}?"
                                            wire:loading.attr="disabled"
                                            wire:target="resend('{{ $log->uuid }}')"
                                            class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 text-sm font-medium transition-colors disabled:opacity-50">
                                            <span wire:loading.remove wire:target="resend('{{ $log->uuid }}')">Resend</span>
                                            <span wire:loading wire:target="resend('{{ $log->uuid }}')">Sending...</span>
                                        </button>
                                    @endif
                                    @if(in_array($log->status, ['queued', 'failed'], true))
                                        <button wire:click="markCompleted('{{ $log->uuid }}')"
                                            wire:confirm="Mark this email as completed WITHOUT sending it?"
                                            class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 text-sm font-medium transition-colors">
                                            Complete
                                        </button>
                                    @endif
                                    <button wire:click="viewLog('{{ $log->uuid }}')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                                        View
                                    </button>
                                </div>
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
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800">
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
                            <div class="flex items-center gap-3">
                                @if(in_array($log->status, ['queued', 'failed'], true) && $log->body)
                                    <button wire:click="resend('{{ $log->uuid }}')"
                                        wire:confirm="Resend this email to {{ $log->to_email }}?"
                                        wire:loading.attr="disabled"
                                        wire:target="resend('{{ $log->uuid }}')"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span wire:loading.remove wire:target="resend('{{ $log->uuid }}')">Resend</span>
                                        <span wire:loading wire:target="resend('{{ $log->uuid }}')">Sending...</span>
                                    </button>
                                @endif
                                @if(in_array($log->status, ['queued', 'failed'], true))
                                    <button wire:click="markCompleted('{{ $log->uuid }}')"
                                        wire:confirm="Mark this email as completed WITHOUT sending it?"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Complete
                                    </button>
                                @endif
                                <button wire:click="closeLog" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
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

                            @if($log->delivery_status || ! empty($log->events))
                                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 text-sm">
                                    <p class="font-medium text-zinc-900 dark:text-white">
                                        Delivery: {{ ucfirst($log->delivery_status ?? 'not tracked') }}
                                        @if($log->bounce_code) <span class="text-zinc-500">(code {{ $log->bounce_code }})</span> @endif
                                    </p>
                                    @if($log->bounce_reason)
                                        <p class="mt-1 text-red-700 dark:text-red-300">{{ $log->bounce_reason }}</p>
                                    @endif
                                    @if(! empty($log->events))
                                        <ul class="mt-2 space-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            @foreach(array_reverse($log->events) as $deliveryEvent)
                                                <li>{{ \Illuminate\Support\Carbon::parse($deliveryEvent['at'] ?? now())->format('d M Y H:i') }} · {{ $deliveryEvent['event'] ?? '' }}{{ isset($deliveryEvent['severity']) ? ' ('.$deliveryEvent['severity'].')' : '' }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif

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
                                                class="w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white"
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
