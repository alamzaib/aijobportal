<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
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

        // Return user without password
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
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
        $user = User::select('id', 'name', 'email', 'password')
            ->where('email', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Login using session (for SPA authentication)
        auth()->login($user);

        // Return user without password
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Logout user (session-based for SPA).
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Logout using session
            auth()->logout();
            
            // Invalidate session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Logout error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            // Still try to logout even if session operations fail
            auth()->logout();

            return response()->json([
                'message' => 'Logged out (session cleanup may have failed)',
            ], 200);
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}

