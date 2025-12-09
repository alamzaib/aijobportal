<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyJobController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ResumeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/status', function () {
    return response()->json([
        'service' => 'backend',
        'version' => '1.0.0',
        'status' => 'operational'
    ]);
});

// CSRF cookie route (must be before auth routes for SPA)
// Optimized: Minimal response, no unnecessary processing
Route::get('/sanctum/csrf-cookie', function () {
    return response()->noContent();
});

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Public job routes
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{id}', [JobController::class, 'show'])->where('id', '.*'); // Allow any characters including slashes

// Debug route - shows available job slugs (remove in production)
Route::get('/jobs-debug/slugs', function () {
    $jobs = \App\Models\Job::where('is_active', true)->get();
    $slugs = $jobs->map(function ($job) {
        $slug = strtolower($job->title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return [
            'id' => $job->id,
            'title' => $job->title,
            'slug' => $slug,
            'full_slug' => $slug . '--' . $job->id
        ];
    });
    return response()->json($slugs);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/user', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
    
    // Job management (protected)
    Route::post('/jobs', [JobController::class, 'store']);
    
    // Company job generation (protected)
    Route::post('/companies/{company}/jobs/generate', [CompanyJobController::class, 'generate']);
    
    // Applications
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/applications', [ApplicationController::class, 'apply']);
    Route::get('/jobs/{jobId}/applications', [ApplicationController::class, 'forJob']);
    
    // Resumes
    Route::get('/resumes', [ResumeController::class, 'index']);
    Route::post('/resumes', [ResumeController::class, 'store']);

    // Debug route to check user roles
    Route::get('/debug/user-roles', function (Request $request) {
        $user = $request->user();
        $user->load('roles');
        
        return response()->json([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'has_admin_role' => $user->hasRole('Admin'),
            'all_roles' => \Spatie\Permission\Models\Role::pluck('name')->toArray(),
        ]);
    });
});

// Admin routes (protected by both auth and admin middleware)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    // Job management
    Route::get('/jobs', [AdminController::class, 'jobs']);
    Route::post('/jobs/{id}/approve', [AdminController::class, 'approveJob']);
    Route::post('/jobs/{id}/reject', [AdminController::class, 'rejectJob']);

    // User management
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users/{id}/block', [AdminController::class, 'blockUser']);
    Route::post('/users/{id}/unblock', [AdminController::class, 'unblockUser']);
    Route::post('/users/{id}/assign-role', [AdminController::class, 'assignRole']);
});
