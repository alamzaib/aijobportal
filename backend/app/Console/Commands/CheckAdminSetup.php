<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class CheckAdminSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:check-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if admin setup is correct (roles exist, users have roles, etc.)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking Admin Setup...');
        $this->newLine();

        // Check if roles table exists
        try {
            $roles = Role::all();
            $this->info('✓ Roles table exists');
        } catch (\Exception $e) {
            $this->error('✗ Roles table does not exist. Run migrations first:');
            $this->line('  php artisan migrate');
            $this->line('  php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"');
            return Command::FAILURE;
        }

        // Check if roles are seeded
        $requiredRoles = ['Admin', 'Employer', 'Candidate'];
        $existingRoles = Role::pluck('name')->toArray();
        $missingRoles = array_diff($requiredRoles, $existingRoles);

        if (empty($missingRoles)) {
            $this->info('✓ All required roles exist: ' . implode(', ', $requiredRoles));
        } else {
            $this->warn('✗ Missing roles: ' . implode(', ', $missingRoles));
            $this->line('  Run: php artisan db:seed --class=RoleSeeder');
        }

        // Check users with Admin role
        $adminUsers = User::role('Admin')->get();
        if ($adminUsers->count() > 0) {
            $this->info("✓ Found {$adminUsers->count()} user(s) with Admin role:");
            foreach ($adminUsers as $user) {
                $this->line("  - {$user->email} (ID: {$user->id})");
            }
        } else {
            $this->warn('✗ No users have Admin role assigned');
            $this->line('  Assign Admin role using: php artisan user:assign-admin email@example.com');
        }

        // Check total users
        $totalUsers = User::count();
        $this->info("✓ Total users in database: {$totalUsers}");

        // Check users without roles
        $usersWithoutRoles = User::doesntHave('roles')->get();
        if ($usersWithoutRoles->count() > 0) {
            $this->warn("⚠ Found {$usersWithoutRoles->count()} user(s) without any roles:");
            foreach ($usersWithoutRoles->take(5) as $user) {
                $this->line("  - {$user->email}");
            }
            if ($usersWithoutRoles->count() > 5) {
                $this->line("  ... and " . ($usersWithoutRoles->count() - 5) . " more");
            }
        }

        $this->newLine();
        $this->info('Setup check complete!');

        return Command::SUCCESS;
    }
}

