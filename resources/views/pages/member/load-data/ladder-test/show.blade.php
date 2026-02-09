<?php

use App\Models\LadderTest;
use App\Models\LadderTestStep;
use App\Models\LoadData;
use App\Models\UserFirearm;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public LadderTest $test;

    // Results editing
    public array $stepVelocities = [];
    public array $stepGroupSize = [];
    public array $stepNotes = [];
    public array $editingSteps = [];  // step IDs currently in edit mode

    // Unit preferences (display only — DB stores fps, inches)
    public string $velocity_unit = 'fps';   // fps or ms (m/s)
    public string $group_unit = 'in';       // in or mm

    // CSV Import
    public bool $showImportForm = false;
    public $csvFile = null;
    public array $importPreview = [];
    public array $importErrors = [];
    public bool $importReady = false;

    // Component editing
    public bool $editingComponents = false;
    public ?int $edit_load_data_id = null;
    public ?int $edit_user_firearm_id = null;
    public string $edit_name = '';
    public string $edit_calibre = '';
    public string $edit_bullet_make = '';
    public ?float $edit_bullet_weight = null;
    public string $edit_bullet_type = '';
    public string $edit_powder_type = '';
    public string $edit_primer_type = '';
    public string $edit_notes = '';

    public function mount(LadderTest $test): void
    {
        if ($test->user_id !== auth()->id()) {
            abort(403);
        }

        $this->test = $test->load(['steps', 'userFirearm', 'loadData']);
        $roundsPerStep = max(1, $this->test->rounds_per_step ?? 3);

        foreach ($test->steps as $step) {
            // Build individual velocity slots (pad to rounds_per_step)
            $vels = $step->velocities ?? [];
            $slots = [];
            for ($i = 0; $i < $roundsPerStep; $i++) {
                $slots[$i] = $vels[$i] ?? null;
            }
            $this->stepVelocities[$step->id] = $slots;
            $this->stepGroupSize[$step->id] = $step->group_size;
            $this->stepNotes[$step->id] = $step->notes ?? '';
        }
    }

    public function editComponents(): void
    {
        $this->edit_load_data_id = $this->test->load_data_id;
        $this->edit_user_firearm_id = $this->test->user_firearm_id;
        $this->edit_name = $this->test->name;
        $this->edit_calibre = $this->test->calibre ?? '';
        $this->edit_bullet_make = $this->test->bullet_make ?? '';
        $this->edit_bullet_weight = $this->test->bullet_weight ? (float) $this->test->bullet_weight : null;
        $this->edit_bullet_type = $this->test->bullet_type ?? '';
        $this->edit_powder_type = $this->test->powder_type ?? '';
        $this->edit_primer_type = $this->test->primer_type ?? '';
        $this->edit_notes = $this->test->notes ?? '';
        $this->editingComponents = true;
    }

    public function cancelEditComponents(): void
    {
        $this->editingComponents = false;
    }

    public function updatedEditLoadDataId($value): void
    {
        if ($value) {
            $load = LoadData::where('id', $value)->where('user_id', auth()->id())->first();
            if ($load) {
                $this->edit_user_firearm_id = $load->user_firearm_id;
                $this->edit_calibre = $load->calibre_name ?? '';
                $this->edit_bullet_make = $load->bullet_make ?? '';
                $this->edit_bullet_weight = $load->bullet_weight ? (float) $load->bullet_weight : null;
                $this->edit_bullet_type = $load->bullet_type ?? '';
                $this->edit_powder_type = $load->powder_type ?? '';
                $this->edit_primer_type = $load->primer_type ?? '';
            }
        }
    }

    public function saveComponents(): void
    {
        $this->validate([
            'edit_name' => ['required', 'string', 'max:255'],
        ]);

        $this->test->update([
            'load_data_id' => $this->edit_load_data_id ?: null,
            'user_firearm_id' => $this->edit_user_firearm_id ?: null,
            'name' => $this->edit_name,
            'calibre' => $this->edit_calibre ?: null,
            'bullet_make' => $this->edit_bullet_make ?: null,
            'bullet_weight' => $this->edit_bullet_weight,
            'bullet_type' => $this->edit_bullet_type ?: null,
            'powder_type' => $this->edit_powder_type ?: null,
            'primer_type' => $this->edit_primer_type ?: null,
            'notes' => $this->edit_notes ?: null,
        ]);

        $this->test->load(['userFirearm', 'loadData']);
        $this->editingComponents = false;

        session()->flash('success', 'Test details updated.');
    }

    // --- Unit conversion helpers ---
    private function fpsToMs($v) { return $v !== null ? round($v * 0.3048, 1) : null; }
    private function msToFps($v) { return $v !== null ? (int) round($v / 0.3048) : null; }
    private function inToMm(?float $v): ?float { return $v !== null ? round($v * 25.4, 2) : null; }
    private function mmToIn(?float $v): ?float { return $v !== null ? round($v / 25.4, 4) : null; }

    public function updatedVelocityUnit($value): void
    {
        // Convert all displayed velocity values between fps and m/s
        foreach ($this->stepVelocities as $id => $slots) {
            if (!is_array($slots)) continue;
            foreach ($slots as $i => $v) {
                if ($v === null || $v === '' || !is_numeric($v)) continue;
                if ($value === 'ms') {
                    $this->stepVelocities[$id][$i] = round($v * 0.3048, 1);
                } else {
                    $this->stepVelocities[$id][$i] = (int) round($v / 0.3048);
                }
            }
        }
    }

    public function updatedGroupUnit($value): void
    {
        foreach ($this->stepGroupSize as $id => $gs) {
            if ($gs === null || $gs === '') continue;
            if ($value === 'mm') {
                $this->stepGroupSize[$id] = $this->inToMm((float) $gs);
            } else {
                $this->stepGroupSize[$id] = $this->mmToIn((float) $gs);
            }
        }
    }

    public function editStep(int $stepId): void
    {
        // Reload fresh data from DB into form fields (in case they were stale)
        $step = LadderTestStep::findOrFail($stepId);
        $roundsPerStep = max(1, $this->test->rounds_per_step ?? 3);

        $vels = $step->velocities ?? [];
        $slots = [];
        for ($i = 0; $i < $roundsPerStep; $i++) {
            $v = $vels[$i] ?? null;
            // Convert to display unit if needed
            if ($v !== null && $this->velocity_unit === 'ms') {
                $v = round($v * 0.3048, 1);
            }
            $slots[$i] = $v;
        }
        $this->stepVelocities[$stepId] = $slots;

        if ($this->group_unit === 'mm' && $step->group_size !== null) {
            $this->stepGroupSize[$stepId] = $this->inToMm((float) $step->group_size);
        } else {
            $this->stepGroupSize[$stepId] = $step->group_size;
        }

        $this->stepNotes[$stepId] = $step->notes ?? '';
        $this->editingSteps[$stepId] = true;
    }

    public function cancelEditStep(int $stepId): void
    {
        unset($this->editingSteps[$stepId]);
    }

    public function saveStepResults(int $stepId): void
    {
        $step = LadderTestStep::where('id', $stepId)
            ->whereHas('ladderTest', fn ($q) => $q->where('user_id', auth()->id()))
            ->firstOrFail();

        // Collect individual velocity values, filtering out empty/non-numeric
        $rawSlots = $this->stepVelocities[$stepId] ?? [];
        $velocities = [];
        foreach ($rawSlots as $v) {
            $v = is_string($v) ? trim($v) : $v;
            if ($v !== null && $v !== '' && is_numeric($v) && (float) $v > 0) {
                $velocities[] = $v;
            }
        }

        // Convert velocities to canonical fps if entered in m/s
        if ($this->velocity_unit === 'ms') {
            $velocities = array_map(fn ($v) => (int) round($v / 0.3048), $velocities);
        } else {
            $velocities = array_map('intval', $velocities);
        }

        // Convert group size to canonical inches if entered in mm
        $groupSize = $this->stepGroupSize[$stepId] ?: null;
        if ($groupSize !== null && $this->group_unit === 'mm') {
            $groupSize = $this->mmToIn((float) $groupSize);
        }

        // Calculate ES and SD (in fps)
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
            'group_size' => $groupSize,
            'es' => $es,
            'sd' => $sd,
            'notes' => $this->stepNotes[$stepId] ?: null,
        ]);

        $this->test->load('steps');

        // Exit edit mode after saving
        unset($this->editingSteps[$stepId]);

        session()->flash('step_saved_' . $stepId, 'Step ' . $step->step_number . ' results saved.');
    }

    public function downloadCsvTemplate()
    {
        $unit = $this->test->unit_label;
        $headers = "step,charge_weight_({$unit}),velocities,group_size_inches,notes";
        $rows = [];

        foreach ($this->test->steps as $step) {
            $charge = rtrim(rtrim($step->charge_weight, '0'), '.');
            $existingVel = $step->velocities ? implode('; ', $step->velocities) : '';
            $existingGroup = $step->group_size ?? '';
            $existingNotes = $step->notes ?? '';
            $rows[] = "{$step->step_number},{$charge},\"{$existingVel}\",{$existingGroup},\"{$existingNotes}\"";
        }

        $csv = $headers . "\n" . implode("\n", $rows);
        $filename = 'ladder-template-' . str_replace(' ', '-', strtolower($this->test->name)) . '.csv';

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function updatedCsvFile(): void
    {
        $this->importPreview = [];
        $this->importErrors = [];
        $this->importReady = false;

        if (!$this->csvFile) return;

        $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
        ]);

        $path = $this->csvFile->getRealPath();
        $lines = array_filter(array_map('trim', file($path)));

        if (count($lines) < 2) {
            $this->importErrors[] = 'CSV file must have a header row and at least one data row.';
            return;
        }

        // Skip header
        $header = str_getcsv(array_shift($lines));

        $stepMap = $this->test->steps->keyBy('step_number');
        $preview = [];
        $errors = [];

        foreach ($lines as $lineNum => $line) {
            $cols = str_getcsv($line);
            if (count($cols) < 2) continue;

            $stepNum = (int) trim($cols[0] ?? '');
            $chargeWeight = trim($cols[1] ?? '');
            $velocitiesRaw = trim($cols[2] ?? '');
            $groupSize = trim($cols[3] ?? '');
            $notes = trim($cols[4] ?? '');

            if (!$stepMap->has($stepNum)) {
                $errors[] = "Row " . ($lineNum + 2) . ": Step {$stepNum} does not exist in this test.";
                continue;
            }

            // Parse velocities (accept comma or semicolon separated)
            $velocities = [];
            if ($velocitiesRaw) {
                $velParts = preg_split('/[;,]+/', $velocitiesRaw);
                foreach ($velParts as $v) {
                    $v = trim($v);
                    if (is_numeric($v) && $v > 0) {
                        $velocities[] = (int) $v;
                    }
                }
            }

            // Calculate ES and SD
            $es = null;
            $sd = null;
            if (count($velocities) >= 2) {
                $es = max($velocities) - min($velocities);
                $mean = array_sum($velocities) / count($velocities);
                $variance = array_sum(array_map(fn ($v) => pow($v - $mean, 2), $velocities)) / count($velocities);
                $sd = (int) round(sqrt($variance));
            }

            $preview[] = [
                'step_number' => $stepNum,
                'charge_weight' => $chargeWeight,
                'velocities' => $velocities,
                'group_size' => $groupSize !== '' ? (float) $groupSize : null,
                'es' => $es,
                'sd' => $sd,
                'notes' => $notes,
                'vel_count' => count($velocities),
                'avg_vel' => count($velocities) > 0 ? (int) round(array_sum($velocities) / count($velocities)) : null,
            ];
        }

        $this->importPreview = $preview;
        $this->importErrors = $errors;
        $this->importReady = count($preview) > 0;
    }

    public function deleteTest(): void
    {
        $this->test->steps()->delete();
        $this->test->delete();

        session()->flash('success', 'Ladder test deleted.');
        $this->redirect(route('ladder-test.index'), navigate: true);
    }

    public function clearStepResults(int $stepId): void
    {
        $step = LadderTestStep::where('id', $stepId)
            ->whereHas('ladderTest', fn ($q) => $q->where('user_id', auth()->id()))
            ->firstOrFail();

        $step->update([
            'velocities' => null,
            'group_size' => null,
            'es' => null,
            'sd' => null,
            'notes' => null,
        ]);

        $roundsPerStep = max(1, $this->test->rounds_per_step ?? 3);
        $this->stepVelocities[$stepId] = array_fill(0, $roundsPerStep, null);
        $this->stepGroupSize[$stepId] = null;
        $this->stepNotes[$stepId] = '';

        $this->test->load('steps');

        // Exit edit mode after clearing
        unset($this->editingSteps[$stepId]);

        session()->flash('step_saved_' . $stepId, 'Step ' . $step->step_number . ' results cleared.');
    }

    public function importResults(): void
    {
        if (empty($this->importPreview)) return;

        $stepMap = $this->test->steps->keyBy('step_number');
        $imported = 0;

        foreach ($this->importPreview as $row) {
            $step = $stepMap->get($row['step_number']);
            if (!$step) continue;

            $step->update([
                'velocities' => !empty($row['velocities']) ? $row['velocities'] : null,
                'group_size' => $row['group_size'],
                'es' => $row['es'],
                'sd' => $row['sd'],
                'notes' => $row['notes'] ?: null,
            ]);

            // Update local form state to reflect import
            $roundsPerStep = max(1, $this->test->rounds_per_step ?? 3);
            $vels = $row['velocities'] ?? [];
            $slots = [];
            for ($i = 0; $i < $roundsPerStep; $i++) {
                $slots[$i] = $vels[$i] ?? null;
            }
            $this->stepVelocities[$step->id] = $slots;
            $this->stepGroupSize[$step->id] = $row['group_size'];
            $this->stepNotes[$step->id] = $row['notes'] ?? '';

            $imported++;
        }

        $this->test->load('steps');
        $this->showImportForm = false;
        $this->csvFile = null;
        $this->importPreview = [];
        $this->importErrors = [];
        $this->importReady = false;
        $this->editingSteps = [];  // Exit all edit modes after import

        session()->flash('success', "Imported results for {$imported} steps.");
    }

    public function with(): array
    {
        $bestStep = $this->test->best_step;

        // Build chart data from steps that have results
        $chartData = [];
        foreach ($this->test->steps as $step) {
            $chartData[] = [
                'step' => $step->step_number,
                'charge' => floatval(rtrim(rtrim($step->charge_weight, '0'), '.')),
                'avgVelocity' => $step->average_velocity ? (int) $step->average_velocity : null,
                'sd' => $step->sd,
                'es' => $step->es,
                'groupSize' => $step->group_size !== null ? (float) $step->group_size : null,
            ];
        }

        $hasAnyResults = collect($chartData)->contains(fn ($d) => $d['avgVelocity'] !== null);

        $data = [
            'steps' => $this->test->steps,
            'bestStepId' => $bestStep?->id,
            'chartData' => $chartData,
            'hasAnyResults' => $hasAnyResults,
        ];

        // Only load these when editing components to avoid unnecessary queries
        if ($this->editingComponents) {
            $data['firearms'] = UserFirearm::forUser(auth()->id())->active()->with('firearmCalibre')->get();
            $data['loads'] = LoadData::forUser(auth()->id())->orderBy('name')->get();
        }

        return $data;
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
                        @if($test->isSeatingDepth())
                            <span class="inline-flex items-center rounded-full bg-nrapa-orange/10 text-nrapa-orange px-2 py-0.5 text-xs font-medium mr-1">Seating Depth</span>
                        @endif
                        {{ rtrim(rtrim($test->start_charge, '0'), '.') }}{{ $test->unit_label }} &rarr; {{ rtrim(rtrim($test->end_charge, '0'), '.') }}{{ $test->unit_label }}
                        ({{ rtrim(rtrim($test->increment, '0'), '.') }}{{ $test->unit_label }} steps)
                        @if($test->calibre) &mdash; {{ $test->calibre }} @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="$set('showImportForm', true)"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Import CSV
                </button>
                <a href="{{ route('ladder-test.labels', $test) }}" target="_blank"
                   class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print Labels
                </a>
                <button wire:click="deleteTest" wire:confirm="Are you sure you want to delete this ladder test? This cannot be undone."
                        class="inline-flex items-center gap-2 rounded-lg border border-red-300 dark:border-red-600 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Test
                </button>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

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
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ rtrim(rtrim($test->best_step->charge_weight, '0'), '.') }}{{ $test->unit_label }}</p>
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
        @if($editingComponents)
            {{-- Editable component form --}}
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Edit Test Details</h2>
                <button wire:click="cancelEditComponents" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-5">
                {{-- Name & Base Load --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Test Name *</label>
                        <input type="text" wire:model="edit_name"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                        @error('edit_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Base Load Recipe</label>
                        <select wire:model.live="edit_load_data_id"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                            <option value="">None</option>
                            @foreach($loads as $l)
                                <option value="{{ $l->id }}">{{ $l->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Firearm</label>
                        <select wire:model="edit_user_firearm_id"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                            <option value="">None</option>
                            @foreach($firearms as $f)
                                <option value="{{ $f->id }}">{{ $f->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Components --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Calibre</label>
                        <input type="text" wire:model="edit_calibre" placeholder="e.g., .308 Win"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Bullet Make</label>
                        <input type="text" wire:model="edit_bullet_make"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Bullet Weight (gr)</label>
                        <input type="number" wire:model="edit_bullet_weight" step="0.1"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Bullet Type</label>
                        <input type="text" wire:model="edit_bullet_type"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Powder Type</label>
                        <input type="text" wire:model="edit_powder_type"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Primer Type</label>
                        <input type="text" wire:model="edit_primer_type"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Notes</label>
                    <textarea wire:model="edit_notes" rows="2" placeholder="Test notes..."
                              class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white"></textarea>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-2">
                    <button wire:click="saveComponents"
                            wire:loading.attr="disabled"
                            class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                        <span wire:loading.remove wire:target="saveComponents">Save Changes</span>
                        <span wire:loading wire:target="saveComponents">Saving...</span>
                    </button>
                    <button wire:click="cancelEditComponents"
                            class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                </div>
            </div>
        @else
            {{-- Read-only component display --}}
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Components</h3>
                <button wire:click="editComponents"
                        class="inline-flex items-center gap-1 rounded-lg border border-nrapa-blue/40 px-2.5 py-1 text-xs font-medium text-nrapa-blue hover:bg-nrapa-blue/5 dark:text-nrapa-blue-light dark:hover:bg-nrapa-blue/10">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </button>
            </div>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-5 text-sm">
                @if($test->bullet_make || $test->bullet_weight || $test->bullet_type)
                    <div>
                        <span class="text-zinc-500">Bullet:</span>
                        <span class="font-medium text-zinc-900 dark:text-white">
                            {{ $test->bullet_make }}
                            @if($test->bullet_weight)
                                {{ $test->bullet_weight }}gr
                                <span class="text-xs text-zinc-400">({{ number_format($test->bullet_weight * 0.06479891, 2) }}g)</span>
                            @endif
                            {{ $test->bullet_type }}
                        </span>
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
                @if($test->loadData)
                    <div>
                        <span class="text-zinc-500">Load:</span>
                        <a href="{{ route('load-data.show', $test->loadData) }}" wire:navigate class="font-medium text-nrapa-blue hover:text-nrapa-blue-dark">{{ $test->loadData->display_name }}</a>
                    </div>
                @endif
            </div>
            @if(!$test->bullet_make && !$test->powder_type && !$test->primer_type && !$test->userFirearm)
                <p class="text-sm text-zinc-400 italic">No components linked. Click Edit to add them.</p>
            @endif
            @if($test->notes)
                <p class="mt-3 text-sm text-zinc-500">{{ $test->notes }}</p>
            @endif
        @endif
    </div>

    <!-- CSV Import Panel -->
    @if($showImportForm)
        <div class="mb-6 rounded-lg border border-nrapa-blue/30 bg-nrapa-blue-light dark:bg-nrapa-blue/5 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Import Results from CSV</h2>
                <button wire:click="$set('showImportForm', false)" class="text-zinc-400 hover:text-zinc-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mb-4 p-3 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">CSV format (first row is the header):</p>
                <code class="block text-xs bg-zinc-100 dark:bg-zinc-900 p-2 rounded font-mono text-zinc-700 dark:text-zinc-300 overflow-x-auto">
                    step,charge_weight,velocities,group_size_inches,notes<br>
                    1,{{ rtrim(rtrim($test->steps->first()?->charge_weight ?? '39.0', '0'), '.') }},"2745; 2752; 2748",0.75,Good consistency
                </code>
                <p class="text-xs text-zinc-500 mt-2">Velocities can be separated by semicolons or commas (use semicolons if your CSV editor uses commas as column separator).</p>
                <button wire:click="downloadCsvTemplate"
                        class="mt-3 inline-flex items-center gap-1 rounded-lg border border-nrapa-blue text-nrapa-blue px-3 py-1.5 text-xs font-medium hover:bg-nrapa-blue hover:text-white transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download Template CSV
                </button>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Upload CSV File</label>
                <input type="file" wire:model="csvFile" accept=".csv,.txt"
                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white file:mr-3 file:rounded file:border-0 file:bg-nrapa-blue file:px-3 file:py-1 file:text-xs file:font-medium file:text-white">
                @error('csvFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if(count($importErrors) > 0)
                <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                    <p class="text-sm font-medium text-red-700 dark:text-red-300 mb-1">Warnings:</p>
                    @foreach($importErrors as $error)
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if(count($importPreview) > 0)
                <div class="mb-4">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Preview ({{ count($importPreview) }} steps found):</p>
                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-xs">
                            <thead class="bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-3 py-2 text-left text-zinc-500">Step</th>
                                    <th class="px-3 py-2 text-left text-zinc-500">Charge</th>
                                    <th class="px-3 py-2 text-left text-zinc-500">Velocities</th>
                                    <th class="px-3 py-2 text-left text-zinc-500">Avg</th>
                                    <th class="px-3 py-2 text-left text-zinc-500">ES</th>
                                    <th class="px-3 py-2 text-left text-zinc-500">SD</th>
                                    <th class="px-3 py-2 text-left text-zinc-500">Group</th>
                                    <th class="px-3 py-2 text-left text-zinc-500">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach($importPreview as $row)
                                    <tr>
                                        <td class="px-3 py-1.5 font-medium">{{ $row['step_number'] }}</td>
                                        <td class="px-3 py-1.5">{{ $row['charge_weight'] }}{{ $test->unit_label }}</td>
                                        <td class="px-3 py-1.5">{{ $row['vel_count'] }} shots</td>
                                        <td class="px-3 py-1.5">{{ $row['avg_vel'] ?? '—' }}</td>
                                        <td class="px-3 py-1.5">{{ $row['es'] ?? '—' }}</td>
                                        <td class="px-3 py-1.5 {{ ($row['sd'] ?? 99) <= 10 ? 'text-green-600' : '' }}">{{ $row['sd'] ?? '—' }}</td>
                                        <td class="px-3 py-1.5">{{ $row['group_size'] !== null ? $row['group_size'] . '"' : '—' }}</td>
                                        <td class="px-3 py-1.5 text-zinc-500 truncate max-w-32">{{ $row['notes'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button wire:click="importResults"
                            class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                        Import {{ count($importPreview) }} Steps
                    </button>
                    <p class="text-xs text-zinc-500">This will overwrite any existing results for the matched steps.</p>
                </div>
            @endif
        </div>
    @endif

    <!-- Velocity / SD / ES Line Graph -->
    @if($hasAnyResults)
        <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6"
             x-data="ladderChart({{ Js::from($chartData) }}, {{ Js::from($test->unit_label) }}, {{ Js::from($test->type_label) }})"
             wire:key="ladder-chart-{{ md5(json_encode($chartData)) }}">
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

    <!-- Unit Preferences & Steps -->
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Step Results</h2>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-xs font-medium text-zinc-500">Velocity:</label>
                <select wire:model.live="velocity_unit"
                        class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-2 py-1 text-xs text-zinc-900 dark:text-white">
                    <option value="fps">fps</option>
                    <option value="ms">m/s</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs font-medium text-zinc-500">Group Size:</label>
                <select wire:model.live="group_unit"
                        class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-2 py-1 text-xs text-zinc-900 dark:text-white">
                    <option value="in">inches</option>
                    <option value="mm">mm</option>
                </select>
            </div>
        </div>
    </div>
    <div class="space-y-4">
        @foreach($steps as $step)
            <div class="rounded-lg border {{ $bestStepId === $step->id ? 'border-green-400 dark:border-green-600 bg-green-50/50 dark:bg-green-900/10' : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800' }} p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $bestStepId === $step->id ? 'bg-green-500 text-white' : 'bg-nrapa-blue text-white' }} text-sm font-bold">
                            {{ $step->step_number }}
                        </span>
                        <div>
                            <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ rtrim(rtrim($step->charge_weight, '0'), '.') }}{{ $test->unit_label }}</span>
                            @if($bestStepId === $step->id)
                                <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Best SD</span>
                            @endif
                        </div>
                    </div>
                    @if($step->has_results)
                        <div class="flex items-center gap-4 text-sm">
                            @if($step->average_velocity)
                                <span class="text-zinc-500">Avg: <strong class="text-zinc-900 dark:text-white">
                                    @if($velocity_unit === 'ms')
                                        {{ number_format($step->average_velocity * 0.3048, 1) }} m/s
                                    @else
                                        {{ $step->average_velocity }} fps
                                    @endif
                                </strong></span>
                            @endif
                            @if($step->es !== null)
                                <span class="text-zinc-500">ES: <strong class="text-zinc-900 dark:text-white">
                                    @if($velocity_unit === 'ms')
                                        {{ number_format($step->es * 0.3048, 1) }}
                                    @else
                                        {{ $step->es }}
                                    @endif
                                </strong></span>
                            @endif
                            @if($step->sd !== null)
                                <span class="text-zinc-500">SD: <strong class="{{ $step->sd <= 10 ? 'text-green-600' : ($step->sd <= 20 ? 'text-amber-600' : 'text-red-600') }}">{{ $step->sd }}</strong></span>
                            @endif
                            @if($step->group_size)
                                <span class="text-zinc-500">Group: <strong class="text-zinc-900 dark:text-white">
                                    @if($group_unit === 'mm')
                                        {{ number_format($step->group_size * 25.4, 1) }}mm
                                    @else
                                        {{ $step->group_size }}"
                                    @endif
                                </strong></span>
                            @endif
                        </div>
                    @endif
                </div>

                @if(session('step_saved_' . $step->id))
                    <div class="mb-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-3 py-2 text-xs text-green-700 dark:text-green-300">
                        {{ session('step_saved_' . $step->id) }}
                    </div>
                @endif

                @php $isEditing = isset($editingSteps[$step->id]); @endphp

                @if($step->has_results && !$isEditing)
                    {{-- Read-only display for saved results --}}
                    <div class="space-y-3">
                    {{-- Shot velocities --}}
                    @if($step->velocities && count($step->velocities) > 0)
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1.5">Shot Velocities ({{ $velocity_unit === 'ms' ? 'm/s' : 'fps' }})</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($step->velocities as $i => $vel)
                                <div class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-3 py-1.5 text-sm">
                                    <span class="text-[10px] font-semibold text-zinc-400">{{ $i + 1 }}</span>
                                    <span class="font-medium text-zinc-900 dark:text-white">
                                        @if($velocity_unit === 'ms')
                                            {{ number_format($vel * 0.3048, 1) }}
                                        @else
                                            {{ $vel }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- ES / SD / Group Size row --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">ES</label>
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-3 py-1.5 text-sm font-semibold text-zinc-900 dark:text-white">
                                @if($step->es !== null)
                                    @if($velocity_unit === 'ms')
                                        {{ number_format($step->es * 0.3048, 1) }}
                                    @else
                                        {{ $step->es }}
                                    @endif
                                @else
                                    <span class="text-zinc-400">&mdash;</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">SD</label>
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-3 py-1.5 text-sm font-semibold {{ $step->sd !== null ? ($step->sd <= 10 ? 'text-green-600' : ($step->sd <= 20 ? 'text-amber-600' : 'text-red-600')) : '' }}">
                                @if($step->sd !== null)
                                    {{ $step->sd }}
                                @else
                                    <span class="text-zinc-400">&mdash;</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Group Size</label>
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                                @if($step->group_size)
                                    @if($group_unit === 'mm')
                                        {{ number_format($step->group_size * 25.4, 1) }}mm
                                    @else
                                        {{ $step->group_size }}"
                                    @endif
                                @else
                                    <span class="text-zinc-400">&mdash;</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    </div>
                    {{-- Notes row + actions --}}
                    <div class="flex items-center gap-3">
                        @if($step->notes)
                            <span class="text-xs text-zinc-500"><strong class="font-medium">Notes:</strong> {{ $step->notes }}</span>
                        @endif
                        <div class="ml-auto flex items-center gap-2">
                            <button wire:click="editStep({{ $step->id }})"
                                    class="rounded-lg border border-nrapa-blue/40 px-3 py-1.5 text-xs font-medium text-nrapa-blue hover:bg-nrapa-blue/5 dark:text-nrapa-blue-light dark:hover:bg-nrapa-blue/10 whitespace-nowrap">
                                Edit
                            </button>
                            <button wire:click="clearStepResults({{ $step->id }})" wire:confirm="Clear all results for Step {{ $step->step_number }}? This cannot be undone."
                                    class="rounded-lg border border-red-300 dark:border-red-600 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 whitespace-nowrap">
                                Clear
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Editable form: first entry or edit mode --}}
                    @php $roundsPerStep = max(1, $test->rounds_per_step ?? 3); @endphp
                    <div class="space-y-3">
                        {{-- Individual velocity inputs --}}
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1.5">
                                Shot Velocities ({{ $velocity_unit === 'ms' ? 'm/s' : 'fps' }})
                            </label>
                            <div class="flex flex-wrap gap-2">
                                @for($i = 0; $i < $roundsPerStep; $i++)
                                    <div class="flex-1 min-w-[80px] max-w-[120px]">
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] font-semibold text-zinc-400">{{ $i + 1 }}</span>
                                            <input type="number" wire:model="stepVelocities.{{ $step->id }}.{{ $i }}"
                                                   step="{{ $velocity_unit === 'ms' ? '0.1' : '1' }}" min="0"
                                                   placeholder="{{ $velocity_unit === 'ms' ? '838' : '2750' }}"
                                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 pl-6 pr-2 py-1.5 text-sm text-zinc-900 dark:text-white text-center">
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        {{-- Group size + Notes + Actions --}}
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 mb-1">
                                    Group Size ({{ $group_unit === 'mm' ? 'mm' : 'inches' }})
                                    <span class="text-zinc-400 font-normal">- optional</span>
                                </label>
                                <input type="number" wire:model="stepGroupSize.{{ $step->id }}" step="{{ $group_unit === 'mm' ? '0.1' : '0.01' }}" min="0" placeholder="{{ $group_unit === 'mm' ? 'e.g., 19.1' : 'e.g., 0.75' }}"
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 mb-1">Notes <span class="text-zinc-400 font-normal">- optional</span></label>
                                <input type="text" wire:model="stepNotes.{{ $step->id }}" placeholder="Notes..."
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                            </div>
                            <div class="flex items-end gap-2">
                                <button wire:click="saveStepResults({{ $step->id }})"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        class="rounded-lg bg-nrapa-blue px-4 py-1.5 text-xs font-medium text-white hover:bg-nrapa-blue-dark whitespace-nowrap">
                                    <span wire:loading.remove wire:target="saveStepResults({{ $step->id }})">Save</span>
                                    <span wire:loading wire:target="saveStepResults({{ $step->id }})">Saving...</span>
                                </button>
                                @if($isEditing)
                                    <button wire:click="cancelEditStep({{ $step->id }})"
                                            class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 whitespace-nowrap">
                                        Cancel
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

@assets
<script>
// Load Chart.js from CDN with fallback retry
(function() {
    if (window.Chart) return;
    function loadChartJs(attempt) {
        if (attempt > 3) return;
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
        s.onload = function() { window._chartJsLoaded = true; };
        s.onerror = function() { setTimeout(function() { loadChartJs(attempt + 1); }, 1000); };
        document.head.appendChild(s);
    }
    loadChartJs(1);
})();
</script>
@endassets

@script
<script>
Alpine.data('ladderChart', (initialData, unitLabel, typeLabel) => ({
    chartInstance: null,
    retryCount: 0,
    _chartData: initialData,
    _unitLabel: unitLabel || 'gr',
    _typeLabel: typeLabel || 'Powder Charge',

    init() {
        // Wait for Chart.js to load, then render
        this.$nextTick(() => this.waitForChartJs());
    },

    waitForChartJs() {
        if (window.Chart) {
            this.renderChart();
        } else if (this.retryCount < 50) {
            this.retryCount++;
            setTimeout(() => this.waitForChartJs(), 100);
        } else {
            console.error('Chart.js failed to load after 5 seconds');
        }
    },

    renderChart() {
        const canvas = this.$refs.ladderCanvas;
        if (!canvas || !window.Chart || !this._chartData) return;

        // Destroy previous instance
        if (this.chartInstance) {
            this.chartInstance.destroy();
            this.chartInstance = null;
        }

        const data = this._chartData;
        const unit = this._unitLabel;
        const labels = data.map(d => d.charge + unit);

        // Velocity data (left Y axis)
        const velocityData = data.map(d => d.avgVelocity);
        const hasVelocity = velocityData.some(v => v !== null && v !== undefined);

        // SD data (right Y axis)
        const sdData = data.map(d => d.sd);
        const hasSd = sdData.some(v => v !== null && v !== undefined);

        // ES data (right Y axis)
        const esData = data.map(d => d.es);
        const hasEs = esData.some(v => v !== null && v !== undefined);

        // Group size data (separate right axis)
        const groupData = data.map(d => d.groupSize);
        const hasGroup = groupData.some(v => v !== null && v !== undefined);

        if (!hasVelocity && !hasSd && !hasEs && !hasGroup) return;

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
                title: { display: true, text: this._typeLabel + ' (' + this._unitLabel + ')', color: textColor, font: { weight: '600' } },
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

        try {
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
                                title: (items) => this._typeLabel + ': ' + items[0].label,
                            }
                        },
                    },
                    scales,
                },
            });
        } catch (e) {
            console.error('Chart.js render error:', e);
        }
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
