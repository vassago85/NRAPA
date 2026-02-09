<?php

namespace Database\Seeders;

use App\Models\FirearmCalibre;
use App\Models\FirearmCalibreAlias;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class FirearmCalibreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * SAPS 350A is the sole source of truth for calibre names/codes.
     * Curated metadata (bullet diameter, case length, aliases) is applied
     * as enrichment where a SAPS entry can be matched.
     * Any calibres without a SAPS code are deactivated.
     */
    public function run(): void
    {
        // 1. Import SAPS 350A calibres as the primary (and only) source
        $sapsCsvPath = resource_path('data/saps_calibres.csv');
        if (File::exists($sapsCsvPath)) {
            $this->seedSapsCalibres($sapsCsvPath);
        }

        // 2. Enrich SAPS entries with curated metadata (diameters, aliases, etc.)
        $this->enrichWithCuratedMetadata();

        // 3. Deactivate any calibres that have no SAPS code
        $deactivated = FirearmCalibre::whereNull('saps_code')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        if ($deactivated > 0) {
            $this->command->info("Deactivated {$deactivated} non-SAPS calibres.");
        }
    }

    /**
     * Import SAPS 350A calibres as the sole source.
     * Creates new calibres or updates existing ones with the SAPS code.
     * Skips non-calibre entries (barrels, frames, components, etc.).
     */
    protected function seedSapsCalibres(string $csvPath): void
    {
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return;
        }

        // Skip header row (Code,Description,FirearmType)
        $header = fgetcsv($handle);

        // Build lookup using normalize() on the actual name column.
        $existing = [];
        foreach (FirearmCalibre::all(['id', 'name', 'saps_code']) as $calibre) {
            $key = FirearmCalibre::normalize($calibre->name);
            $existing[$key] = ['id' => $calibre->id, 'saps_code' => $calibre->saps_code];
        }

        $matched = 0;
        $created = 0;
        $skipped = 0;
        $seen = []; // Track normalized names to handle duplicates

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) continue;

            $sapsCode = trim($row[0]);
            $sapsName = trim($row[1]);
            if (empty($sapsName)) continue;

            // Skip non-calibre entries
            if ($this->shouldSkipSapsEntry($sapsName)) {
                $skipped++;
                continue;
            }

            $normalizedName = FirearmCalibre::normalize($sapsName);

            // First SAPS entry for this name wins (many duplicates exist)
            if (isset($seen[$normalizedName])) {
                continue;
            }
            $seen[$normalizedName] = $sapsCode;

            if (isset($existing[$normalizedName])) {
                // Existing calibre found – set SAPS code and ensure active
                $updates = ['is_active' => true];
                if (empty($existing[$normalizedName]['saps_code'])) {
                    $updates['saps_code'] = $sapsCode;
                }
                FirearmCalibre::where('id', $existing[$normalizedName]['id'])
                    ->update($updates);
                $matched++;
            } else {
                // New calibre from SAPS – create with inferred category
                $category = $this->inferCategory($sapsName);
                $ignition = $this->inferIgnition($sapsName);

                try {
                    FirearmCalibre::create([
                        'saps_code' => $sapsCode,
                        'name' => $sapsName,
                        'normalized_name' => $normalizedName,
                        'category' => $category,
                        'ignition' => $ignition,
                        'is_active' => true,
                        'is_obsolete' => false,
                        'is_wildcat' => false,
                    ]);
                    $existing[$normalizedName] = ['id' => null, 'saps_code' => $sapsCode];
                    $created++;
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    // Name already exists (case-insensitive match) – update saps_code instead
                    FirearmCalibre::whereRaw('LOWER(name) = ?', [strtolower($sapsName)])
                        ->whereNull('saps_code')
                        ->update(['saps_code' => $sapsCode, 'is_active' => true]);
                    $matched++;
                }
            }
        }

        fclose($handle);
        $this->command->info("SAPS calibres: {$matched} matched, {$created} new, {$skipped} non-calibre entries skipped.");
    }

    /**
     * Enrich SAPS entries with curated metadata (bullet diameter, case length, aliases).
     * Only updates existing SAPS entries – never creates new calibres.
     */
    protected function enrichWithCuratedMetadata(): void
    {
        $enrichment = $this->getCuratedEnrichmentData();

        $enriched = 0;
        foreach ($enrichment as $entry) {
            $normalizedName = FirearmCalibre::normalize($entry['name']);
            $aliases = $entry['aliases'] ?? [];
            unset($entry['aliases']);

            // Only enrich calibres that have a SAPS code (i.e. are in the SAPS list)
            $calibre = FirearmCalibre::whereNotNull('saps_code')
                ->where(function ($q) use ($normalizedName, $entry) {
                    $q->where('normalized_name', $normalizedName)
                      ->orWhereRaw('LOWER(name) = ?', [strtolower($entry['name'])]);
                })
                ->first();

            if (!$calibre) {
                continue; // No matching SAPS entry – skip
            }

            // Update metadata fields (only if not already set)
            $updates = [];
            if (!empty($entry['bullet_diameter_mm']) && empty($calibre->bullet_diameter_mm)) {
                $updates['bullet_diameter_mm'] = $entry['bullet_diameter_mm'];
            }
            if (!empty($entry['case_length_mm']) && empty($calibre->case_length_mm)) {
                $updates['case_length_mm'] = $entry['case_length_mm'];
            }
            if (!empty($entry['family']) && empty($calibre->family)) {
                $updates['family'] = $entry['family'];
            }

            if (!empty($updates)) {
                $calibre->update($updates);
                $enriched++;
            }

            // Add aliases
            foreach ($aliases as $alias) {
                $normalizedAlias = FirearmCalibre::normalize($alias);
                $existingAlias = FirearmCalibreAlias::where('normalized_alias', $normalizedAlias)->first();
                if (!$existingAlias) {
                    FirearmCalibreAlias::create([
                        'firearm_calibre_id' => $calibre->id,
                        'alias' => $alias,
                        'normalized_alias' => $normalizedAlias,
                    ]);
                }
            }
        }

        // Also try enrichment from the curated CSV if it exists
        $csvPath = resource_path('data/calibres.csv');
        if (File::exists($csvPath)) {
            $enriched += $this->enrichFromCsv($csvPath);
        }

        $this->command->info("Enriched {$enriched} SAPS calibres with curated metadata.");
    }

    /**
     * Enrich SAPS entries from the curated CSV file (bullet diameter, case length, etc.).
     */
    protected function enrichFromCsv(string $csvPath): int
    {
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return 0;
        }

        $header = fgetcsv($handle);
        $enriched = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 11) continue;

            $name = $row[0];
            $normalizedName = FirearmCalibre::normalize($name);

            // Only match against SAPS entries
            $calibre = FirearmCalibre::whereNotNull('saps_code')
                ->where(function ($q) use ($normalizedName, $name) {
                    $q->where('normalized_name', $normalizedName)
                      ->orWhereRaw('LOWER(name) = ?', [strtolower($name)]);
                })
                ->first();

            if (!$calibre) {
                continue;
            }

            $updates = [];
            if (!empty($row[3]) && empty($calibre->family)) {
                $updates['family'] = $row[3];
            }
            if (!empty($row[4]) && empty($calibre->bullet_diameter_mm)) {
                $updates['bullet_diameter_mm'] = (float) $row[4];
            }
            if (!empty($row[5]) && empty($calibre->case_length_mm)) {
                $updates['case_length_mm'] = (float) $row[5];
            }
            if (!empty($row[6]) && empty($calibre->parent)) {
                $updates['parent'] = $row[6];
            }
            if (!empty($row[10]) && empty($calibre->tags)) {
                $updates['tags'] = explode(',', $row[10]);
            }

            if (!empty($updates)) {
                $calibre->update($updates);
                $enriched++;
            }
        }

        fclose($handle);
        return $enriched;
    }

    /**
     * Curated metadata to enrich SAPS entries with bullet diameters, case lengths, and aliases.
     * Keyed by common calibre name – the seeder matches these against SAPS entries.
     */
    protected function getCuratedEnrichmentData(): array
    {
        return [
            // Handgun
            ['name' => '.25 ACP', 'bullet_diameter_mm' => 6.35, 'aliases' => ['6.35mm Browning', '.25 Auto']],
            ['name' => '.32 ACP', 'bullet_diameter_mm' => 7.65, 'aliases' => ['7.65mm Browning', '.32 Auto']],
            ['name' => '.32 S&W', 'bullet_diameter_mm' => 7.94],
            ['name' => '.380 ACP', 'bullet_diameter_mm' => 9.0, 'aliases' => ['9mm Short', '9mm Kurz', '9x17mm']],
            ['name' => '9MM PARABELLUM', 'bullet_diameter_mm' => 9.01, 'aliases' => ['9mm', '9mm Luger', '9x19mm', '9mm Para']],
            ['name' => '.357 SIG', 'bullet_diameter_mm' => 9.04],
            ['name' => '.357 MAG', 'bullet_diameter_mm' => 9.04, 'aliases' => ['.357 Magnum']],
            ['name' => '.38 SPECIAL', 'bullet_diameter_mm' => 9.04, 'aliases' => ['.38 Spl']],
            ['name' => '.40 S&W', 'bullet_diameter_mm' => 10.16],
            ['name' => '10MM AUTO', 'bullet_diameter_mm' => 10.16],
            ['name' => '.41 REM MAG', 'bullet_diameter_mm' => 10.41, 'aliases' => ['.41 Magnum']],
            ['name' => '.44 MAG', 'bullet_diameter_mm' => 10.92, 'aliases' => ['.44 Magnum', '.44 Rem Mag']],
            ['name' => '.44 SPECIAL', 'bullet_diameter_mm' => 10.92, 'aliases' => ['.44 Spl']],
            ['name' => '.45 ACP', 'bullet_diameter_mm' => 11.48, 'aliases' => ['.45 Auto']],
            ['name' => '.45 COLT', 'bullet_diameter_mm' => 11.48, 'aliases' => ['.45 Long Colt', '.45 LC']],
            ['name' => '.454 CASULL', 'bullet_diameter_mm' => 11.48],
            ['name' => '.50 ACTION EXPRESS', 'bullet_diameter_mm' => 12.7, 'aliases' => ['.50 AE']],
            ['name' => '.500 S&W MAG', 'bullet_diameter_mm' => 12.7, 'aliases' => ['.500 S&W Magnum']],

            // Rifle – common
            ['name' => '.204 RUGER', 'bullet_diameter_mm' => 5.2, 'case_length_mm' => 47.0],
            ['name' => '.218 BEE', 'bullet_diameter_mm' => 5.7],
            ['name' => '.22 HORNET', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 35.6],
            ['name' => '.222 REM', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 43.2, 'aliases' => ['.222 Remington']],
            ['name' => '.223 REM', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 45.0, 'aliases' => ['.223 Remington', '.223']],
            ['name' => '5.56X45 NATO', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 45.0, 'aliases' => ['5.56x45mm', '5.56 NATO']],
            ['name' => '.22-250 REM', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 48.6, 'aliases' => ['.22-250', '.22-250 Remington']],
            ['name' => '.243 WIN', 'bullet_diameter_mm' => 6.17, 'case_length_mm' => 51.9, 'aliases' => ['.243 Winchester', '.243']],
            ['name' => '6MM REM', 'bullet_diameter_mm' => 6.17, 'case_length_mm' => 56.0, 'aliases' => ['6mm Remington']],
            ['name' => '6.5 CREEDMOOR', 'bullet_diameter_mm' => 6.72, 'case_length_mm' => 48.0, 'aliases' => ['6.5 CM', '6.5mm Creedmoor']],
            ['name' => '6.5X55 SWED MAUS', 'bullet_diameter_mm' => 6.72, 'case_length_mm' => 55.0, 'aliases' => ['6.5x55 Swedish', '6.5x55 Swede', '6.5x55mm']],
            ['name' => '6.5 PRC', 'bullet_diameter_mm' => 6.72, 'case_length_mm' => 51.0, 'aliases' => ['6.5 Precision Rifle Cartridge']],
            ['name' => '.25-06 REM', 'bullet_diameter_mm' => 6.53, 'case_length_mm' => 63.3, 'aliases' => ['.25-06 Remington']],
            ['name' => '.270 WIN', 'bullet_diameter_mm' => 7.04, 'case_length_mm' => 64.5, 'aliases' => ['.270 Winchester', '.270']],
            ['name' => '7X57 MAUSER', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 57.0, 'aliases' => ['7x57mm', '7mm Mauser']],
            ['name' => '7MM-08 REM', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 51.2, 'aliases' => ['7mm-08', '7mm-08 Remington']],
            ['name' => '7MM REM MAG', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 64.0, 'aliases' => ['7mm Remington Magnum']],
            ['name' => '7MM PRC', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 51.0],
            ['name' => '.280 REM', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 64.5, 'aliases' => ['.280 Remington']],
            ['name' => '.30 CARBINE', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 33.0],
            ['name' => '.30-30 WIN', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 51.8, 'aliases' => ['.30-30', '.30-30 Winchester']],
            ['name' => '.300 AAC BLACKOUT', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 35.0, 'aliases' => ['.300 BLK', '300 Blackout']],
            ['name' => '.308 WIN', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 51.2, 'aliases' => ['.308 Winchester', '.308']],
            ['name' => '7.62X51 NATO', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 51.2, 'aliases' => ['7.62x51mm', '7.62 NATO']],
            ['name' => '7.62X39', 'bullet_diameter_mm' => 7.92, 'case_length_mm' => 39.0, 'aliases' => ['7.62x39mm']],
            ['name' => '7.62X54R', 'bullet_diameter_mm' => 7.92, 'case_length_mm' => 54.0, 'aliases' => ['7.62x54mmR', '7.62 Russian']],
            ['name' => '.30-06 SPRING', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 63.3, 'aliases' => ['.30-06', '.30-06 Springfield']],
            ['name' => '.300 WIN MAG', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 66.7, 'aliases' => ['.300 Winchester Magnum']],
            ['name' => '.300 WSM', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 53.3],
            ['name' => '.300 WEATH MAG', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 73.0, 'aliases' => ['.300 Weatherby Magnum']],
            ['name' => '.300 REM ULTRA MAG', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 72.4, 'aliases' => ['.300 RUM']],
            ['name' => '.300 PRC', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 58.0, 'aliases' => ['.300 Precision Rifle Cartridge']],
            ['name' => '.303 BRITISH', 'bullet_diameter_mm' => 7.7, 'case_length_mm' => 56.4],
            ['name' => '8X57 MAUSER', 'bullet_diameter_mm' => 8.22, 'case_length_mm' => 57.0, 'aliases' => ['8mm Mauser', '7.92x57mm']],
            ['name' => '.338 WIN MAG', 'bullet_diameter_mm' => 8.58, 'case_length_mm' => 63.3, 'aliases' => ['.338 Winchester Magnum']],
            ['name' => '.338 LAPUA MAG', 'bullet_diameter_mm' => 8.58, 'case_length_mm' => 70.0, 'aliases' => ['.338 Lapua', '8.6x70mm']],
            ['name' => '.375 H&H MAG', 'bullet_diameter_mm' => 9.53, 'case_length_mm' => 72.4, 'aliases' => ['.375 Holland & Holland', '.375 H&H']],
            ['name' => '.375 RUGER', 'bullet_diameter_mm' => 9.53, 'case_length_mm' => 65.0],
            ['name' => '.404 JEFFERY', 'bullet_diameter_mm' => 10.3, 'case_length_mm' => 73.0, 'aliases' => ['10.75x73mm']],
            ['name' => '.416 RIGBY', 'bullet_diameter_mm' => 10.57, 'case_length_mm' => 74.0],
            ['name' => '.458 WIN MAG', 'bullet_diameter_mm' => 11.63, 'case_length_mm' => 63.3, 'aliases' => ['.458 Winchester Magnum']],
            ['name' => '.458 LOTT', 'bullet_diameter_mm' => 11.63, 'case_length_mm' => 72.4],
            ['name' => '.470 NE', 'bullet_diameter_mm' => 12.0, 'case_length_mm' => 83.0, 'aliases' => ['.470 Nitro Express']],
            ['name' => '.500 NE', 'bullet_diameter_mm' => 12.7, 'case_length_mm' => 83.0, 'aliases' => ['.500 Nitro Express']],
            ['name' => '.50 BMG', 'bullet_diameter_mm' => 12.7, 'case_length_mm' => 99.0, 'aliases' => ['12.7x99mm NATO', '.50 Browning']],

            // Shotgun
            ['name' => '10 GA', 'bullet_diameter_mm' => 19.7, 'aliases' => ['10 Gauge']],
            ['name' => '12 GA', 'bullet_diameter_mm' => 18.5, 'aliases' => ['12 Gauge']],
            ['name' => '16 GA', 'bullet_diameter_mm' => 16.8, 'aliases' => ['16 Gauge']],
            ['name' => '20 GA', 'bullet_diameter_mm' => 15.6, 'aliases' => ['20 Gauge']],
            ['name' => '28 GA', 'bullet_diameter_mm' => 14.0, 'aliases' => ['28 Gauge']],
            ['name' => '.410 BORE', 'bullet_diameter_mm' => 10.41, 'aliases' => ['.410', '410 Gauge']],
        ];
    }

    /**
     * Determine if a SAPS entry should be skipped (not a real calibre).
     */
    protected function shouldSkipSapsEntry(string $name): bool
    {
        $upper = strtoupper($name);

        $skipPrefixes = [
            'BARREL',
            'FRAME',
            'RECEIVER',
            'BOLT KNOB',
            'PRIMERS',
            'PROOF BARREL',
            'POWERHEAD',
            'JAKKALSKANON',
            'FLARE GUN',
            'STARTERS',
        ];

        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($upper, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Infer the calibre category from the SAPS name.
     */
    protected function inferCategory(string $name): string
    {
        $upper = strtoupper($name);

        // Shotgun indicators
        if (preg_match('/\b\d+\s*GA\b/i', $name) || preg_match('/\b\d+\s*GAUGE\b/i', $name)) {
            return 'shotgun';
        }
        if (str_contains($upper, 'BORE') && !str_contains($upper, '30 BORE')) {
            return 'shotgun';
        }

        // Muzzle loader indicators
        if (str_contains($upper, 'MUZZLE LOADER') || str_contains($upper, 'CAP&BALL')) {
            return 'muzzleloader';
        }

        // Air gun indicators
        if (str_contains($upper, 'AIR/WIND')) {
            return 'rifle';
        }

        // Handgun indicators
        $handgunPatterns = [
            '.25 ACP', '6.35MM BROWNING', '6.35MM BROW',
            '.32 ACP', '.32 S&W', '.32 LONG', '.32 SHORT', '.32 RIM-FIRE',
            '.380 ACP', '9MM SHORT', '9MM PAR', '9X19', '9X18', '9X21', '9X23',
            '.38 SPECIAL', '.38 SPL', '.38 S&W', '.38 SUPER', '.38 SHORT',
            '.357 MAG', '.357 SIG',
            '.40 S&W',
            '.41 MAG', '.41 REM MAG', '.41 LONG', '.41 SHORT', '.41 SPECIAL',
            '.44 MAG', '.44 S&W', '.44 SPECIAL', '.44 RUSSIAN', '.44 BULL',
            '.45 ACP', '.45 GAP', '.45 COLT', '.45 S&W', '.45 WEBLEY',
            '.454 CASULL', '.460 S&W', '.475 LINEBAUGH', '.480 RUGER',
            '.500 S&W', '.50 ACTION EXPRESS',
            '5.7X28', '4.25MM LILIPUT', '4MM FLOBERT',
            '7.62X25', '7.63 MM MAUSER', '7.63X25', '7.65MM',
            '10MM AUTO', '.320 REVOLVER', '.442', '.455 WEBLEY', '.455 COLT',
            '.455 ELEY', '.476 ENFIELD', '9.65MM NORMAL',
            '.22 SHORT', '.22 LONG ', '.22 LONG/',
            '.380 SHORT', '.380 LONG',
        ];

        foreach ($handgunPatterns as $pattern) {
            if (str_contains($upper, strtoupper($pattern))) {
                return 'handgun';
            }
        }

        if (str_contains($upper, '(PISTOL)') || str_contains($upper, 'REVOLVER')) {
            return 'handgun';
        }

        return 'rifle';
    }

    /**
     * Infer the ignition type from the SAPS name.
     */
    protected function inferIgnition(string $name): string
    {
        $upper = strtoupper($name);

        $rimfirePatterns = [
            '.22 SHORT', '.22 LONG', '.22 LR', '.22 S/L/LR',
            '.22 EXTRA LONG', '.22 WIN MAG RIM', '.22 WMR',
            '.22 RIM FIRE', '.22 RIMFIRE', '.22 WIN RIMFIRE',
            '.17 HORN MACH', '.17 HORN MAG RIM', '.17 HMR',
            '.22 MAGNUM', '.22 CB CAP', '.22 BB CAP',
            'RIM-FIRE', 'RIMFIRE', 'RIM FIRE',
        ];

        foreach ($rimfirePatterns as $pattern) {
            if (str_contains($upper, $pattern)) {
                return 'rimfire';
            }
        }

        if (str_contains($upper, 'AIR/WIND')) {
            return 'centerfire';
        }

        return 'centerfire';
    }
}
