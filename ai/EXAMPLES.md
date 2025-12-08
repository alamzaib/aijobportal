# API Examples - Quick Reference

**Note:** When using Docker Compose, use port **8001**. When running locally, use port **8000**.

## Health Check

```bash
# Docker Compose
curl http://localhost:8001/health

# Local development
curl http://localhost:8000/health
```

## Generate Job Description - Basic

```bash
# Docker Compose
curl -X POST http://localhost:8001/ai/generate-job-description \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Senior Software Engineer",
    "company_name": "Tech Corp"
  }'
```

## Generate Job Description - With Prompts

```bash
# Docker Compose
curl -X POST http://localhost:8001/ai/generate-job-description \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Senior Software Engineer",
    "company_name": "Tech Corp",
    "prompts": "Focus on Python and React experience. Include remote work options.",
    "locale": "en"
  }'
```

## Generate Job Description - Spanish Locale

```bash
# Docker Compose
curl -X POST http://localhost:8001/ai/generate-job-description \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Desarrollador Full Stack",
    "company_name": "Empresa Tech",
    "locale": "es"
  }'
```

## Generate Job Description - French Locale

```bash
# Docker Compose
curl -X POST http://localhost:8001/ai/generate-job-description \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Développeur Backend",
    "company_name": "Startup Tech",
    "locale": "fr"
  }'
```

## Using PowerShell (Windows)

```powershell
# Docker Compose (port 8001)
Invoke-RestMethod -Uri "http://localhost:8001/health" -Method Get

$body = @{
    title = "Senior Software Engineer"
    company_name = "Tech Corp"
    prompts = "Focus on Python and React"
    locale = "en"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8001/ai/generate-job-description" -Method Post -Body $body -ContentType "application/json"
```
