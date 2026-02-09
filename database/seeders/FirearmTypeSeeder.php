<?php

namespace Database\Seeders;

use App\Models\FirearmType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FirearmTypeSeeder extends Seeder
{
    /**
     * SAPS 350A FirearmTypes dropdown codes.
     *
     * 20  = PISTOL
     * 21  = REVOLVER
     * 22  = SHOTGUN
     * 23  = RIFLE
     * 24  = COMBINATION
     * 185 = MACHINE GUN
     * 187 = S/L: PIST CAL - RIFLE/CARB
     * 600 = PRIMERS
     * 1013 = MAIN FIREARM COMPONENT
     * 2060 = S/L: RIFLE CAL - RIFLE/CARBINE
     * 2072 = FULL-AUTO: PIST CAL - RIFLE/CARB
     * 2073 = FULL-AUTO:RIFLE CAL-RIFLE/CARB
     */
    public function run(): void
    {
        $firearmTypes = [
            // ===== HANDGUNS =====
            [
                'name' => 'Revolver',
                'saps_code' => '21',
                'category' => 'handgun',
                'ignition_type' => 'both',
                'action_type' => 'revolver',
                'description' => 'Manual action handgun with a rotating cylinder',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Semi-Auto Pistol',
                'saps_code' => '20',
                'category' => 'handgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'semi_auto',
                'description' => 'Self-loading handgun that fires one round per trigger pull',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Single Shot Pistol',
                'saps_code' => '20',
                'category' => 'handgun',
                'ignition_type' => 'both',
                'action_type' => 'single_shot',
                'description' => 'Manual action handgun requiring reload after each shot',
                'dedicated_type' => 'both',
            ],
            
            // ===== RIFLES =====
            [
                'name' => 'Bolt-Action Rifle',
                'saps_code' => '23',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'bolt_action',
                'description' => 'Manual action rifle with a bolt mechanism',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Semi-Auto Rifle',
                'saps_code' => '23',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'semi_auto',
                'description' => 'Self-loading rifle that fires one round per trigger pull',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Lever-Action Rifle',
                'saps_code' => '23',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'lever_action',
                'description' => 'Manual action rifle operated by a lever mechanism',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Single Shot Rifle',
                'saps_code' => '23',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'single_shot',
                'description' => 'Manual action rifle requiring reload after each shot',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Pump-Action Rifle',
                'saps_code' => '23',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'pump_action',
                'description' => 'Manual action rifle operated by a pump/slide mechanism',
                'dedicated_type' => 'both',
            ],
            
            // ===== SHOTGUNS =====
            [
                'name' => 'Break-Action Shotgun',
                'saps_code' => '22',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'break_action',
                'description' => 'Manual action shotgun that breaks open to load/unload (single, double barrel, over/under)',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Pump-Action Shotgun',
                'saps_code' => '22',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'pump_action',
                'description' => 'Manual action shotgun operated by a pump/slide mechanism',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Semi-Auto Shotgun',
                'saps_code' => '22',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'semi_auto',
                'description' => 'Self-loading shotgun that fires one round per trigger pull',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Bolt-Action Shotgun',
                'saps_code' => '22',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'bolt_action',
                'description' => 'Manual action shotgun with a bolt mechanism',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Lever-Action Shotgun',
                'saps_code' => '22',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'lever_action',
                'description' => 'Manual action shotgun operated by a lever mechanism',
                'dedicated_type' => 'both',
            ],
        ];

        $sortOrder = 0;
        foreach ($firearmTypes as $type) {
            $sortOrder++;
            
            FirearmType::updateOrCreate(
                ['slug' => Str::slug($type['name'])],
                [
                    'name' => $type['name'],
                    'saps_code' => $type['saps_code'],
                    'category' => $type['category'],
                    'ignition_type' => $type['ignition_type'],
                    'action_type' => $type['action_type'],
                    'description' => $type['description'],
                    'dedicated_type' => $type['dedicated_type'],
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );
        }

        $this->command->info('Seeded ' . count($firearmTypes) . ' firearm types with SAPS codes.');
    }
}
