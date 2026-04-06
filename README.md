# Devotional Growth

A mobile-first web application for structured devotional reading and Bible study, built with Laravel 13, React 19, and Inertia.js v2.

## Overview

Devotional Growth provides two primary modes of engagement:

1. **Thematic Devotions** — Structured devotional content organized by topics (faith, forgiveness, poverty, shame, etc.) with scripture references, reflection prompts, and optional Seventh-day Adventist insights.
2. **Bible Study** — Systematic Bible reading plans, word studies, and Greek/Hebrew origin exploration.

Admin users create and publish devotional content (manually or via AI-assisted generation). Regular users browse, read, complete, bookmark, and add personal observations.

## Features

- **Admin Content Management** — Draft/published workflow for themes and devotional entries, with CRUD operations and entry reordering
- **AI-Assisted Content Generation** — Admins provide a prompt; Laravel AI SDK (Prism) generates structured devotional content for review and publishing
- **AI-Generated Images** — Server-side image generation via OpenAI DALL-E for devotional entries
- **Bible Reading Plans** — Structured reading plans with daily progress tracking, streak counts, and missed reading catch-up
- **Word Studies** — Greek/Hebrew word studies seeded from Strong's Concordance with scripture occurrence mapping
- **Bookmarks** — Polymorphic bookmarks for devotional entries, scripture references, and word studies
- **Observations** — Personal reflections on devotional entries, visible to linked partners
- **Partner Linking** — Optional pairing with a devotional partner for shared observations, partner notifications, and "completed together" tracking
- **Notifications** — Partner activity alerts with unread counts and mark-all-as-read
- **Social Login** — Authentication via Google, Apple, and GitHub using Laravel Socialite
- **Passwordless Email OTP** — 6-digit one-time password login with rate limiting and attempt tracking
- **PWA Support** — Installable as a Progressive Web App with offline access for previously viewed content via Workbox service worker
- **Mobile-First Design** — Bottom navigation on mobile, sidebar navigation on desktop, following the "Editorial Serenity" design system

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13 (PHP 8.4) |
| Frontend | React 19 + Inertia.js v2 |
| Styling | Tailwind CSS v4 |
| UI Components | shadcn/ui |
| Type-Safe Routes | Laravel Wayfinder |
| Testing | Pest v4 + PHPUnit 12 |
| Static Analysis | Larastan v3 |
| Code Formatting | Laravel Pint |
| Auth | Laravel Socialite + Fortify |
| AI Content | Laravel AI SDK (Prism) |
| AI Images | OpenAI DALL-E |
| Bible Data | Bible API (API.Bible / bible-api.com) with database caching |
| PWA | vite-plugin-pwa + Workbox |
| Database | PostgreSQL |
| Local Dev | Laravel Herd |

## Architecture

```
Frontend (React 19 + Inertia.js)
  ├── Inertia Pages (user-facing + admin)
  ├── shadcn/ui Components
  └── Service Worker (Workbox)

Backend (Laravel 13)
  ├── Controllers (final readonly)
  ├── Admin Middleware (is_admin check)
  ├── Actions (business logic)
  ├── Form Requests (validation)
  ├── Eloquent Models
  ├── Laravel Socialite (OAuth)
  └── Prism (AI generation)

External Services
  ├── Bible API
  ├── OpenAI DALL-E
  ├── AI Provider (via Prism)
  ├── OAuth Providers (Google, Apple, GitHub)
  └── Mail Service (SMTP)
```

### Request Flow

1. User interacts with a React page component
2. Inertia.js sends a request to a Laravel route
3. Controller validates via Form Request, delegates to an Action
4. Action executes business logic, interacts with models/external APIs
5. Controller returns an Inertia response with page props
6. React renders the updated page; Service Worker caches responses for offline use

Admin routes pass through admin middleware (`is_admin` check) before reaching the controller.

## Getting Started

### Prerequisites

- PHP 8.4+
- PostgreSQL
- Node.js + Bun
- [Laravel Herd](https://herd.laravel.com/) (recommended for local development)

### Installation

```bash
# Clone the repository
git clone <repository-url>
cd devotional-growth

# Install PHP dependencies
composer install

# Install JavaScript dependencies
bun install

# Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed the database (word studies, etc.)
php artisan db:seed

# Build frontend assets
bun run build
```

### Development

```bash
# Start the development server (Vite + queue worker)
composer run dev

# Or run Vite separately
bun run dev
```

The application is automatically available at `https://devotional-growth.test` when using Laravel Herd.

### Testing

```bash
# Run all tests
php artisan test --compact

# Run a specific test file
php artisan test --compact tests/Feature/ExampleTest.php

# Filter by test name
php artisan test --compact --filter=testName
```

### Code Quality

```bash
# Format code with Pint
vendor/bin/pint

# Run static analysis
vendor/bin/phpstan analyse
```

## Data Models

| Model | Purpose |
|-------|---------|
| `User` | Extended with `partner_id`, `is_admin`, social accounts |
| `SocialAccount` | OAuth provider credentials linked to users |
| `EmailOtp` | Hashed OTP codes for passwordless email login |
| `Theme` | Devotional topic grouping (admin-created, draft/published) |
| `DevotionalEntry` | Devotional content within a theme (draft/published) |
| `ScriptureCache` | Cached Bible passage text from API |
| `ReadingPlan` | Bible reading plan definition |
| `ReadingPlanProgress` | User's reading plan progress |
| `WordStudy` | Greek/Hebrew word study data (seeded from Strong's) |
| `Bookmark` | Polymorphic bookmarks (devotional, scripture, word study) |
| `DevotionalCompletion` | Per-user entry completion records |
| `Observation` | User reflections on devotional entries |
| `GeneratedImage` | AI-generated images for entries |
| `AiGenerationLog` | Audit trail for AI content generation |
| `NotificationPreference` | Per-user notification settings |

## Design System

The application follows the **"Editorial Serenity"** design system — a high-end editorial aesthetic inspired by boutique journals and architectural monographs:

- Parchment backgrounds (`#FCF9F2`)
- Newsreader serif for headlines
- Inter sans-serif for body/UI text
- Moss green (`#56642B`) accents
- Depth through surface layering rather than borders

See `.kiro/specs/devotional-manager/design.md` for full design documentation and screen mockups.

## Navigation

| Viewport | Navigation |
|----------|-----------|
| Mobile (< 768px) | Bottom navigation bar (Themes, Study, Saved) |
| Desktop (>= 768px) | Sidebar navigation (Themes, Bible Study, Bookmarks, Notifications, Settings) |

## License

Proprietary. All rights reserved.
