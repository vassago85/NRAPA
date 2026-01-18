<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sortOrder = 0;
        $allPermissions = Permission::getAllPermissions();

        foreach ($allPermissions as $group => $permissions) {
            foreach ($permissions as $slug => $data) {
                Permission::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $data['name'],
                        'group' => $group,
                        'description' => $data['description'],
                        'is_high_risk' => $data['high_risk'],
                        'sort_order' => $sortOrder++,
                    ]
                );
            }
        }

        $this->command->info('Permissions seeded successfully!');
    }
}
