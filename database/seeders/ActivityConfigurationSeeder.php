<?php

namespace Database\Seeders;

use App\Models\ActivityTag;
use App\Models\ActivityType;
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
     * Only two activity types: Dedicated Hunting and Dedicated Sport-Shooting.
     * All other details are provided via activity tags.
     */
    private function seedActivityTypes(): void
    {
        $types = [
            ['name' => 'Dedicated Hunting', 'track' => 'hunting', 'group' => null, 'sort_order' => 0],
            ['name' => 'Dedicated Sport-Shooting', 'track' => 'sport', 'group' => null, 'sort_order' => 1],
        ];

        foreach ($types as $type) {
            ActivityType::updateOrCreate(
                ['slug' => Str::slug($type['name'])],
                [
                    'name' => $type['name'],
                    'track' => $type['track'],
                    'group' => $type['group'],
                    'is_active' => true,
                    'sort_order' => $type['sort_order'],
                ]
            );
        }
    }

    /**
     * Seed activity tags.
     * These provide detailed information about the activity (e.g., Safari/Outing, Expo/Show, Training Course, etc.).
     * Previously these were activity types, but now they're tags for more granular categorization.
     */
    private function seedActivityTags(): void
    {
        // Hunting tags (detailed activity types)
        $huntingTags = [
            ['key' => 'hunting-safari-outing', 'label' => 'Hunting Safari/Outing', 'track' => 'hunting', 'sort_order' => 0],
            ['key' => 'hunting-expo-show', 'label' => 'Hunting Expo/Show', 'track' => 'hunting', 'sort_order' => 1],
            ['key' => 'hunting-meeting', 'label' => 'Hunting Meeting', 'track' => 'hunting', 'sort_order' => 2],
            ['key' => 'hunting-related-course', 'label' => 'Hunting Related Course', 'track' => 'hunting', 'sort_order' => 3],
            ['key' => 'hunting-related-activity', 'label' => 'Hunting Related Activity', 'track' => 'hunting', 'sort_order' => 4],
            ['key' => 'big-game-hunt', 'label' => 'Big Game Hunt', 'track' => 'hunting', 'sort_order' => 5],
            ['key' => 'plains-game-hunt', 'label' => 'Plains Game Hunt', 'track' => 'hunting', 'sort_order' => 6],
            ['key' => 'small-game-hunt', 'label' => 'Small Game Hunt', 'track' => 'hunting', 'sort_order' => 7],
            ['key' => 'bird-hunting', 'label' => 'Bird Hunting', 'track' => 'hunting', 'sort_order' => 8],
            ['key' => 'bow-hunting', 'label' => 'Bow Hunting', 'track' => 'hunting', 'sort_order' => 9],
            ['key' => 'hunting-training-course', 'label' => 'Hunting Training Course', 'track' => 'hunting', 'sort_order' => 10],
            ['key' => 'game-management-course', 'label' => 'Game Management Course', 'track' => 'hunting', 'sort_order' => 11],
            ['key' => 'hunting-guide-duties', 'label' => 'Hunting Guide Duties', 'track' => 'hunting', 'sort_order' => 12],
        ];

        // Sport-shooting tags (detailed activity types)
        $sportTags = [
            ['key' => 'sport-shooting-meeting', 'label' => 'Sport-Shooting Meeting', 'track' => 'sport', 'sort_order' => 0],
            ['key' => 'sport-shooting-related-activity', 'label' => 'Sport-Shooting Related Activity', 'track' => 'sport', 'sort_order' => 1],
            ['key' => 'long-distance-shooting-course', 'label' => 'Long Distance Shooting Course', 'track' => 'sport', 'sort_order' => 2],
            ['key' => 'organised-shooting-competition-event', 'label' => 'Organised Shooting Competition/Event', 'track' => 'sport', 'sort_order' => 3],
            ['key' => 'range-officer-duties', 'label' => 'Range Officer Duties', 'track' => 'sport', 'sort_order' => 4],
            ['key' => 'sport-shooting-target-practice', 'label' => 'Sport Shooting Target Practice', 'track' => 'sport', 'sort_order' => 5],
            ['key' => 'sport-shooting-training-course', 'label' => 'Sport Shooting Training Course', 'track' => 'sport', 'sort_order' => 6],
            // Competition/discipline tags
            ['key' => 'long-range', 'label' => 'Long Range', 'track' => 'sport', 'sort_order' => 10],
            ['key' => 'silhouette', 'label' => 'Silhouette', 'track' => 'sport', 'sort_order' => 11],
            ['key' => 'veldskiet', 'label' => 'Veldskiet', 'track' => 'sport', 'sort_order' => 12],
            ['key' => 'practical', 'label' => 'Practical', 'track' => 'sport', 'sort_order' => 13],
            ['key' => 'precision', 'label' => 'Precision', 'track' => 'sport', 'sort_order' => 14],
            ['key' => 'ipsc', 'label' => 'IPSC', 'track' => 'sport', 'sort_order' => 15],
            ['key' => 'idpa', 'label' => 'IDPA', 'track' => 'sport', 'sort_order' => 16],
            ['key' => '3-gun', 'label' => '3-Gun', 'track' => 'sport', 'sort_order' => 17],
            ['key' => 'cowboy-action', 'label' => 'Cowboy Action', 'track' => 'sport', 'sort_order' => 18],
            ['key' => 'benchrest', 'label' => 'Benchrest', 'track' => 'sport', 'sort_order' => 19],
            ['key' => 'f-class', 'label' => 'F-Class', 'track' => 'sport', 'sort_order' => 20],
            ['key' => 'prs', 'label' => 'PRS', 'track' => 'sport', 'sort_order' => 21],
            ['key' => 'nrl', 'label' => 'NRL', 'track' => 'sport', 'sort_order' => 22],
        ];

        // Tags that can be used by both tracks
        $bothTags = [
            ['key' => 'hunt-sport-shooting-expo-show', 'label' => 'Hunt & Sport-Shooting Expo/Show', 'track' => null, 'sort_order' => 100],
            ['key' => 'load-development-reloading-course', 'label' => 'Load Development and/or Reloading Course', 'track' => null, 'sort_order' => 101],
            ['key' => 'firearm-training-course', 'label' => 'Firearm Training Course', 'track' => null, 'sort_order' => 102],
        ];

        $allTags = array_merge($huntingTags, $sportTags, $bothTags);

        foreach ($allTags as $tag) {
            ActivityTag::updateOrCreate(
                ['key' => $tag['key']],
                [
                    'label' => $tag['label'],
                    'track' => $tag['track'],
                    'is_active' => true,
                    'sort_order' => $tag['sort_order'],
                ]
            );
        }
    }
}
