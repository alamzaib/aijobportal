<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and auto-login using session (for SPA).
     */
    public function register(Request $request): JsonResponse
    {
        // Ensure JSON data is merged into request
        if ($request->isJson() && $request->json()->all()) {
            $request->merge($request->json()->all());
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Auto-login using session (for SPA authentication)
        auth()->login($user);

        // Assign Candidate role by default
        $user->assignRole('Candidate');

        // Load roles
        $user->load('roles');

        // Return user without password
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
            ],
        ], 201);
    }

    /**
     * Login user using session authentication (for SPA).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Optimize query - select only needed fields
        $user = User::select('id', 'name', 'email', 'password', 'is_blocked')
            ->where('email', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is blocked
        if ($user->is_blocked) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been blocked. Please contact support.'],
            ]);
        }

        // Login using session (for SPA authentication)
        auth()->login($user);

        // Load roles
        $user->load('roles');

        // Return user without password
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
            ],
        ]);
    }

    /**
     * Logout user (session-based for SPA).
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Get session before logout
            $session = $request->session();
            $sessionId = $session->getId();
            
            // Logout user from auth guard
            auth()->logout();
            
            // Flush all session data
            $session->flush();
            
            // Invalidate the session (marks it as invalid)
            $session->invalidate();
            
            // Regenerate CSRF token
            $session->regenerateToken();
            
            // Delete session file if using file driver
            if (config('session.driver') === 'file' && $sessionId) {
                try {
                    $sessionPath = storage_path('framework/sessions');
                    $sessionFile = $sessionPath . '/sess_' . $sessionId;
                    if (file_exists($sessionFile)) {
                        @unlink($sessionFile);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete session file: ' . $e->getMessage());
                }
            }

            // Get session cookie configuration
            $cookieName = config('session.cookie', 'laravel_session');
            $cookiePath = config('session.path', '/');
            $cookieDomain = config('session.domain');
            $cookieSecure = config('session.secure', false);
            $cookieHttpOnly = config('session.http_only', true);
            $cookieSameSite = config('session.same_site', 'lax');

            // Get actual cookie name from request if available
            $requestCookieName = null;
            if ($request->hasCookie($cookieName)) {
                $requestCookieName = $cookieName;
            } else {
                // Try common cookie names
                $commonNames = ['laravel_session', 'taeab_session', 'aijobportal_session'];
                foreach ($commonNames as $name) {
                    if ($request->hasCookie($name)) {
                        $requestCookieName = $name;
                        break;
                    }
                }
            }

            // Use the cookie name from request if found, otherwise use config
            $actualCookieName = $requestCookieName ?: $cookieName;

            // Log cookie clearing attempt for debugging
            \Log::info('Logging out user', [
                'config_cookie_name' => $cookieName,
                'actual_cookie_name' => $actualCookieName,
                'cookie_path' => $cookiePath,
                'cookie_domain' => $cookieDomain,
                'session_id' => $sessionId,
            ]);

            // Create response with headers to clear cookie
            $response = response()->json([
                'message' => 'Successfully logged out',
                'cookie_cleared' => true,
            ]);

            // Clear cookie using multiple methods to ensure it works
            // Try both the config name and actual cookie name
            
            $cookieNamesToClear = array_unique([$cookieName, $actualCookieName]);
            
            foreach ($cookieNamesToClear as $name) {
                // Method 1: Use withoutCookie() - this is the primary method
                if ($cookieDomain) {
                    $response->withoutCookie($name, $cookiePath, $cookieDomain);
                } else {
                    $response->withoutCookie($name, $cookiePath);
                }

                // Method 2: Set expired cookie explicitly (most reliable)
                // This ensures the cookie is cleared regardless of domain/path issues
                $response->cookie(
                    $name,
                    '',
                    time() - 3600, // Expire 1 hour ago
                    $cookiePath,
                    $cookieDomain,
                    $cookieSecure,
                    $cookieHttpOnly,
                    false,
                    $cookieSameSite
                );

                // Method 3: Also try clearing without domain (for localhost)
                $response->cookie(
                    $name,
                    '',
                    time() - 3600,
                    $cookiePath,
                    null, // No domain - matches current domain
                    $cookieSecure,
                    $cookieHttpOnly,
                    false,
                    $cookieSameSite
                );
            }

            return $response;
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Logout error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            // Still try to logout even if session operations fail
            try {
                auth()->logout();
                if ($request->hasSession()) {
                    $request->session()->flush();
                    $request->session()->invalidate();
                }
            } catch (\Exception $logoutException) {
                \Log::warning('Auth logout failed: ' . $logoutException->getMessage());
            }

            // Get session cookie configuration
            $cookieName = config('session.cookie', 'laravel_session');
            $cookiePath = config('session.path', '/');
            $cookieDomain = config('session.domain');
            $cookieSecure = config('session.secure', false);
            $cookieHttpOnly = config('session.http_only', true);
            $cookieSameSite = config('session.same_site', 'lax');

            $response = response()->json([
                'message' => 'Logged out',
            ], 200);

            // Clear cookie even on error - use multiple methods
            if ($cookieDomain) {
                $response->withoutCookie($cookieName, $cookiePath, $cookieDomain);
            } else {
                $response->withoutCookie($cookieName, $cookiePath);
            }

            // Set expired cookie explicitly
            $response->cookie(
                $cookieName,
                '',
                time() - 3600, // Expire 1 hour ago
                $cookiePath,
                $cookieDomain,
                $cookieSecure,
                $cookieHttpOnly,
                false,
                $cookieSameSite
            );

            // Also try without domain
            if ($cookieDomain) {
                $response->cookie(
                    $cookieName,
                    '',
                    time() - 3600,
                    $cookiePath,
                    null,
                    $cookieSecure,
                    $cookieHttpOnly,
                    false,
                    $cookieSameSite
                );
            }

            return $response;
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // If no user, return 401
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }
        
        // Refresh roles relationship and clear Spatie cache to ensure fresh data
        $user->load('roles');
        
        // Clear Spatie permissions cache for this user to ensure fresh role check
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
            'is_blocked' => $user->is_blocked,
        ]);
    }
}

