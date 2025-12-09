<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Clear Spatie permissions cache to ensure fresh role check
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Refresh user model to get latest roles
        $user->refresh();
        
        // Load roles if not already loaded
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Check role using fresh data
        if (!$user->hasRole('Admin')) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'has_admin_role' => $user->hasRole('Admin'),
            ], 403);
        }

        return $next($request);
    }
}

