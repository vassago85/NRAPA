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
     */
    public function run(): void
    {
        // First, seed from CSV file if it exists
        $csvPath = resource_path('data/calibres.csv');
        if (File::exists($csvPath)) {
            $this->seedFromCsv($csvPath);
        }

        // Then add additional calibres not in CSV
        $this->seedAdditionalCalibres();
    }

    /**
     * Seed calibres from CSV file.
     */
    protected function seedFromCsv(string $csvPath): void
    {
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return;
        }

        // Skip header row
        $header = fgetcsv($handle);
        
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 11) continue;
            
            $data = [
                'name' => $row[0],
                'normalized_name' => $row[1] ?: FirearmCalibre::normalize($row[0]),
                'category' => $row[2] ?: 'rifle',
                'family' => $row[3] ?: null,
                'bullet_diameter_mm' => !empty($row[4]) ? (float)$row[4] : null,
                'case_length_mm' => !empty($row[5]) ? (float)$row[5] : null,
                'parent' => $row[6] ?: null,
                'is_wildcat' => filter_var($row[7] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_obsolete' => filter_var($row[8] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_active' => filter_var($row[9] ?? true, FILTER_VALIDATE_BOOLEAN),
                'tags' => !empty($row[10]) ? explode(',', $row[10]) : null,
            ];

            // Determine ignition type from category or name
            if ($data['category'] === 'rimfire') {
                $data['ignition'] = 'rimfire';
                $data['category'] = 'rifle'; // rimfire is ignition type, category should be rifle/handgun
            } else {
                $data['ignition'] = 'centerfire';
            }

            // Fix rimfire calibres
            if (str_contains(strtolower($data['name']), '.22 lr') || 
                str_contains(strtolower($data['name']), '.22 wmr') ||
                str_contains(strtolower($data['name']), '.17 hmr') ||
                str_contains(strtolower($data['name']), '.17 wsm')) {
                $data['ignition'] = 'rimfire';
            }

            // Use name for lookup since it has unique constraint
            FirearmCalibre::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
            $count++;
        }

        fclose($handle);
        $this->command->info("Seeded {$count} calibres from CSV.");
    }

    /**
     * Seed additional calibres not in CSV.
     */
    protected function seedAdditionalCalibres(): void
    {
        $calibres = [
            // ===== HANDGUN - RIMFIRE =====
            ['name' => '.17 HM2', 'category' => 'handgun', 'ignition' => 'rimfire', 'bullet_diameter_mm' => 4.37, 'aliases' => ['.17 Hornady Mach 2']],
            ['name' => '.17 HMR', 'category' => 'handgun', 'ignition' => 'rimfire', 'bullet_diameter_mm' => 4.37, 'aliases' => ['.17 Hornady Magnum Rimfire']],
            ['name' => '.22 Short', 'category' => 'handgun', 'ignition' => 'rimfire', 'bullet_diameter_mm' => 5.7],
            ['name' => '.22 Long', 'category' => 'handgun', 'ignition' => 'rimfire', 'bullet_diameter_mm' => 5.7],
            ['name' => '.22 LR', 'category' => 'handgun', 'ignition' => 'rimfire', 'bullet_diameter_mm' => 5.7, 'aliases' => ['.22 Long Rifle', '.22']],
            ['name' => '.22 WMR', 'category' => 'handgun', 'ignition' => 'rimfire', 'bullet_diameter_mm' => 5.7, 'aliases' => ['.22 Magnum', '.22 Winchester Magnum Rimfire', '.22 Mag']],

            // ===== HANDGUN - CENTERFIRE (Revolvers) =====
            ['name' => '.32 S&W', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.94],
            ['name' => '.32 S&W Long', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.94],
            ['name' => '.32 H&R Magnum', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.94],
            ['name' => '.327 Federal Magnum', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.94],
            ['name' => '.38 S&W', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.04],
            ['name' => '.38 Special', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.04, 'aliases' => ['.38 Spl', '.38 Spc']],
            ['name' => '.357 Magnum', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.04, 'aliases' => ['.357 Mag']],
            ['name' => '.41 Magnum', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.41, 'aliases' => ['.41 Rem Mag']],
            ['name' => '.44 Russian', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.92, 'is_obsolete' => true],
            ['name' => '.44 Special', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.92, 'aliases' => ['.44 Spl']],
            ['name' => '.44 Magnum', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.92, 'aliases' => ['.44 Mag', '.44 Rem Mag']],
            ['name' => '.45 Colt', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.48, 'aliases' => ['.45 Long Colt', '.45 LC']],
            ['name' => '.454 Casull', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.48],
            ['name' => '.460 S&W Magnum', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.63],
            ['name' => '.475 Linebaugh', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 12.07],
            ['name' => '.480 Ruger', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 12.19],
            ['name' => '.500 S&W Magnum', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 12.7],
            ['name' => '.500 Linebaugh', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 12.7],

            // ===== HANDGUN - CENTERFIRE (Semi-auto) =====
            ['name' => '.25 ACP', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.35, 'aliases' => ['6.35mm Browning', '.25 Auto']],
            ['name' => '.30 Luger', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.65, 'aliases' => ['7.65x21mm Parabellum']],
            ['name' => '.32 ACP', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.65, 'aliases' => ['7.65mm Browning', '.32 Auto']],
            ['name' => '.380 ACP', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.0, 'aliases' => ['9mm Short', '9mm Kurz', '9x17mm']],
            ['name' => '9x18 Makarov', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.27, 'aliases' => ['9mm Makarov', '9x18mm']],
            ['name' => '9x19 Parabellum', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.01, 'aliases' => ['9mm', '9mm Luger', '9x19mm', '9mm Para']],
            ['name' => '9x21 IMI', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.01, 'aliases' => ['9x21mm']],
            ['name' => '.357 SIG', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.04],
            ['name' => '.40 S&W', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.16, 'aliases' => ['10x22mm']],
            ['name' => '10mm Auto', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.16, 'aliases' => ['10mm']],
            ['name' => '.45 ACP', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.48, 'aliases' => ['.45 Auto', '11.43x23mm']],
            ['name' => '.45 GAP', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.48, 'aliases' => ['.45 Glock']],
            ['name' => '.50 Action Express', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 12.7, 'aliases' => ['.50 AE']],
            ['name' => '5.7x28mm', 'category' => 'handgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 5.7, 'aliases' => ['5.7x28mm FN']],

            // ===== SHOTGUN =====
            ['name' => '.410 Bore', 'category' => 'shotgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.41, 'aliases' => ['.410', '410 Gauge']],
            ['name' => '28 Gauge', 'category' => 'shotgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 14.0],
            ['name' => '24 Gauge', 'category' => 'shotgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 14.7, 'is_obsolete' => true],
            ['name' => '20 Gauge', 'category' => 'shotgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 15.6],
            ['name' => '16 Gauge', 'category' => 'shotgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 16.8],
            ['name' => '12 Gauge', 'category' => 'shotgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 18.5],
            ['name' => '10 Gauge', 'category' => 'shotgun', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 19.7],

            // ===== RIFLE - RIMFIRE =====
            ['name' => '.17 WSM', 'category' => 'rifle', 'ignition' => 'rimfire', 'bullet_diameter_mm' => 4.37, 'case_length_mm' => 27.0],
            ['name' => '.17 HMR (Rifle)', 'category' => 'rifle', 'ignition' => 'rimfire', 'bullet_diameter_mm' => 4.37, 'case_length_mm' => 26.7, 'aliases' => ['.17 HMR']],
            ['name' => '.22 LR (Rifle)', 'category' => 'rifle', 'ignition' => 'rimfire', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 15.6, 'aliases' => ['.22 Long Rifle']],
            ['name' => '.22 WMR (Rifle)', 'category' => 'rifle', 'ignition' => 'rimfire', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 26.8, 'aliases' => ['.22 Magnum']],

            // ===== RIFLE - CENTERFIRE (Small/Varmint) =====
            ['name' => '.17 Hornet', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 4.37],
            ['name' => '.17 Remington', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 4.37],
            ['name' => '.204 Ruger', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 5.2, 'case_length_mm' => 47.0],
            ['name' => '.218 Bee', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 5.7],
            ['name' => '.220 Swift', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 56.0],
            ['name' => '.221 Fireball', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 5.7],
            ['name' => '.222 Remington', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 43.2, 'aliases' => ['.222 Rem']],
            ['name' => '.223 Remington', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 45.0, 'aliases' => ['.223 Rem', '.223']],
            ['name' => '5.56x45 NATO', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 45.0, 'aliases' => ['5.56x45mm', '5.56 NATO']],
            ['name' => '.22-250 Remington', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 48.6, 'aliases' => ['.22-250', '.22-250 Rem']],
            ['name' => '.224 Valkyrie', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 5.7, 'case_length_mm' => 39.0],

            // ===== RIFLE - CENTERFIRE (6mm) =====
            ['name' => '.243 Winchester', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.17, 'case_length_mm' => 51.9, 'aliases' => ['.243 Win', '.243']],
            ['name' => '6mm Remington', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.17, 'case_length_mm' => 56.0],
            ['name' => '6mm Creedmoor', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.17, 'case_length_mm' => 48.0, 'aliases' => ['6mm CM']],
            ['name' => '6mm BR', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.0, 'case_length_mm' => 47.0],
            ['name' => '6mm GT', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.0, 'case_length_mm' => 47.0],
            ['name' => '6mm Dasher', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.0, 'case_length_mm' => 47.0],

            // ===== RIFLE - CENTERFIRE (6.5mm) =====
            ['name' => '.25-06 Remington', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.53, 'case_length_mm' => 63.3, 'aliases' => ['.25-06 Rem']],
            ['name' => '6.5 Creedmoor', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.72, 'case_length_mm' => 48.0, 'aliases' => ['6.5 CM', '6.5mm Creedmoor']],
            ['name' => '6.5x47 Lapua', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.72, 'case_length_mm' => 47.0],
            ['name' => '6.5x55 Swedish', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.72, 'case_length_mm' => 55.0, 'aliases' => ['6.5x55 Swede', '6.5x55mm']],
            ['name' => '6.5 PRC', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.72, 'case_length_mm' => 51.0, 'aliases' => ['6.5 Precision Rifle Cartridge']],
            ['name' => '6.5-284 Norma', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.72, 'case_length_mm' => 55.0],
            ['name' => '.264 Winchester Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 6.72, 'case_length_mm' => 63.5],

            // ===== RIFLE - CENTERFIRE (.270/7mm) =====
            ['name' => '.270 Winchester', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.04, 'case_length_mm' => 64.5, 'aliases' => ['.270 Win', '.270']],
            ['name' => '.270 WSM', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.04, 'case_length_mm' => 53.3],
            ['name' => '.270 Weatherby Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.04, 'case_length_mm' => 64.8],
            ['name' => '7x57 Mauser', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 57.0, 'aliases' => ['7x57mm', '7mm Mauser']],
            ['name' => '7mm-08 Remington', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 51.2, 'aliases' => ['7mm-08']],
            ['name' => '7mm Remington Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 64.0, 'aliases' => ['7mm Rem Mag']],
            ['name' => '7mm WSM', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 53.3],
            ['name' => '.280 Remington', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 64.5],
            ['name' => '28 Nosler', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 54.0],
            ['name' => '7mm SAUM', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 51.0, 'aliases' => ['7 SAUM']],
            ['name' => '7mm PRC', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.24, 'case_length_mm' => 51.0],

            // ===== RIFLE - CENTERFIRE (.30 cal) =====
            ['name' => '.30 Carbine', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 33.0],
            ['name' => '.30-30 Winchester', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 51.8, 'aliases' => ['.30-30', '.30-30 Win']],
            ['name' => '.300 AAC Blackout', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 35.0, 'aliases' => ['.300 BLK', '300 Blackout']],
            ['name' => '.308 Winchester', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 51.2, 'aliases' => ['.308 Win', '.308']],
            ['name' => '7.62x51 NATO', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 51.2, 'aliases' => ['7.62x51mm', '7.62 NATO']],
            ['name' => '7.62x39', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.92, 'case_length_mm' => 39.0, 'aliases' => ['7.62x39mm', '7.62 Soviet']],
            ['name' => '7.62x54R', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.92, 'case_length_mm' => 54.0, 'aliases' => ['7.62x54mmR', '7.62 Russian']],
            ['name' => '.30-06 Springfield', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 63.3, 'aliases' => ['.30-06', '.30-06 Sprg']],
            ['name' => '.300 WSM', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 53.3],
            ['name' => '.300 Winchester Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 66.7, 'aliases' => ['.300 Win Mag']],
            ['name' => '.300 Weatherby Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 73.0],
            ['name' => '.300 RUM', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 72.4, 'aliases' => ['.300 Remington Ultra Mag']],
            ['name' => '.300 PRC', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 58.0, 'aliases' => ['.300 Precision Rifle Cartridge']],
            ['name' => '.300 Norma Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.82, 'case_length_mm' => 65.0],
            ['name' => '.303 British', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 7.7, 'case_length_mm' => 56.4],

            // ===== RIFLE - CENTERFIRE (8mm/.33) =====
            ['name' => '8x57 Mauser', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 8.22, 'case_length_mm' => 57.0, 'aliases' => ['8mm Mauser', '7.92x57mm']],
            ['name' => '.338 Winchester Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 8.58, 'case_length_mm' => 63.3, 'aliases' => ['.338 Win Mag']],
            ['name' => '.338 Federal', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 8.58, 'case_length_mm' => 51.2],
            ['name' => '.338 Lapua Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 8.58, 'case_length_mm' => 70.0, 'aliases' => ['.338 Lapua', '8.6x70mm']],
            ['name' => '.338 Norma Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 8.58, 'case_length_mm' => 63.5],
            ['name' => '.340 Weatherby Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 8.58, 'case_length_mm' => 73.0],

            // ===== RIFLE - CENTERFIRE (.35 and larger) =====
            ['name' => '.35 Remington', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.12, 'case_length_mm' => 51.8],
            ['name' => '.35 Whelen', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.12, 'case_length_mm' => 63.3],
            ['name' => '.375 Holland & Holland Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.53, 'case_length_mm' => 72.4, 'aliases' => ['.375 H&H', '.375 H&H Mag']],
            ['name' => '.375 Ruger', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.53, 'case_length_mm' => 65.0],
            ['name' => '.378 Weatherby Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 9.53, 'case_length_mm' => 73.0],
            ['name' => '.404 Jeffery', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.3, 'case_length_mm' => 73.0, 'aliases' => ['10.75x73mm']],
            ['name' => '.416 Rigby', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.57, 'case_length_mm' => 74.0],
            ['name' => '.416 Remington Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.57, 'case_length_mm' => 72.4],
            ['name' => '.416 Ruger', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.57, 'case_length_mm' => 65.0],
            ['name' => '.444 Marlin', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.92, 'case_length_mm' => 53.0],
            ['name' => '.45-70 Government', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.63, 'case_length_mm' => 53.0, 'aliases' => ['.45-70 Govt', '.45-70']],
            ['name' => '.450 Bushmaster', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.5, 'case_length_mm' => 39.0],
            ['name' => '.450 Marlin', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.63, 'case_length_mm' => 53.0],
            ['name' => '.450/400 Nitro Express', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 10.3, 'case_length_mm' => 83.0],
            ['name' => '.458 Winchester Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.63, 'case_length_mm' => 63.3, 'aliases' => ['.458 Win Mag']],
            ['name' => '.458 Lott', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.63, 'case_length_mm' => 72.4],
            ['name' => '.460 Weatherby Magnum', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 11.63, 'case_length_mm' => 74.0],
            ['name' => '.470 Nitro Express', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 12.0, 'case_length_mm' => 83.0],
            ['name' => '.500 Nitro Express', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 12.7, 'case_length_mm' => 83.0],
            ['name' => '.50 BMG', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 12.7, 'case_length_mm' => 99.0, 'aliases' => ['12.7x99mm NATO', '.50 Browning']],
            ['name' => '.50 Beowulf', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 12.7, 'case_length_mm' => 42.0],
            ['name' => '.577 Nitro Express', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 14.9, 'case_length_mm' => 83.0],
            ['name' => '.600 Nitro Express', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 15.2, 'case_length_mm' => 83.0],
            ['name' => '.700 Nitro Express', 'category' => 'rifle', 'ignition' => 'centerfire', 'bullet_diameter_mm' => 17.8, 'case_length_mm' => 89.0],
        ];

        $count = 0;
        foreach ($calibres as $data) {
            $aliases = $data['aliases'] ?? [];
            unset($data['aliases']);
            
            $data['normalized_name'] = FirearmCalibre::normalize($data['name']);
            $data['is_active'] = true;
            $data['is_obsolete'] = $data['is_obsolete'] ?? false;
            $data['is_wildcat'] = $data['is_wildcat'] ?? false;
            
            // Use name for lookup since it has unique constraint
            $calibre = FirearmCalibre::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
            
            // Add aliases (skip if normalized alias already exists)
            foreach ($aliases as $alias) {
                $normalizedAlias = FirearmCalibre::normalize($alias);
                // Check if this normalized alias already exists for ANY calibre
                $existingAlias = FirearmCalibreAlias::where('normalized_alias', $normalizedAlias)->first();
                if (!$existingAlias) {
                    FirearmCalibreAlias::create([
                        'firearm_calibre_id' => $calibre->id,
                        'alias' => $alias,
                        'normalized_alias' => $normalizedAlias,
                    ]);
                }
            }
            
            $count++;
        }

        $this->command->info("Seeded {$count} additional calibres.");
    }
}
