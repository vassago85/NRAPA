<?php

namespace Database\Seeders;

use App\Models\Calibre;
use App\Models\FirearmType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FirearmConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFirearmTypes();
        $this->seedCalibres();
    }

    /**
     * Seed firearm types.
     */
    protected function seedFirearmTypes(): void
    {
        $types = [
            // Handguns
            [
                'name' => 'Revolver',
                'category' => 'handgun',
                'ignition_type' => 'both',
                'action_type' => 'revolver',
                'description' => 'A repeating handgun with a revolving cylinder containing multiple chambers.',
                'sort_order' => 10,
            ],
            [
                'name' => 'Semi-Automatic Pistol',
                'category' => 'handgun',
                'ignition_type' => 'both',
                'action_type' => 'semi_auto',
                'description' => 'A handgun that automatically chambers a new round after each shot.',
                'sort_order' => 11,
            ],
            [
                'name' => 'Single Shot Pistol',
                'category' => 'handgun',
                'ignition_type' => 'both',
                'action_type' => 'single_shot',
                'description' => 'A pistol designed to fire a single shot before reloading.',
                'sort_order' => 12,
            ],
            
            // Rifles - Manual Action
            [
                'name' => 'Bolt Action Rifle',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'bolt_action',
                'description' => 'A rifle with a manually operated bolt mechanism.',
                'sort_order' => 20,
            ],
            [
                'name' => 'Lever Action Rifle',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'lever_action',
                'description' => 'A rifle using a lever mechanism to cycle the action.',
                'sort_order' => 21,
            ],
            [
                'name' => 'Single Shot Rifle',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'single_shot',
                'description' => 'A rifle designed to fire a single shot before reloading.',
                'sort_order' => 22,
            ],
            [
                'name' => 'Break Action Rifle',
                'category' => 'rifle',
                'ignition_type' => 'centerfire',
                'action_type' => 'break_action',
                'description' => 'A rifle that opens at a hinge for loading.',
                'sort_order' => 23,
            ],
            
            // Rifles - Self-Loading
            [
                'name' => 'Semi-Automatic Rifle',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'semi_auto',
                'description' => 'A rifle that automatically chambers a new round after each shot.',
                'sort_order' => 30,
            ],
            
            // Shotguns
            [
                'name' => 'Pump Action Shotgun',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'pump_action',
                'description' => 'A shotgun using a pump mechanism to cycle the action.',
                'sort_order' => 40,
            ],
            [
                'name' => 'Semi-Automatic Shotgun',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'semi_auto',
                'description' => 'A shotgun that automatically chambers a new shell after each shot.',
                'sort_order' => 41,
            ],
            [
                'name' => 'Break Action Shotgun',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'break_action',
                'description' => 'A shotgun that opens at a hinge for loading (single/double barrel).',
                'sort_order' => 42,
            ],
            [
                'name' => 'Bolt Action Shotgun',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'bolt_action',
                'description' => 'A shotgun with a manually operated bolt mechanism.',
                'sort_order' => 43,
            ],
            [
                'name' => 'Lever Action Shotgun',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'lever_action',
                'description' => 'A shotgun using a lever mechanism to cycle the action.',
                'sort_order' => 44,
            ],
        ];

        foreach ($types as $type) {
            FirearmType::updateOrCreate(
                ['slug' => Str::slug($type['name'])],
                array_merge($type, [
                    'slug' => Str::slug($type['name']),
                    'dedicated_type' => 'both',
                    'is_active' => true,
                ])
            );
        }
    }

    /**
     * Seed calibres.
     */
    protected function seedCalibres(): void
    {
        // Handgun Calibres - Complete list including rare/obsolete
        $handgunCalibres = [
            // Rare/Obsolete small calibres
            ['name' => '2.7mm Kolibri', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '4.25mm Liliput', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '5.45×18mm', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['5.45x18mm PSM']],
            ['name' => '5.7×28mm', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['5.7x28', 'FN 5.7']],
            
            // Rimfire
            ['name' => '.17 HM2', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => ['.17 Hornady Mach 2']],
            ['name' => '.17 HMR', 'ignition_type' => 'rimfire', 'is_common' => true, 'aliases' => ['.17 Hornady Magnum Rimfire']],
            ['name' => '.22 Short', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => ['.22 S']],
            ['name' => '.22 Long', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => ['.22 L']],
            ['name' => '.22 LR', 'ignition_type' => 'rimfire', 'is_common' => true, 'aliases' => ['.22 Long Rifle', '.22']],
            ['name' => '.22 WMR', 'ignition_type' => 'rimfire', 'is_common' => true, 'aliases' => ['.22 Magnum', '.22 Winchester Magnum Rimfire']],
            
            // Centerfire - Small
            ['name' => '.25 ACP', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['6.35mm Browning', '.25 Auto']],
            ['name' => '.25 NAA', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.30 Luger', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['7.65mm Parabellum', '7.65x21mm']],
            ['name' => '.32 ACP', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7.65mm Browning', '.32 Auto']],
            ['name' => '.32 S&W', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.32 S&W Long', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.32 S&W L']],
            ['name' => '.32 H&R Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.327 Federal Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            
            // Centerfire - Medium
            ['name' => '.38 S&W', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.38 Special', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.38 Spl', '.38 S&W Special']],
            ['name' => '.357 Magnum', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.357 Mag']],
            ['name' => '.357 SIG', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['357 SIG']],
            ['name' => '.380 ACP', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['9mm Short', '9x17mm', '.380 Auto']],
            ['name' => '9×18 Makarov', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['9mm Makarov']],
            ['name' => '9×19 Parabellum', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['9mm Luger', '9mm Para', '9mm']],
            ['name' => '9×21 IMI', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '9×23 Winchester', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            
            // Centerfire - Large
            ['name' => '.40 S&W', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['40 S&W', '10mm Short']],
            ['name' => '.41 Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.41 Rem Mag']],
            ['name' => '10mm Auto', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['10mm']],
            ['name' => '.44 Russian', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.44 Special', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.44 Spl']],
            ['name' => '.44 Magnum', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.44 Mag', '.44 Rem Mag']],
            ['name' => '.45 ACP', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.45 Auto', '11.43x23mm']],
            ['name' => '.45 GAP', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.45 Glock Auto Pistol']],
            ['name' => '.45 Colt', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.45 Long Colt', '.45 LC']],
            
            // Centerfire - Magnum/Large Bore
            ['name' => '.454 Casull', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.460 S&W Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.480 Ruger', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.500 Linebaugh', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.500 JRH', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.500 S&W Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.500 S&W']],
            ['name' => '.50 Action Express', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.50 AE']],
        ];

        // Rifle Calibres - Rimfire (including rare/obsolete)
        $rifleRimfireCalibres = [
            ['name' => '2mm Kolibri', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '4mm Randzünder', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['4mm Rimfire']],
            ['name' => '.17 Aguila', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.17 HM2', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => ['.17 Hornady Mach 2']],
            ['name' => '.17 HMR', 'ignition_type' => 'rimfire', 'is_common' => true, 'aliases' => ['.17 Hornady Magnum Rimfire']],
            ['name' => '.17 WSM', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => ['.17 Winchester Super Magnum']],
            ['name' => '.22 BB Cap', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.22 CB', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => ['.22 CB Cap']],
            ['name' => '.22 CB Long', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.22 Short', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.22 Long', 'ignition_type' => 'rimfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.22 Long Rifle', 'ignition_type' => 'rimfire', 'is_common' => true, 'aliases' => ['.22 LR', '.22']],
            ['name' => '.22 Extra Long', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.22 WRF', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['.22 Winchester Rimfire']],
            ['name' => '.22 WMR', 'ignition_type' => 'rimfire', 'is_common' => true, 'aliases' => ['.22 Magnum', '.22 Winchester Magnum Rimfire']],
            ['name' => '.25 Stevens', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.25 Stevens Short', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.32 Rimfire', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.32 Rimfire Long', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.38 Rimfire', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.38 Rimfire Long', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.41 Swiss Rimfire', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['10.4mm Swiss']],
            ['name' => '.44 Henry Rimfire', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['.44 Henry']],
            ['name' => '.56-52 Spencer', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.56-56 Spencer', 'ignition_type' => 'rimfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
        ];

        // Rifle Calibres - Centerfire (comprehensive list)
        $rifleCenterfireCalibres = [
            // .14-.17 calibre (Wildcats and small varmint)
            ['name' => '.14-221 Walker', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.17 Hornet', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.17 Remington', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.17 Remington Fireball', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.17 Tactical', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.19 Calhoon', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            
            // .20 calibre
            ['name' => '.20 Practical', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.20 VarTarg', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.204 Ruger', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => []],
            
            // .22 calibre centerfire
            ['name' => '.218 Bee', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.219 Zipper', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.220 Russian', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.220 Swift', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => []],
            ['name' => '.221 Fireball', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.222 Remington', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.222 Rem', 'Triple Deuce']],
            ['name' => '.222 Remington Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.222 Rem Mag']],
            ['name' => '.223 Remington', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.223 Rem', '.223']],
            ['name' => '5.56×45 NATO', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['5.56 NATO', '5.56x45']],
            ['name' => '.224 Lancer', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.224 Predator', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.224 Valkyrie', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => []],
            ['name' => '.225 Winchester', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.227 Fury', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.22 Nosler', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.22 PPC', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.22-250 Remington', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.22-250', '.22-250 Rem']],
            
            // 6mm/.24 calibre
            ['name' => '.240 Weatherby Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.243 Winchester', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.243 Win', '.243']],
            ['name' => '.244 Remington', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.246 Sisk', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.250 Savage', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.250-3000']],
            ['name' => '.25 Souper', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.25-06 Remington', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.25-06', '.25-06 Rem']],
            ['name' => '.257 Roberts', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.257 Bob']],
            ['name' => '.257 Weatherby Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.260 Remington', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.260 Rem']],
            ['name' => '6mm ARC', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['6mm Advanced Rifle Cartridge']],
            ['name' => '6mm BR', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['6mm Bench Rest']],
            ['name' => '6mm Dasher', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '6mm PPC', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '6mm Remington', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => []],
            ['name' => '6mm Creedmoor', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['6mm CM']],
            
            // 6.5mm calibre
            ['name' => '6.5×47 Lapua', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => []],
            ['name' => '6.5×50 Arisaka', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['6.5 Japanese']],
            ['name' => '6.5×52 Carcano', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['6.5 Carcano']],
            ['name' => '6.5×54 Mannlicher', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['6.5 Mannlicher-Schönauer']],
            ['name' => '6.5×55 Swedish', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['6.5x55 Swede', '6.5 Swedish Mauser']],
            ['name' => '6.5 Creedmoor', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['6.5 CM', '6.5 Creed']],
            ['name' => '6.5 PRC', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['6.5 Precision Rifle Cartridge']],
            
            // 6.8mm/.270 calibre
            ['name' => '6.8 SPC', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['6.8 Remington SPC', '6.8x43mm']],
            ['name' => '.264 Winchester Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.270 Winchester', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.270 Win', '.270']],
            ['name' => '.270 Weatherby Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.270 WSM', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.270 Winchester Short Magnum']],
            ['name' => '.275 Rigby', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            
            // 7mm calibre
            ['name' => '.280 Remington', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7mm Express Remington']],
            ['name' => '.280 Ackley Improved', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.280 AI']],
            ['name' => '7×57 Mauser', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7mm Mauser']],
            ['name' => '7×64 Brenneke', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '7mm-08 Remington', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7mm-08', '7mm-08 Rem']],
            ['name' => '7mm Remington Magnum', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7mm Rem Mag', '7mm Mag']],
            ['name' => '7mm STW', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['7mm Shooting Times Westerner']],
            ['name' => '7mm Weatherby Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '7mm PRC', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7mm Precision Rifle Cartridge']],
            
            // .30 calibre
            ['name' => '.300 AAC Blackout', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.300 BLK', '300 Blackout', '7.62x35mm']],
            ['name' => ".300 Ham'r", 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.300 Hamr']],
            ['name' => '.300 Savage', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.300 H&H Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.300 Holland & Holland']],
            ['name' => '.300 Winchester Magnum', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.300 Win Mag', '.300 WM']],
            ['name' => '.300 WSM', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.300 Winchester Short Magnum']],
            ['name' => '.300 Weatherby Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.300 PRC', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.300 Precision Rifle Cartridge']],
            ['name' => '.303 British', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.303', '7.7x56mmR']],
            ['name' => '.307 Winchester', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.308 Winchester', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.308 Win', '.308']],
            ['name' => '7.62×39', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7.62x39mm', '7.62 Soviet']],
            ['name' => '7.62×51 NATO', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7.62 NATO', '7.62x51']],
            ['name' => '7.62×54R', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7.62x54mmR', '7.62 Russian']],
            ['name' => '.30-06 Springfield', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.30-06', '30-06', '7.62x63mm']],
            ['name' => '.30-40 Krag', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.30 US Army']],
            ['name' => '.30 Nosler', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.30-30 Winchester', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.30-30', '.30 WCF']],
            ['name' => '.30 Carbine', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['7.62x33mm']],
            
            // .318-.338 calibre
            ['name' => '.318 Westley Richards', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.333 Jeffery', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            ['name' => '.338 Federal', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.338 Winchester Magnum', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.338 Win Mag']],
            ['name' => '.338 Lapua Magnum', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.338 Lapua', '8.6x70mm']],
            ['name' => '.340 Weatherby Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.348 Winchester', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            
            // .35 calibre
            ['name' => '.35 Remington', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => []],
            ['name' => '.35 Whelen', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.358 Winchester', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.360 Buckhammer', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.366 TKM', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            
            // .375-.378 calibre
            ['name' => '.375 Winchester', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.375 H&H Magnum', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.375 Holland & Holland', '.375 H&H']],
            ['name' => '.375 Ruger', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.375 EnABLER', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.376 Steyr', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.378 Weatherby Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            
            // .404-.425 calibre
            ['name' => '.404 Jeffery', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['10.75x73mm']],
            ['name' => '.408 CheyTac', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['10.36x77mm']],
            ['name' => '.416 Rigby', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.416 Remington Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.416 Weatherby Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.423 Okapi', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.425 Westley Richards', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => []],
            
            // .45-.475 calibre
            ['name' => '.45-70 Government', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.45-70', '.45-70 Govt']],
            ['name' => '.450 Bushmaster', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => []],
            ['name' => '.450 Rigby', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.450/400 Nitro Express', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.400 Jeffery']],
            ['name' => '.458 SOCOM', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => []],
            ['name' => '.458 Winchester Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.458 Win Mag']],
            ['name' => '.458 Lott', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.460 Weatherby Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.470 Capstick', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.470 Nitro Express', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.470 NE']],
            ['name' => '.475 Turnbull', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            
            // .50+ calibre (African/Extreme)
            ['name' => '.500 Jeffery', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['12.7x70mm Schuler']],
            ['name' => '.500 Nitro Express', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.500 NE']],
            ['name' => '.505 Gibbs', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.510 Wells', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.50 Beowulf', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['12.7x42mm']],
            ['name' => '.50 BMG', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['12.7x99mm NATO', '.50 Browning']],
            ['name' => '.50 DTC Europ', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
            ['name' => '.50 McMillan', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.50 McMillan Fat Mac']],
            ['name' => '.55 Boys', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['.55 Boys Anti-Tank']],
            ['name' => '.577 Tyrannosaur', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.577 T-Rex']],
            ['name' => '.577 Nitro Express', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.577 NE']],
            ['name' => '.600 Nitro Express', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.600 NE']],
            ['name' => '.700 Nitro Express', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['.700 NE']],
            
            // 8mm calibre (added for completeness)
            ['name' => '8×57 Mauser', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['8mm Mauser', '7.92x57mm']],
            ['name' => '8mm Remington Magnum', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => []],
        ];

        // Shotgun Calibres (Gauges)
        $shotgunCalibres = [
            ['name' => '.410 Bore', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['.410', '.410 Gauge']],
            ['name' => '28 Gauge', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['28 Ga', '28ga']],
            ['name' => '24 Gauge', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['24 Ga']],
            ['name' => '20 Gauge', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['20 Ga', '20ga']],
            ['name' => '16 Gauge', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['16 Ga', '16ga']],
            ['name' => '12 Gauge', 'ignition_type' => 'centerfire', 'is_common' => true, 'aliases' => ['12 Ga', '12ga']],
            ['name' => '10 Gauge', 'ignition_type' => 'centerfire', 'is_common' => false, 'aliases' => ['10 Ga', '10ga']],
            ['name' => '8 Gauge', 'ignition_type' => 'centerfire', 'is_common' => false, 'is_obsolete' => true, 'aliases' => ['8 Ga']],
        ];

        // Seed handgun calibres
        $sortOrder = 100;
        foreach ($handgunCalibres as $calibre) {
            $this->createCalibre($calibre, 'handgun', $sortOrder++);
        }

        // Seed rifle rimfire calibres
        $sortOrder = 200;
        foreach ($rifleRimfireCalibres as $calibre) {
            $this->createCalibre($calibre, 'rifle', $sortOrder++);
        }

        // Seed rifle centerfire calibres
        $sortOrder = 300;
        foreach ($rifleCenterfireCalibres as $calibre) {
            $this->createCalibre($calibre, 'rifle', $sortOrder++);
        }

        // Seed shotgun calibres
        $sortOrder = 500;
        foreach ($shotgunCalibres as $calibre) {
            $this->createCalibre($calibre, 'shotgun', $sortOrder++);
        }
    }

    /**
     * Create or update a calibre.
     */
    protected function createCalibre(array $data, string $category, int $sortOrder): void
    {
        Calibre::updateOrCreate(
            ['slug' => Str::slug($data['name'])],
            [
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'category' => $category,
                'ignition_type' => $data['ignition_type'],
                'aliases' => $data['aliases'] ?? [],
                'is_active' => true,
                'is_common' => $data['is_common'] ?? false,
                'is_obsolete' => $data['is_obsolete'] ?? false,
                'sort_order' => $sortOrder,
            ]
        );
    }
}
