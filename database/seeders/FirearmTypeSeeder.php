<?php

namespace Database\Seeders;

use App\Models\FirearmType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FirearmTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $firearmTypes = [
            // ===== HANDGUNS =====
            [
                'name' => 'Revolver',
                'category' => 'handgun',
                'ignition_type' => 'both',
                'action_type' => 'revolver',
                'description' => 'Manual action handgun with a rotating cylinder',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Semi-Auto Pistol',
                'category' => 'handgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'semi_auto',
                'description' => 'Self-loading handgun that fires one round per trigger pull',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Single Shot Pistol',
                'category' => 'handgun',
                'ignition_type' => 'both',
                'action_type' => 'single_shot',
                'description' => 'Manual action handgun requiring reload after each shot',
                'dedicated_type' => 'both',
            ],
            
            // ===== RIFLES =====
            [
                'name' => 'Bolt-Action Rifle',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'bolt_action',
                'description' => 'Manual action rifle with a bolt mechanism',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Semi-Auto Rifle',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'semi_auto',
                'description' => 'Self-loading rifle that fires one round per trigger pull',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Lever-Action Rifle',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'lever_action',
                'description' => 'Manual action rifle operated by a lever mechanism',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Single Shot Rifle',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'single_shot',
                'description' => 'Manual action rifle requiring reload after each shot',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Pump-Action Rifle',
                'category' => 'rifle',
                'ignition_type' => 'both',
                'action_type' => 'pump_action',
                'description' => 'Manual action rifle operated by a pump/slide mechanism',
                'dedicated_type' => 'both',
            ],
            
            // ===== SHOTGUNS =====
            [
                'name' => 'Break-Action Shotgun',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'break_action',
                'description' => 'Manual action shotgun that breaks open to load/unload (single, double barrel, over/under)',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Pump-Action Shotgun',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'pump_action',
                'description' => 'Manual action shotgun operated by a pump/slide mechanism',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Semi-Auto Shotgun',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'semi_auto',
                'description' => 'Self-loading shotgun that fires one round per trigger pull',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Bolt-Action Shotgun',
                'category' => 'shotgun',
                'ignition_type' => 'centerfire',
                'action_type' => 'bolt_action',
                'description' => 'Manual action shotgun with a bolt mechanism',
                'dedicated_type' => 'both',
            ],
            [
                'name' => 'Lever-Action Shotgun',
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

        $this->command->info('Seeded ' . count($firearmTypes) . ' firearm types.');
    }
}
