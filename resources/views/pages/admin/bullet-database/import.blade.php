<?php

use App\Models\Bullet;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Import Bullets - Admin')] class extends Component {
    use WithFileUploads;

    public $file = null;
    public string $format = 'csv';
    public bool $autoConvertUnits = true;
    public array $preview = [];
    public array $errors = [];
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public bool $showResults = false;

    public function parseFile(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $this->preview = [];
        $this->errors = [];

        $path = $this->file->getRealPath();
        $content = file_get_contents($path);

        if ($this->format === 'json') {
            $rows = json_decode($content, true);
            if (!is_array($rows)) {
                $this->errors[] = ['row' => 0, 'message' => 'Invalid JSON format.'];
                return;
            }
        } else {
            $lines = array_filter(explode("\n", $content));
            if (count($lines) < 2) {
                $this->errors[] = ['row' => 0, 'message' => 'CSV must have a header row and at least one data row.'];
                return;
            }
            $headers = str_getcsv(array_shift($lines));
            $headers = array_map('trim', $headers);
            $rows = [];
            foreach ($lines as $i => $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $values = str_getcsv($line);
                if (count($values) !== count($headers)) {
                    $this->errors[] = ['row' => $i + 2, 'message' => 'Column count mismatch. Expected ' . count($headers) . ', got ' . count($values)];
                    continue;
                }
                $rows[] = array_combine($headers, $values);
            }
        }

        // Validate each row
        $required = ['manufacturer', 'brand_line', 'bullet_label', 'caliber_label', 'weight_gr', 'construction', 'intended_use', 'source_url'];

        foreach ($rows as $i => $row) {
            $rowErrors = [];

            foreach ($required as $field) {
                if (empty($row[$field] ?? '')) {
                    $rowErrors[] = "Missing required field: {$field}";
                }
            }

            // Check diameter: need at least one
            $hasIn = !empty($row['diameter_in'] ?? '');
            $hasMm = !empty($row['diameter_mm'] ?? '');
            if (!$hasIn && !$hasMm) {
                // Try to derive from caliber_label
                $dims = Bullet::diameterForCaliber($row['caliber_label'] ?? '');
                if ($dims && $this->autoConvertUnits) {
                    $row['diameter_in'] = $dims['in'];
                    $row['diameter_mm'] = $dims['mm'];
                    $rows[$i] = $row;
                } else {
                    $rowErrors[] = 'Missing diameter (in or mm) and cannot derive from caliber.';
                }
            } elseif ($hasIn && !$hasMm && $this->autoConvertUnits) {
                $row['diameter_mm'] = Bullet::inToMm((float) $row['diameter_in']);
                $rows[$i] = $row;
            } elseif ($hasMm && !$hasIn && $this->autoConvertUnits) {
                $row['diameter_in'] = Bullet::mmToIn((float) $row['diameter_mm']);
                $rows[$i] = $row;
            }

            // Auto-convert length if one side given
            if ($this->autoConvertUnits) {
                if (!empty($row['length_in'] ?? '') && empty($row['length_mm'] ?? '')) {
                    $row['length_mm'] = Bullet::inToMm((float) $row['length_in']);
                    $rows[$i] = $row;
                } elseif (!empty($row['length_mm'] ?? '') && empty($row['length_in'] ?? '')) {
                    $row['length_in'] = Bullet::mmToIn((float) $row['length_mm']);
                    $rows[$i] = $row;
                }
            }

            if (!empty($rowErrors)) {
                $this->errors[] = ['row' => $i + 2, 'message' => implode('; ', $rowErrors)];
            }
        }

        $this->preview = array_slice($rows, 0, 100); // Show first 100 for preview
    }

    public function import(): void
    {
        if (empty($this->preview)) {
            return;
        }

        $this->importedCount = 0;
        $this->skippedCount = 0;
        $now = now();

        foreach ($this->preview as $row) {
            try {
                Bullet::updateOrCreate(
                    [
                        'manufacturer' => $row['manufacturer'],
                        'brand_line' => $row['brand_line'],
                        'caliber_label' => $row['caliber_label'],
                        'weight_gr' => (int) $row['weight_gr'],
                        'sku_or_part_no' => $row['sku_or_part_no'] ?? null,
                        'twist_note' => $row['twist_note'] ?? null,
                        'bc_reference' => $row['bc_reference'] ?? null,
                    ],
                    [
                        'bullet_label' => $row['bullet_label'],
                        'diameter_in' => (float) $row['diameter_in'],
                        'diameter_mm' => (float) $row['diameter_mm'],
                        'length_in' => !empty($row['length_in']) ? (float) $row['length_in'] : null,
                        'length_mm' => !empty($row['length_mm']) ? (float) $row['length_mm'] : null,
                        'bc_g1' => !empty($row['bc_g1']) ? (float) $row['bc_g1'] : null,
                        'bc_g7' => !empty($row['bc_g7']) ? (float) $row['bc_g7'] : null,
                        'bc_reference' => $row['bc_reference'] ?? null,
                        'construction' => $row['construction'],
                        'intended_use' => $row['intended_use'],
                        'twist_note' => $row['twist_note'] ?? null,
                        'sku_or_part_no' => $row['sku_or_part_no'] ?? null,
                        'source_url' => $row['source_url'],
                        'status' => $row['status'] ?? 'active',
                        'last_verified_at' => $row['last_verified_at'] ?? $now,
                    ]
                );
                $this->importedCount++;
            } catch (\Exception $e) {
                $this->skippedCount++;
            }
        }

        $this->showResults = true;
        $this->preview = [];
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = ['manufacturer', 'brand_line', 'bullet_label', 'caliber_label', 'weight_gr', 'diameter_in', 'diameter_mm', 'length_in', 'length_mm', 'bc_g1', 'bc_g7', 'bc_reference', 'construction', 'intended_use', 'twist_note', 'sku_or_part_no', 'source_url', 'status', 'last_verified_at'];
        $examples = [
            ['Hornady', 'ELD Match', '6.5mm 140 gr ELD Match', '6.5mm', '140', '0.264', '6.706', '1.376', '34.950', '0.646', '0.326', 'Mach 2.25', 'cup_and_core', 'match', '1:8"', '2634', 'https://www.hornady.com/bullets/eld-match', 'active', ''],
            ['Sierra', 'MatchKing', '30 Cal 175 gr HPBT MatchKing', '30 Cal', '175', '0.308', '7.823', '1.240', '31.496', '0.505', '0.264', '', 'otm', 'match', '1:10"', '2275', 'https://www.sierrabullets.com/product/30-caliber-175-gr-hpbt-matchking', 'active', ''],
            ['Barnes', 'TTSX', '30 Cal 168 gr TTSX BT', '30 Cal', '168', '0.308', '7.823', '', '', '0.470', '', '', 'monolithic_copper', 'hunting', '1:10"', '30846', 'https://www.barnesbullets.com/bullets/ttsx', 'active', ''],
            ['Nosler', 'AccuBond', '7mm 160 gr AccuBond', '7mm', '160', '0.284', '7.214', '', '', '0.531', '0.270', '', 'bonded', 'hunting', '1:9"', '54932', 'https://www.nosler.com/accubond', 'active', ''],
            ['Berger', 'Hybrid Target', '6.5mm 140 gr Hybrid Target', '6.5mm', '140', '0.264', '6.706', '1.376', '34.950', '0.607', '0.311', '', 'otm', 'match', '1:8"', '26414', 'https://www.bergerbullets.com/products/6-5mm-140-grain-hybrid-target', 'active', ''],
            ['Peregrine', 'VRG3', '30 Cal 180 gr VRG3', '30 Cal', '180', '0.308', '7.823', '', '', '0.480', '', '', 'monolithic_copper', 'hunting', '1:10"', '', 'https://www.peregrinebullets.com/vrg3', 'active', ''],
        ];

        return response()->streamDownload(function () use ($headers, $examples) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($examples as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'bullets-import-template.csv', ['Content-Type' => 'text/csv']);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 p-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Import Bullets</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Import bullet data from file</p>
    </x-slot>
    
    <div>
        <a href="{{ route('admin.bullet-database.index') }}" wire:navigate class="text-zinc-400 hover:text-zinc-600 transition-colors">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
    </div>

    @if($showResults)
    <div class="rounded-xl border border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-800 p-4">
        <h3 class="font-semibold text-emerald-800 dark:text-emerald-200">Import Complete</h3>
        <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-1">{{ $importedCount }} bullets imported/updated, {{ $skippedCount }} skipped.</p>
        <a href="{{ route('admin.bullet-database.index') }}" wire:navigate class="mt-2 inline-block text-sm text-nrapa-blue hover:underline">View Database</a>
    </div>
    @endif

    <div class="max-w-4xl space-y-6">
        {{-- Upload --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Upload File</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Format</label>
                    <select wire:model="format" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">File</label>
                    <input type="file" wire:model="file" accept=".csv,.json,.txt"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end gap-2">
                    <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <input type="checkbox" wire:model="autoConvertUnits" class="rounded border-zinc-300">
                        Auto-convert units
                    </label>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button wire:click="parseFile" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors" @if(!$file) disabled @endif>
                    Parse & Preview
                </button>
                <button wire:click="downloadTemplate" class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download CSV Template (6 examples)
                </button>
            </div>
        </div>

        {{-- Format Reference --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">CSV Format Reference</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <h3 class="font-medium text-zinc-700 dark:text-zinc-300 mb-1">Required Columns</h3>
                    <ul class="space-y-0.5 text-zinc-600 dark:text-zinc-400">
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">manufacturer</code> — e.g. Hornady, Sierra, Barnes</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">brand_line</code> — e.g. ELD Match, MatchKing, TTSX</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">bullet_label</code> — Full description</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">caliber_label</code> — e.g. 6.5mm, 30 Cal, 7mm</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">weight_gr</code> — Weight in grains</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">construction</code> — See valid values below</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">intended_use</code> — See valid values below</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">source_url</code> — Manufacturer product URL</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-medium text-zinc-700 dark:text-zinc-300 mb-1">Optional Columns</h3>
                    <ul class="space-y-0.5 text-zinc-600 dark:text-zinc-400">
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">diameter_in</code> / <code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">diameter_mm</code> — Auto-converts if one given</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">length_in</code> / <code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">length_mm</code> — Auto-converts if one given</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">bc_g1</code> / <code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">bc_g7</code> — Ballistic coefficients</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">bc_reference</code> — e.g. Mach 2.25</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">twist_note</code> — e.g. 1:8", 1:10"</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">sku_or_part_no</code> — Manufacturer SKU</li>
                        <li><code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1 rounded">status</code> — active, discontinued, unknown</li>
                    </ul>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <h3 class="font-medium text-zinc-700 dark:text-zinc-300 mb-1">Construction Values</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        @foreach(App\Models\Bullet::constructionTypes() as $key => $label)
                            <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{{ $key }}</code>{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </p>
                </div>
                <div>
                    <h3 class="font-medium text-zinc-700 dark:text-zinc-300 mb-1">Intended Use Values</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        @foreach(App\Models\Bullet::intendedUses() as $key => $label)
                            <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{{ $key }}</code>{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </p>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="font-medium text-zinc-700 dark:text-zinc-300 mb-1 text-sm">Known Caliber Labels (auto-diameter)</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    @foreach(array_keys(App\Models\Bullet::caliberDiameters()) as $cal)
                        <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{{ $cal }}</code>{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
            </div>
        </div>

        {{-- Errors --}}
        @if(count($errors) > 0)
        <div class="rounded-xl border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
            <h3 class="font-semibold text-red-800 dark:text-red-200 mb-2">Validation Errors ({{ count($errors) }})</h3>
            <div class="max-h-48 overflow-y-auto space-y-1">
                @foreach($errors as $err)
                <p class="text-sm text-red-700 dark:text-red-300">Row {{ $err['row'] }}: {{ $err['message'] }}</p>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Preview --}}
        @if(count($preview) > 0)
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Preview ({{ count($preview) }} rows)</h2>
                <button wire:click="import" wire:confirm="Import {{ count($preview) }} bullets? Existing matches will be updated."
                        class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                    Import {{ count($preview) }} Bullets
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-xs">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 text-left font-medium text-zinc-500">Manufacturer</th>
                            <th class="px-2 py-1 text-left font-medium text-zinc-500">Line</th>
                            <th class="px-2 py-1 text-left font-medium text-zinc-500">Label</th>
                            <th class="px-2 py-1 text-left font-medium text-zinc-500">Cal</th>
                            <th class="px-2 py-1 text-left font-medium text-zinc-500">Wt</th>
                            <th class="px-2 py-1 text-left font-medium text-zinc-500">Dia"</th>
                            <th class="px-2 py-1 text-left font-medium text-zinc-500">G1</th>
                            <th class="px-2 py-1 text-left font-medium text-zinc-500">G7</th>
                            <th class="px-2 py-1 text-left font-medium text-zinc-500">Const.</th>
                            <th class="px-2 py-1 text-left font-medium text-zinc-500">Use</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                        @foreach($preview as $row)
                        <tr>
                            <td class="px-2 py-1 text-zinc-900 dark:text-white">{{ $row['manufacturer'] ?? '' }}</td>
                            <td class="px-2 py-1 text-zinc-600">{{ $row['brand_line'] ?? '' }}</td>
                            <td class="px-2 py-1 text-zinc-600">{{ $row['bullet_label'] ?? '' }}</td>
                            <td class="px-2 py-1 text-zinc-600">{{ $row['caliber_label'] ?? '' }}</td>
                            <td class="px-2 py-1 text-zinc-900 dark:text-white font-medium">{{ $row['weight_gr'] ?? '' }}</td>
                            <td class="px-2 py-1 text-zinc-600">{{ $row['diameter_in'] ?? '' }}</td>
                            <td class="px-2 py-1 text-zinc-600">{{ $row['bc_g1'] ?? '—' }}</td>
                            <td class="px-2 py-1 text-zinc-600">{{ $row['bc_g7'] ?? '—' }}</td>
                            <td class="px-2 py-1 text-zinc-600">{{ $row['construction'] ?? '' }}</td>
                            <td class="px-2 py-1 text-zinc-600">{{ $row['intended_use'] ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
