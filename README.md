# Taeab.com Monorepo

This monorepo contains all services for Taeab.com:

- **Frontend**: Next.js 14+ with TypeScript and Tailwind CSS
- **Backend**: Laravel 11 API
- **AI Service**: FastAPI Python application

## Prerequisites

- Node.js 18+ and npm 9+
- PHP 8.2+ and Composer
- Python 3.11+ and pip
- Docker and Docker Compose (for containerized development)
- PostgreSQL 15+ (if running locally without Docker)
- Redis 7+ (if running locally without Docker)

## Project Structure

```
taeab-monorepo/
├── frontend/          # Next.js + TypeScript + Tailwind
├── backend/           # Laravel 11 API
├── ai/                # FastAPI Python service
├── docker-compose.yml # Docker orchestration
└── README.md
```

## Local Development

### Option 1: Docker Compose (Recommended)

The easiest way to run all services locally:

```bash
# Build and start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop all services
docker-compose down
```

Services will be available at:
- Frontend: http://localhost:3000
- Backend API: http://localhost:8080
- AI Service: http://localhost:8000
- PostgreSQL: localhost:5432
- Redis: localhost:6379

### Option 2: Manual Setup

#### 1. Install Dependencies

```bash
# Install all dependencies
npm run install:all

# Or install individually:
npm run install:frontend
npm run install:backend
npm run install:ai
```

#### 2. Environment Setup

**Frontend** (`frontend/.env.local`):
```env
NEXT_PUBLIC_API_URL=http://localhost:8080/api
NEXT_PUBLIC_AI_SERVICE_URL=http://localhost:8000
```

**Backend** (`backend/.env`):
```env
APP_NAME=Taeab
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=taeab
DB_USERNAME=postgres
DB_PASSWORD=postgres

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**AI Service** (`ai/.env`):
```env
DATABASE_URL=postgresql://postgres:postgres@localhost:5432/taeab
REDIS_URL=redis://localhost:6379
```

#### 3. Database Setup (Backend)

```bash
cd backend
php artisan key:generate
php artisan migrate
php artisan db:seed  # Optional
```

#### 4. Run Services

**Terminal 1 - Frontend:**
```bash
npm run dev:frontend
# or
cd frontend && npm run dev
```

**Terminal 2 - Backend:**
```bash
npm run dev:backend
# or
cd backend && php artisan serve --port=8080
```

**Terminal 3 - AI Service:**
```bash
npm run dev:ai
# or
cd ai && uvicorn main:app --reload --host 0.0.0.0 --port 8000
```

## Development Workflow

1. **Frontend Development**: Work in `frontend/` directory
   - Uses Next.js 14+ with App Router
   - TypeScript for type safety
   - Tailwind CSS for styling

2. **Backend Development**: Work in `backend/` directory
   - Laravel 11 API
   - RESTful endpoints
   - Database migrations in `database/migrations/`

3. **AI Service Development**: Work in `ai/` directory
   - FastAPI with async support
   - Python 3.11+
   - Auto-generated API docs at `/docs`

## Docker Services

The `docker-compose.yml` includes:
- **frontend**: Next.js development server
- **backend**: Laravel with PHP-FPM and Nginx
- **ai**: FastAPI Python service
- **postgres**: PostgreSQL 15 database
- **redis**: Redis 7 cache/session store

## Building for Production

```bash
# Build frontend
npm run build:frontend

# Backend and AI services are typically deployed as containers
# See individual Dockerfiles for production builds
```

## Contributing

1. Create a feature branch
2. Make your changes
3. Test locally (with Docker or manual setup)
4. Submit a pull request

## License

Proprietary - Taeab.com

