<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed membership configuration first
        $this->call(MembershipConfigurationSeeder::class);

        // Seed activity configuration
        $this->call(ActivityConfigurationSeeder::class);

        // Create admin user
        $admin = User::factory()->create([
            'uuid' => Str::uuid(),
            'name' => 'NRAPA Admin',
            'email' => 'admin@nrapa.co.za',
            'is_admin' => true,
        ]);

        // Assign super-admin role
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $admin->roles()->attach($superAdminRole);
        }

        // Create test member
        User::factory()->create([
            'uuid' => Str::uuid(),
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'is_admin' => false,
        ]);
    }
}
