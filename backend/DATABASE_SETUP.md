# Database Setup Guide

## Issue
You're getting "Access denied for user 'root'@'172.18.0.1' (using password: NO)" when running migrations.

## Solutions

### If Running Locally (Not in Docker)

Your `.env` file should have:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aijobportal
DB_USERNAME=root
DB_PASSWORD=          # Leave empty if XAMPP MySQL has no password, or set your password
```

**Steps:**
1. Make sure XAMPP MySQL is running
2. Create the database if it doesn't exist:
   ```sql
   CREATE DATABASE IF NOT EXISTS aijobportal;
   ```
3. Check your MySQL root password - if it's not empty, set `DB_PASSWORD` in `.env`
4. Run migrations:
   ```bash
   php artisan migrate
   ```

### If Running in Docker

The docker-compose.yml already configures the database. You should run migrations **inside the Docker container**:

```bash
# Run migrations inside the backend container
docker compose exec backend php artisan migrate

# Or if using docker-compose (older syntax)
docker-compose exec backend php artisan migrate
```

**Docker Database Settings:**
- Host: `mysql` (service name)
- Username: `taeab`
- Password: `taeab`
- Database: `aijobportal`

### Quick Fix: Skip Migrations (Temporary)

Since the `aiJob` relationship is now optional, your pages will work even without the `ai_jobs` table. The job detail pages will load successfully without AI job data.

## Verify Database Connection

Test the connection:
```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

If this works, your database connection is fine and you can run migrations.

## Common Issues

1. **MySQL not running**: Start XAMPP MySQL service
2. **Wrong password**: Check your MySQL root password
3. **Database doesn't exist**: Create it manually or let migrations create it
4. **Port conflict**: Make sure port 3306 is available

