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
     *
     * SAPS 350A is the sole source of truth for make names/codes.
     * Curated data (country) is applied as enrichment where a SAPS
     * entry can be matched. Non-SAPS makes are deactivated.
     */
    public function run(): void
    {
        // 1. Import SAPS 350A makes as the primary (and only) source
        $sapsCsvPath = resource_path('data/saps_makes.csv');
        if (File::exists($sapsCsvPath)) {
            $this->seedSapsMakes($sapsCsvPath);
        }

        // 2. Enrich SAPS makes with curated country data
        $makesCsvPath = resource_path('data/firearm_makes.csv');
        if (File::exists($makesCsvPath)) {
            $this->enrichWithCuratedData($makesCsvPath);
        }

        // 3. Deactivate any makes without a SAPS code
        $deactivated = FirearmMake::whereNull('saps_code')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        if ($deactivated > 0) {
            $this->command->info("Deactivated {$deactivated} non-SAPS makes.");
        }

        // 4. Seed models from CSV (only for active makes)
        $modelsCsvPath = resource_path('data/firearm_models.csv');
        if (File::exists($modelsCsvPath)) {
            $this->seedModelsFromCsv($modelsCsvPath);
        }
    }

    /**
     * Enrich SAPS makes with curated data (country of origin).
     * Only updates existing SAPS makes – never creates new makes.
     */
    protected function enrichWithCuratedData(string $csvPath): void
    {
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return;
        }

        $header = fgetcsv($handle);
        $enriched = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 4) continue;

            $name = trim($row[0]);
            $country = trim($row[2]) ?: null;
            if (!$country) continue;

            $normalizedName = FirearmMake::normalize($name);

            // Only enrich makes that have a SAPS code
            $make = FirearmMake::whereNotNull('saps_code')
                ->where(function ($q) use ($normalizedName, $name) {
                    $q->where('normalized_name', $normalizedName)
                      ->orWhereRaw('LOWER(name) = ?', [strtolower($name)]);
                })
                ->whereNull('country')
                ->first();

            if ($make) {
                $make->update(['country' => $country]);
                $enriched++;
            }
        }

        fclose($handle);
        $this->command->info("Enriched {$enriched} SAPS makes with country data.");
    }

    /**
     * Import SAPS 350A makes as the sole source.
     * Creates new makes or updates existing ones with the SAPS code.
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
                // Existing make found – set SAPS code and ensure active
                $updates = ['is_active' => true];
                if (empty($existing[$normalizedName]['saps_code'])) {
                    $updates['saps_code'] = $sapsCode;
                }
                FirearmMake::where('id', $existing[$normalizedName]['id'])
                    ->update($updates);
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
                        ->update(['saps_code' => $sapsCode, 'is_active' => true]);
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
