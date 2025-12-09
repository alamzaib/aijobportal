<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    /**
     * Get all jobs with pagination and filters
     */
    public function jobs(Request $request): JsonResponse
    {
        $query = Job::with('company');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->get('per_page', 15);
        $jobs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($jobs);
    }

    /**
     * Approve a job
     */
    public function approveJob(string $id): JsonResponse
    {
        $job = Job::findOrFail($id);
        $job->update([
            'status' => 'approved',
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Job approved successfully',
            'job' => $job->load('company'),
        ]);
    }

    /**
     * Reject a job
     */
    public function rejectJob(string $id): JsonResponse
    {
        $job = Job::findOrFail($id);
        $job->update([
            'status' => 'rejected',
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Job rejected successfully',
            'job' => $job->load('company'),
        ]);
    }

    /**
     * Get all users with pagination and filters
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::with('roles');

        // Filter by role
        if ($request->has('role')) {
            $query->role($request->role);
        }

        // Filter by blocked status
        if ($request->has('is_blocked')) {
            $query->where('is_blocked', $request->boolean('is_blocked'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform users to include role names
        $users->getCollection()->transform(function ($user) {
            $user->role_names = $user->roles->pluck('name');
            return $user;
        });

        return response()->json($users);
    }

    /**
     * Block a user
     */
    public function blockUser(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent blocking admins
        if ($user->hasRole('Admin')) {
            return response()->json([
                'message' => 'Cannot block admin users',
            ], 403);
        }

        $user->update(['is_blocked' => true]);

        return response()->json([
            'message' => 'User blocked successfully',
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Unblock a user
     */
    public function unblockUser(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_blocked' => false]);

        return response()->json([
            'message' => 'User unblocked successfully',
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|in:Admin,Employer,Candidate',
        ]);

        $user = User::findOrFail($id);
        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        try {
            // Check if status column exists in jobs table
            $hasStatusColumn = Schema::hasColumn('jobs', 'status');
            
            $stats = [
                'total_jobs' => Job::count(),
                'pending_jobs' => $hasStatusColumn ? Job::where('status', 'pending')->count() : 0,
                'approved_jobs' => $hasStatusColumn ? Job::where('status', 'approved')->count() : 0,
                'rejected_jobs' => $hasStatusColumn ? Job::where('status', 'rejected')->count() : 0,
                'total_users' => User::count(),
                'blocked_users' => Schema::hasColumn('users', 'is_blocked') 
                    ? User::where('is_blocked', true)->count() 
                    : 0,
            ];

            // Check if Spatie permissions is set up
            try {
                $stats['admin_users'] = User::role('Admin')->count();
                $stats['employer_users'] = User::role('Employer')->count();
                $stats['candidate_users'] = User::role('Candidate')->count();
            } catch (\Exception $e) {
                // If roles don't exist yet, set to 0
                \Log::warning('Error counting users by role: ' . $e->getMessage());
                $stats['admin_users'] = 0;
                $stats['employer_users'] = 0;
                $stats['candidate_users'] = 0;
            }

            return response()->json($stats);
        } catch (\Exception $e) {
            \Log::error('Admin dashboard error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to load dashboard statistics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

