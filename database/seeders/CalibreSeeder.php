<?php

namespace Database\Seeders;

use App\Models\Calibre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CalibreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $calibres = [
            // ===== HANDGUN CALIBRES =====
            // Handgun - Centerfire - Manual (Revolvers)
            ['name' => '2.7mm Kolibri', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '4.25mm Liliput', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.32 S&W', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.32 S&W Long', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.32 H&R Magnum', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.327 Federal Magnum', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.38 S&W', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.38 Special', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.38 Spl', '.38 Spc']],
            ['name' => '.357 Magnum', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.357 Mag']],
            ['name' => '.41 Magnum', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.41 Rem Mag']],
            ['name' => '.44 Russian', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.44 Special', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.44 Spl']],
            ['name' => '.44 Magnum', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.44 Mag', '.44 Rem Mag']],
            ['name' => '.45 Colt', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.45 Long Colt', '.45 LC']],
            ['name' => '.454 Casull', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.460 S&W Magnum', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.480 Ruger', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.500 Linebaugh', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.500 JRH', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.500 S&W Magnum', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            
            // Handgun - Centerfire - Self-loading (Semi-auto)
            ['name' => '5.45×18mm', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['5.45x18mm PSM']],
            ['name' => '5.7×28mm', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['5.7x28mm FN']],
            ['name' => '.25 ACP', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['6.35mm Browning', '.25 Auto']],
            ['name' => '.25 NAA', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.30 Luger', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['7.65×21mm Parabellum']],
            ['name' => '.32 ACP', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['7.65mm Browning', '.32 Auto']],
            ['name' => '.357 SIG', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.380 ACP', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['9mm Short', '9mm Kurz', '9×17mm']],
            ['name' => '9×18 Makarov', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['9mm Makarov', '9x18mm']],
            ['name' => '9×19 Parabellum', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['9mm', '9mm Luger', '9x19mm']],
            ['name' => '9×21 IMI', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['9x21mm']],
            ['name' => '9×23 Winchester', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['9x23mm Win']],
            ['name' => '.40 S&W', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['10×22mm']],
            ['name' => '10mm Auto', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['10mm']],
            ['name' => '.45 ACP', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.45 Auto', '11.43×23mm']],
            ['name' => '.45 GAP', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.45 Glock']],
            ['name' => '.50 Action Express', 'category' => 'handgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.50 AE']],
            
            // Handgun - Rimfire
            ['name' => '.17 HM2', 'category' => 'handgun', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => ['.17 Hornady Mach 2']],
            ['name' => '.17 HMR', 'category' => 'handgun', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => ['.17 Hornady Magnum Rimfire']],
            ['name' => '.22 Short', 'category' => 'handgun', 'ignition_type' => 'rimfire', 'is_common' => false],
            ['name' => '.22 Long', 'category' => 'handgun', 'ignition_type' => 'rimfire', 'is_common' => false],
            ['name' => '.22 LR', 'category' => 'handgun', 'ignition_type' => 'rimfire', 'is_common' => true, 'aliases' => ['.22 Long Rifle', '.22']],
            ['name' => '.22 WMR', 'category' => 'handgun', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => ['.22 Magnum', '.22 Winchester Magnum Rimfire']],
            
            // ===== SHOTGUN CALIBRES =====
            ['name' => '.410 Bore', 'category' => 'shotgun', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.410', '410 Gauge']],
            ['name' => '28 Gauge', 'category' => 'shotgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '24 Gauge', 'category' => 'shotgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '20 Gauge', 'category' => 'shotgun', 'ignition_type' => 'centerfire', 'is_common' => true],
            ['name' => '16 Gauge', 'category' => 'shotgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '12 Gauge', 'category' => 'shotgun', 'ignition_type' => 'centerfire', 'is_common' => true],
            ['name' => '10 Gauge', 'category' => 'shotgun', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '8 Gauge', 'category' => 'shotgun', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true],
            
            // ===== RIFLE CALIBRES =====
            // Rifle - Rimfire
            ['name' => '2mm Kolibri', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '4mm Randzünder', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['4mm M20']],
            ['name' => '.17 Aguila', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false],
            ['name' => '.22 BB Cap', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.22 CB', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false],
            ['name' => '.22 CB Long', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false],
            ['name' => '.22 Extra Long', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.22 WRF', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['.22 Winchester Rim Fire']],
            ['name' => '.25 Stevens', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.25 Stevens Short', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.32 Rimfire', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.32 Rimfire Long', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.38 Rimfire', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.38 Rimfire Long', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.41 Swiss Rimfire', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.44 Henry Rimfire', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.56-52 Spencer', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            ['name' => '.56-56 Spencer', 'category' => 'rifle', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true],
            
            // Rifle - Centerfire (common modern calibres)
            ['name' => '.17 Hornet', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.17 Remington', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.204 Ruger', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.218 Bee', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.220 Swift', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.222 Remington', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.222 Rem']],
            ['name' => '.223 Remington', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.223 Rem', '.223']],
            ['name' => '5.56×45 NATO', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['5.56x45mm', '5.56 NATO']],
            ['name' => '.224 Valkyrie', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.22-250 Remington', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.22-250', '.22-250 Rem']],
            ['name' => '.243 Winchester', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.243 Win', '.243']],
            ['name' => '.25-06 Remington', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.25-06 Rem']],
            ['name' => '6.5 Creedmoor', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['6.5 CM']],
            ['name' => '6.5×55 Swedish', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['6.5x55 Swede', '6.5x55mm']],
            ['name' => '.270 Winchester', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.270 Win', '.270']],
            ['name' => '7×57 Mauser', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['7x57mm', '7mm Mauser']],
            ['name' => '7mm Remington Magnum', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7mm Rem Mag']],
            ['name' => '.308 Winchester', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.308 Win', '.308']],
            ['name' => '7.62×51 NATO', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7.62x51mm', '7.62 NATO']],
            ['name' => '7.62×39', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7.62x39mm', '7.62 Soviet']],
            ['name' => '.30-06 Springfield', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.30-06', '.30-06 Sprg']],
            ['name' => '.338 Lapua Magnum', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.338 Lapua', '8.6×70mm']],
            ['name' => '.375 Holland & Holland Magnum', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.375 H&H', '.375 H&H Mag']],
            ['name' => '.375 Ruger', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.375 EnABLER', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.404 Jeffery', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['10.75×73mm']],
            ['name' => '.416 Rigby', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.458 Winchester Magnum', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.458 Win Mag']],
            ['name' => '.458 Lott', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.50 BMG', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['12.7×99mm NATO', '.50 Browning']],
            ['name' => '.577 Nitro Express', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.600 Nitro Express', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
            ['name' => '.700 Nitro Express', 'category' => 'rifle', 'ignition_type' => 'centerfire', 'is_common' => false],
        ];

        $sortOrder = 0;
        foreach ($calibres as $calibre) {
            $sortOrder++;
            
            Calibre::updateOrCreate(
                ['slug' => Str::slug($calibre['name'])],
                [
                    'name' => $calibre['name'],
                    'category' => $calibre['category'],
                    'ignition_type' => $calibre['ignition_type'],
                    'aliases' => $calibre['aliases'] ?? null,
                    'is_active' => true,
                    'is_common' => $calibre['is_common'] ?? false,
                    'is_obsolete' => $calibre['is_obsolete'] ?? false,
                    'sort_order' => $sortOrder,
                ]
            );
        }

        $this->command->info('Seeded ' . count($calibres) . ' calibres.');
    }
}
