<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use App\Models\Calibre;
use App\Models\Country;
use App\Models\EventCategory;
use App\Models\EventType;
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
        $this->seedEventCategories();
        $this->seedEventTypes();
        $this->seedFirearmTypes();
        $this->seedCalibres();
        $this->seedProvinces();
        $this->seedCountries();
    }

    /**
     * Seed activity types (Related Activity).
     */
    private function seedActivityTypes(): void
    {
        $types = [
            ['name' => 'Dedicated Hunting', 'dedicated_type' => 'hunter'],
            ['name' => 'Dedicated Sport-Shooting', 'dedicated_type' => 'sport'],
        ];

        foreach ($types as $index => $type) {
            ActivityType::updateOrCreate(
                ['slug' => Str::slug($type['name'])],
                [
                    'name' => $type['name'],
                    'dedicated_type' => $type['dedicated_type'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }

    /**
     * Seed event categories (Type of Activity).
     */
    private function seedEventCategories(): void
    {
        $huntingType = ActivityType::where('slug', 'dedicated-hunting')->first();
        $sportType = ActivityType::where('slug', 'dedicated-sport-shooting')->first();

        // Hunting-specific categories
        $huntingCategories = [
            ['name' => 'Hunting Safari/Outing', 'dedicated_type' => 'hunter'],
            ['name' => 'Hunting Expo/Show', 'dedicated_type' => 'hunter'],
            ['name' => 'Hunting Meeting', 'dedicated_type' => 'hunter'],
            ['name' => 'Hunting Related Course', 'dedicated_type' => 'hunter'],
            ['name' => 'Hunting Related Activity', 'dedicated_type' => 'hunter'],
        ];

        foreach ($huntingCategories as $index => $category) {
            EventCategory::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => strtoupper($category['name']),
                    'activity_type_id' => $huntingType?->id,
                    'dedicated_type' => $category['dedicated_type'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        // Sport-shooting specific categories
        $sportCategories = [
            ['name' => 'Sport-Shooting Meeting', 'dedicated_type' => 'sport'],
            ['name' => 'Sport-Shooting Related Activity', 'dedicated_type' => 'sport'],
        ];

        foreach ($sportCategories as $index => $category) {
            EventCategory::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => strtoupper($category['name']),
                    'activity_type_id' => $sportType?->id,
                    'dedicated_type' => $category['dedicated_type'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        // Categories that apply to BOTH hunter and sport shooter
        $bothCategories = [
            ['name' => 'Hunt & Sport-Shooting Expo/Show', 'activity_type_id' => null],
            ['name' => 'Load Development and/or Reloading Course', 'activity_type_id' => null],
            ['name' => 'Firearm Training Course', 'activity_type_id' => null],
            ['name' => 'Range Officer Duties', 'activity_type_id' => null],
        ];

        foreach ($bothCategories as $index => $category) {
            EventCategory::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => strtoupper($category['name']),
                    'activity_type_id' => $category['activity_type_id'],
                    'dedicated_type' => 'both',
                    'is_active' => true,
                    'sort_order' => 100 + $index, // Put after specific categories
                ]
            );
        }
    }

    /**
     * Seed event types.
     */
    private function seedEventTypes(): void
    {
        // Sport-shooting event types
        $sportEventTypes = [
            'Long Distance Shooting Course',
            'Organised Shooting Competition/Event',
            'Range Officer Duties',
            'Sport Shooting Target Practice',
            'Sport Shooting Training Course',
            'Long Range',
            'Silhouette',
            'Veldskiet',
            'Practical',
            'Precision',
            'IPSC',
            'IDPA',
            '3-Gun',
            'Cowboy Action',
            'Benchrest',
            'F-Class',
            'PRS',
            'NRL',
        ];

        // Hunting event types
        $huntingEventTypes = [
            'Big Game Hunt',
            'Plains Game Hunt',
            'Small Game Hunt',
            'Bird Hunting',
            'Bow Hunting',
            'Hunting Training Course',
            'Game Management Course',
            'Hunting Guide Duties',
        ];

        // Get category IDs for sport-shooting activities
        $sportCategory = EventCategory::where('slug', 'sport-shooting-related-activity')->first();

        foreach ($sportEventTypes as $index => $name) {
            EventType::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => strtoupper($name),
                    'event_category_id' => $sportCategory?->id,
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        // Get category IDs for hunting activities
        $huntingCategory = EventCategory::where('slug', 'hunting-related-activity')->first();

        foreach ($huntingEventTypes as $index => $name) {
            EventType::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => strtoupper($name),
                    'event_category_id' => $huntingCategory?->id,
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }

    /**
     * Seed firearm types.
     */
    private function seedFirearmTypes(): void
    {
        $types = [
            ['name' => 'Barrel (Main Firearm Component)', 'dedicated_type' => 'both'],
            ['name' => 'Combination', 'dedicated_type' => 'both'],
            ['name' => 'Competency', 'dedicated_type' => 'both'],
            ['name' => 'Handgun', 'dedicated_type' => 'sport'],
            ['name' => 'Other', 'dedicated_type' => 'both'],
            ['name' => 'Pistol', 'dedicated_type' => 'sport'],
            ['name' => 'Receiver (Main Firearm Component)', 'dedicated_type' => 'both'],
            ['name' => 'Revolver', 'dedicated_type' => 'sport'],
            ['name' => 'Rifle', 'dedicated_type' => 'both'],
            ['name' => 'Self Loading Pistol/Carbine', 'dedicated_type' => 'sport'],
            ['name' => 'Self Loading Rifle/Carbine', 'dedicated_type' => 'both'],
            ['name' => 'Shotgun', 'dedicated_type' => 'both'],
        ];

        foreach ($types as $index => $type) {
            FirearmType::updateOrCreate(
                ['slug' => Str::slug($type['name'])],
                [
                    'name' => strtoupper($type['name']),
                    'dedicated_type' => $type['dedicated_type'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }

    /**
     * Seed common calibres.
     */
    private function seedCalibres(): void
    {
        $calibres = [
            // Rifle calibres
            ['name' => '.22 LR', 'category' => 'rifle'],
            ['name' => '.22 WMR', 'category' => 'rifle'],
            ['name' => '.223 Remington / 5.56x45mm', 'category' => 'rifle'],
            ['name' => '.243 Winchester', 'category' => 'rifle'],
            ['name' => '.270 Winchester', 'category' => 'rifle'],
            ['name' => '.30-06 Springfield', 'category' => 'rifle'],
            ['name' => '.300 Winchester Magnum', 'category' => 'rifle'],
            ['name' => '.308 Winchester / 7.62x51mm', 'category' => 'rifle'],
            ['name' => '.338 Lapua Magnum', 'category' => 'rifle'],
            ['name' => '.375 H&H Magnum', 'category' => 'rifle'],
            ['name' => '.416 Rigby', 'category' => 'rifle'],
            ['name' => '.458 Winchester Magnum', 'category' => 'rifle'],
            ['name' => '6.5 Creedmoor', 'category' => 'rifle'],
            ['name' => '7mm Remington Magnum', 'category' => 'rifle'],
            ['name' => '7.62x39mm', 'category' => 'rifle'],

            // Handgun calibres
            ['name' => '.22 LR (Handgun)', 'category' => 'handgun'],
            ['name' => '.32 ACP', 'category' => 'handgun'],
            ['name' => '.38 Special', 'category' => 'handgun'],
            ['name' => '.357 Magnum', 'category' => 'handgun'],
            ['name' => '.40 S&W', 'category' => 'handgun'],
            ['name' => '.44 Magnum', 'category' => 'handgun'],
            ['name' => '.45 ACP', 'category' => 'handgun'],
            ['name' => '9mm Parabellum / 9x19mm', 'category' => 'handgun'],
            ['name' => '10mm Auto', 'category' => 'handgun'],

            // Shotgun calibres
            ['name' => '12 Gauge', 'category' => 'shotgun'],
            ['name' => '16 Gauge', 'category' => 'shotgun'],
            ['name' => '20 Gauge', 'category' => 'shotgun'],
            ['name' => '28 Gauge', 'category' => 'shotgun'],
            ['name' => '.410 Bore', 'category' => 'shotgun'],

            // Other
            ['name' => 'Other', 'category' => 'other'],
        ];

        foreach ($calibres as $index => $calibre) {
            Calibre::updateOrCreate(
                ['slug' => Str::slug($calibre['name'])],
                [
                    'name' => $calibre['name'],
                    'category' => $calibre['category'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }

    /**
     * Seed South African provinces.
     */
    private function seedProvinces(): void
    {
        $provinces = [
            ['code' => 'EC', 'name' => 'Eastern Cape'],
            ['code' => 'FS', 'name' => 'Free State'],
            ['code' => 'GP', 'name' => 'Gauteng'],
            ['code' => 'KZN', 'name' => 'KwaZulu-Natal'],
            ['code' => 'LP', 'name' => 'Limpopo'],
            ['code' => 'MP', 'name' => 'Mpumalanga'],
            ['code' => 'NC', 'name' => 'Northern Cape'],
            ['code' => 'NW', 'name' => 'North West'],
            ['code' => 'WC', 'name' => 'Western Cape'],
        ];

        foreach ($provinces as $province) {
            Province::updateOrCreate(
                ['code' => $province['code']],
                [
                    'name' => $province['name'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Seed countries (with South Africa first).
     */
    private function seedCountries(): void
    {
        $countries = [
            ['code' => 'ZA', 'name' => 'South Africa', 'sort_order' => 0],
            ['code' => 'BW', 'name' => 'Botswana', 'sort_order' => 1],
            ['code' => 'LS', 'name' => 'Lesotho', 'sort_order' => 2],
            ['code' => 'MZ', 'name' => 'Mozambique', 'sort_order' => 3],
            ['code' => 'NA', 'name' => 'Namibia', 'sort_order' => 4],
            ['code' => 'SZ', 'name' => 'Eswatini', 'sort_order' => 5],
            ['code' => 'ZW', 'name' => 'Zimbabwe', 'sort_order' => 6],
            ['code' => 'ZM', 'name' => 'Zambia', 'sort_order' => 7],
            ['code' => 'TZ', 'name' => 'Tanzania', 'sort_order' => 8],
            ['code' => 'XX', 'name' => 'Other', 'sort_order' => 999],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                [
                    'name' => $country['name'],
                    'is_active' => true,
                    'sort_order' => $country['sort_order'],
                ]
            );
        }
    }
}
