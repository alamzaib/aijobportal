# Git Commands for Initializing Taeab.com Monorepo

## Initialize Git Repository

```bash
# Initialize git repository
git init

# Add all files to staging
git add .

# Create initial commit
git commit -m "Initial commit: Taeab.com monorepo setup

- Add Next.js frontend with TypeScript and Tailwind CSS
- Add Laravel 11 backend API
- Add FastAPI Python AI service
- Add Docker configuration for all services
- Add docker-compose.yml for local development
- Add comprehensive documentation"
```

## Alternative: Step-by-Step Commands

```bash
# 1. Initialize repository
git init

# 2. Add remote (if you have one)
# git remote add origin <your-repository-url>

# 3. Stage all files
git add .

# 4. Check what will be committed
git status

# 5. Create initial commit
git commit -m "Initial commit: Taeab.com monorepo setup"

# 6. (Optional) Set default branch name
git branch -M main

# 7. (Optional) Push to remote
# git push -u origin main
```

## Verify Repository

```bash
# Check git status
git status

# View commit history
git log --oneline

# View repository structure
git ls-files
```

## Next Steps After Initial Commit

1. **Create a remote repository** (GitHub, GitLab, etc.)
2. **Add remote origin**:
   ```bash
   git remote add origin <your-repository-url>
   ```
3. **Push to remote**:
   ```bash
   git push -u origin main
   ```

## Branch Strategy (Recommended)

```bash
# Create and switch to development branch
git checkout -b develop

# Create feature branch
git checkout -b feature/your-feature-name

# After completing feature, merge to develop
git checkout develop
git merge feature/your-feature-name

# When ready for production
git checkout main
git merge develop
```

