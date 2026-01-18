<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        // Seed permissions
        $this->call(PermissionsSeeder::class);

        // Create developer user (Site Developer - Paul Charsley)
        $developer = User::updateOrCreate(
            ['email' => 'paul@charsley.co.za'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Paul Charsley',
                'password' => Hash::make('PaulCharsley2026!'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'role' => User::ROLE_DEVELOPER,
            ]
        );

        // Create admin user (Super Admin - will be managed by owners)
        $admin = User::updateOrCreate(
            ['email' => 'admin@nrapa.co.za'],
            [
                'uuid' => Str::uuid(),
                'name' => 'NRAPA Admin',
                'password' => Hash::make('NrapaAdmin2026!'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'role' => User::ROLE_ADMIN,
                'admin_type' => User::ADMIN_TYPE_SUPER,
                'nominated_by' => $developer->id,
                'nominated_at' => now(),
            ]
        );

        // Grant default Super Admin permissions
        $defaultPermissions = Permission::getDefaultSuperAdminPermissions();
        $permissions = Permission::whereIn('slug', $defaultPermissions)->pluck('id');
        $syncData = $permissions->mapWithKeys(fn ($id) => [
            $id => [
                'granted_by' => $developer->id,
                'granted_at' => now(),
            ],
        ])->toArray();
        $admin->permissions()->sync($syncData);

        // Create test member
        User::updateOrCreate(
            ['email' => 'member@example.com'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Test Member',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'role' => User::ROLE_MEMBER,
            ]
        );
    }
}
