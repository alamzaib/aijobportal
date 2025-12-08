# Artisan Commands Reference

## Database Migrations

### Run all migrations
```bash
php artisan migrate
```

### Rollback last migration
```bash
php artisan migrate:rollback
```

### Rollback all migrations
```bash
php artisan migrate:reset
```

### Refresh database (rollback + migrate)
```bash
php artisan migrate:refresh
```

### Fresh database (drop all tables + migrate)
```bash
php artisan migrate:fresh
```

## Database Seeders

### Run all seeders
```bash
php artisan db:seed
```

### Run specific seeder
```bash
php artisan db:seed --class=CompanySeeder
php artisan db:seed --class=JobSeeder
```

### Fresh migration with seeding
```bash
php artisan migrate:fresh --seed
```

## Cache Management

### Clear application cache
```bash
php artisan cache:clear
```

### Clear config cache
```bash
php artisan config:clear
```

### Clear route cache
```bash
php artisan route:clear
```

### Clear view cache
```bash
php artisan view:clear
```

### Cache config
```bash
php artisan config:cache
```

### Cache routes
```bash
php artisan route:cache
```

## Queue Management

### Process queue jobs
```bash
php artisan queue:work
```

### Process queue jobs with Redis
```bash
php artisan queue:work redis
```

### Listen to queue
```bash
php artisan queue:listen
```

### Failed jobs
```bash
php artisan queue:failed
php artisan queue:retry <job-id>
php artisan queue:retry all
```

## Quick Setup Commands

### Complete setup (fresh database + seed)
```bash
php artisan migrate:fresh --seed
```

### Generate application key (if not set)
```bash
php artisan key:generate
```

### Create storage link
```bash
php artisan storage:link
```

