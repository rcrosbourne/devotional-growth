# Plan: Sabbath School Quarterly Integration

> Source PRD: docs/sabbath-school-quarterly-prd.md

## Architectural Decisions

Durable decisions that apply across all phases:

- **Models**: `Quarterly`, `Lesson`, `LessonDay`, `LessonDayCompletion`, `LessonDayScriptureReference`, `LessonDayObservation` — parallel to Theme/DevotionalEntry, not reusing them
- **Routes (user)**: Nested under `/sabbath-school` with named prefix `sabbath-school.`
- **Routes (admin)**: Nested under `/admin/sabbath-school` with named prefix `admin.sabbath-school.`
- **Controllers**: `final readonly` classes following existing pattern. User: `SabbathSchool\QuarterlyController`, `SabbathSchool\LessonController`, `SabbathSchool\LessonDayController`, `SabbathSchool\LessonDayCompletionController`, `SabbathSchool\LessonDayObservationController`. Admin: `Admin\SabbathSchool\QuarterlyController`
- **Actions**: All business logic in `app/Actions/SabbathSchool/` namespace
- **Pages**: Inertia pages in `resources/js/pages/sabbath-school/` (user) and `resources/js/pages/admin/sabbath-school/` (admin)
- **Navigation**: "Sabbath School" top-level nav item in bottom nav (mobile) and sidebar (desktop)
- **Bookmarks**: Reuse existing polymorphic `Bookmark` model for `Lesson` and `LessonDay`
- **Notifications**: Follow existing `SendPartnerNotification` action pattern with preference checking
- **Images**: Stored at `storage/app/public/images/sabbath-school/{quarter_code}/lesson-{number}.{ext}`
- **Quarter code format**: `{yy}{a-d}` where a=Q1, b=Q2, c=Q3, d=Q4

---

## Phase 1: Schema + Scraper + Admin Import + Basic Browsing

**Goal**: Prove the entire pipeline end-to-end — admin imports a quarter from ssnet.org, content is parsed and stored, users can browse it.

### What to build

A complete vertical slice from database to UI. Create all core models (Quarterly, Lesson, LessonDay) with migrations and factories. Build the rule-based HTML parser as an Action that fetches lesson pages from ssnet.org, splits by day anchors, extracts titles/body/memory text/discussion questions, and handles parse failures gracefully. Create the admin Sabbath School page with an import form. Add the "Sabbath School" nav item. Build a basic index page showing imported quarters and a quarter view showing lesson cards.

### Tasks

#### 1.1 Database migrations and models
- [ ] Create migration for `quarterlies` table (id, title, quarter_code unique, year, quarter_number, is_active default false, description nullable text, source_url, last_synced_at nullable timestamp, timestamps)
- [ ] Create migration for `lessons` table (id, quarterly_id FK cascade, lesson_number, title, date_start date, date_end date, memory_text text, memory_text_reference string, image_path nullable, image_prompt nullable text, has_parse_warnings boolean default false, timestamps; unique on quarterly_id+lesson_number)
- [ ] Create migration for `lesson_days` table (id, lesson_id FK cascade, day_position integer, day_name string, title string, body longText, discussion_questions json nullable, has_parse_warning boolean default false, timestamps; unique on lesson_id+day_position)
- [ ] Create `Quarterly` model with relationships: `lessons()` HasMany, scope `active()`
- [ ] Create `Lesson` model with relationships: `quarterly()` BelongsTo, `days()` HasMany, `scriptureReferences()` HasManyThrough
- [ ] Create `LessonDay` model with relationships: `lesson()` BelongsTo
- [ ] Create factories for all three models
- [ ] Run migrations and verify schema

#### 1.2 HTML parser action
- [ ] Create `ParseQuarterlyLesson` action that accepts a lesson HTML string and lesson number, returns structured data (title, date range, memory text, memory text reference, 7 day sections each with title/body/discussion_questions)
- [ ] Parse day sections by splitting on anchors (#sab, #sun, #mon, #tue, #wed, #thu, #fri)
- [ ] Extract memory text from Sabbath section using "Memory Text:" pattern
- [ ] Extract discussion questions from Friday section (numbered list items) into string array
- [ ] Style Ellen G. White citations as blockquotes during parsing
- [ ] Strip Inside Story content (everything after Friday section or matching Inside Story patterns)
- [ ] Set has_parse_warning flag when a section can't be cleanly parsed, storing raw HTML as fallback
- [ ] Extract lesson title and date range from page header
- [ ] Write tests for the parser with sample HTML fixtures

#### 1.3 Quarter import action
- [ ] Create `ImportQuarter` action that accepts an optional quarter_code (defaults to current quarter based on date)
- [ ] Implement quarter code resolution: if none provided, calculate from current date (Q1=a, Q2=b, Q3=c, Q4=d)
- [ ] Fetch the quarter index page from ssnet.org to extract the quarter title
- [ ] Fetch all 13 lesson pages using URL pattern `https://ssnet.org/lessons/{code}/less{01-13}.html`
- [ ] Use Laravel HTTP client with retry logic (3 attempts, 500ms backoff, 10s timeout) matching existing FetchScripturePassage pattern
- [ ] Parse each lesson using `ParseQuarterlyLesson` action
- [ ] Upsert Quarterly record (match by quarter_code)
- [ ] Upsert Lesson records (match by quarterly_id + lesson_number)
- [ ] Upsert LessonDay records (match by lesson_id + day_position)
- [ ] Set is_active to true on the imported quarter (set others to false)
- [ ] Update last_synced_at on the Quarterly
- [ ] Handle partial failures gracefully — import what's available, skip unavailable lessons (quarter published incrementally)
- [ ] Write tests for the import action (mock HTTP responses)

#### 1.4 Admin Sabbath School page
- [ ] Create `Admin\SabbathSchool\QuarterlyController` (final readonly) with index and import methods
- [ ] Create `ImportQuarterRequest` form request (optional quarter_code validation: string, max 4 chars, alphanumeric)
- [ ] Register admin routes: `GET /admin/sabbath-school` (index), `POST /admin/sabbath-school/import` (import)
- [ ] Create Inertia page `admin/sabbath-school/index.tsx` with:
  - List of imported quarters (title, quarter_code, lesson count, last_synced_at)
  - Import form with optional quarter_code text input and submit button
  - Loading/progress state during import
- [ ] Add "Manage Sabbath School" to admin nav items in devotional-layout
- [ ] Write feature tests for admin import endpoint (auth, validation, success, failure)

#### 1.5 User-facing Sabbath School index and quarter view
- [ ] Create `SabbathSchool\QuarterlyController` with index and show methods
- [ ] Register routes: `GET /sabbath-school` (index), `GET /sabbath-school/{quarterly}` (show)
- [ ] Create Inertia page `sabbath-school/index.tsx`:
  - Active quarter featured prominently (title, description, lesson count)
  - Past quarters listed below in "Previous Quarters" section
  - Context-aware empty state: friendly message for users, import button for admins
- [ ] Create Inertia page `sabbath-school/show.tsx` (quarter view):
  - Quarter title and description header
  - 13 lesson cards in a grid/list showing: lesson number, title, date range, memory text preview
  - Highlight current lesson based on today's date
- [ ] Add "Sabbath School" to bottom nav (mobile) and sidebar nav (desktop) in devotional-layout
- [ ] Add Wayfinder route generation for new routes
- [ ] Write feature tests for user-facing pages (auth required, shows published content)

### Acceptance criteria
- [ ] Admin can navigate to /admin/sabbath-school and see the import form
- [ ] Admin can import a quarter (with or without specifying quarter_code) and see it listed
- [ ] Parser correctly extracts 7 daily sections, memory text, discussion questions from real ssnet.org HTML
- [ ] Parser flags sections it can't cleanly parse without failing the entire import
- [ ] Partially available quarters (fewer than 13 lessons) import successfully
- [ ] "Sabbath School" nav item appears in mobile bottom nav and desktop sidebar
- [ ] User can browse to /sabbath-school and see the imported quarter
- [ ] User can click into a quarter and see 13 lesson cards
- [ ] Empty state shows appropriate message for users vs admins
- [ ] All new code has passing tests

---

## Phase 2: Lesson + Day View with Scripture Integration

**Goal**: Users can read lesson content day-by-day with scripture references that support version switching.

### What to build

The lesson detail view showing the memory text as a prominent header card, with 7 day sections navigable via tabs or cards. The day detail view showing the full study content (body text, discussion questions on Friday). Scripture reference extraction during the scrape (parse "Read [reference]" patterns from day body text) stored as LessonDayScriptureReference records. Integrate with existing FetchScripturePassage action so users can view extracted references in different Bible versions. Previous/next navigation between days within a lesson.

### Tasks

#### 2.1 Scripture reference extraction
- [ ] Create migration for `lesson_day_scripture_references` table (id, lesson_day_id FK cascade, book, chapter, verse_start, verse_end nullable, raw_reference, timestamps)
- [ ] Create `LessonDayScriptureReference` model with `lessonDay()` BelongsTo relationship
- [ ] Add `scriptureReferences()` HasMany relationship to LessonDay model
- [ ] Update `ParseQuarterlyLesson` action to extract scripture references from "Read [reference]" patterns in day body text
- [ ] Store extracted references as LessonDayScriptureReference records during import (upsert safe)
- [ ] Write tests for scripture reference parsing with various formats ("John 3:16", "Psalm 23:1-6", "Romans 8:28-39", "1 John 2:15-17")

#### 2.2 Lesson view page
- [ ] Add show method to `SabbathSchool\LessonController` (or extend QuarterlyController)
- [ ] Register route: `GET /sabbath-school/{quarterly}/lessons/{lesson}`
- [ ] Create Inertia page `sabbath-school/lesson.tsx`:
  - Memory text displayed as a prominent header card (verse text + reference)
  - 7 day cards/tabs showing day name, title, completion status placeholder
  - Each day card links to the day detail view
  - Lesson progress indicator (placeholder — wired up in Phase 3)
  - Breadcrumb navigation back to quarter view
- [ ] Write feature tests for lesson view

#### 2.3 Day view page
- [ ] Create `SabbathSchool\LessonDayController` with show method
- [ ] Register route: `GET /sabbath-school/{quarterly}/lessons/{lesson}/days/{lessonDay}`
- [ ] Create Inertia page `sabbath-school/day.tsx`:
  - Day name and title header
  - Body content rendered as HTML
  - Discussion questions section (if present — mainly Friday) displayed as a styled list
  - Scripture references section: list extracted references with expandable passage text
  - Bible version selector that re-fetches scripture passage text via existing scripture system
  - Previous/next day navigation within the lesson
  - When on Sabbath (first day), "previous" goes to the previous lesson's Friday (or disabled if lesson 1)
  - When on Friday (last day), "next" goes to the next lesson's Sabbath (or disabled if lesson 13)
  - Subtle footer attribution: "Content sourced from ssnet.org" with link
- [ ] Create an endpoint or reuse existing `ScriptureController` for fetching passage text by reference + version
- [ ] Write feature tests for day view and scripture fetching

### Acceptance criteria
- [ ] Scripture references are extracted during import and stored as LessonDayScriptureReference records
- [ ] User can navigate from quarter view -> lesson view -> day view
- [ ] Lesson view shows memory text prominently and 7 day cards
- [ ] Day view renders body content, discussion questions (Friday), and scripture references
- [ ] User can switch Bible version and see scripture passage text update
- [ ] Previous/next navigation works between days and across lesson boundaries
- [ ] Attribution footer appears on day view pages
- [ ] All new code has passing tests

---

## Phase 3: Completion Tracking + Progress

**Goal**: Users can mark daily sections complete, see progress through lessons and quarters, and partners get notified.

### What to build

LessonDayCompletion model and migration. A completion toggle on the day view. Progress indicators on the lesson view (X/7 days) and quarter view (X/13 lessons). Lesson auto-completes when all 7 days are marked. "Completed together" indicator when both partners have completed a day. Partner notifications on completion following the existing SendPartnerNotification pattern.

### Tasks

#### 3.1 Completion model and action
- [ ] Create migration for `lesson_day_completions` table (id, user_id FK cascade, lesson_day_id FK cascade, completed_at timestamp, timestamps; unique on user_id+lesson_day_id)
- [ ] Create `LessonDayCompletion` model with `user()` BelongsTo, `lessonDay()` BelongsTo
- [ ] Add `completions()` HasMany relationship to LessonDay model
- [ ] Create `CompleteLessonDay` action (follows existing `CompleteDevotionalEntry` pattern):
  - Uses firstOrCreate pattern to prevent duplicates
  - Checks for partner and notification preferences
  - Sends partner notification if applicable
- [ ] Create `UncompleteLessonDay` action to remove completion record
- [ ] Write unit tests for both actions

#### 3.2 Completion notification
- [ ] Create `PartnerCompletedLessonDay` notification (follows existing `PartnerCompletedEntry` pattern):
  - Implements ShouldQueue
  - Database channel only
  - Includes partner name, lesson title, day name in notification data
- [ ] Add a new notification type constant for lesson day completion to SendPartnerNotification (or handle inline)
- [ ] Wire notification preference checking (reuse `completion_notifications` preference flag)
- [ ] Write tests for notification dispatch and preference checking

#### 3.3 Completion endpoints
- [ ] Create `SabbathSchool\LessonDayCompletionController` with store and destroy methods
- [ ] Register routes: `POST /sabbath-school/lessons/days/{lessonDay}/complete`, `DELETE /sabbath-school/lessons/days/{lessonDay}/complete`
- [ ] Write feature tests for completion endpoints (auth, toggle, duplicate prevention, partner notification)

#### 3.4 Progress UI
- [ ] Update day view (`sabbath-school/day.tsx`): add completion button/toggle, show "completed" state, show "completed together" when both partners have completed
- [ ] Update lesson view (`sabbath-school/lesson.tsx`): show completion checkmark per day card, progress bar (X/7 days), "lesson complete" state when 7/7
- [ ] Update quarter view (`sabbath-school/show.tsx`): show progress per lesson card (X/7 days), overall quarter progress indicator
- [ ] Update Sabbath School index (`sabbath-school/index.tsx`): show overall quarter progress on the featured card
- [ ] Pass completion data as props from controllers (eager load completions for current user and partner)

### Acceptance criteria
- [ ] User can mark a lesson day as complete from the day view
- [ ] User can unmark a lesson day completion
- [ ] Completing a day updates the progress on the lesson view and quarter view
- [ ] Lesson shows as "complete" when all 7 days are marked
- [ ] "Completed together" indicator appears when both partners have completed a day
- [ ] Partner receives notification when user completes a lesson day (respects preferences)
- [ ] Duplicate completions are prevented (unique constraint)
- [ ] All new code has passing tests

---

## Phase 4: Observations + Partner Collaboration

**Goal**: Users can add freeform observations to daily sections, visible to their partner, with notifications.

### What to build

LessonDayObservation model (following existing Observation pattern — direct FK, not polymorphic). Create/edit/delete endpoints. Display observations on the day view alongside the study content, showing both user's and partner's observations. Partner notification on new observation.

### Tasks

#### 4.1 Observation model
- [ ] Create migration for `lesson_day_observations` table (id, user_id FK cascade, lesson_day_id FK cascade, body text, edited_at nullable timestamp, timestamps)
- [ ] Create `LessonDayObservation` model with `user()` BelongsTo, `lessonDay()` BelongsTo
- [ ] Add `observations()` HasMany relationship to LessonDay model
- [ ] Create factory for LessonDayObservation

#### 4.2 Observation actions
- [ ] Create `CreateLessonDayObservation` action:
  - Creates observation record
  - Checks for partner and notification preferences
  - Sends partner notification if applicable
- [ ] Create `UpdateLessonDayObservation` action (sets edited_at timestamp)
- [ ] Create `DeleteLessonDayObservation` action
- [ ] Write unit tests for all observation actions

#### 4.3 Observation notification
- [ ] Create `PartnerAddedLessonDayObservation` notification (follows existing `PartnerAddedObservation` pattern):
  - Implements ShouldQueue
  - Database channel only
  - Includes partner name, lesson title, day name in notification data
- [ ] Wire notification preference checking (reuse `observation_notifications` preference flag)
- [ ] Write tests for notification dispatch

#### 4.4 Observation endpoints
- [ ] Create `SabbathSchool\LessonDayObservationController` with store, update, destroy methods
- [ ] Create form request for observation validation (body required, string)
- [ ] Register routes: `POST /sabbath-school/lessons/days/{lessonDay}/observations`, `PUT /sabbath-school/observations/{observation}`, `DELETE /sabbath-school/observations/{observation}`
- [ ] Write feature tests (auth, CRUD, only own observations editable/deletable, partner visibility)

#### 4.5 Observation UI
- [ ] Update day view (`sabbath-school/day.tsx`):
  - Observation form below study content (textarea + submit)
  - List of observations in chronological order, showing author name and timestamp
  - Edit/delete controls on own observations
  - Partner's observations displayed (if partner linked)
  - "edited" indicator for edited observations
  - Hide partner collaboration UI when no partner linked
- [ ] Pass observations as props from LessonDayController (eager load user's and partner's observations)

### Acceptance criteria
- [ ] User can add an observation to a lesson day
- [ ] User can edit their own observation (edited_at timestamp updated)
- [ ] User can delete their own observation (with confirmation)
- [ ] Partner's observations are visible on shared lesson days
- [ ] Partner receives notification when user adds an observation (respects preferences)
- [ ] Observations display in chronological order with author attribution
- [ ] Observation form is hidden or simplified when no partner linked (solo mode still works)
- [ ] All new code has passing tests

---

## Phase 5: AI Image Generation

**Goal**: Each lesson gets an AI-generated image for visual enrichment, generated as background jobs after import.

### What to build

A `GenerateLessonImage` action following the existing `GenerateDevotionalImage` pattern. A queued job that dispatches after quarter import, generating one image per lesson. Image display on lesson cards (quarter view) and the lesson view header. Admin UI showing image generation status per lesson.

### Tasks

#### 5.1 Image generation action and job
- [ ] Create `GenerateLessonImage` action:
  - Accepts a Lesson model
  - Builds prompt from lesson title + memory text + general theme context
  - Uses Laravel AI SDK: `Image::of($prompt)->square()->quality('medium')->timeout(120)->generate()`
  - Stores image at `storage/app/public/images/sabbath-school/{quarter_code}/lesson-{number}.{ext}`
  - Updates Lesson record with image_path and image_prompt
  - Handles errors gracefully (logs warning, doesn't fail the job permanently)
- [ ] Create `GenerateLessonImageJob` queued job that calls the action
- [ ] Update `ImportQuarter` action to dispatch GenerateLessonImageJob for each lesson without an existing image after scrape completes
- [ ] Write tests for image generation action (mock AI SDK)

#### 5.2 Image display
- [ ] Update quarter view (`sabbath-school/show.tsx`): display AI-generated image on each lesson card (with fallback/placeholder if not yet generated)
- [ ] Update lesson view (`sabbath-school/lesson.tsx`): display lesson image as a hero/header image
- [ ] Update Sabbath School index (`sabbath-school/index.tsx`): show active quarter's image or a featured lesson image
- [ ] Ensure images are served via public storage link

#### 5.3 Admin image status
- [ ] Update admin quarterly index (`admin/sabbath-school/index.tsx`): show image generation progress per quarter (e.g., "10/13 images generated")
- [ ] Optionally add a "regenerate image" button per lesson in admin detail view
- [ ] Create admin quarter detail route and page: `GET /admin/sabbath-school/{quarterly}` showing per-lesson status (title, parse warnings, image status)

### Acceptance criteria
- [ ] AI image generation jobs are dispatched automatically after quarter import
- [ ] Each lesson gets one AI-generated image based on its content
- [ ] Images display on lesson cards in the quarter view
- [ ] Images display as a hero on the lesson view
- [ ] Placeholder/skeleton shows when image is not yet generated
- [ ] Re-sync does not regenerate images for lessons that already have one
- [ ] Admin can see image generation progress per quarter
- [ ] All new code has passing tests

---

## Phase 6: Bookmarks + Re-Sync + Polish

**Goal**: Complete the feature with bookmarks, admin re-sync, quarter management, and polish.

### What to build

Polymorphic bookmarks on Lesson and LessonDay using the existing Bookmark model. Admin re-sync functionality (upsert preserving user data). Admin quarter management (set active, view list). Context-aware empty states. Attribution footer. Edge case handling and UI polish.

### Tasks

#### 6.1 Bookmarks
- [ ] Add `bookmarks()` MorphMany relationship to Lesson model
- [ ] Add `bookmarks()` MorphMany relationship to LessonDay model
- [ ] Add bookmark toggle to lesson view (bookmark the whole lesson)
- [ ] Add bookmark toggle to day view (bookmark a specific day)
- [ ] Update Bookmarks index page to display Lesson and LessonDay bookmarks in their own group
- [ ] Reuse existing BookmarkController or extend it to handle the new bookmarkable types
- [ ] Write tests for bookmarking lessons and lesson days

#### 6.2 Admin re-sync
- [ ] Add re-sync endpoint: `POST /admin/sabbath-school/{quarterly}/sync`
- [ ] Implement re-sync in `ImportQuarter` action (or create `SyncQuarter` action):
  - Re-fetch all lesson pages from ssnet.org
  - Upsert lessons by quarterly_id + lesson_number
  - Upsert lesson days by lesson_id + day_position
  - Update content fields only (title, body, discussion_questions, scripture refs, memory text)
  - Preserve all user data (completions, observations, bookmarks)
  - Queue AI image generation only for new lessons without images
  - Update last_synced_at
- [ ] Add re-sync button to admin quarterly index page per quarter
- [ ] Show last_synced_at timestamp per quarter
- [ ] Write tests for re-sync (verify user data preserved, content updated, new lessons created)

#### 6.3 Admin quarter management
- [ ] Add activate endpoint: `PUT /admin/sabbath-school/{quarterly}/activate`
- [ ] Implement: set is_active=true on target quarter, is_active=false on all others
- [ ] Add "Set Active" button per quarter in admin index
- [ ] Visual indicator for which quarter is currently active
- [ ] Write tests for activation

#### 6.4 Polish and edge cases
- [ ] Verify all empty states work correctly:
  - Sabbath School index with no quarters (user vs admin)
  - Quarter view with partially imported lessons
  - Day view with no observations yet
- [ ] Verify mobile responsiveness on all new pages (320px to 1440px)
- [ ] Ensure touch targets meet 44x44px minimum on interactive elements
- [ ] Verify navigation works correctly:
  - Bottom nav active state on Sabbath School pages
  - Sidebar nav active state on desktop
  - Breadcrumb navigation through quarter -> lesson -> day
- [ ] Verify Notification Center displays lesson day completion and observation notifications correctly
- [ ] Run full test suite to check for regressions
- [ ] Run `vendor/bin/pint --dirty --format agent` to fix formatting
- [ ] Run `composer test:local` for final validation

### Acceptance criteria
- [ ] Users can bookmark lessons and lesson days
- [ ] Bookmarks appear in the Bookmarks page grouped appropriately
- [ ] Admin can re-sync a quarter and content updates without losing user data
- [ ] Admin can set a quarter as active (only one active at a time)
- [ ] Empty states display correctly for users and admins
- [ ] All pages are mobile-responsive with proper touch targets
- [ ] Navigation correctly highlights Sabbath School across all sub-pages
- [ ] Notifications for lesson day activities display correctly in Notification Center
- [ ] Full test suite passes
- [ ] Code formatting passes pint
