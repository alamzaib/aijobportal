# Environment Variables Setup

## Security Best Practices

The `docker-compose.yml` file now uses environment variable substitution to keep sensitive data (like API keys) out of version control.

## Setup Instructions

1. **Create a `.env` file in the project root:**

   ```bash
   # Copy the example template
   cp .env.example .env
   ```

   Or create it manually with:

   ```bash
   # .env file (DO NOT COMMIT THIS FILE)
   OPENAI_API_KEY=your_actual_openai_api_key_here
   ```

2. **The `.env` file is already in `.gitignore`**, so it won't be committed to git.

3. **Docker Compose will automatically load the `.env` file** when you run:
   ```bash
   docker-compose up
   ```

## Current Configuration

The `docker-compose.yml` now uses:
```yaml
- OPENAI_API_KEY=${OPENAI_API_KEY:-}
```

This means:
- It reads from the `OPENAI_API_KEY` environment variable
- Falls back to empty string if not set (the `:-` syntax)
- The `.env` file in the project root is automatically loaded by docker-compose

## For Team Members

1. Clone the repository
2. Copy `.env.example` to `.env` (if `.env.example` exists)
3. Fill in your actual API keys and secrets
4. The `.env` file will never be committed to git

## Verifying Setup

After creating your `.env` file, verify it works:

```bash
# Check if docker-compose can read the variable
docker-compose config | grep OPENAI_API_KEY
```

You should see your API key (masked) in the output.

## Important Notes

- ✅ `.env` is in `.gitignore` - safe to commit `docker-compose.yml`
- ✅ Never commit `.env` files
- ✅ Use `.env.example` as a template for team members
- ✅ Each developer should have their own `.env` file

