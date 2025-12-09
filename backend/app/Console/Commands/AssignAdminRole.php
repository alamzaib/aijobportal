<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-admin {email : The email of the user to assign Admin role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign Admin role to a user by email';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return Command::FAILURE;
        }

        // Check if roles exist
        try {
            $adminRole = \Spatie\Permission\Models\Role::where('name', 'Admin')->first();
            
            if (!$adminRole) {
                $this->error('Admin role does not exist. Please run: php artisan db:seed --class=RoleSeeder');
                return Command::FAILURE;
            }

            $user->assignRole('Admin');
            
            // Clear Spatie permissions cache to ensure changes take effect immediately
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Refresh user model to ensure roles are loaded
            $user->refresh();
            $user->load('roles');
            
            $this->info("Admin role assigned successfully to {$user->name} ({$user->email})");
            $this->info("User roles: " . implode(', ', $user->roles->pluck('name')->toArray()));
            $this->line('');
            $this->comment('Note: If the user is currently logged in, they may need to refresh their browser or log out and log back in.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error assigning role: ' . $e->getMessage());
            $this->info('Make sure you have run: php artisan db:seed --class=RoleSeeder');
            return Command::FAILURE;
        }
    }
}

