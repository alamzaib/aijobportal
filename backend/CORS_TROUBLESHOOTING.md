# CORS Troubleshooting Guide

## Current Configuration

1. **CORS Config** (`config/cors.php`):
   - `allowed_origins`: Set from `CORS_ALLOWED_ORIGINS` env variable
   - `paths`: `['api/*', 'sanctum/csrf-cookie']`
   - `supports_credentials`: `true`

2. **Middleware**:
   - `HandleCors` is in the global middleware stack (`app/Http/Kernel.php`)

3. **Environment**:
   - Make sure `.env` has: `CORS_ALLOWED_ORIGINS=http://localhost:3000`

## Steps to Fix CORS Issues

1. **Clear all caches**:
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan cache:clear
   ```

2. **Verify your `.env` file** has:
   ```
   CORS_ALLOWED_ORIGINS=http://localhost:3000
   ```

3. **Restart your Laravel server** after making changes

4. **Test the CORS configuration**:
   ```bash
   php artisan tinker
   >>> config('cors.allowed_origins')
   ```
   Should return: `["http://localhost:3000"]`

5. **Check if the server is running on the correct port**:
   - Your frontend calls: `http://localhost:8080/api/auth/register`
   - Make sure Laravel is running on port 8080

## Alternative: Manual CORS Headers (if above doesn't work)

If the automatic CORS handling doesn't work, you can add a custom middleware:

1. Create middleware:
   ```bash
   php artisan make:middleware CorsMiddleware
   ```

2. Add to `app/Http/Middleware/CorsMiddleware.php`:
   ```php
   public function handle($request, Closure $next)
   {
       $response = $next($request);
       
       $origin = $request->header('Origin');
       $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'));
       
       if (in_array($origin, $allowedOrigins)) {
           $response->headers->set('Access-Control-Allow-Origin', $origin);
       }
       
       $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
       $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
       $response->headers->set('Access-Control-Allow-Credentials', 'true');
       
       if ($request->getMethod() === 'OPTIONS') {
           return response('', 200);
       }
       
       return $response;
   }
   ```

3. Register in `bootstrap/app.php`:
   ```php
   $middleware->api(prepend: [
       \App\Http\Middleware\CorsMiddleware::class,
   ]);
   ```

