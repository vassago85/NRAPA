<?php

use App\Models\LadderTest;
use Livewire\Component;

new class extends Component {
    public string $search = '';

    public function deleteLadderTest(string $uuid): void
    {
        LadderTest::where('uuid', $uuid)
            ->where('user_id', auth()->id())
            ->delete();

        session()->flash('success', 'Ladder test deleted.');
    }

    public function with(): array
    {
        $query = LadderTest::where('user_id', auth()->id())
            ->with(['userFirearm', 'steps']);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return [
            'tests' => $query->orderByDesc('created_at')->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Virtual Safe</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Incremental charge testing for load development</p>
            </div>
            <a href="{{ route('ladder-test.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Ladder Test
            </a>
        </div>
        <x-virtual-safe-tabs current="ladder" />
    </x-slot>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Search -->
    <div class="mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search ladder tests..."
               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
    </div>

    <!-- Tests List -->
    @forelse($tests as $test)
        <div class="mb-4 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <a href="{{ route('ladder-test.show', $test) }}" wire:navigate
                       class="text-lg font-semibold text-nrapa-blue hover:text-nrapa-blue-dark">
                        {{ $test->name }}
                    </a>
                    <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-zinc-500">
                        @if($test->isSeatingDepth())
                            <span class="inline-flex items-center rounded-full bg-nrapa-orange/10 text-nrapa-orange px-2 py-0.5 text-xs font-medium">Seating Depth</span>
                        @endif
                        @if($test->calibre)
                            <span class="font-medium text-nrapa-orange">{{ $test->calibre }}</span>
                        @endif
                        <span>{{ rtrim(rtrim($test->start_charge, '0'), '.') }}{{ $test->unit_label }} &rarr; {{ rtrim(rtrim($test->end_charge, '0'), '.') }}{{ $test->unit_label }} ({{ rtrim(rtrim($test->increment, '0'), '.') }}{{ $test->unit_label }} steps)</span>
                        <span>{{ $test->steps->count() }} steps &times; {{ $test->rounds_per_step }} = {{ $test->total_rounds }} rounds</span>
                    </div>
                    @if($test->userFirearm)
                        <p class="mt-1 text-xs text-zinc-400">{{ $test->userFirearm->display_name }}</p>
                    @endif
                    @if($test->best_step)
                        <p class="mt-1 text-xs text-green-600 dark:text-green-400">
                            Best: Step {{ $test->best_step->step_number }} ({{ rtrim(rtrim($test->best_step->charge_weight, '0'), '.') }}{{ $test->unit_label }}) &mdash; SD: {{ $test->best_step->sd }}
                        </p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('ladder-test.show', $test) }}" wire:navigate
                       class="text-sm font-medium text-nrapa-blue hover:text-nrapa-blue-dark">View</a>
                    <button wire:click="deleteLadderTest('{{ $test->uuid }}')" wire:confirm="Delete this ladder test?"
                            class="text-sm font-medium text-red-500 hover:text-red-700">Delete</button>
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">No ladder tests yet</h3>
            <p class="mt-2 text-sm text-zinc-500">Create a ladder test to find the optimal charge weight for your loads.</p>
            <a href="{{ route('ladder-test.create') }}" wire:navigate
               class="mt-4 inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                Create First Test
            </a>
        </div>
    @endforelse
</div>
