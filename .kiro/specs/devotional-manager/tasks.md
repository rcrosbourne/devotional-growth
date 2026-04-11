# Implementation Plan: Devotional Manager

## Overview

Incremental implementation of the Devotional Manager feature on top of the existing Laravel 13 + React 19 + Inertia.js starter kit. Tasks are ordered: database/models first, then authentication, then backend logic (admin content management, user-facing features), then frontend pages, then PWA/offline, then testing. Each task builds on previous steps. All backend code uses PHP 8.4 with `declare(strict_types=1)`, `final readonly` Actions, `final` Models, Pest v4 tests. All frontend code uses React 19, TypeScript 6, shadcn/ui, Tailwind CSS v4.

## Tasks

- [ ] 1. Database migrations and Eloquent models
  - [x] 1.1 Create migration to modify `users` table — add `partner_id` (nullable FK to users, nullOnDelete), `is_admin` (boolean, default false), make `password` nullable
    - Run `php artisan make:migration` to create the migration
    - Update the `User` model: add `partner_id`, `is_admin` property annotations, casts, `partner()` belongsTo, `socialAccounts()` hasMany, `observations()` hasMany, `bookmarks()` hasMany, `completions()` hasMany, `notificationPreference()` hasOne, `isAdmin()` helper, `hasPartner()` helper
    - Update `UserFactory` with `is_admin` and `partner_id` states
    - _Requirements: 8.3, 13.4, 14.1_

  - [x] 1.2 Create `SocialAccount` model, migration, and factory
    - Migration: `social_accounts` table per design schema (id, user_id FK, provider, provider_id, provider_token, provider_refresh_token, timestamps, unique constraints)
    - Model: `final` class with `user()` belongsTo, property annotations, casts
    - Factory with states for google, apple, github providers
    - _Requirements: Social login_

  - [x] 1.3 Create `EmailOtp` model, migration, and factory
    - Migration: `email_otps` table per design schema (id, email index, code_hash, attempts default 0, expires_at, timestamps)
    - Model: `final` class with property annotations, casts, `isExpired()` and `hasExceededAttempts()` helpers
    - Factory with default and expired states
    - _Requirements: Email OTP login_

  - [x] 1.4 Create `Theme` model, migration, and factory
    - Migration: `themes` table per design schema (id, created_by FK to users, name unique, description nullable, status default 'draft', timestamps)
    - Model: `final` class with `entries()` hasMany, `creator()` belongsTo, `scopePublished()` query scope, property annotations, casts
    - Factory with draft and published states
    - _Requirements: 1.1, 1.2, 9.1_

  - [x] 1.5 Create `DevotionalEntry` model, migration, and factory
    - Migration: `devotional_entries` table per design schema (id, theme_id FK cascade, title, body longText, reflection_prompts nullable, adventist_insights nullable, display_order default 0, status default 'draft', timestamps)
    - Model: `final` class with `theme()` belongsTo, `scriptureReferences()` hasMany, `completions()` hasMany, `observations()` hasMany, `generatedImage()` hasOne, `scopePublished()`, property annotations, casts
    - Factory with draft and published states
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 1.6 Create `ScriptureReference` model, migration, and factory
    - Migration: `scripture_references` table per design schema (id, devotional_entry_id FK cascade, book, chapter, verse_start, verse_end nullable, raw_reference, timestamps)
    - Model: `final` class with `devotionalEntry()` belongsTo, property annotations, casts
    - Factory
    - _Requirements: 3.1, 3.5_

  - [x] 1.7 Create `ScriptureCache` model, migration, and factory
    - Migration: `scripture_caches` table per design schema (id, book, chapter, verse_start, verse_end nullable, bible_version, text longText, timestamps, unique composite index)
    - Model: `final` class with property annotations, casts
    - Factory
    - _Requirements: 3.1, 3.2_

  - [x] 1.8 Create `ReadingPlan`, `ReadingPlanDay`, and `ReadingPlanProgress` models, migrations, and factories
    - Migrations per design schema for all three tables
    - `ReadingPlan`: `days()` hasMany, `progress()` hasMany, `scopeDefault()`
    - `ReadingPlanDay`: `readingPlan()` belongsTo, `progress()` hasMany
    - `ReadingPlanProgress`: `user()` belongsTo, `readingPlan()` belongsTo, `readingPlanDay()` belongsTo
    - Factories for all three
    - _Requirements: 4.1, 4.2, 4.4_

  - [x] 1.9 Create `WordStudy` and `WordStudyPassage` models, migrations, and factories
    - Migrations per design schema for both tables
    - `WordStudy`: `passages()` hasMany, property annotations, casts
    - `WordStudyPassage`: `wordStudy()` belongsTo
    - Factories for both
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 1.10 Create `Bookmark` model, migration, and factory
    - Migration: `bookmarks` table per design schema (id, user_id FK cascade, morphs bookmarkable, timestamps, unique composite)
    - Model: `final` class with `user()` belongsTo, `bookmarkable()` morphTo, property annotations, casts
    - Factory
    - _Requirements: 6.1, 6.2_

  - [x] 1.11 Create `DevotionalCompletion` model, migration, and factory
    - Migration: `devotional_completions` table per design schema (id, user_id FK cascade, devotional_entry_id FK cascade, completed_at timestamp, timestamps, unique composite)
    - Model: `final` class with `user()` belongsTo, `devotionalEntry()` belongsTo, property annotations, casts
    - Factory
    - _Requirements: 8.1, 8.2_

  - [x] 1.12 Create `Observation` model, migration, and factory
    - Migration: `observations` table per design schema (id, user_id FK cascade, devotional_entry_id FK cascade, body text, edited_at nullable, timestamps)
    - Model: `final` class with `user()` belongsTo, `devotionalEntry()` belongsTo, property annotations, casts
    - Factory
    - _Requirements: 13.1, 13.2_

  - [x] 1.13 Create `GeneratedImage` model, migration, and factory
    - Migration: `generated_images` table per design schema (id, devotional_entry_id FK cascade, path, prompt text, timestamps)
    - Model: `final` class with `devotionalEntry()` belongsTo, property annotations, casts
    - Factory
    - _Requirements: 12.4, 12.5_

  - [x] 1.14 Create `AiGenerationLog` model, migration, and factory
    - Migration: `ai_generation_logs` table per design schema (id, admin_id FK cascade, prompt text, generated_content json nullable, status, error_message nullable, devotional_entry_id nullable FK nullOnDelete, timestamps)
    - Model: `final` class with `admin()` belongsTo, `devotionalEntry()` belongsTo, property annotations, casts
    - Factory with pending/completed/failed/approved states
    - _Requirements: AI content generation_

  - [x] 1.15 Create `NotificationPreference` model, migration, and factory
    - Migration: `notification_preferences` table per design schema (id, user_id FK cascade unique, completion_notifications default true, observation_notifications default true, new_theme_notifications default true, reminder_notifications default true, timestamps)
    - Model: `final` class with `user()` belongsTo, property annotations, casts
    - Factory
    - _Requirements: 14.8, 14.9_

  - [x] 1.16 Create Enums: `ContentStatus` (draft/published), `SocialProvider` (google/apple/github), `AiGenerationStatus` (pending/completed/failed/approved/rejected)
    - Place in `app/Enums/`
    - Use in model casts where applicable
    - _Requirements: Admin content model, Social login_


- [ ] 2. Authentication — Social Login and Email OTP
  - [x] 2.1 Install Laravel Socialite, configure Google/Apple/GitHub providers
    - Add `laravel/socialite` via Composer
    - Configure provider credentials in `config/services.php` using `config()` (not `env()` directly)
    - Add provider env vars to `.env.example`
    - _Requirements: Social login_

  - [x] 2.2 Create `HandleSocialLogin` action
    - `final readonly` class with `handle(string $provider, SocialiteUser $socialiteUser): User`
    - Find existing SocialAccount by provider+provider_id, return linked User
    - If no SocialAccount but email matches existing User, link the social account to that User
    - If no User exists, create new User (name, email, email_verified_at = now, password = null) and SocialAccount
    - Wrap in `DB::transaction()`
    - _Requirements: Social login, Property 32_

  - [x] 2.3 Create `DisconnectSocialAccount` action
    - `final readonly` class with `handle(User $user, string $provider): void`
    - Ensure user has at least one other auth method (another social account or email OTP) before disconnecting
    - _Requirements: Social login_

  - [x] 2.4 Create `SocialLoginController` with redirect and callback routes
    - `final readonly` controller
    - `redirect(string $provider)` — validate provider, redirect to Socialite
    - `callback(string $provider)` — get Socialite user, delegate to `HandleSocialLogin`, login, redirect to dashboard
    - Add routes: `GET /auth/{provider}/redirect`, `GET /auth/{provider}/callback`
    - _Requirements: Social login_

  - [x] 2.5 Create `SendEmailOtp` action
    - `final readonly` class with `handle(string $email): void`
    - Generate random 6-digit code, hash with `Hash::make()`, store in `email_otps` table with 10-min expiry
    - Delete any existing OTPs for that email first
    - Send OTP via Laravel Mailable class
    - Rate limit: 5 requests per email per hour (use `RateLimiter`)
    - _Requirements: Email OTP login, Property 34_

  - [x] 2.6 Create `VerifyEmailOtp` action
    - `final readonly` class with `handle(string $email, string $code): User`
    - Find OTP record by email, check expiry, check attempts < 3, verify code hash
    - On success: find or create User by email, delete OTP record, return User
    - On failure: increment attempts, throw validation exception
    - _Requirements: Email OTP login, Properties 35, 36_

  - [x] 2.7 Create `EmailOtpController` with request/verify routes and Form Requests
    - `final readonly` controller
    - `create()` — render `auth/email-otp` page
    - `store(SendEmailOtpRequest)` — validate email, delegate to `SendEmailOtp`, redirect to verify page
    - `showVerify()` — render `auth/email-otp-verify` page
    - `verify(VerifyEmailOtpRequest)` — delegate to `VerifyEmailOtp`, login user, redirect to dashboard
    - Create `SendEmailOtpRequest` and `VerifyEmailOtpRequest` Form Request classes
    - Add routes under `guest` middleware
    - _Requirements: Email OTP login_

  - [x] 2.8 Create `EnsureUserIsAdmin` middleware
    - Check `$request->user()->is_admin` — abort 403 if false
    - Register in `bootstrap/app.php` as named middleware `admin`
    - _Requirements: Admin content model, Property 38_

  - [x] 2.9 Write unit tests for `HandleSocialLogin`, `DisconnectSocialAccount`, `SendEmailOtp`, `VerifyEmailOtp` actions
    - Test happy paths, edge cases (existing user, new user, duplicate email, expired OTP, max attempts)
    - _Requirements: Social login, Email OTP login_

  - [x] 2.10 Write feature tests for `SocialLoginController` and `EmailOtpController`
    - Test redirect, callback, OTP request, OTP verify, rate limiting, error cases
    - Mock Socialite and Mail facades
    - _Requirements: Social login, Email OTP login_

- [x] 3. Checkpoint — Database and auth foundation
  - All 340 tests pass. No issues found.

- [x] 4. Admin content management — Themes
  - [x] 4.1 Create `CreateTheme` action
    - `final readonly` class with `handle(User $admin, string $name, ?string $description): Theme`
    - Create theme with `created_by` = admin id, status = draft
    - _Requirements: 9.1, Property 15_

  - [x] 4.2 Create `UpdateTheme` action
    - `handle(Theme $theme, string $name, ?string $description): Theme`
    - Update name/description, preserve associated entries
    - _Requirements: 9.3, Property 16_

  - [x] 4.3 Create `DeleteTheme` action
    - `handle(Theme $theme): void`
    - Delete theme (cascade deletes entries via FK)
    - _Requirements: 9.4, Property 17_

  - [x] 4.4 Create `PublishTheme` action
    - `handle(Theme $theme): Theme`
    - Change status from draft to published
    - _Requirements: Property 41_

  - [x] 4.5 Create Form Requests: `CreateThemeRequest`, `UpdateThemeRequest`
    - Validate name (required, unique), description (optional)
    - _Requirements: 9.1, 9.2_

  - [x] 4.6 Create `Admin\ThemeController` with full CRUD + publish routes
    - `final readonly` controller
    - `index()` — list all themes (draft + published) for admin
    - `create()` — render admin create form
    - `store(CreateThemeRequest)` — delegate to `CreateTheme`, redirect
    - `edit(Theme)` — render admin edit form
    - `update(UpdateThemeRequest, Theme)` — delegate to `UpdateTheme`, redirect
    - `destroy(Theme)` — delegate to `DeleteTheme`, redirect
    - `publish(Theme)` — delegate to `PublishTheme`, redirect
    - Register routes under `auth` + `admin` middleware prefix `/admin/themes`
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [x]* 4.7 Write unit tests for `CreateTheme`, `UpdateTheme`, `DeleteTheme`, `PublishTheme` actions
    - Test validation, cascade delete, status transitions
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [x]* 4.8 Write feature tests for `Admin\ThemeController`
    - Test all CRUD endpoints, admin authorization (non-admin gets 403), validation errors
    - _Requirements: 9.1, 9.2, 9.3, 9.4, Property 38_

- [x] 5. Admin content management — Devotional Entries
  - [x] 5.1 Create `CreateDevotionalEntry` action
    - `handle(Theme $theme, array $data): DevotionalEntry`
    - Create entry with title, body, reflection_prompts, adventist_insights, display_order, status = draft
    - Create associated ScriptureReference records from parsed references
    - Wrap in `DB::transaction()`
    - _Requirements: 2.1, 2.2, 2.3, Property 4_

  - [x] 5.2 Create `UpdateDevotionalEntry` action
    - `handle(DevotionalEntry $entry, array $data): DevotionalEntry`
    - Update fields, sync scripture references
    - _Requirements: 2.4_

  - [x] 5.3 Create `DeleteDevotionalEntry` action
    - `handle(DevotionalEntry $entry): void`
    - Delete entry (cascade via FK)
    - _Requirements: 2.5_

  - [x] 5.4 Create `PublishDevotionalEntry` action
    - `handle(DevotionalEntry $entry): DevotionalEntry`
    - Change status from draft to published
    - _Requirements: Property 41_

  - [x] 5.5 Create `ReorderDevotionalEntries` action
    - `handle(Theme $theme, array $orderedIds): void`
    - Update display_order for each entry based on position in array
    - _Requirements: 2.6, Property 5_

  - [x] 5.6 Create `ScriptureReferenceParser` service class in `app/Services/`
    - `parse(string $raw): object` — parse "John 3:16", "Psalm 23:1-6" etc. into book, chapter, verse_start, verse_end
    - `format(string $book, int $chapter, int $verseStart, ?int $verseEnd): string` — format back to string
    - Create `ScriptureReferenceFormat` validation rule in `app/Rules/`
    - _Requirements: 3.5, Property 6_

  - [x] 5.7 Create Form Requests: `CreateDevotionalEntryRequest`, `UpdateDevotionalEntryRequest`, `ReorderDevotionalEntriesRequest`
    - Validate title (required), body (required), scripture_references (required, array, min:1), reflection_prompts (optional), adventist_insights (optional)
    - _Requirements: 2.1, 2.4, Property 3_

  - [x] 5.8 Create `Admin\DevotionalEntryController` with full CRUD + publish + reorder routes
    - `final readonly` controller
    - `index(Theme)` — list all entries for theme (draft + published)
    - `create(Theme)` — render admin create form
    - `store(CreateDevotionalEntryRequest, Theme)` — delegate to `CreateDevotionalEntry`, redirect
    - `edit(Theme, DevotionalEntry)` — render admin edit form
    - `update(UpdateDevotionalEntryRequest, Theme, DevotionalEntry)` — delegate to `UpdateDevotionalEntry`, redirect
    - `destroy(Theme, DevotionalEntry)` — delegate to `DeleteDevotionalEntry`, redirect
    - `publish(Theme, DevotionalEntry)` — delegate to `PublishDevotionalEntry`, redirect
    - `reorder(ReorderDevotionalEntriesRequest, Theme)` — delegate to `ReorderDevotionalEntries`
    - Register routes under `auth` + `admin` middleware prefix `/admin/themes/{theme}/entries`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [x]* 5.9 Write unit tests for entry actions and `ScriptureReferenceParser`
    - Test `CreateDevotionalEntry`, `UpdateDevotionalEntry`, `DeleteDevotionalEntry`, `PublishDevotionalEntry`, `ReorderDevotionalEntries`
    - Test scripture parsing for various formats (single verse, range, multi-chapter)
    - _Requirements: 2.1–2.6, 3.5_

  - [x]* 5.10 Write feature tests for `Admin\DevotionalEntryController`
    - Test all CRUD endpoints, admin authorization, validation errors, reorder
    - _Requirements: 2.1–2.6, Property 38_

- [ ] 6. AI content generation (admin)
  - [ ] 6.1 Install Laravel AI SDK (Prism) and configure AI provider
    - Add `laravel/ai` via Composer
    - Configure AI provider credentials in `config/services.php`
    - _Requirements: AI content generation_

  - [ ] 6.2 Create `GenerateDevotionalContent` action
    - `final readonly` class with `handle(User $admin, string $prompt): AiGenerationLog`
    - Call Prism with structured prompt requesting title, body, scripture_references, reflection_prompts, adventist_insights
    - Store request and result in `AiGenerationLog` (status: pending → completed/failed)
    - Return the log record with generated content
    - Mock Prism in tests
    - _Requirements: AI content generation, Properties 39, 40_

  - [ ] 6.3 Create `Admin\AiContentController`
    - `final readonly` controller
    - `create()` — render AI generation interface page
    - `store(GenerateContentRequest)` — delegate to `GenerateDevotionalContent`, return generated content
    - Register routes under `auth` + `admin` middleware prefix `/admin/ai-content`
    - _Requirements: AI content generation_

  - [ ]* 6.4 Write unit tests for `GenerateDevotionalContent` action
    - Mock Prism, test successful generation, failed generation, structured output validation
    - _Requirements: AI content generation, Properties 39, 40_

  - [ ]* 6.5 Write feature tests for `Admin\AiContentController`
    - Test generation endpoint, admin authorization, error handling
    - _Requirements: AI content generation_

- [ ] 7. Checkpoint — Admin backend complete
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 8. User-facing backend — Themes and Devotional Entries (read-only)
  - [ ] 8.1 Create `ThemeController` (user-facing, read-only)
    - `final readonly` controller
    - `index()` — list published themes with entry counts, completed counts per user, progress percentage
    - `show(Theme)` — show published theme with published entries in display_order, completion status
    - Only return published themes (use `scopePublished()`)
    - Register routes under `auth` + `verified` middleware
    - _Requirements: 1.1, 1.2, 1.3, 1.4, Properties 1, 2, 37_

  - [ ] 8.2 Create `DevotionalEntryController` (user-facing, read-only)
    - `final readonly` controller
    - `show(Theme, DevotionalEntry)` — show published entry with title, body, scripture texts, reflection prompts, adventist insights, completion status, previous/next navigation, partner observations (if partner linked), generated image
    - Only return published entries within published themes
    - Include previous/next entry IDs for navigation
    - _Requirements: 10.1, 10.2, 10.3, 10.4, Properties 18, 19, 33_

  - [ ]* 8.3 Write feature tests for user-facing `ThemeController` and `DevotionalEntryController`
    - Test published-only filtering, progress calculation, previous/next navigation, solo vs partner mode
    - Test non-admin cannot see draft content (Property 37)
    - _Requirements: 1.1–1.4, 10.1–10.4_

- [ ] 9. Scripture passage fetching and caching
  - [ ] 9.1 Create `FetchScripturePassage` action
    - `final readonly` class with `handle(string $book, int $chapter, int $verseStart, ?int $verseEnd, string $bibleVersion = 'KJV'): string`
    - Check `ScriptureCache` first; if cached, return cached text
    - If not cached, call Bible API via Laravel HTTP client (3 retries, 500ms backoff)
    - Cache the response in `ScriptureCache`
    - Return error message if API fails and no cache exists
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [ ] 9.2 Create `ScriptureController`
    - `final readonly` controller
    - `show(Request)` — fetch scripture passage by reference and version, delegate to `FetchScripturePassage`
    - Register route under `auth` middleware
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [ ]* 9.3 Write unit tests for `FetchScripturePassage` action
    - Mock HTTP client, test caching, test API failure fallback, test version switching
    - _Requirements: 3.1–3.4_

- [ ] 10. Devotional completion tracking
  - [ ] 10.1 Create `CompleteDevotionalEntry` action
    - `handle(User $user, DevotionalEntry $entry): DevotionalCompletion`
    - Create completion record with user_id, devotional_entry_id, completed_at
    - Check if partner also completed → determine "completed together" status
    - Dispatch partner notification if partner is linked and notifications enabled
    - _Requirements: 8.1, 8.2, 8.3, Properties 13, 14_

  - [ ] 10.2 Add completion routes to `DevotionalEntryController`
    - `POST /themes/{theme}/entries/{entry}/complete` — delegate to `CompleteDevotionalEntry`
    - _Requirements: 8.1_

  - [ ]* 10.3 Write unit tests for `CompleteDevotionalEntry` action
    - Test completion recording, "completed together" logic, partner notification dispatch
    - _Requirements: 8.1–8.4_

- [ ] 11. Bible reading plans
  - [ ] 11.1 Create `ActivateReadingPlan` action
    - `handle(User $user, ReadingPlan $plan): ReadingPlanProgress`
    - Record start date, calculate daily passages based on plan definition
    - _Requirements: 4.2, Property 7_

  - [ ] 11.2 Create `CompleteReadingDay` action
    - `handle(User $user, ReadingPlanDay $day): ReadingPlanProgress`
    - Record completion date, update progress
    - _Requirements: 4.4, Property 8_

  - [ ] 11.3 Create `ReadingPlanController`
    - `final readonly` controller
    - `index()` — show Bible study dashboard with active reading plan, current day passages, progress
    - `show(ReadingPlan)` — show reading plan detail with daily list, progress percentage, missed days
    - `activate(ReadingPlan)` — delegate to `ActivateReadingPlan`
    - `completeDay(ReadingPlanDay)` — delegate to `CompleteReadingDay`
    - Register routes under `auth` + `verified` middleware prefix `/bible-study`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, Properties 7, 8, 9_

  - [ ] 11.4 Create a database seeder for the default 365-day reading plan
    - Seed `ReadingPlan` and `ReadingPlanDay` records covering the entire Bible
    - _Requirements: 4.1_

  - [ ]* 11.5 Write unit tests for `ActivateReadingPlan` and `CompleteReadingDay` actions
    - Test day calculation, progress percentage, missed day identification
    - _Requirements: 4.1–4.6_

  - [ ]* 11.6 Write feature tests for `ReadingPlanController`
    - Test activation, completion, progress display, missed days
    - _Requirements: 4.1–4.6_

- [ ] 12. Word study
  - [ ] 12.1 Create `WordStudyController`
    - `final readonly` controller
    - `show(WordStudy)` — show word study detail with original word, transliteration, definition, Strong's number, associated passages
    - `search(Request)` — search word studies by English word or Strong's number
    - Register routes under `auth` + `verified` middleware prefix `/bible-study/word-study`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, Property 10_

  - [ ] 12.2 Create a database seeder for Strong's Concordance word study data
    - Seed `WordStudy` and `WordStudyPassage` records from a dataset
    - _Requirements: 5.1, 5.2_

  - [ ]* 12.3 Write feature tests for `WordStudyController`
    - Test word study display, search, empty results
    - _Requirements: 5.1–5.4_

- [ ] 13. Bookmarks
  - [ ] 13.1 Create `CreateBookmark` and `DeleteBookmark` actions
    - `CreateBookmark::handle(User $user, string $bookmarkableType, int $bookmarkableId): Bookmark`
    - `DeleteBookmark::handle(Bookmark $bookmark): void`
    - Validate bookmarkable type is one of: DevotionalEntry, ScriptureReference, WordStudy
    - _Requirements: 6.1, 6.2, 6.3, Properties 11, 12_

  - [ ] 13.2 Create `BookmarkController`
    - `final readonly` controller
    - `index()` — list bookmarks grouped by type (devotional, scripture, word study)
    - `store(CreateBookmarkRequest)` — delegate to `CreateBookmark`
    - `destroy(Bookmark)` — delegate to `DeleteBookmark`
    - Register routes under `auth` + `verified` middleware
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [ ]* 13.3 Write unit and feature tests for bookmark actions and controller
    - Test creation, deletion, grouping by type, authorization
    - _Requirements: 6.1–6.4_

- [ ] 14. Observations
  - [ ] 14.1 Create `CreateObservation`, `UpdateObservation`, `DeleteObservation` actions
    - `CreateObservation::handle(User $user, DevotionalEntry $entry, string $body): Observation`
    - `UpdateObservation::handle(Observation $observation, string $body): Observation` — set edited_at
    - `DeleteObservation::handle(Observation $observation): void`
    - Dispatch partner notification on create (if partner linked and notifications enabled)
    - _Requirements: 13.1–13.7, Properties 22, 23, 24, 25_

  - [ ] 14.2 Create `ObservationController`
    - `final readonly` controller
    - `store(CreateObservationRequest, DevotionalEntry)` — delegate to `CreateObservation`
    - `update(UpdateObservationRequest, Observation)` — delegate to `UpdateObservation`
    - `destroy(Observation)` — delegate to `DeleteObservation`
    - Authorization: users can only edit/delete their own observations
    - Register routes under `auth` + `verified` middleware
    - _Requirements: 13.1–13.7_

  - [ ]* 14.3 Write unit and feature tests for observation actions and controller
    - Test CRUD, partner visibility, chronological ordering, authorization
    - _Requirements: 13.1–13.8_

- [ ] 15. AI image generation
  - [ ] 15.1 Create `GenerateDevotionalImage` action
    - `handle(DevotionalEntry $entry): GeneratedImage`
    - Construct prompt from entry title, scripture references, and body content
    - Call OpenAI DALL-E API via Laravel HTTP client
    - Store image in filesystem (local/S3), create `GeneratedImage` record
    - Replace existing image if one exists (after confirmation flag)
    - _Requirements: 12.1–12.8, Properties 20, 21_

  - [ ] 15.2 Create `DevotionalImageController`
    - `final readonly` controller
    - `store(DevotionalEntry)` — delegate to `GenerateDevotionalImage`, return image data
    - Register route under `auth` + `verified` middleware
    - _Requirements: 12.1–12.7_

  - [ ]* 15.3 Write unit and feature tests for image generation
    - Mock OpenAI HTTP calls, test prompt construction, test error handling, test image replacement
    - _Requirements: 12.1–12.8_

- [ ] 16. Partner linking and notifications
  - [ ] 16.1 Create `LinkPartner` action
    - `handle(User $user, User $partner): void`
    - Set `partner_id` on both users (bidirectional)
    - Wrap in `DB::transaction()`
    - _Requirements: 8.3, 13.4_

  - [ ] 16.2 Create `SendPartnerNotification` action
    - `handle(User $partner, string $type, array $data): void`
    - Check partner's notification preferences before sending
    - Use Laravel's built-in notification system (`DatabaseNotification`)
    - Notification types: completion, observation, new_theme
    - _Requirements: 14.1–14.4, 14.8, 14.9, Properties 26, 31_

  - [ ] 16.3 Create `NotificationController`
    - `final readonly` controller
    - `index()` — list notifications in reverse chronological order, mark all as read on visit
    - `updatePreferences(UpdateNotificationPreferencesRequest)` — update notification preferences
    - Register routes under `auth` + `verified` middleware
    - _Requirements: 14.5, 14.6, 14.7, 14.8, Properties 27, 28, 29, 30_

  - [ ] 16.4 Create `PartnerController`
    - `final readonly` controller
    - `store(LinkPartnerRequest)` — delegate to `LinkPartner`
    - `destroy()` — unlink partner (set both partner_id to null)
    - Register routes under `auth` + `verified` middleware
    - _Requirements: 8.3, 13.4_

  - [ ]* 16.5 Write unit and feature tests for partner linking, notifications, and preferences
    - Test bidirectional linking, notification dispatch, preference filtering, mark-as-read, unread count
    - _Requirements: 14.1–14.10_

- [ ] 17. Checkpoint — All backend logic complete
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 18. Frontend — Design system and layout
  - [ ] 18.1 Set up Editorial Serenity design system in Tailwind CSS v4
    - Configure custom colors: parchment `#FCF9F2`, moss green `#56642B`, surface layers
    - Add Newsreader (serif) and Inter (sans-serif) font imports
    - Configure Tailwind theme extensions for the design tokens
    - Update `resources/css/app.css` with design system variables
    - _Requirements: 7.1, 7.5_

  - [ ] 18.2 Create `devotional-layout` component with responsive navigation
    - Mobile (< 768px): bottom navigation bar with Themes, Study, Bookmarks, Settings tabs
    - Desktop (≥ 768px): sidebar navigation with all nav items + notification badge
    - Use existing `app-layout` as reference, extend with devotional-specific nav
    - Touch-friendly tap targets (min 44x44px)
    - Readable font sizes (min 16px body, 1.5 line-height)
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [ ] 18.3 Create shared UI components
    - `bottom-nav` — mobile bottom navigation bar
    - `completion-indicator` — visual completion status (self, partner, both)
    - `progress-bar` — theme/reading plan progress visualization
    - `confirmation-dialog` — reusable delete confirmation modal
    - `offline-indicator` — banner showing offline status
    - `notification-badge` — unread notification count badge
    - `entry-navigator` — previous/next entry navigation controls
    - _Requirements: 7.3, 8.2, 8.3, 4.6, 11.6_

- [ ] 19. Frontend — Auth pages
  - [ ] 19.1 Create welcome/login page (`resources/js/pages/auth/login.tsx`)
    - Social login buttons (Google, Apple, GitHub) linking to Socialite redirect routes
    - "Login with Email" button linking to OTP flow
    - Split layout on desktop (photo left, form right), single column on mobile
    - Use Wayfinder for type-safe route generation
    - _Requirements: Social login, Email OTP login_

  - [ ] 19.2 Create email OTP pages (`auth/email-otp.tsx`, `auth/email-otp-verify.tsx`)
    - Email input page with `useForm` for email submission
    - OTP verification page with 6-digit input (use `input-otp` package already installed)
    - Error handling for expired/invalid codes, rate limiting
    - _Requirements: Email OTP login_

- [ ] 20. Frontend — Admin pages
  - [ ] 20.1 Create admin themes pages (`admin/themes/index.tsx`, `admin/themes/create.tsx`, `admin/themes/edit.tsx`)
    - Index: list all themes with status chips (draft/published), entry counts, action buttons
    - Create: form with name, description fields
    - Edit: pre-populated form with existing data, delete button
    - Publish action button on index and edit pages
    - Use `useForm` for form handling, Wayfinder for routes
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [ ] 20.2 Create admin devotional entry pages (`admin/devotional-entries/index.tsx`, `admin/devotional-entries/create.tsx`, `admin/devotional-entries/edit.tsx`)
    - Index: data table with entries, status, actions, drag-to-reorder
    - Create: form with title, body (rich text), scripture references (dynamic add/remove), reflection prompts, adventist insights
    - Edit: pre-populated form, delete button, publish button
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [ ] 20.3 Create admin AI content generation page (`admin/ai-content/generate.tsx`)
    - Prompt textarea with "Generate Content" button
    - Live preview of generated content in editorial format
    - "Approve & Edit" button to save generated content as a new draft entry
    - Loading state during generation, error handling
    - _Requirements: AI content generation_

- [ ] 21. Frontend — User-facing theme and devotional pages
  - [ ] 21.1 Create themes index page (`themes/index.tsx`)
    - Published theme cards with cover image, title, description, progress bar, completion percentage
    - Featured series hero section
    - Overall progress summary
    - Empty state when no themes exist
    - _Requirements: 1.1, 1.3, 1.4_

  - [ ] 21.2 Create theme detail page (`themes/show.tsx`)
    - Entry list in display order with title, scripture refs, completion indicators
    - Theme progress tracking
    - Completion summary when all entries done
    - _Requirements: 1.2, 8.2, 8.4, 10.3_

  - [ ] 21.3 Create daily devotional view page (`devotional-entries/show.tsx`)
    - Single scrollable view: title, scripture passage (with version selector), body, reflection prompts, adventist insights
    - `scripture-passage` component with inline Bible text and `bible-version-selector` dropdown
    - `completion-indicator` and mark-complete button
    - `entry-navigator` for previous/next within theme
    - `observation-form` and `observation-list` for notes
    - `bookmark-button` toggle
    - `image-generator` button with loading state for AI images
    - Partner observations display (if partner linked)
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 3.1, 3.2, 3.3, 8.1, 8.3, 12.1, 12.3, 12.5, 13.1, 13.3, 13.4, 13.8_

- [ ] 22. Frontend — Bible study pages
  - [ ] 22.1 Create Bible study dashboard (`bible-study/index.tsx`)
    - Verse of the day section
    - Word study search input
    - Reading plan cards with progress indicators
    - _Requirements: 4.3, 5.1_

  - [ ] 22.2 Create reading plan progress page (`bible-study/reading-plan.tsx`)
    - Circular progress visualization
    - Daily reading list with completed/current/upcoming states
    - Missed readings section with catch-up option
    - Mark day complete action
    - _Requirements: 4.3, 4.4, 4.5, 4.6_

  - [ ] 22.3 Create word study detail page (`bible-study/word-study.tsx`)
    - Greek/Hebrew word display, transliteration, definition, Strong's number
    - Associated passages list
    - `word-study-popover` component for tapping words in scripture
    - Bookmark button for word studies
    - Empty state when no data available
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 23. Frontend — Bookmarks and notifications pages
  - [ ] 23.1 Create bookmarks page (`bookmarks/index.tsx`)
    - Bookmarks grouped by type: Devotional Entries, Scripture References, Word Studies
    - Remove bookmark action with confirmation
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [ ] 23.2 Create notifications page (`notifications/index.tsx`)
    - Notification list in reverse chronological order
    - Unread/read grouping with visual distinction (green left border for unread)
    - "Mark all as read" action (auto on page visit)
    - Notification preference toggles (completion, observation, new theme, reminders)
    - _Requirements: 14.5, 14.6, 14.7, 14.8, 14.9, 14.10_

- [ ] 24. Frontend — Settings page
  - [ ] 24.1 Create settings page (`settings/devotional.tsx` or extend existing settings)
    - Partner linking section with link/unlink CTA
    - Notification preference toggles
    - Bible version preference
    - Social account management (connect/disconnect providers)
    - _Requirements: 14.8, Social login_

- [ ] 25. Checkpoint — All frontend pages complete
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 26. PWA support
  - [ ] 26.1 Install and configure `vite-plugin-pwa` with Workbox
    - Add `vite-plugin-pwa` to dev dependencies
    - Configure in `vite.config.ts` with Workbox strategies: cache-first for static assets, network-first for API responses
    - _Requirements: 11.2_

  - [ ] 26.2 Create Web App Manifest
    - Application name, icons (192x192, 512x512), theme color (moss green), background color (parchment), display mode "standalone"
    - _Requirements: 11.1, 11.10_

  - [ ] 26.3 Implement service worker caching strategies
    - Cache application shell (HTML, CSS, JS, fonts) on first load
    - Cache viewed devotional entries and scripture passages for offline access
    - Cache Bible passage text per version
    - _Requirements: 11.2, 11.3, 11.4_

  - [ ] 26.4 Implement offline fallback and sync
    - Offline indicator banner when no network
    - Serve cached content when offline
    - "Content unavailable offline" message for uncached content
    - Queue offline actions (completions, observations) for sync on reconnect
    - _Requirements: 11.5, 11.6, 11.7, 11.8_

  - [ ] 26.5 Implement PWA install prompt
    - Detect installability criteria, show install prompt card
    - "Install to Home Screen" button, "Later" dismiss option
    - _Requirements: 11.9_

- [ ] 27. Wayfinder route generation
  - [ ] 27.1 Run `php artisan wayfinder:generate` to generate type-safe route bindings for all new controllers
    - Ensure all new routes are available as typed imports in frontend
    - Update frontend pages to use Wayfinder route helpers instead of hardcoded URLs
    - _Requirements: All frontend pages_

- [ ] 28. Property-based tests
  - [ ]* 28.1 Write property tests for theme and entry ordering
    - **Property 1: Published devotional entries are returned in sequential display order**
    - **Property 2: Theme progress counts are accurate for published entries**
    - **Validates: Requirements 1.2, 1.3, 8.4**

  - [ ]* 28.2 Write property tests for devotional entry validation and round-trip
    - **Property 3: Devotional entry validation rejects incomplete submissions**
    - **Property 4: Devotional entry creation round-trip preserves all fields**
    - **Property 5: Reordering entries persists the requested permutation**
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.6**

  - [ ]* 28.3 Write property test for scripture reference parsing
    - **Property 6: Scripture reference parsing round-trip**
    - **Validates: Requirements 3.5**

  - [ ]* 28.4 Write property tests for reading plan calculations
    - **Property 7: Reading plan day calculation is correct for any start date**
    - **Property 8: Reading plan progress percentage is accurate**
    - **Property 9: Missed reading plan days are correctly identified**
    - **Validates: Requirements 4.2, 4.3, 4.4, 4.5**

  - [ ]* 28.5 Write property tests for word study and bookmarks
    - **Property 10: Word study display includes all required fields and passages**
    - **Property 11: Bookmark creation round-trip preserves all data**
    - **Property 12: Bookmarks are correctly grouped by type**
    - **Validates: Requirements 5.2, 5.3, 6.1, 6.2, 6.4**

  - [ ]* 28.6 Write property tests for completion tracking
    - **Property 13: Completion recording preserves user identity and timestamp**
    - **Property 14: "Completed together" indicator requires both partners**
    - **Validates: Requirements 8.1, 8.2, 8.3**

  - [ ]* 28.7 Write property tests for observations
    - **Property 22: Observation creation round-trip and display**
    - **Property 23: Partner observations are visible to linked partner**
    - **Property 24: Observation edits update text and record timestamp**
    - **Property 25: Observations are displayed in chronological order**
    - **Validates: Requirements 13.2, 13.3, 13.4, 13.5, 13.7**

  - [ ]* 28.8 Write property tests for notifications
    - **Property 26: Partner events dispatch notifications**
    - **Property 27: Notifications are listed in reverse chronological order**
    - **Property 28: Opening notification center marks all as read**
    - **Property 29: Unread notification count is accurate**
    - **Property 30: Notification preference round-trip**
    - **Property 31: Disabled notification types are not dispatched**
    - **Validates: Requirements 14.1, 14.2, 14.3, 14.5, 14.6, 14.7, 14.8, 14.9**

  - [ ]* 28.9 Write property tests for auth and admin authorization
    - **Property 32: Social login round-trip preserves user identity**
    - **Property 33: Solo users see no partner features**
    - **Property 34: OTP creation stores hashed code with correct expiry**
    - **Property 35: OTP verification succeeds if and only if code is correct, not expired, and under attempt limit**
    - **Property 36: Incorrect OTP submission increments attempt counter**
    - **Property 37: Only published content is visible to non-admin users**
    - **Property 38: Non-admin users cannot perform admin content actions**
    - **Validates: Social login, Email OTP login, Requirements 13.8**

  - [ ]* 28.10 Write property tests for AI generation and publishing
    - **Property 39: AI-generated content preserves expected structure**
    - **Property 40: AI generation log round-trip**
    - **Property 41: Publishing changes content status from draft to published**
    - **Validates: AI content generation, Draft/published workflow**

  - [ ]* 28.11 Write property tests for daily devotional view and AI images
    - **Property 18: Daily devotional view includes all populated fields**
    - **Property 19: Previous/next navigation is correct within a theme**
    - **Property 20: AI image prompt incorporates entry content**
    - **Property 21: Generated image is associated with the correct entry**
    - **Validates: Requirements 10.1, 10.2, 12.2, 12.4**

  - [ ]* 28.12 Write property tests for theme admin operations
    - **Property 15: Theme name uniqueness is enforced**
    - **Property 16: Editing a theme preserves its entries**
    - **Property 17: Deleting a theme cascades to its entries**
    - **Validates: Requirements 9.1, 9.2, 9.3, 9.4**

- [ ] 29. Final checkpoint — Full integration
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document using Pest datasets with Faker-generated data (100+ iterations each)
- Unit tests validate specific examples and edge cases
- All backend code follows the Actions pattern (`final readonly`, single `handle()` method)
- All controllers are `final readonly` and delegate to Actions
- All models are `final` with `declare(strict_types=1)`
- Use `php artisan make:` commands for scaffolding new files
- Use Laravel Wayfinder for type-safe frontend route generation
- External services (Bible API, OpenAI, Prism) must be mocked in tests using `Http::fake()`
