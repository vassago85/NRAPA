<?php

use App\Models\AuditLog;
use App\Models\KnowledgeTestAttempt;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Marking Queue - Admin')] class extends Component {
    use WithPagination;

    #[Computed]
    public function pendingAttempts()
    {
        return KnowledgeTestAttempt::with(['user', 'knowledgeTest'])
            ->needsMarking()
            ->orderBy('submitted_at', 'asc')
            ->paginate(20);
    }

    #[Computed]
    public function recentlyMarked()
    {
        return KnowledgeTestAttempt::with(['user', 'knowledgeTest', 'marker'])
            ->whereNotNull('marked_at')
            ->orderBy('marked_at', 'desc')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function stats()
    {
        return [
            'pending' => KnowledgeTestAttempt::needsMarking()->count(),
            'marked_today' => KnowledgeTestAttempt::whereDate('marked_at', today())->count(),
            'passed_today' => KnowledgeTestAttempt::whereDate('marked_at', today())->where('passed', true)->count(),
        ];
    }

    public function reopenAttempt(string $uuid): void
    {
        $attempt = KnowledgeTestAttempt::where('uuid', $uuid)->firstOrFail();

        if ($attempt->marked_at === null) {
            session()->flash('error', 'That attempt has not been marked yet.');
            return;
        }

        $previous = [
            'total_score' => $attempt->total_score,
            'passed' => $attempt->passed,
            'marked_at' => $attempt->marked_at?->toIso8601String(),
            'marked_by' => $attempt->marked_by,
        ];

        $attempt->reopenForMarking(Auth::user());

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'knowledge_test_reopened',
            'auditable_type' => KnowledgeTestAttempt::class,
            'auditable_id' => $attempt->id,
            'old_values' => $previous,
            'new_values' => [
                'total_score' => null,
                'passed' => null,
                'marked_at' => null,
                'marked_by' => null,
                'source' => 'marking_queue',
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        unset($this->pendingAttempts, $this->recentlyMarked, $this->stats);
        session()->flash('success', "Reopened {$attempt->user->name}'s attempt. It is back in the queue.");
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Test Marking</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review and mark knowledge test submissions</p>
    </x-slot>
    
    {{-- Back Button --}}
    <div>
        <a href="{{ route('admin.knowledge-tests.index') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Back
        </a>
    </div>

    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
        <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Marking</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['pending'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Marked Today</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->stats['marked_today'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Passed Today</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->stats['passed_today'] }}</p>
        </div>
    </div>

    {{-- Pending Attempts --}}
    <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-800">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Pending Marking</h2>
        </div>

        @if($this->pendingAttempts->count() > 0)
        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @foreach($this->pendingAttempts as $attempt)
            <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        {{ $attempt->user->initials() }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $attempt->user->name }}</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $attempt->user->email }}</p>
                        <div class="mt-1 flex items-center gap-2 text-sm">
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                {{ $attempt->knowledgeTest->name }}
                            </span>
                            <span class="text-zinc-400">•</span>
                            <span class="text-zinc-500 dark:text-zinc-400">
                                MC Score: {{ $attempt->auto_score ?? 0 }} pts
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Submitted</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $attempt->submitted_at->format('d M Y') }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $attempt->submitted_at->diffForHumans() }}</p>
                    </div>
                    <a href="{{ route('admin.knowledge-tests.mark-attempt', $attempt) }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        Mark
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        @if($this->pendingAttempts->hasPages())
        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-800">
            {{ $this->pendingAttempts->links() }}
        </div>
        @endif
        @else
        <div class="p-12 text-center">
            <svg class="mx-auto size-12 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">All caught up!</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                There are no test attempts waiting to be marked.
            </p>
        </div>
        @endif
    </div>

    {{-- Recently Marked --}}
    @if($this->recentlyMarked->count() > 0)
    <div class="rounded-2xl shadow-sm border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-800">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recently Marked</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Member</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Test</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Result</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Marked By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Marked</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach($this->recentlyMarked as $attempt)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $attempt->user->name }}</p>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                            {{ $attempt->knowledgeTest->name }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-900 dark:text-white">
                            {{ $attempt->total_score }} pts
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($attempt->passed)
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Passed</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/40 dark:text-red-200">Failed</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                            {{ $attempt->marker?->name ?? 'System' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                            {{ $attempt->marked_at->format('d M Y H:i') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.knowledge-tests.mark-attempt', $attempt) }}"
                                    wire:navigate
                                    class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    View
                                </a>
                                <button
                                    type="button"
                                    wire:click="reopenAttempt('{{ $attempt->uuid }}')"
                                    wire:confirm="Reopen {{ $attempt->user->name }}'s attempt for re-marking? The current pass/fail result and marker scores will be cleared. The member's answers are kept."
                                    class="inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200 dark:hover:bg-amber-900/50 transition-colors">
                                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                    </svg>
                                    Reopen
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
