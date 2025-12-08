# AI Service - FastAPI Application

FastAPI service for generating job descriptions using OpenAI.

## Features

- `/health` - Health check endpoint
- `/ai/generate-job-description` - Generate job descriptions using OpenAI

## Development Flow

### Prerequisites

- Python 3.11+
- Docker and Docker Compose (optional)
- OpenAI API key

### Local Development

1. **Set up environment variables:**

   Create a `.env` file in the `ai/` directory:

   ```bash
   OPENAI_API_KEY=your_openai_api_key_here
   ```

2. **Install dependencies:**

   ```bash
   cd ai
   pip install -r requirements.txt
   ```

3. **Run the application:**

   ```bash
   uvicorn app:app --reload --host 0.0.0.0 --port 8000
   ```

   The service will be available at `http://localhost:8000`

   **Note:** If running via Docker Compose, the service is available at `http://localhost:8001` (port 8000 is used by Laravel backend)

### Docker Development

1. **Set environment variable in docker-compose.yml:**

   Add `OPENAI_API_KEY` to the `ai` service environment section:

   ```yaml
   ai:
     environment:
       - OPENAI_API_KEY=your_openai_api_key_here
   ```

2. **Build and run:**

   ```bash
   docker-compose up ai
   ```

   Or rebuild if needed:

   ```bash
   docker-compose build ai
   docker-compose up ai
   ```

   The service will be available at `http://localhost:8001` (port 8000 is used by Laravel backend)

### Testing Endpoints

**Note:** When using Docker Compose, use port **8001**. When running locally, use port **8000**.

#### Health Check

```bash
# Docker Compose (port 8001)
curl http://localhost:8001/health

# Local development (port 8000)
curl http://localhost:8000/health
```

Expected response:

```json
{
  "status": "healthy",
  "openai_configured": true
}
```

#### Generate Job Description

```bash
# Docker Compose (port 8001)
curl -X POST http://localhost:8001/ai/generate-job-description \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Senior Software Engineer",
    "company_name": "Tech Corp",
    "prompts": "Focus on Python and React experience",
    "locale": "en"
  }'
```

Minimal request (without optional fields):

```bash
# Docker Compose (port 8001)
curl -X POST http://localhost:8001/ai/generate-job-description \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Data Scientist",
    "company_name": "AI Innovations"
  }'
```

With different locale:

```bash
# Docker Compose (port 8001)
curl -X POST http://localhost:8001/ai/generate-job-description \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Desarrollador Full Stack",
    "company_name": "Empresa Tech",
    "locale": "es"
  }'
```

Expected response:

```json
{
  "job_description": "Job description text here...",
  "title": "Senior Software Engineer",
  "company_name": "Tech Corp",
  "locale": "en"
}
```

### API Documentation

Once the service is running, visit:

- Swagger UI: `http://localhost:8001/docs` (Docker) or `http://localhost:8000/docs` (local)
- ReDoc: `http://localhost:8001/redoc` (Docker) or `http://localhost:8000/redoc` (local)

### Request/Response Models

#### GenerateJobDescriptionRequest

- `title` (required): Job title string
- `company_name` (required): Company name string
- `prompts` (optional): Additional requirements or prompts
- `locale` (optional, default: "en"): Language/locale code

#### GenerateJobDescriptionResponse

- `job_description`: Generated job description text
- `title`: Job title
- `company_name`: Company name
- `locale`: Locale used

### Error Handling

The service returns appropriate HTTP status codes:

- `200`: Success
- `500`: Server error (e.g., OpenAI API key not configured, API error)

### Production Considerations

1. **Environment Variables**: Use secure secret management for `OPENAI_API_KEY`
2. **CORS**: Update CORS origins to specific domains instead of `["*"]`
3. **Rate Limiting**: Consider adding rate limiting middleware
4. **Logging**: Add structured logging for production monitoring
5. **Error Handling**: Enhance error messages for production (avoid exposing internal details)
6. **Model Selection**: Consider using `gpt-4` for better quality (update in `app.py`)
