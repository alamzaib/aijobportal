# Laravel 11 API Setup Instructions

## Installation Steps

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Configure Environment**
   - Copy `ENV_EXAMPLE.txt` to `.env` (or create `.env` from the content in `ENV_EXAMPLE.txt`)
   - Update database credentials, Redis settings, and AWS S3 keys in `.env`
   - Generate application key:
     ```bash
     php artisan key:generate
     ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

4. **Seed Database**
   ```bash
   php artisan db:seed
   ```
   This will create 5 companies and 10 jobs.

## Artisan Commands

### Run Migrations
```bash
php artisan migrate
```

### Run Seeders
```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=CompanySeeder
php artisan db:seed --class=JobSeeder
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## API Endpoints

### Public Routes
- `POST /api/register` - Register new user
- `POST /api/login` - Login user
- `GET /api/jobs` - List all active jobs (with filters: ?location=, ?type=, ?search=)
- `GET /api/jobs/{id}` - Get job details

### Protected Routes (require auth:sanctum)
- `GET /api/user` - Get authenticated user
- `POST /api/logout` - Logout user
- `POST /api/jobs` - Create new job
- `POST /api/applications` - Apply for a job

## Sanctum Configuration

Sanctum is configured for SPA authentication. Make sure your frontend domain is included in `SANCTUM_STATEFUL_DOMAINS` in `.env`.

## Redis Configuration

Redis is configured for:
- Caching (default driver)
- Queues (default connection)

Make sure Redis is running and accessible at the configured host/port.

