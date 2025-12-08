# API Response Examples

This document contains example API responses for reference when developing the frontend.

## Authentication Endpoints

### POST /api/auth/register

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Success Response (201):**
```json
{
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
  "token_type": "Bearer"
}
```

**Error Response (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email has already been taken."
    ],
    "password": [
      "The password confirmation does not match."
    ]
  }
}
```

### POST /api/auth/login

**Request:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Success Response (200):**
```json
{
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
  "token_type": "Bearer"
}
```

**Error Response (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The provided credentials are incorrect."
    ]
  }
}
```

### GET /api/auth/user

**Success Response (200):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "name": "John Doe",
  "email": "john@example.com",
  "email_verified_at": null,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

**Error Response (401):**
```json
{
  "message": "Unauthenticated."
}
```

### POST /api/auth/logout

**Success Response (200):**
```json
{
  "message": "Successfully logged out"
}
```

## Job Endpoints

### GET /api/jobs

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `search` (optional): Search term for title/description
- `location` (optional): Filter by location
- `type` (optional): Filter by job type (full-time, part-time, contract, internship)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "company_id": "660e8400-e29b-41d4-a716-446655440001",
      "title": "Senior Frontend Developer",
      "description": "We are looking for an experienced Frontend Developer...",
      "location": "San Francisco, CA",
      "type": "full-time",
      "salary_min": "100000.00",
      "salary_max": "150000.00",
      "salary_currency": "USD",
      "requirements": [
        "5+ years of experience in frontend development",
        "Strong proficiency in React and TypeScript",
        "Experience with Next.js or similar frameworks"
      ],
      "benefits": [
        "Competitive salary and equity",
        "Health, dental, and vision insurance",
        "Flexible working hours",
        "Remote work options"
      ],
      "is_active": true,
      "posted_at": "2024-01-01T00:00:00.000000Z",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z",
      "company": {
        "id": "660e8400-e29b-41d4-a716-446655440001",
        "name": "Tech Corp",
        "website": "https://techcorp.com",
        "logo": null,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
      }
    }
  ],
  "current_page": 1,
  "first_page_url": "http://localhost:8000/api/jobs?page=1",
  "from": 1,
  "last_page": 5,
  "last_page_url": "http://localhost:8000/api/jobs?page=5",
  "links": [
    {
      "url": null,
      "label": "&laquo; Previous",
      "active": false
    },
    {
      "url": "http://localhost:8000/api/jobs?page=1",
      "label": "1",
      "active": true
    },
    {
      "url": "http://localhost:8000/api/jobs?page=2",
      "label": "2",
      "active": false
    }
  ],
  "next_page_url": "http://localhost:8000/api/jobs?page=2",
  "path": "http://localhost:8000/api/jobs",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 75
}
```

### GET /api/jobs/{id}

**Success Response (200):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "company_id": "660e8400-e29b-41d4-a716-446655440001",
  "title": "Senior Frontend Developer",
  "description": "We are looking for an experienced Frontend Developer to join our team. You will be responsible for building user-facing web applications using modern JavaScript frameworks.\n\nYou will work closely with our design and backend teams to create seamless user experiences. The ideal candidate has a strong understanding of React, TypeScript, and modern web development practices.",
  "location": "San Francisco, CA",
  "type": "full-time",
  "salary_min": "100000.00",
  "salary_max": "150000.00",
  "salary_currency": "USD",
  "requirements": [
    "5+ years of experience in frontend development",
    "Strong proficiency in React and TypeScript",
    "Experience with Next.js or similar frameworks",
    "Knowledge of Tailwind CSS or similar CSS frameworks",
    "Understanding of RESTful APIs",
    "Experience with version control (Git)"
  ],
  "benefits": [
    "Competitive salary and equity",
    "Health, dental, and vision insurance",
    "Flexible working hours",
    "Remote work options",
    "Professional development budget",
    "Unlimited PTO"
  ],
  "is_active": true,
  "posted_at": "2024-01-01T00:00:00.000000Z",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z",
  "company": {
    "id": "660e8400-e29b-41d4-a716-446655440001",
    "name": "Tech Corp",
    "website": "https://techcorp.com",
    "logo": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "ai_job": null
}
```

**Error Response (404):**
```json
{
  "message": "No query results for model [App\\Models\\Job] 550e8400-e29b-41d4-a716-446655440000"
}
```

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Job] {id}"
}
```

### 419 CSRF Token Mismatch
```json
{
  "message": "CSRF token mismatch."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "The field name is required.",
      "The field name must be a string."
    ]
  }
}
```

### 500 Server Error
```json
{
  "message": "Server Error"
}
```

## Notes

- All timestamps are in ISO 8601 format (UTC)
- UUIDs are used for all IDs
- Pagination follows Laravel's standard pagination format
- With Sanctum HttpOnly cookies, the `token` field in login/register responses may not be needed for SPA authentication, but is included for API token authentication
- The `withCredentials: true` option must be set in axios requests to send cookies
- CSRF protection is handled automatically by Laravel Sanctum for SPA authentication

