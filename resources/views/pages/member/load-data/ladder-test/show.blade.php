<?php

use App\Models\LadderTest;
use App\Models\LadderTestStep;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;

new class extends Component {
    public LadderTest $test;

    // Results editing
    public array $stepVelocities = [];
    public array $stepGroupSize = [];
    public array $stepNotes = [];

    public function mount(LadderTest $test): void
    {
        if ($test->user_id !== auth()->id()) {
            abort(403);
        }

        $this->test = $test->load(['steps', 'userFirearm', 'loadData']);

        foreach ($test->steps as $step) {
            $this->stepVelocities[$step->id] = $step->velocities ? implode(', ', $step->velocities) : '';
            $this->stepGroupSize[$step->id] = $step->group_size;
            $this->stepNotes[$step->id] = $step->notes ?? '';
        }
    }

    public function saveStepResults(int $stepId): void
    {
        $step = LadderTestStep::where('id', $stepId)
            ->whereHas('ladderTest', fn ($q) => $q->where('user_id', auth()->id()))
            ->firstOrFail();

        $velocitiesRaw = $this->stepVelocities[$stepId] ?? '';
        $velocities = array_filter(
            array_map('trim', explode(',', $velocitiesRaw)),
            fn ($v) => is_numeric($v) && $v > 0
        );
        $velocities = array_map('intval', $velocities);

        // Calculate ES and SD
        $es = null;
        $sd = null;
        if (count($velocities) >= 2) {
            $es = max($velocities) - min($velocities);
            $mean = array_sum($velocities) / count($velocities);
            $variance = array_sum(array_map(fn ($v) => pow($v - $mean, 2), $velocities)) / count($velocities);
            $sd = (int) round(sqrt($variance));
        }

        $step->update([
            'velocities' => !empty($velocities) ? array_values($velocities) : null,
            'group_size' => $this->stepGroupSize[$stepId] ?: null,
            'es' => $es,
            'sd' => $sd,
            'notes' => $this->stepNotes[$stepId] ?: null,
        ]);

        $this->test->load('steps');

        session()->flash('step_saved_' . $stepId, 'Step ' . $step->step_number . ' results saved.');
    }

    public function printLadderLabels()
    {
        $pdf = Pdf::loadView('documents.ladder-test-label', [
            'test' => $this->test,
            'steps' => $this->test->steps,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'ladder-labels-' . str_replace(' ', '-', strtolower($this->test->name)) . '.pdf');
    }

    public function with(): array
    {
        $bestStep = $this->test->best_step;

        // Build chart data from steps that have results
        $chartData = [];
        foreach ($this->test->steps as $step) {
            $chartData[] = [
                'step' => $step->step_number,
                'charge' => (float) $step->charge_weight,
                'avgVelocity' => $step->average_velocity ? (int) $step->average_velocity : null,
                'sd' => $step->sd,
                'es' => $step->es,
                'groupSize' => $step->group_size !== null ? (float) $step->group_size : null,
            ];
        }

        $hasAnyResults = collect($chartData)->contains(fn ($d) => $d['avgVelocity'] !== null);

        return [
            'steps' => $this->test->steps,
            'bestStepId' => $bestStep?->id,
            'chartData' => $chartData,
            'hasAnyResults' => $hasAnyResults,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('ladder-test.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $test->name }}</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $test->start_charge }}gr &rarr; {{ $test->end_charge }}gr
                        ({{ $test->increment }}gr steps)
                        @if($test->calibre) &mdash; {{ $test->calibre }} @endif
                    </p>
                </div>
            </div>
            <button wire:click="printLadderLabels"
                    class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Ladder Labels
            </button>
        </div>
    </x-slot>

    <!-- Summary -->
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
            <p class="text-2xl font-bold text-nrapa-blue">{{ $steps->count() }}</p>
            <p class="text-xs text-zinc-500">Steps</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
            <p class="text-2xl font-bold text-nrapa-orange">{{ $test->total_rounds }}</p>
            <p class="text-xs text-zinc-500">Total Rounds</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $test->rounds_per_step }}</p>
            <p class="text-xs text-zinc-500">Rounds/Step</p>
        </div>
        @if($test->best_step)
            <div class="rounded-lg border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20 p-4 text-center">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $test->best_step->charge_weight }}gr</p>
                <p class="text-xs text-green-600 dark:text-green-400">Best (SD: {{ $test->best_step->sd }})</p>
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-400">—</p>
                <p class="text-xs text-zinc-500">Best Step</p>
            </div>
        @endif
    </div>

    <!-- Component Info -->
    <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-5 text-sm">
            @if($test->bullet_make)
                <div>
                    <span class="text-zinc-500">Bullet:</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $test->bullet_make }} {{ $test->bullet_weight ? $test->bullet_weight . 'gr' : '' }} {{ $test->bullet_type }}</span>
                </div>
            @endif
            @if($test->powder_type)
                <div>
                    <span class="text-zinc-500">Powder:</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $test->powder_type }}</span>
                </div>
            @endif
            @if($test->primer_type)
                <div>
                    <span class="text-zinc-500">Primer:</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $test->primer_type }}</span>
                </div>
            @endif
            @if($test->userFirearm)
                <div>
                    <span class="text-zinc-500">Firearm:</span>
                    <a href="{{ route('armoury.show', $test->userFirearm) }}" wire:navigate class="font-medium text-nrapa-blue hover:text-nrapa-blue-dark">{{ $test->userFirearm->display_name }}</a>
                </div>
            @endif
        </div>
        @if($test->notes)
            <p class="mt-3 text-sm text-zinc-500">{{ $test->notes }}</p>
        @endif
    </div>

    <!-- Velocity / SD / ES Line Graph -->
    @if($hasAnyResults)
        <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6"
             x-data="ladderChart({{ Js::from($chartData) }})"
             x-init="init()"
             wire:key="ladder-chart-{{ collect($chartData)->map(fn($d) => ($d['avgVelocity'] ?? 0) . ($d['sd'] ?? 0))->implode('-') }}">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Results Graph</h2>
                <div class="flex items-center gap-4 text-xs">
                    <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full" style="background: #0B4EA2;"></span> Avg Velocity</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full" style="background: #F58220;"></span> SD</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full" style="background: #dc2626;"></span> ES</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full" style="background: #16a34a;"></span> Group Size</span>
                </div>
            </div>
            <div style="position: relative; height: 320px;">
                <canvas x-ref="ladderCanvas"></canvas>
            </div>
        </div>
    @else
        <div class="mb-6 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50 p-8 text-center">
            <svg class="mx-auto h-10 w-10 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
            </svg>
            <p class="mt-3 text-sm text-zinc-500">Enter velocity data for your steps below to see the results graph.</p>
        </div>
    @endif

    <!-- Steps Table -->
    <div class="space-y-4">
        @foreach($steps as $step)
            <div class="rounded-lg border {{ $bestStepId === $step->id ? 'border-green-400 dark:border-green-600 bg-green-50/50 dark:bg-green-900/10' : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800' }} p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $bestStepId === $step->id ? 'bg-green-500 text-white' : 'bg-nrapa-blue text-white' }} text-sm font-bold">
                            {{ $step->step_number }}
                        </span>
                        <div>
                            <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $step->charge_weight }}gr</span>
                            @if($bestStepId === $step->id)
                                <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Best SD</span>
                            @endif
                        </div>
                    </div>
                    @if($step->has_results)
                        <div class="flex items-center gap-4 text-sm">
                            @if($step->average_velocity)
                                <span class="text-zinc-500">Avg: <strong class="text-zinc-900 dark:text-white">{{ $step->average_velocity }} fps</strong></span>
                            @endif
                            @if($step->es !== null)
                                <span class="text-zinc-500">ES: <strong class="text-zinc-900 dark:text-white">{{ $step->es }}</strong></span>
                            @endif
                            @if($step->sd !== null)
                                <span class="text-zinc-500">SD: <strong class="{{ $step->sd <= 10 ? 'text-green-600' : ($step->sd <= 20 ? 'text-amber-600' : 'text-red-600') }}">{{ $step->sd }}</strong></span>
                            @endif
                            @if($step->group_size)
                                <span class="text-zinc-500">Group: <strong class="text-zinc-900 dark:text-white">{{ $step->group_size }}"</strong></span>
                            @endif
                        </div>
                    @endif
                </div>

                @if(session('step_saved_' . $step->id))
                    <div class="mb-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-3 py-2 text-xs text-green-700 dark:text-green-300">
                        {{ session('step_saved_' . $step->id) }}
                    </div>
                @endif

                <!-- Results Form -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Velocities (comma-separated)</label>
                        <input type="text" wire:model="stepVelocities.{{ $step->id }}" placeholder="e.g., 2745, 2752, 2748"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Group Size (inches)</label>
                        <input type="number" wire:model="stepGroupSize.{{ $step->id }}" step="0.01" placeholder="e.g., 0.75"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Notes</label>
                        <div class="flex gap-2">
                            <input type="text" wire:model="stepNotes.{{ $step->id }}" placeholder="Notes..."
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                            <button wire:click="saveStepResults({{ $step->id }})"
                                    class="rounded-lg bg-nrapa-blue px-3 py-1.5 text-xs font-medium text-white hover:bg-nrapa-blue-dark whitespace-nowrap">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endassets

@script
<script>
Alpine.data('ladderChart', (initialData) => ({
    chartInstance: null,
    data: initialData,

    init() {
        this.$nextTick(() => this.renderChart());
    },

    renderChart() {
        const canvas = this.$refs.ladderCanvas;
        if (!canvas || !window.Chart) return;

        // Destroy previous instance
        if (this.chartInstance) {
            this.chartInstance.destroy();
            this.chartInstance = null;
        }

        const labels = this.data.map(d => d.charge + 'gr');

        // Velocity data (left Y axis)
        const velocityData = this.data.map(d => d.avgVelocity);
        const hasVelocity = velocityData.some(v => v !== null);

        // SD data (right Y axis)
        const sdData = this.data.map(d => d.sd);
        const hasSd = sdData.some(v => v !== null);

        // ES data (right Y axis)
        const esData = this.data.map(d => d.es);
        const hasEs = esData.some(v => v !== null);

        // Group size data (separate right axis)
        const groupData = this.data.map(d => d.groupSize);
        const hasGroup = groupData.some(v => v !== null);

        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)';
        const textColor = isDark ? '#a1a1aa' : '#71717a';

        const datasets = [];

        if (hasVelocity) {
            datasets.push({
                label: 'Avg Velocity (fps)',
                data: velocityData,
                borderColor: '#0B4EA2',
                backgroundColor: 'rgba(11,78,162,0.1)',
                borderWidth: 2.5,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#0B4EA2',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: 0.3,
                fill: false,
                yAxisID: 'y',
                spanGaps: true,
            });
        }

        if (hasSd) {
            datasets.push({
                label: 'SD',
                data: sdData,
                borderColor: '#F58220',
                backgroundColor: 'rgba(245,130,32,0.1)',
                borderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#F58220',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: 0.3,
                fill: false,
                yAxisID: 'y1',
                spanGaps: true,
            });
        }

        if (hasEs) {
            datasets.push({
                label: 'ES',
                data: esData,
                borderColor: '#dc2626',
                backgroundColor: 'rgba(220,38,38,0.1)',
                borderWidth: 2,
                borderDash: [6, 3],
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#dc2626',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: 0.3,
                fill: false,
                yAxisID: 'y1',
                spanGaps: true,
            });
        }

        if (hasGroup) {
            datasets.push({
                label: 'Group Size (in)',
                data: groupData,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22,163,74,0.1)',
                borderWidth: 2,
                borderDash: [3, 3],
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#16a34a',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: 0.3,
                fill: false,
                yAxisID: 'y1',
                spanGaps: true,
            });
        }

        const scales = {
            x: {
                title: { display: true, text: 'Charge Weight (gr)', color: textColor, font: { weight: '600' } },
                grid: { color: gridColor },
                ticks: { color: textColor },
            }
        };

        if (hasVelocity) {
            scales.y = {
                type: 'linear',
                position: 'left',
                title: { display: true, text: 'Avg Velocity (fps)', color: '#0B4EA2', font: { weight: '600' } },
                grid: { color: gridColor },
                ticks: { color: '#0B4EA2' },
            };
        }

        if (hasSd || hasEs || hasGroup) {
            scales.y1 = {
                type: 'linear',
                position: 'right',
                title: { display: true, text: 'SD / ES / Group', color: '#F58220', font: { weight: '600' } },
                grid: { drawOnChartArea: false },
                ticks: { color: '#F58220' },
                beginAtZero: true,
            };
        }

        this.chartInstance = new Chart(canvas, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { color: textColor, usePointStyle: true, pointStyle: 'circle', padding: 16 },
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#27272a' : '#fff',
                        titleColor: isDark ? '#fff' : '#18181b',
                        bodyColor: isDark ? '#d4d4d8' : '#3f3f46',
                        borderColor: isDark ? '#3f3f46' : '#e4e4e7',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            title: (items) => 'Charge: ' + items[0].label,
                        }
                    },
                },
                scales,
            },
        });
    },

    destroy() {
        if (this.chartInstance) {
            this.chartInstance.destroy();
            this.chartInstance = null;
        }
    },
}));
</script>
@endscript
