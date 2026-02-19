<?php

use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Knowledge Tests - Admin')] class extends Component {
    public bool $showArchived = false;

    #[Computed]
    public function tests()
    {
        $query = KnowledgeTest::withCount(['activeQuestions', 'attempts'])
            ->orderBy('name');

        if ($this->showArchived) {
            $query->archived();
        } else {
            $query->notArchived();
        }

        return $query->get();
    }

    #[Computed]
    public function stats()
    {
        return [
            'total_tests' => KnowledgeTest::notArchived()->count(),
            'active_tests' => KnowledgeTest::where('is_active', true)->notArchived()->count(),
            'pending_marking' => KnowledgeTestAttempt::needsMarking()->count(),
            'total_attempts' => KnowledgeTestAttempt::submitted()->count(),
            'archived_tests' => KnowledgeTest::archived()->count(),
        ];
    }

    public function toggleActive(int $id): void
    {
        $test = KnowledgeTest::findOrFail($id);
        $test->update(['is_active' => !$test->is_active]);
    }

    public function archiveTest(int $id): void
    {
        $test = KnowledgeTest::findOrFail($id);
        $test->archive();
        session()->flash('success', 'Test archived successfully. It will no longer appear to members.');
    }

    public function restoreTest(int $id): void
    {
        $test = KnowledgeTest::findOrFail($id);
        $test->restore();
        session()->flash('success', 'Test restored from archive.');
    }

    public function deleteTest(int $id): void
    {
        $test = KnowledgeTest::findOrFail($id);

        // Only allow deletion of tests with no attempts, or archived tests
        if ($test->attempts()->count() > 0 && !$test->isArchived()) {
            session()->flash('error', 'Cannot delete a test with attempts. Archive it first, then delete from the archive.');
            return;
        }

        $test->questions()->delete();
        $test->attempts()->delete(); // Also delete attempts if archived
        $test->delete();

        session()->flash('success', 'Test and all associated data deleted permanently.');
    }

    public function toggleShowArchived(): void
    {
        $this->showArchived = !$this->showArchived;
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Learning & Compliance</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage knowledge tests and assessments</p>
    </x-slot>

    <x-admin-learning-tabs current="tests" />

    {{-- Action Bar --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">
        <div class="flex gap-3">
            <a href="{{ route('admin.knowledge-tests.marking') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                Marking Queue
                @if($this->stats['pending_marking'] > 0)
                <span class="inline-flex items-center justify-center rounded-full bg-yellow-500 px-2 py-0.5 text-xs font-bold text-white">
                    {{ $this->stats['pending_marking'] }}
                </span>
                @endif
            </a>
            <a href="{{ route('admin.knowledge-tests.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Test
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/40">
        <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Tests</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['total_tests'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Active Tests</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->stats['active_tests'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Marking</p>
            <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->stats['pending_marking'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Attempts</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['total_attempts'] }}</p>
        </div>
        <button wire:click="toggleShowArchived" class="rounded-xl border border-zinc-200 bg-white p-4 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-left">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Archived Tests</p>
            <p class="mt-1 text-2xl font-bold text-zinc-500 dark:text-zinc-400">{{ $this->stats['archived_tests'] }}</p>
            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">{{ $showArchived ? 'View Active' : 'View Archived' }}</p>
        </button>
    </div>

    {{-- Tests List --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        @if($showArchived)
        <div class="border-b border-zinc-200 bg-amber-50 px-6 py-3 dark:border-zinc-700 dark:bg-amber-900/20">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                <svg class="inline-block size-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                Viewing Archived Tests - These tests are hidden from members
            </p>
        </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Test</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">For</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Questions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pass Mark</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Attempts</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->tests as $test)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $test->name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ Str::limit($test->description, 50) }}</p>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($test->dedicated_type === 'hunter')
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Hunter</span>
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700 dark:bg-red-900 dark:text-red-300 ml-1">Required</span>
                            @elseif($test->dedicated_type === 'sport' || $test->dedicated_type === 'sport_shooter')
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Sport</span>
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700 dark:bg-red-900 dark:text-red-300 ml-1">Required</span>
                            @elseif($test->dedicated_type === 'both')
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">Both</span>
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700 dark:bg-red-900 dark:text-red-300 ml-1">Required</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">All Members</span>
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-xs text-sky-700 dark:bg-sky-900 dark:text-sky-300 ml-1">Optional</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-900 dark:text-white">
                            {{ $test->active_questions_count }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-900 dark:text-white">
                            {{ $test->passing_score }}%
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-900 dark:text-white">
                            {{ $test->attempts_count }} <span class="text-zinc-500">total</span>
                            <p class="text-xs text-zinc-500">{{ $test->max_attempts }} max per user</p>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($test->isArchived())
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Archived</span>
                            @elseif($test->is_active)
                            <button wire:click="toggleActive({{ $test->id }})">
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                            </button>
                            @else
                            <button wire:click="toggleActive({{ $test->id }})">
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">Inactive</span>
                            </button>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <div class="flex items-center justify-end gap-3">
                                @if($test->isArchived())
                                    {{-- Archived test actions --}}
                                    <button wire:click="restoreTest({{ $test->id }})" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                                        Restore
                                    </button>
                                    <button wire:click="deleteTest({{ $test->id }})" wire:confirm="Are you sure you want to PERMANENTLY delete this test? This will delete all questions and {{ $test->attempts_count }} attempt(s). This cannot be undone!" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                        Delete Forever
                                    </button>
                                @else
                                    {{-- Active/Inactive test actions --}}
                                    <a href="{{ route('admin.knowledge-tests.questions', $test) }}" wire:navigate class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                        Questions
                                    </a>
                                    <a href="{{ route('admin.knowledge-tests.edit', $test) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                                        Edit
                                    </a>
                                    @if($test->attempts_count === 0)
                                    <button wire:click="deleteTest({{ $test->id }})" wire:confirm="Are you sure you want to delete this test?" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                        Delete
                                    </button>
                                    @else
                                    <button wire:click="archiveTest({{ $test->id }})" wire:confirm="Archive this test? It will be hidden from members but attempts will be preserved. You can restore or permanently delete it later." class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition-colors">
                                        Archive
                                    </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No knowledge tests</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Get started by creating your first test.</p>
                            <a href="{{ route('admin.knowledge-tests.create') }}" wire:navigate class="mt-4 inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Create Test
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
