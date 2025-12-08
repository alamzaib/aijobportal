# Security Vulnerability Notes

## Current Vulnerability: glob (GHSA-5j98-mcp5-4vw2)

**Status:** Known issue, low production risk - Cannot be fixed without breaking changes

**Details:**
- **Package:** glob (10.2.0 - 10.4.5)
- **Severity:** High
- **Currently Installed:** glob@10.3.10 (vulnerable)
- **Patched Version Available:** glob@10.5.0+
- **Affected:** `eslint-config-next@14.2.33` → `@next/eslint-plugin-next@14.2.33` → `glob@10.3.10`
- **Impact:** Command injection vulnerability in glob CLI tool

**Why npm overrides don't work:**
- `@next/eslint-plugin-next@14.2.33` has a strict dependency on `glob@10.3.10`
- npm overrides cannot override strict version constraints in nested dependencies
- The override in `package.json` is configured but not effective

**Why this is acceptable:**
1. **Dev dependency only:** This affects development tooling (ESLint), not production code
2. **CLI tool vulnerability:** The vulnerability is in the CLI interface (`-c/--cmd` flag), not the library itself
3. **No runtime impact:** Your production application is not affected
4. **Low exploitability:** Requires direct CLI usage with malicious input, unlikely in normal development
5. **Breaking changes required:** Fixing would require upgrading to Next.js 16, which introduces breaking changes

**Resolution Options:**

### Option 1: Wait for Next.js Update (Recommended)
The Next.js team will likely release a patch version that updates the glob dependency. Monitor:
- Next.js releases: https://github.com/vercel/next.js/releases
- npm audit: Run `npm audit` periodically

### Option 2: Accept the Risk
Since this is a dev-only dependency and doesn't affect production:
- Continue development as normal
- Monitor for updates
- The risk is minimal for development environments

### Option 3: Upgrade to Next.js 16 (Breaking Changes)
If you need to fix this immediately, you'll need to upgrade ESLint to v9 as well:
```bash
npm install next@latest eslint-config-next@latest eslint@latest
```
**Warning:** This requires:
- Next.js 16 breaking changes (check migration guide)
- ESLint 9 breaking changes (check migration guide)
- Updating ESLint configuration files
- Potential code changes for new linting rules

**Recommended:** Only do this if you're ready for a major upgrade cycle.

### Option 4: Remove ESLint (Not Recommended)
You could remove `eslint-config-next` from devDependencies, but this removes linting capabilities.

## Monitoring

Run periodically:
```bash
npm audit
npm outdated
```

## Current Status

- **Production Risk:** None (dev dependency only)
- **Development Risk:** Low (CLI tool vulnerability)
- **Action Required:** Monitor for Next.js updates
- **Last Checked:** $(Get-Date -Format "yyyy-MM-dd")

