# Admin Panel Setup Instructions

## Installation Steps

1. **Install Spatie Laravel Permission Package**

   ```bash
   cd backend
   composer require spatie/laravel-permission
   ```

2. **Publish Spatie Permission Migrations**

   ```bash
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   ```

3. **Run Migrations**

   ```bash
   php artisan migrate
   ```

4. **Seed Roles**

   ```bash
   php artisan db:seed --class=RoleSeeder
   ```

   Or run all seeders:

   ```bash
   php artisan db:seed
   ```

5. **Create an Admin User**

   **Option 1: Using Artisan Command (Recommended)**

   ```bash
   # First, register/login a user normally through the frontend
   # Then assign Admin role using:
   php artisan user:assign-admin your-email@example.com
   ```

   **Option 2: Using Tinker**

   ```bash
   php artisan tinker
   ```

   Then run:

   ```php
   $user = App\Models\User::create([
       'name' => 'Admin User',
       'email' => 'admin@example.com',
       'password' => bcrypt('password'),
   ]);
   $user->assignRole('Admin');
   ```

   **Option 3: Assign role to existing user**

   ```bash
   php artisan tinker
   ```

   Then run:

   ```php
   $user = App\Models\User::where('email', 'your-email@example.com')->first();
   $user->assignRole('Admin');
   ```

6. **Verify Setup (Optional but Recommended)**

   ```bash
   php artisan admin:check-setup
   ```

   This command will check if:

   - Roles table exists
   - All required roles are created
   - Users have Admin role assigned
   - Any users without roles

## Troubleshooting

### Getting 403 Error When Accessing Admin Panel

If you're getting a 403 error when accessing `/admin`, follow these steps:

1. **Check your setup:**

   ```bash
   php artisan admin:check-setup
   ```

2. **Clear cache (Spatie permissions uses caching):**

   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. **Verify your user has Admin role:**

   ```bash
   php artisan tinker
   ```

   Then:

   ```php
   $user = App\Models\User::where('email', 'your-email@example.com')->first();
   $user->roles; // Should show Admin role
   $user->hasRole('Admin'); // Should return true
   ```

4. **Check debug endpoint:**
   Visit `http://localhost:8000/api/debug/user-roles` (while logged in) to see your current roles.

5. **If roles don't exist, seed them:**

   ```bash
   php artisan db:seed --class=RoleSeeder
   ```

6. **If user doesn't have Admin role, assign it:**
   ```bash
   php artisan user:assign-admin your-email@example.com
   ```

## API Endpoints

All admin endpoints are protected by `auth:sanctum` and `admin` middleware.

### Dashboard

- `GET /api/admin/dashboard` - Get dashboard statistics

### Job Management

- `GET /api/admin/jobs` - List all jobs (supports query params: `status`, `search`, `per_page`)
- `POST /api/admin/jobs/{id}/approve` - Approve a job
- `POST /api/admin/jobs/{id}/reject` - Reject a job

### User Management

- `GET /api/admin/users` - List all users (supports query params: `role`, `is_blocked`, `search`, `per_page`)
- `POST /api/admin/users/{id}/block` - Block a user
- `POST /api/admin/users/{id}/unblock` - Unblock a user
- `POST /api/admin/users/{id}/assign-role` - Assign role to user (body: `{ "role": "Admin|Employer|Candidate" }`)

## Frontend Pages

- `/admin` - Admin dashboard with statistics
- `/admin/jobs` - Job management page
- `/admin/users` - User management page

All admin pages are protected by `AdminRouteGuard` component which checks for Admin role.

## Roles

- **Admin**: Full access to admin panel, can approve/reject jobs, manage users
- **Employer**: Can create and manage jobs
- **Candidate**: Can view jobs and apply

## Policies

- `JobPolicy`: Controls access to job operations
- `UserPolicy`: Controls access to user operations

## Middleware

- `AdminMiddleware`: Ensures user has Admin role before accessing admin routes
