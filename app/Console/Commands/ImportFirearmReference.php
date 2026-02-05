<?php

namespace App\Console\Commands;

use App\Models\FirearmCalibre;
use App\Models\FirearmCalibreAlias;
use App\Models\FirearmMake;
use App\Models\FirearmModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportFirearmReference extends Command
{
    protected $signature = 'nrapa:import-firearm-reference 
                            {--file= : Specific CSV file to import (calibres|aliases|makes|models)}
                            {--force : Force re-import even if data exists}';

    protected $description = 'Import firearm reference data from CSV files (idempotent)';

    public function handle(): int
    {
        $this->info('Starting firearm reference data import...');

        $fileOption = $this->option('file');
        $force = $this->option('force');

        $files = $fileOption 
            ? [$fileOption] 
            : ['calibres', 'aliases', 'makes', 'models'];

        foreach ($files as $file) {
            $this->importFile($file, $force);
        }

        $this->info('Import completed successfully!');
        return Command::SUCCESS;
    }

    protected function importFile(string $type, bool $force): void
    {
        // Map type to actual CSV filename
        $fileMap = [
            'calibres' => 'calibres.csv',
            'aliases' => 'calibre_aliases.csv',
            'makes' => 'firearm_makes.csv',
            'models' => 'firearm_models.csv',
        ];

        $filename = $fileMap[$type] ?? "{$type}.csv";
        $csvPath = resource_path("data/{$filename}");

        if (!file_exists($csvPath)) {
            $this->warn("CSV file not found: {$csvPath}");
            return;
        }

        $this->info("Importing {$type}...");

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->error("Could not open {$csvPath}");
            return;
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            $this->error("Could not read headers from {$csvPath}");
            fclose($handle);
            return;
        }

        $count = 0;
        $updated = 0;
        $created = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($headers)) {
                    $this->warn("Skipping malformed row: " . implode(',', $row));
                    $skipped++;
                    continue;
                }

                $data = array_combine($headers, $row);

                match($type) {
                    'calibres' => $this->importCalibre($data, $force, $created, $updated, $skipped),
                    'aliases' => $this->importAlias($data, $force, $created, $updated, $skipped),
                    'makes' => $this->importMake($data, $force, $created, $updated, $skipped),
                    'models' => $this->importModel($data, $force, $created, $updated, $skipped),
                };

                $count++;
            }

            DB::commit();

            $this->info("  Processed: {$count} rows");
            $this->info("  Created: {$created}");
            $this->info("  Updated: {$updated}");
            if ($skipped > 0) {
                $this->warn("  Skipped: {$skipped}");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error importing {$type}: " . $e->getMessage());
            throw $e;
        } finally {
            fclose($handle);
        }
    }

    protected function importCalibre(array $data, bool $force, int &$created, int &$updated, int &$skipped): void
    {
        // Handle ignition: if category is 'rimfire', set ignition=rimfire and category=rifle
        // Otherwise, set ignition based on explicit ignition field or default to centerfire
        if (isset($data['category']) && $data['category'] === 'rimfire') {
            $data['ignition'] = 'rimfire';
            $data['category'] = 'rifle'; // Default to rifle, can be updated manually if also used in handguns
        } elseif (empty($data['ignition'])) {
            // If ignition not explicitly set, default to centerfire
            $data['ignition'] = 'centerfire';
        }
        
        // Normalize boolean fields
        $data['is_wildcat'] = $this->parseBoolean($data['is_wildcat'] ?? false);
        $data['is_obsolete'] = $this->parseBoolean($data['is_obsolete'] ?? false);
        $data['is_active'] = $this->parseBoolean($data['is_active'] ?? true);

        // Parse numeric fields
        $data['bullet_diameter_mm'] = !empty($data['bullet_diameter_mm']) ? (float)$data['bullet_diameter_mm'] : null;
        $data['case_length_mm'] = !empty($data['case_length_mm']) ? (float)$data['case_length_mm'] : null;

        // Parse tags JSON
        if (!empty($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = json_decode($data['tags'], true) ?? [];
        }

        // Normalize name if not provided
        if (empty($data['normalized_name'])) {
            $data['normalized_name'] = FirearmCalibre::normalize($data['name']);
        }

        // Check by name (unique constraint) first, then normalized_name
        $calibre = FirearmCalibre::where('name', $data['name'])
            ->orWhere('normalized_name', $data['normalized_name'])
            ->first();

        if ($calibre) {
            if ($force) {
                $calibre->update($data);
                $updated++;
            } else {
                $skipped++;
            }
        } else {
            try {
                FirearmCalibre::create($data);
                $created++;
            } catch (\Illuminate\Database\QueryException $e) {
                // If unique constraint violation, try to find and update
                if (str_contains($e->getMessage(), 'UNIQUE constraint')) {
                    $existing = FirearmCalibre::where('name', $data['name'])->first();
                    if ($existing) {
                        if ($force) {
                            $existing->update($data);
                            $updated++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
        }
    }

    protected function importAlias(array $data, bool $force, int &$created, int &$updated, int &$skipped): void
    {
        // Resolve calibre by ID or name
        $calibreId = $data['firearm_calibre_id'] ?? null;
        $calibreName = $data['calibre_name'] ?? null;
        
        $calibre = null;
        
        // Try to find by ID first
        if ($calibreId) {
            $calibre = FirearmCalibre::find($calibreId);
        }
        
        // If not found and we have a name, try to find by name
        if (!$calibre && $calibreName) {
            $calibre = FirearmCalibre::where('name', $calibreName)
                ->orWhere('normalized_name', FirearmCalibre::normalize($calibreName))
                ->first();
        }
        
        if (!$calibre) {
            $this->warn("Skipping alias - calibre not found: " . ($calibreName ?? "ID: {$calibreId}"));
            $skipped++;
            return;
        }
        
        $calibreId = $calibre->id;

        // Normalize alias
        if (empty($data['normalized_alias'])) {
            $data['normalized_alias'] = FirearmCalibre::normalize($data['alias']);
        }

        // Set the calibre ID
        $data['firearm_calibre_id'] = $calibreId;

        $alias = FirearmCalibreAlias::where('normalized_alias', $data['normalized_alias'])->first();

        if ($alias) {
            if ($force) {
                $alias->update($data);
                $updated++;
            } else {
                $skipped++;
            }
        } else {
            FirearmCalibreAlias::create($data);
            $created++;
        }
    }

    protected function importMake(array $data, bool $force, int &$created, int &$updated, int &$skipped): void
    {
        $data['is_active'] = $this->parseBoolean($data['is_active'] ?? true);

        if (empty($data['normalized_name'])) {
            $data['normalized_name'] = FirearmMake::normalize($data['name']);
        }

        // Check by name (unique constraint) first, then normalized_name
        $make = FirearmMake::where('name', $data['name'])
            ->orWhere('normalized_name', $data['normalized_name'])
            ->first();

        if ($make) {
            if ($force) {
                $make->update($data);
                $updated++;
            } else {
                $skipped++;
            }
        } else {
            try {
                FirearmMake::create($data);
                $created++;
            } catch (\Illuminate\Database\QueryException $e) {
                // If unique constraint violation, try to find and update
                if (str_contains($e->getMessage(), 'UNIQUE constraint')) {
                    $existing = FirearmMake::where('name', $data['name'])->first();
                    if ($existing) {
                        if ($force) {
                            $existing->update($data);
                            $updated++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
        }
    }

    protected function importModel(array $data, bool $force, int &$created, int &$updated, int &$skipped): void
    {
        $data['is_active'] = $this->parseBoolean($data['is_active'] ?? true);
        $makeId = $data['firearm_make_id'] ?? null;
        $makeName = $data['make_name'] ?? null;
        
        $make = null;
        
        // Try to find by ID first
        if ($makeId) {
            $make = FirearmMake::find($makeId);
        }
        
        // If not found and we have a name, try to find by name
        if (!$make && $makeName) {
            $make = FirearmMake::where('name', $makeName)
                ->orWhere('normalized_name', FirearmMake::normalize($makeName))
                ->first();
        }
        
        if (!$make) {
            $this->warn("Skipping model - make not found: " . ($makeName ?? "ID: {$makeId}"));
            $skipped++;
            return;
        }
        
        $data['firearm_make_id'] = $make->id;

        if (empty($data['normalized_name'])) {
            $data['normalized_name'] = FirearmModel::normalize($data['name']);
        }

        $model = FirearmModel::where('firearm_make_id', $data['firearm_make_id'])
            ->where('normalized_name', $data['normalized_name'])
            ->first();

        if ($model) {
            if ($force) {
                $model->update($data);
                $updated++;
            } else {
                $skipped++;
            }
        } else {
            FirearmModel::create($data);
            $created++;
        }
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }
        return (bool)$value;
    }
}
