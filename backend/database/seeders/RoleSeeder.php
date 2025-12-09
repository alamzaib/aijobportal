<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $employerRole = Role::firstOrCreate(['name' => 'Employer']);
        $candidateRole = Role::firstOrCreate(['name' => 'Candidate']);

        // Create permissions (optional - for more granular control)
        $permissions = [
            'view jobs',
            'create jobs',
            'edit jobs',
            'delete jobs',
            'approve jobs',
            'reject jobs',
            'view users',
            'manage users',
            'block users',
            'assign roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to admin
        $adminRole->givePermissionTo(Permission::all());

        // Assign specific permissions to employer
        $employerRole->givePermissionTo([
            'view jobs',
            'create jobs',
            'edit jobs',
        ]);

        // Assign basic permissions to candidate
        $candidateRole->givePermissionTo([
            'view jobs',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}

