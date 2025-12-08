# Troubleshooting Job Slug 404 Issues

## Steps to Fix Cache Issues

### 1. Clear Laravel Caches
```bash
cd backend
php artisan config:clear
php artisan route:clear
php artisan cache:clear  # May fail if Redis not configured, that's OK
php artisan view:clear
```

### 2. Restart PHP Server
If using `php artisan serve`:
- Stop the server (Ctrl+C)
- Restart: `php artisan serve`

### 3. Clear Browser Cache
- Hard refresh: `Ctrl+Shift+R` (Windows/Linux) or `Cmd+Shift+R` (Mac)
- Or open DevTools → Network tab → Check "Disable cache"

### 4. Test the Backend Directly
Test the API endpoint directly:
```bash
# Test with UUID
curl http://localhost:8000/api/jobs/{job-uuid}

# Test with slug
curl http://localhost:8000/api/jobs/backend-engineer

# Test with slug--uuid format
curl http://localhost:8000/api/jobs/backend-engineer--{uuid}
```

### 5. Check Laravel Logs
```bash
tail -f backend/storage/logs/laravel.log
```

## How Slug Lookup Works

The backend now supports three formats:

1. **UUID only**: `550e8400-e29b-41d4-a716-446655440000`
2. **Slug with UUID**: `backend-engineer--550e8400-e29b-41d4-a716-446655440000`
3. **Slug only**: `backend-engineer`

The `findBySlugOrId()` method:
1. First checks if it's a valid UUID
2. Then checks if it contains `--` and extracts UUID
3. Finally searches all active jobs by matching slug generated from title

## Debugging

If still not working, check:

1. **Are jobs active?** - Only `is_active = true` jobs are returned
2. **Check job titles** - The slug is generated from the job title
3. **Check logs** - Laravel logs will show what identifier was searched
4. **Test with a known UUID** - Try accessing `/api/jobs/{actual-uuid}` first

## Common Issues

**Issue**: Still getting 404
- **Solution**: Check that jobs exist and are active
- Verify the slug matches the job title (spaces become dashes, lowercase)

**Issue**: Route not found
- **Solution**: Run `php artisan route:clear` and restart server

**Issue**: Method not found
- **Solution**: Ensure `findBySlugOrId()` method exists in Job model
- Check autoload: `composer dump-autoload`

