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
        // First, seed makes from CSV
        $makesCsvPath = resource_path('data/firearm_makes.csv');
        if (File::exists($makesCsvPath)) {
            $this->seedMakesFromCsv($makesCsvPath);
        }

        // Then seed models from CSV
        $modelsCsvPath = resource_path('data/firearm_models.csv');
        if (File::exists($modelsCsvPath)) {
            $this->seedModelsFromCsv($modelsCsvPath);
        }
    }

    /**
     * Seed firearm makes from CSV file.
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

            FirearmMake::updateOrCreate(
                ['normalized_name' => $normalizedName],
                $data
            );
            $count++;
        }

        fclose($handle);
        $this->command->info("Seeded {$count} firearm makes from CSV.");
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
