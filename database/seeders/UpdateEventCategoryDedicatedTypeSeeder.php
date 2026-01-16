<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

class UpdateEventCategoryDedicatedTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hunting-related categories - hunter only
        EventCategory::where('slug', 'hunting')->update(['dedicated_type' => 'hunter']);

        // Shooting competition categories - both
        EventCategory::whereIn('slug', [
            'club-shoot',
            'regional-shoot',
            'provincial-shoot',
            'national-shoot',
            'international-shoot',
            'training-courses',
            'load-development',
        ])->update(['dedicated_type' => 'both']);
    }
}
