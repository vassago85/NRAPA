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

        // Create developer user (Site Developer - Paul Charsley)
        $developer = User::factory()->create([
            'uuid' => Str::uuid(),
            'name' => 'Paul Charsley',
            'email' => 'paul@charsley.co.za',
            'password' => bcrypt('PaulCharsley2026!'),
            'is_admin' => true,
            'role' => User::ROLE_DEVELOPER,
        ]);

        // Create admin user (legacy - will be managed by owners)
        $admin = User::factory()->create([
            'uuid' => Str::uuid(),
            'name' => 'NRAPA Admin',
            'email' => 'admin@nrapa.co.za',
            'is_admin' => true,
            'role' => User::ROLE_ADMIN,
            'nominated_by' => $developer->id,
            'nominated_at' => now(),
        ]);

        // Assign super-admin role if exists
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
            'role' => User::ROLE_MEMBER,
        ]);
    }
}
