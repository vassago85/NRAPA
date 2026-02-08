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
        $example = ['Hornady', 'ELD Match', '6.5mm 140 gr ELD Match', '6.5mm', '140', '0.264', '6.706', '', '', '0.646', '0.326', 'Mach 2.25', 'cup_and_core', 'match', '', '', 'https://www.hornady.com/bc', 'active', ''];

        return response()->streamDownload(function () use ($headers, $example) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, $example);
            fclose($handle);
        }, 'bullets-import-template.csv', ['Content-Type' => 'text/csv']);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 p-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.bullet-database.index') }}" wire:navigate class="text-zinc-400 hover:text-zinc-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Import Bullets</h1>
    </div>

    @if($showResults)
    <div class="rounded-lg border border-green-300 bg-green-50 dark:bg-green-900/20 dark:border-green-800 p-4">
        <h3 class="font-semibold text-green-800 dark:text-green-200">Import Complete</h3>
        <p class="text-sm text-green-700 dark:text-green-300 mt-1">{{ $importedCount }} bullets imported/updated, {{ $skippedCount }} skipped.</p>
        <a href="{{ route('admin.bullet-database.index') }}" wire:navigate class="mt-2 inline-block text-sm text-nrapa-blue hover:underline">View Database</a>
    </div>
    @endif

    <div class="max-w-4xl space-y-6">
        {{-- Upload --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
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
                <button wire:click="parseFile" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark" @if(!$file) disabled @endif>
                    Parse & Preview
                </button>
                <button wire:click="downloadTemplate" class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                    Download CSV Template
                </button>
            </div>
        </div>

        {{-- Errors --}}
        @if(count($errors) > 0)
        <div class="rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
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
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Preview ({{ count($preview) }} rows)</h2>
                <button wire:click="import" wire:confirm="Import {{ count($preview) }} bullets? Existing matches will be updated."
                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
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
