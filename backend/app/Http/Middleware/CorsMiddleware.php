<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get allowed origins from config or environment
        $allowedOrigins = config('cors.allowed_origins', []);
        
        // Fallback to environment variable if config is empty
        if (empty($allowedOrigins)) {
            $envOrigins = env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:3001');
            $allowedOrigins = $envOrigins ? explode(',', $envOrigins) : ['http://localhost:3000', 'http://localhost:3001'];
            // Trim whitespace from origins
            $allowedOrigins = array_map('trim', $allowedOrigins);
        }
        
        $origin = $request->header('Origin');
        
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }
        
        // Set CORS headers - always set them for API routes
        if ($request->is('api/*')) {
            // Check if origin is in allowed list (case-insensitive, handle trailing slashes)
            $originMatched = false;
            if ($origin) {
                // Normalize origin (remove trailing slash)
                $normalizedOrigin = rtrim($origin, '/');
                foreach ($allowedOrigins as $allowedOrigin) {
                    $normalizedAllowed = rtrim($allowedOrigin, '/');
                    if (strcasecmp($normalizedOrigin, $normalizedAllowed) === 0) {
                        $response->headers->set('Access-Control-Allow-Origin', $origin);
                        $originMatched = true;
                        break;
                    }
                }
            }
            
            // If no origin matched but we have an origin header, don't set it
            // If no origin header at all, allow all (for same-origin or testing)
            if (!$originMatched && !$origin) {
                $response->headers->set('Access-Control-Allow-Origin', '*');
            }
            
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }
        
        return $response;
    }
}
