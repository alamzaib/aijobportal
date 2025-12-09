# CORS Error Fix Guide

## Understanding the Error

The error `Access to XMLHttpRequest at 'http://localhost:8000/api/jobs?...' from origin 'http://localhost:3001' has been blocked by CORS policy` occurs when:

1. The frontend (running on `http://localhost:3001`) tries to make a request to the backend API (`http://localhost:8000`)
2. The backend doesn't include the proper CORS headers allowing the frontend origin
3. The browser blocks the request for security reasons

## What Was Fixed

1. **Enhanced CorsMiddleware** (`backend/app/Http/Middleware/CorsMiddleware.php`):
   - Improved origin matching (case-insensitive, handles trailing slashes)
   - Better fallback to environment variables
   - More robust header setting

2. **Updated CORS Config** (`backend/config/cors.php`):
   - Trims whitespace from origins
   - Ensures proper array formatting

## Steps to Fix

### 1. Clear Configuration Cache

The config might be cached. Clear it:

```bash
# Inside Docker container
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan cache:clear

# Or locally
cd backend
php artisan config:clear
php artisan cache:clear
```

### 2. Verify Environment Variables

Check that `CORS_ALLOWED_ORIGINS` is set correctly:

**In Docker** (already set in `docker-compose.yml`):
```yaml
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:3001,http://127.0.0.1:3000,http://127.0.0.1:3001
```

**In `.env` file** (if running locally):
```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:3001,http://127.0.0.1:3000,http://127.0.0.1:3001
```

### 3. Restart Backend Container

After clearing cache and updating config:

```bash
docker-compose restart backend
```

### 4. Test CORS Configuration

Verify the config is loaded correctly:

```bash
docker-compose exec backend php artisan tinker
```

Then run:
```php
config('cors.allowed_origins')
```

Should return:
```php
["http://localhost:3000", "http://localhost:3001", "http://127.0.0.1:3000", "http://127.0.0.1:3001"]
```

### 5. Test the API Endpoint

Test with curl to see CORS headers:

```bash
curl -H "Origin: http://localhost:3001" \
     -H "Access-Control-Request-Method: GET" \
     -H "Access-Control-Request-Headers: Content-Type" \
     -X OPTIONS \
     -v \
     http://localhost:8000/api/jobs
```

You should see:
```
< Access-Control-Allow-Origin: http://localhost:3001
< Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS
< Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN
< Access-Control-Allow-Credentials: true
```

## Quick Fix Commands

Run these commands in order:

```bash
# 1. Clear caches
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan route:clear

# 2. Restart backend
docker-compose restart backend

# 3. Check logs if still having issues
docker-compose logs backend
```

## Common Issues

### Issue: Still getting CORS error after fix

**Solution:**
1. Make sure you've restarted the backend container
2. Clear browser cache or try incognito mode
3. Check browser console for exact error message
4. Verify the Origin header matches exactly (no trailing slash differences)

### Issue: Config not loading

**Solution:**
1. Check `.env` file exists in `backend/` directory
2. Verify environment variables are set in `docker-compose.yml`
3. Run `php artisan config:clear` to remove cached config

### Issue: Middleware not running

**Solution:**
1. Check `bootstrap/app.php` has `CorsMiddleware` registered
2. Verify middleware is in `api` middleware group
3. Check logs: `docker-compose logs backend`

## Verification

After applying the fix, test from your frontend:

```javascript
fetch('http://localhost:8000/api/jobs?page=1&per_page=12', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
  },
})
.then(response => response.json())
.then(data => console.log('Success:', data))
.catch(error => console.error('Error:', error));
```

If successful, you should see the jobs data without CORS errors.

## Additional Notes

- The middleware now handles both `http://localhost:3001` and `http://127.0.0.1:3001`
- CORS headers are only set for API routes (`api/*`)
- Preflight OPTIONS requests are handled automatically
- The middleware checks origin matching case-insensitively

