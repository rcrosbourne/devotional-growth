---
inclusion: always
---

# Tech Stack

## Backend
- PHP 8.4, Laravel 13
- Laravel Fortify for authentication (2FA, password reset, email verification)
- Inertia.js v2 (server-side adapter) for SPA-style page rendering
- Laravel Wayfinder for type-safe route generation on the frontend
- SQLite (default dev database)

## Frontend
- React 19, TypeScript 6
- Inertia.js React adapter (`@inertiajs/react`)
- Tailwind CSS v4 (via `@tailwindcss/vite`)
- shadcn/ui (new-york style, Radix UI primitives, Lucide icons)
- class-variance-authority + clsx + tailwind-merge for styling utilities
- Vite 8 as the build tool

## Testing
- Pest v4 (PHP test framework on top of PHPUnit)
- Pest Browser plugin (Playwright-based browser tests)
- Tests require 100% code coverage (`--exactly=100.0`)
- 100% type coverage enforced (`--type-coverage --min=100`)

## Static Analysis & Linting
- PHPStan at max level (with Larastan extension)
- Rector for automated PHP refactoring
- Laravel Pint for PHP code style (Laravel preset with strict rules)
- ESLint + Prettier for TypeScript/React
- Prettier plugins: organize-imports, tailwindcss

## Package Managers
- Composer (PHP)
- Bun (JS — `bun.lock` present, but npm scripts are used in composer scripts)

## Common Commands

```bash
# Full dev environment (server + queue + logs + vite)
composer dev

# Run all tests (type-coverage + unit + lint + types)
composer test

# Run unit/feature tests only (100% coverage required)
composer test:unit

# Run linting checks (pint, rector, eslint)
composer test:lint

# Auto-fix lint issues
composer lint

# Run static analysis (phpstan + tsc)
composer test:types

# Build frontend assets
npm run build

# Initial project setup
composer setup
```
