<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use App\Models\ActivityTag;
use App\Models\Calibre;
use App\Models\Country;
use App\Models\FirearmType;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ActivityConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedActivityTypes();
        $this->seedActivityTags();
        // Note: Firearm types and calibres are now seeded via FormDataSeeder
        // Provinces and countries are also seeded via FormDataSeeder
    }

    /**
     * Seed activity types.
     */
    private function seedActivityTypes(): void
    {
        $types = [
            // Hunting track
            ['name' => 'Hunting Safari/Outing', 'track' => 'hunting', 'group' => 'Hunting'],
            ['name' => 'Hunting Expo/Show', 'track' => 'hunting', 'group' => 'Expos'],
            ['name' => 'Hunting Meeting', 'track' => 'hunting', 'group' => 'Meetings'],
            ['name' => 'Hunting Related Course', 'track' => 'hunting', 'group' => 'Training'],
            ['name' => 'Hunting Related Activity', 'track' => 'hunting', 'group' => 'Hunting'],
            ['name' => 'Big Game Hunt', 'track' => 'hunting', 'group' => 'Hunting'],
            ['name' => 'Plains Game Hunt', 'track' => 'hunting', 'group' => 'Hunting'],
            ['name' => 'Small Game Hunt', 'track' => 'hunting', 'group' => 'Hunting'],
            ['name' => 'Bird Hunting', 'track' => 'hunting', 'group' => 'Hunting'],
            ['name' => 'Bow Hunting', 'track' => 'hunting', 'group' => 'Hunting'],
            ['name' => 'Hunting Training Course', 'track' => 'hunting', 'group' => 'Training'],
            ['name' => 'Game Management Course', 'track' => 'hunting', 'group' => 'Training'],
            ['name' => 'Hunting Guide Duties', 'track' => 'hunting', 'group' => 'Other'],
            
            // Sport shooting track
            ['name' => 'Sport-Shooting Meeting', 'track' => 'sport', 'group' => 'Meetings'],
            ['name' => 'Sport-Shooting Related Activity', 'track' => 'sport', 'group' => 'Competitions'],
            ['name' => 'Long Distance Shooting Course', 'track' => 'sport', 'group' => 'Training'],
            ['name' => 'Organised Shooting Competition/Event', 'track' => 'sport', 'group' => 'Competitions'],
            ['name' => 'Range Officer Duties', 'track' => 'sport', 'group' => 'Other'],
            ['name' => 'Sport Shooting Target Practice', 'track' => 'sport', 'group' => 'Competitions'],
            ['name' => 'Sport Shooting Training Course', 'track' => 'sport', 'group' => 'Training'],
            
            // Both tracks (can be used by either)
            ['name' => 'Hunt & Sport-Shooting Expo/Show', 'track' => null, 'group' => 'Expos'],
            ['name' => 'Load Development and/or Reloading Course', 'track' => null, 'group' => 'Training'],
            ['name' => 'Firearm Training Course', 'track' => null, 'group' => 'Training'],
        ];

        foreach ($types as $index => $type) {
            ActivityType::updateOrCreate(
                ['slug' => Str::slug($type['name'])],
                [
                    'name' => $type['name'],
                    'track' => $type['track'],
                    'group' => $type['group'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }

    /**
     * Seed activity tags (optional tags like PRS, IPSC, IDPA).
     * These were previously event types.
     */
    private function seedActivityTags(): void
    {
        // Sport-shooting tags
        $sportTags = [
            ['key' => 'long-range', 'label' => 'Long Range', 'track' => 'sport'],
            ['key' => 'silhouette', 'label' => 'Silhouette', 'track' => 'sport'],
            ['key' => 'veldskiet', 'label' => 'Veldskiet', 'track' => 'sport'],
            ['key' => 'practical', 'label' => 'Practical', 'track' => 'sport'],
            ['key' => 'precision', 'label' => 'Precision', 'track' => 'sport'],
            ['key' => 'ipsc', 'label' => 'IPSC', 'track' => 'sport'],
            ['key' => 'idpa', 'label' => 'IDPA', 'track' => 'sport'],
            ['key' => '3-gun', 'label' => '3-Gun', 'track' => 'sport'],
            ['key' => 'cowboy-action', 'label' => 'Cowboy Action', 'track' => 'sport'],
            ['key' => 'benchrest', 'label' => 'Benchrest', 'track' => 'sport'],
            ['key' => 'f-class', 'label' => 'F-Class', 'track' => 'sport'],
            ['key' => 'prs', 'label' => 'PRS', 'track' => 'sport'],
            ['key' => 'nrl', 'label' => 'NRL', 'track' => 'sport'],
        ];

        // Hunting tags
        $huntingTags = [
            ['key' => 'big-game', 'label' => 'Big Game', 'track' => 'hunting'],
            ['key' => 'plains-game', 'label' => 'Plains Game', 'track' => 'hunting'],
            ['key' => 'small-game', 'label' => 'Small Game', 'track' => 'hunting'],
            ['key' => 'bird-hunting', 'label' => 'Bird Hunting', 'track' => 'hunting'],
            ['key' => 'bow-hunting', 'label' => 'Bow Hunting', 'track' => 'hunting'],
        ];

        $allTags = array_merge($sportTags, $huntingTags);

        foreach ($allTags as $index => $tag) {
            ActivityTag::updateOrCreate(
                ['key' => $tag['key']],
                [
                    'label' => $tag['label'],
                    'track' => $tag['track'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
