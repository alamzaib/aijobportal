# Meilisearch Integration Setup Guide

This guide explains how to set up and use Meilisearch for job search functionality.

## Overview

Meilisearch provides typo-tolerant, fast search with filtering capabilities. Jobs are indexed with the following fields:
- `title` - Job title
- `description` - Job description
- `location` - Full location string
- `location_city` - Extracted city from location
- `skills` - Array of skills from requirements and AI suggestions
- `company_name` - Company name
- `company_id` - Company ID
- `type` - Job type (full-time, part-time, etc.)
- `salary_min` - Minimum salary
- `salary_max` - Maximum salary
- `salary_currency` - Currency code
- `posted_at` - Posting timestamp
- `is_active` - Active status

## Docker Setup

Meilisearch is already configured in `docker-compose.yml`. To start it:

```bash
docker-compose up -d meilisearch
```

Or start all services:

```bash
docker-compose up -d
```

Meilisearch will be available at `http://localhost:7700`

## Backend Setup

### 1. Install Dependencies

The required packages are already added to `composer.json`:
- `laravel/scout` - Laravel Scout for search
- `meilisearch/meilisearch-php` - Meilisearch PHP client

Install them:

```bash
cd backend
composer install
```

### 2. Configure Environment Variables

Add these to your `.env` file (or update `ENV_EXAMPLE.txt`):

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey
```

For local development (outside Docker):

```env
MEILISEARCH_HOST=http://localhost:7700
```

### 3. Sync Existing Jobs to Meilisearch

After starting Meilisearch, sync your existing jobs:

```bash
# Inside Docker container
docker-compose exec backend php artisan scout:sync-jobs

# Or locally
cd backend
php artisan scout:sync-jobs
```

This command will:
- Find all active jobs
- Load company and AI job relationships
- Index them in Meilisearch

### 4. Configure Meilisearch Index Settings

The index settings are configured automatically when jobs are indexed. The following settings are applied:

- **Searchable Attributes**: `title`, `description`, `location`, `location_city`, `company_name`, `skills`
- **Filterable Attributes**: `location`, `location_city`, `type`, `salary_min`, `salary_max`, `company_id`, `is_active`
- **Sortable Attributes**: `posted_at`, `salary_min`, `salary_max`

To manually configure index settings, you can use the Meilisearch dashboard at `http://localhost:7700` or use the Meilisearch API.

## API Usage

### Search Endpoint

```
GET /api/jobs?search=developer&location=New York&type=full-time&salary_min=50000&salary_max=100000
```

**Query Parameters:**
- `search` - Search query (triggers Meilisearch search)
- `location` - Filter by location (city or full location)
- `type` - Filter by job type (full-time, part-time, contract, freelance, internship)
- `salary_min` - Minimum salary filter
- `salary_max` - Maximum salary filter
- `page` - Page number (default: 1)
- `per_page` - Results per page (default: 15)

**Note:** When `search` parameter is provided, Meilisearch is used. Otherwise, regular database queries are used.

### Example Requests

```bash
# Simple search
curl "http://localhost:8000/api/jobs?search=python"

# Search with filters
curl "http://localhost:8000/api/jobs?search=developer&location=San Francisco&type=full-time&salary_min=80000"

# Filter without search (uses database)
curl "http://localhost:8000/api/jobs?location=Remote&type=contract"
```

## Frontend Usage

The frontend search component (`frontend/pages/jobs/index.tsx`) includes:

1. **Main Search Bar** - Typo-tolerant search across title, description, skills, and company
2. **Location Filter** - Filter by city or location
3. **Job Type Filter** - Filter by employment type
4. **Salary Range Filters** - Min and max salary filters
5. **Clear Filters** - Button to reset all filters

## Automatic Indexing

Jobs are automatically indexed when:
- A new job is created (if `is_active` is true)
- An existing job is updated
- A job's `is_active` status changes to true

Jobs are removed from the index when:
- A job is deleted
- A job's `is_active` status changes to false

## Commands

### Sync Jobs

```bash
php artisan scout:sync-jobs
```

Options:
- `--chunk=500` - Number of records to process at a time (default: 500)

### Import All Models

```bash
php artisan scout:import "App\Models\Job"
```

### Flush Index

```bash
php artisan scout:flush "App\Models\Job"
```

## Troubleshooting

### Meilisearch Not Responding

1. Check if the container is running:
   ```bash
   docker-compose ps meilisearch
   ```

2. Check logs:
   ```bash
   docker-compose logs meilisearch
   ```

3. Verify connection:
   ```bash
   curl http://localhost:7700/health
   ```

### Jobs Not Appearing in Search

1. Ensure jobs are synced:
   ```bash
   php artisan scout:sync-jobs
   ```

2. Check if jobs are active (`is_active = true`)

3. Verify Meilisearch index:
   ```bash
   curl http://localhost:7700/indexes/jobs/stats
   ```

### Filter Not Working

1. Ensure the field is in `toSearchableArray()` method
2. Check that the field is marked as filterable in Meilisearch
3. Verify filter syntax matches Meilisearch requirements

## Performance Tips

1. **Batch Indexing**: Use the `--chunk` option when syncing large datasets
2. **Queue Indexing**: Set `SCOUT_QUEUE=true` in `.env` to queue indexing operations
3. **Index Settings**: Configure searchable/filterable attributes appropriately
4. **Pagination**: Always use pagination for search results

## Security

- Change `MEILISEARCH_KEY` in production
- Use environment variables for sensitive configuration
- Consider restricting Meilisearch access to internal network only

## Additional Resources

- [Meilisearch Documentation](https://www.meilisearch.com/docs)
- [Laravel Scout Documentation](https://laravel.com/docs/scout)
- [Meilisearch PHP SDK](https://github.com/meilisearch/meilisearch-php)

