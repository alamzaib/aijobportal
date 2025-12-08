# Testing Job Slugs

## Quick Test Commands

Test these URLs in your browser or with curl:

1. **Test with UUID** (should always work):
   ```
   http://localhost:8000/api/jobs/a08ab225-bb51-4547-aec9-6fe8ba2209e8
   ```

2. **Test with full slug** (title--uuid):
   ```
   http://localhost:8000/api/jobs/database-administrator--a08ab225-bb51-4547-aec9-6fe8ba2209e8
   ```

3. **Test with slug only**:
   ```
   http://localhost:8000/api/jobs/database-administrator
   ```

4. **Test frontend page**:
   ```
   http://localhost:3001/jobs/database-administrator--a08ab225-bb51-4547-aec9-6fe8ba2209e8
   ```

## Expected Behavior

- UUID format: Should work immediately
- Full slug format: Should extract UUID and work
- Slug only: Should match by title and work

## If Still Getting 404

1. Check database connection - ensure `.env` has correct DB credentials
2. Check if jobs are active - only `is_active = true` jobs are returned
3. Check Laravel logs: `tail -f storage/logs/laravel.log`
4. Test debug endpoint: `http://localhost:8000/api/jobs-debug/slugs`

## Database Connection Issue

If you see "Access denied" errors:
- Check `.env` file has correct `DB_PASSWORD`
- Ensure MySQL is running
- Check database user permissions

