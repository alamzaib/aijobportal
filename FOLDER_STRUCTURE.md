# Taeab.com Monorepo - Folder Structure

```
taeab-monorepo/
├── .gitignore                          # Top-level gitignore
├── package.json                        # Monorepo root package.json
├── README.md                           # Main documentation
├── docker-compose.yml                  # Docker orchestration
│
├── frontend/                           # Next.js + TypeScript + Tailwind
│   ├── Dockerfile                      # Production Dockerfile
│   ├── Dockerfile.dev                  # Development Dockerfile
│   ├── package.json                    # Frontend dependencies
│   ├── tsconfig.json                   # TypeScript configuration
│   ├── next.config.js                  # Next.js configuration
│   ├── tailwind.config.js              # Tailwind CSS configuration
│   ├── postcss.config.js               # PostCSS configuration
│   ├── .eslintrc.json                  # ESLint configuration (optional)
│   ├── .env.local                      # Frontend environment variables
│   ├── app/                            # Next.js App Router
│   │   ├── layout.tsx                  # Root layout
│   │   ├── page.tsx                    # Home page
│   │   ├── globals.css                 # Global styles
│   │   └── ...
│   ├── components/                     # React components
│   ├── lib/                            # Utility functions
│   ├── public/                         # Static assets
│   └── node_modules/                   # Dependencies (gitignored)
│
├── backend/                            # Laravel 11 API
│   ├── Dockerfile                      # Production Dockerfile
│   ├── Dockerfile.dev                  # Development Dockerfile
│   ├── composer.json                   # PHP dependencies
│   ├── composer.lock                   # Locked PHP dependencies
│   ├── .env.example                    # Environment template
│   ├── .env                            # Environment variables (gitignored)
│   ├── app/                            # Application code
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   └── ...
│   ├── bootstrap/                      # Bootstrap files
│   │   └── app.php
│   ├── config/                         # Configuration files
│   ├── database/                       # Database files
│   │   ├── migrations/
│   │   ├── seeders/
│   │   └── factories/
│   ├── public/                         # Public entry point
│   │   └── index.php
│   ├── routes/                         # Route definitions
│   │   ├── api.php
│   │   ├── web.php
│   │   └── console.php
│   ├── storage/                        # Storage (gitignored)
│   ├── tests/                          # Tests
│   ├── vendor/                         # Composer dependencies (gitignored)
│   └── docker/                         # Docker configuration
│       ├── nginx.conf                  # Nginx configuration
│       ├── default.conf                # Nginx site config
│       └── supervisord.conf            # Supervisor config
│
└── ai/                                 # FastAPI Python Service
    ├── Dockerfile                      # Production Dockerfile
    ├── Dockerfile.dev                  # Development Dockerfile
    ├── requirements.txt                # Python dependencies
    ├── .env                            # Environment variables (gitignored)
    ├── main.py                         # FastAPI application entry
    ├── app/                            # Application modules
    │   ├── __init__.py
    │   ├── models/                     # Data models
    │   ├── routers/                    # API routers
    │   ├── services/                   # Business logic
    │   └── utils/                      # Utilities
    ├── tests/                          # Tests
    └── __pycache__/                    # Python cache (gitignored)
```

## Service Ports

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8080
- **AI Service**: http://localhost:8000
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379

