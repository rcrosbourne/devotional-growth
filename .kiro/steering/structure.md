---
inclusion: always
---

# Project Structure & Conventions

## PHP Backend

### Actions Pattern (`app/Actions/`)
- Business logic lives in Action classes, not controllers.
- Each action is a `final readonly` class with a single `handle()` method.
- Named by what they do, no suffix (e.g., `CreateUser`, not `CreateUserAction`).
- Dependencies injected via constructor. Actions are called from controllers, jobs, commands, etc.
- Create new actions with: `php artisan make:action "{Name}" --no-interaction`

### Controllers (`app/Http/Controllers/`)
- `final readonly` classes. Thin — delegate to Actions for business logic.
- Use Inertia responses (`Inertia::render()`) for page rendering.
- Use Laravel's `#[CurrentUser]` attribute for injecting the authenticated user.

### Form Requests (`app/Http/Requests/`)
- Dedicated request classes for validation. Named `Create*Request`, `Update*Request`, `Delete*Request`.
- Custom validation rules live in `app/Rules/`.

### Models (`app/Models/`)
- Models are `final` classes. Use `declare(strict_types=1)` everywhere.
- Models are unguarded (`Model::unguard()` in AppServiceProvider).
- PHPDoc `@property-read` annotations for all model properties.
- Explicit `casts()` method defining all attribute types.

### Routes (`routes/web.php`)
- Single web routes file. Grouped by middleware (`auth`, `guest`, `auth+verified`).
- Named routes used throughout (e.g., `route('dashboard')`).

### PHP Code Style
- `declare(strict_types=1)` in every PHP file.
- All classes are `final` (enforced by Pint).
- No superfluous PHP annotations except `@`-prefixed type annotations.
- Strict comparisons enforced (`===`).
- Ordered class elements: traits → constants → properties → constructor → magic → public static → public → protected → private.
- Use `DateTimeImmutable` over `DateTime`.

## React Frontend (`resources/js/`)

### Pages (`resources/js/pages/`)
- Inertia page components. Each maps to a route via `Inertia::render('page-name')`.
- Default export, wrapped in a layout component.
- Use `<Head title="..." />` for page titles.

### Layouts (`resources/js/layouts/`)
- `app-layout.tsx` — main authenticated layout with sidebar.
- `auth-layout.tsx` — guest/authentication pages layout.
- Settings pages use a nested settings layout.

### Components (`resources/js/components/`)
- `components/ui/` — shadcn/ui primitives (don't edit directly, managed by shadcn CLI).
- App-specific components at the top level of `components/`.

### Routing (`resources/js/routes/`, `resources/js/wayfinder/`)
- Laravel Wayfinder generates type-safe route helpers from PHP routes.
- Import routes like: `import { dashboard } from '@/routes'`, use as `dashboard().url`.
- `resources/js/actions/` — auto-generated Wayfinder action bindings (don't edit).

### Hooks (`resources/js/hooks/`)
- Custom React hooks prefixed with `use-` (kebab-case filenames).

### Types (`resources/js/types/`)
- Shared TypeScript interfaces in `index.d.ts` (User, Auth, SharedData, NavItem, etc.).

### Path Aliases
- `@/` maps to `resources/js/` (configured in components.json and tsconfig).

## Tests (`tests/`)

### Structure
- `tests/Unit/` — unit tests mirroring `app/` structure (Actions, Models, Rules, Middleware).
- `tests/Feature/Controllers/` — feature tests per controller.
- `tests/Browser/` — Playwright-based browser tests via Pest Browser plugin.
- `tests/Unit/ArchTest.php` — architectural rules (strict mode, security preset, controllers not directly used).

### Conventions
- Pest syntax: `it('does something', function () { ... })`.
- `RefreshDatabase` trait applied globally in `Pest.php`.
- Time is frozen by default in all tests (`$this->freezeTime()`).
- Stray HTTP requests and processes are prevented.
- Use `$this->fromRoute()` to set referer before requests.
- Use `expect()` chains for assertions.
- Feature tests assert Inertia responses with `->assertInertia(fn ($page) => ...)`.
