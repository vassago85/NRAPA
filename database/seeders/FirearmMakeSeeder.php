<?php

namespace Database\Seeders;

use App\Models\FirearmMake;
use App\Models\FirearmModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class FirearmMakeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed our curated makes (preserves country data)
        $makesCsvPath = resource_path('data/firearm_makes.csv');
        if (File::exists($makesCsvPath)) {
            $this->seedMakesFromCsv($makesCsvPath);
        }

        // 2. Import SAPS 350A makes (adds saps_code, creates new entries)
        $sapsCsvPath = resource_path('data/saps_makes.csv');
        if (File::exists($sapsCsvPath)) {
            $this->seedSapsMakes($sapsCsvPath);
        }

        // 3. Seed models from CSV
        $modelsCsvPath = resource_path('data/firearm_models.csv');
        if (File::exists($modelsCsvPath)) {
            $this->seedModelsFromCsv($modelsCsvPath);
        }
    }

    /**
     * Seed firearm makes from our curated CSV file.
     */
    protected function seedMakesFromCsv(string $csvPath): void
    {
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return;
        }

        // Skip header row
        $header = fgetcsv($handle);
        
        $count = 0;
        $seen = []; // Track normalized names to skip duplicates
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 4) continue;
            
            $name = trim($row[0]);
            $normalizedName = FirearmMake::normalize($name);
            
            // Skip if we've already seen this normalized name (handles duplicates in CSV)
            if (isset($seen[$normalizedName])) {
                continue;
            }
            $seen[$normalizedName] = true;
            
            $data = [
                'name' => $name,
                'normalized_name' => $normalizedName,
                'country' => trim($row[2]) ?: null,
                'is_active' => filter_var($row[3] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];

            // Try to find existing by name first (handles cases where stored
            // normalized_name differs from what normalize() produces now)
            $existing = FirearmMake::where('name', $name)->first()
                ?? FirearmMake::where('normalized_name', $normalizedName)->first();

            if ($existing) {
                $existing->update($data);
            } else {
                try {
                    FirearmMake::create($data);
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    // Case-insensitive name match – update existing record instead
                    FirearmMake::whereRaw('LOWER(name) = ?', [strtolower($name)])
                        ->update($data);
                }
            }
            $count++;
        }

        fclose($handle);
        $this->command->info("Seeded {$count} curated firearm makes.");
    }

    /**
     * Import SAPS 350A makes.
     * - Matches existing makes by normalized name and adds saps_code.
     * - Creates new makes from unmatched SAPS entries.
     */
    protected function seedSapsMakes(string $csvPath): void
    {
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return;
        }

        // Skip header row (Code,Description)
        $header = fgetcsv($handle);

        // Build lookup using normalize() on the actual name column.
        // The stored normalized_name may differ from what normalize() produces.
        $existing = [];
        foreach (FirearmMake::all(['id', 'name', 'saps_code']) as $make) {
            $key = FirearmMake::normalize($make->name);
            $existing[$key] = ['id' => $make->id, 'saps_code' => $make->saps_code];
        }

        $matched = 0;
        $created = 0;
        $seen = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) continue;

            $sapsCode = trim($row[0]);
            $sapsName = trim($row[1]);
            if (empty($sapsName)) continue;

            $normalizedName = FirearmMake::normalize($sapsName);

            // Skip duplicates within this CSV (some SAPS entries may share names)
            if (isset($seen[$normalizedName])) {
                continue;
            }
            $seen[$normalizedName] = true;

            if (isset($existing[$normalizedName])) {
                // Existing make found – just add the SAPS code
                if (empty($existing[$normalizedName]['saps_code'])) {
                    FirearmMake::where('id', $existing[$normalizedName]['id'])
                        ->update(['saps_code' => $sapsCode]);
                }
                $matched++;
            } else {
                // New make from SAPS – create it
                $displayName = $this->formatMakeName($sapsName);
                try {
                    FirearmMake::create([
                        'saps_code' => $sapsCode,
                        'name' => $displayName,
                        'normalized_name' => $normalizedName,
                        'country' => null,
                        'is_active' => true,
                    ]);
                    $existing[$normalizedName] = ['id' => null, 'saps_code' => $sapsCode];
                    $created++;
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    // Name already exists (case-insensitive match) – update saps_code instead
                    FirearmMake::whereRaw('LOWER(name) = ?', [strtolower($displayName)])
                        ->whereNull('saps_code')
                        ->update(['saps_code' => $sapsCode]);
                    $matched++;
                }
            }
        }

        fclose($handle);
        $this->command->info("SAPS makes: {$matched} matched, {$created} new entries created.");
    }

    /**
     * Format a SAPS make name for display.
     * Converts UPPERCASE to Title Case, preserving short acronyms.
     */
    protected function formatMakeName(string $name): string
    {
        // If 3 chars or fewer and all uppercase, keep as-is (likely acronym: CZ, FN, BSA, etc.)
        if (strlen($name) <= 3 && $name === strtoupper($name)) {
            return $name;
        }

        // Split into words and process each
        $words = explode(' ', $name);
        $formatted = [];

        foreach ($words as $word) {
            // Keep short all-uppercase words as-is (acronyms like FN, CZ, BSA, H&R, etc.)
            if (strlen($word) <= 3 && $word === strtoupper($word) && !str_contains($word, '(')) {
                $formatted[] = $word;
            }
            // Keep all-caps words that look like acronyms (no lowercase)
            elseif (preg_match('/^[A-Z&\/\.\-]+$/', $word) && strlen($word) <= 4) {
                $formatted[] = $word;
            }
            // Handle parenthesised words
            elseif (str_starts_with($word, '(')) {
                $inner = substr($word, 1);
                if (strlen($inner) <= 3 && $inner === strtoupper($inner)) {
                    $formatted[] = $word; // Keep short acronyms in parens
                } else {
                    $formatted[] = '(' . ucfirst(strtolower(rtrim($inner, ')'))) . (str_ends_with($word, ')') ? ')' : '');
                }
            }
            else {
                $formatted[] = ucfirst(strtolower($word));
            }
        }

        return implode(' ', $formatted);
    }

    /**
     * Seed firearm models from CSV file.
     */
    protected function seedModelsFromCsv(string $csvPath): void
    {
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return;
        }

        // Skip header row
        $header = fgetcsv($handle);
        
        $count = 0;
        $makeCache = []; // Cache make IDs for performance
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5) continue;
            
            $makeName = trim($row[0]);
            $modelName = trim($row[1]);
            
            // Get or cache the make ID
            if (!isset($makeCache[$makeName])) {
                $make = FirearmMake::where('name', $makeName)->first();
                if (!$make) {
                    // Create the make if it doesn't exist
                    $make = FirearmMake::create([
                        'name' => $makeName,
                        'normalized_name' => FirearmMake::normalize($makeName),
                        'is_active' => true,
                    ]);
                }
                $makeCache[$makeName] = $make->id;
            }
            $makeId = $makeCache[$makeName];
            
            $normalizedModelName = FirearmModel::normalize($modelName);
            
            $data = [
                'firearm_make_id' => $makeId,
                'name' => $modelName,
                'normalized_name' => $normalizedModelName,
                'category_hint' => trim($row[3]) ?: null,
                'is_active' => filter_var($row[4] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];

            // Use composite key for models: make_id + normalized_name
            FirearmModel::updateOrCreate(
                [
                    'firearm_make_id' => $makeId,
                    'normalized_name' => $normalizedModelName,
                ],
                $data
            );
            $count++;
        }

        fclose($handle);
        $this->command->info("Seeded {$count} firearm models from CSV.");
    }
}
