# PRD: Sabbath School Quarterly Integration

> Status: Approved
> Created: 2026-04-12
> Branch: feature/add-quarterly-information

## 1. Overview

Add a first-class "Sabbath School" feature to the devotional app that imports Adventist quarterly lesson content from ssnet.org, presents it with a premium interactive experience including AI-generated images, and enables couples to study lessons collaboratively using the same observation/completion patterns as the existing devotional system.

## 2. Goals

- Allow users to study the Sabbath School quarterly within the app with a better, more interactive experience than the source website
- Enable collaborative study between partners (observations, completion tracking, notifications)
- Provide AI-generated images per lesson for visual enrichment
- Integrate with the existing scripture system for Bible version switching
- Give admins a simple workflow to import and manage quarterly content

## 3. Context & Constraints

### Existing System
- The app has Thematic Devotions (Theme -> DevotionalEntry) and Bible Study (ReadingPlan, WordStudy) modes
- Collaborative features exist: observations (freeform notes on DevotionalEntry), completion tracking (DevotionalCompletion), partner notifications, polymorphic bookmarks
- Scripture system: FetchScripturePassage action fetches from bible-api.com, caches in ScriptureCache table, supports version switching (KJV default)
- AI image generation: GenerateDevotionalImage action uses Laravel AI SDK (DALL-E), stores in public filesystem
- Admin panel: dedicated controllers under /admin with Theme/DevotionalEntry CRUD, AI content generation
- Stack: Laravel 12, React 19, Inertia.js v2, Tailwind v4, PostgreSQL

### Source Content
- ssnet.org publishes quarterly Sabbath School lessons freely
- URL pattern: `https://ssnet.org/lessons/{quarter_code}/less{01-13}.html`
- Quarter codes: year + letter (e.g., `26b` = 2026 Q2, where a=Q1, b=Q2, c=Q3, d=Q4)
- Each quarter has 13 lessons, each lesson has 7 daily sections (Sabbath through Friday)
- Content structure per lesson:
  - **Sabbath**: Introduction + Memory Text (verse to memorize for the week)
  - **Sunday-Thursday**: Daily study sections with title, scripture readings, commentary, reflection questions, EGW quotes
  - **Friday**: "Further Thought" with EGW quotes, numbered Discussion Questions, summary
- HTML uses predictable anchors: `#sab`, `#sun`, `#mon`, `#tue`, `#wed`, `#thu`, `#fri`
- Site updates content regularly; quarters sometimes publish lessons incrementally (week by week)

### Design Decisions

1. **Parallel models**: Quarterly/Lesson/LessonDay are separate from Theme/DevotionalEntry. No shoehorning.
2. **Hybrid collaboration granularity**: Each day is independently completable with observations. A lesson is "complete" when all 7 days are done.
3. **Admin-triggered scrape**: Admins import quarters manually. Defaults to current quarter if no code specified.
4. **One AI image per lesson**: 13 images per quarter, generated as background jobs after scrape.
5. **Hybrid scripture integration**: Body text stored as-is; scripture references also extracted separately for version switching.
6. **Consistent collaboration model**: Same patterns as existing — freeform observations, per-item completion, partner notifications, polymorphic bookmarks.
7. **Multiple quarter support**: Current quarter featured, past quarters preserved with all user data.
8. **Rule-based HTML parser**: Graceful degradation — flags unparseable sections for admin review.
9. **Upsert on re-sync**: Match by lesson_number + day_position, update content, preserve user data.
10. **Skip Inside Story**: Only study content is imported.
11. **Subtle attribution**: Footer fine-print crediting ssnet.org.

## 4. Data Model

### Quarterly
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| title | string | e.g., "Growing in a Relationship With God" |
| quarter_code | string (unique) | e.g., "26b" — used for ssnet.org URL construction |
| year | integer | e.g., 2026 |
| quarter_number | integer (1-4) | Derived from quarter_code letter |
| is_active | boolean (default false) | Featured quarter on landing page |
| description | text (nullable) | Optional summary |
| source_url | string | Base ssnet.org URL for this quarter |
| last_synced_at | timestamp (nullable) | Last successful scrape timestamp |
| created_at | timestamp | |
| updated_at | timestamp | |

### Lesson
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| quarterly_id | foreignId | FK to quarterlies, cascade delete |
| lesson_number | integer (1-13) | |
| title | string | e.g., "Pride Versus Humility" |
| date_start | date | Week start date |
| date_end | date | Week end date |
| memory_text | text | Memory verse text |
| memory_text_reference | string | e.g., "Luke 14:11, NKJV" |
| image_path | string (nullable) | Path to AI-generated image |
| image_prompt | text (nullable) | Prompt used for generation |
| has_parse_warnings | boolean (default false) | True if any day section couldn't be cleanly parsed |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique constraint: (quarterly_id, lesson_number)

### LessonDay
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| lesson_id | foreignId | FK to lessons, cascade delete |
| day_position | integer (0-6) | 0=Sabbath, 1=Sunday, ..., 6=Friday |
| day_name | string | "Sabbath", "Sunday", ..., "Friday" |
| title | string | Section title, e.g., "The Tight Fingers of Pride" |
| body | longText | Commentary/study content (HTML) |
| discussion_questions | json (nullable) | Array of strings, mainly for Friday |
| has_parse_warning | boolean (default false) | True if this section couldn't be cleanly parsed |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique constraint: (lesson_id, day_position)

### LessonDayCompletion
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| user_id | foreignId | FK to users, cascade delete |
| lesson_day_id | foreignId | FK to lesson_days, cascade delete |
| completed_at | timestamp | |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique constraint: (user_id, lesson_day_id)

### LessonDayScriptureReference
| Field | Type | Notes |
|-------|------|-------|
| id | bigint (PK) | |
| lesson_day_id | foreignId | FK to lesson_days, cascade delete |
| book | string | |
| chapter | integer | |
| verse_start | integer | |
| verse_end | integer (nullable) | |
| raw_reference | string | Original reference text, e.g., "1 John 2:15-17" |
| created_at | timestamp | |
| updated_at | timestamp | |

### Observations on LessonDay
Extend the existing Observation model or create a LessonDayObservation table following the same pattern:
- user_id (FK), lesson_day_id (FK), body (text), edited_at (nullable timestamp), timestamps

### Bookmarks
Reuse existing polymorphic Bookmark model — add Lesson and LessonDay as bookmarkable types.

## 5. Content Ingestion

### Admin Import Flow
1. Admin navigates to `/admin/sabbath-school`
2. Enters optional quarter_code (e.g., "26b") or leaves blank for current quarter
3. System determines quarter code if not provided (based on current date)
4. System fetches index page and all 13 lesson pages from ssnet.org
5. HTML parser extracts structured content per lesson and day
6. Content stored via upsert (create or update matching records)
7. AI image generation jobs queued for lessons without images
8. Admin sees import progress and results

### Quarter Code Resolution
If no quarter code provided, calculate from current date:
- Q1 (Jan-Mar) = `{yy}a`, Q2 (Apr-Jun) = `{yy}b`, Q3 (Jul-Sep) = `{yy}c`, Q4 (Oct-Dec) = `{yy}d`

### HTML Parser Responsibilities
1. Fetch lesson HTML from ssnet.org
2. Split content by day anchors (#sab, #sun, #mon, #tue, #wed, #thu, #fri)
3. Per section, extract:
   - Section title (first heading after anchor)
   - Body content (all content between anchors, cleaned)
   - Scripture references (parse "Read [reference]" patterns)
4. From Sabbath section, extract Memory Text and its reference
5. From Friday section, extract numbered Discussion Questions into JSON array
6. Style EGW quotes/citations as blockquotes
7. Strip Inside Story content
8. Flag sections that can't be cleanly parsed (set has_parse_warning)
9. Extract lesson metadata: title, date range from page header

### Re-Sync Behavior
- Match Lesson by (quarterly_id, lesson_number)
- Match LessonDay by (lesson_id, day_position)
- Update content fields (title, body, discussion_questions, scripture refs)
- DO NOT touch: completions, observations, bookmarks
- Create new lessons/days that didn't exist before
- Queue AI image generation only for lessons without existing images
- Update Quarterly.last_synced_at

## 6. AI Image Generation

- Triggered after scrape completes, dispatched as queued background jobs
- One job per lesson (13 jobs per quarter)
- Prompt constructed from: lesson title + memory text + general theme context
- Uses existing Laravel AI SDK pattern (Image::of()->square()->quality('medium'))
- Stored at `storage/app/public/images/sabbath-school/{quarterly_code}/lesson-{number}.{ext}`
- image_path and image_prompt saved on Lesson model
- Only generated for lessons that don't already have an image (re-sync safe)

## 7. Scripture Integration

### Hybrid Approach
- **Body text**: Stored as-is from scrape. Scripture text embedded in commentary reads naturally.
- **Extracted references**: Parsed during scrape, stored as LessonDayScriptureReference records.
- **Version switching**: Frontend displays extracted references using existing FetchScripturePassage action and ScriptureCache, allowing users to view in different Bible versions.
- **Cache reuse**: Same scripture_caches table — a passage fetched for a devotional entry benefits quarterly users and vice versa.

## 8. Collaboration Features

All follow existing patterns from the devotional entry system:

### Observations
- Freeform text attached to a LessonDay
- Visible to user and their linked partner
- Create, edit (with edited_at timestamp), delete with confirmation
- Chronological order within each LessonDay
- Partner notification on new observation (respects notification_preferences)

### Completion Tracking
- Per LessonDay: user marks a day as complete, recorded with timestamp
- Per Lesson: aggregated — lesson is "complete" when all 7 days are marked
- "Completed together" indicator when both partners have completed a day
- Progress percentage displayed on quarter view (per lesson) and lesson view (per day)
- Partner notification on completion (respects notification_preferences)

### Bookmarks
- Polymorphic — users can bookmark a Lesson (to revisit it) or a LessonDay (specific day's content)
- Appears in existing Bookmarks page grouped with other bookmark types

## 9. Navigation & Pages

### Navigation
- New top-level item: **"Sabbath School"**
- Mobile: added to bottom navigation bar
- Desktop: added to sidebar navigation and top tab bar
- Admin: "Manage Sabbath School" added to admin nav section

### User-Facing Routes & Pages

| Route | Page | Description |
|-------|------|-------------|
| `GET /sabbath-school` | Sabbath School Index | Active quarter featured, past quarters below |
| `GET /sabbath-school/{quarterly}` | Quarter View | 13 lesson cards with images, progress |
| `GET /sabbath-school/{quarterly}/lessons/{lesson}` | Lesson View | Memory text, 7 day cards, completion |
| `GET /sabbath-school/{quarterly}/lessons/{lesson}/days/{lessonDay}` | Day View | Content, scripture, observations, completion |
| `POST /sabbath-school/lessons/days/{lessonDay}/complete` | — | Mark day complete |
| `DELETE /sabbath-school/lessons/days/{lessonDay}/complete` | — | Unmark day complete |
| `POST /sabbath-school/lessons/days/{lessonDay}/observations` | — | Create observation |
| `PUT /sabbath-school/observations/{observation}` | — | Update observation |
| `DELETE /sabbath-school/observations/{observation}` | — | Delete observation |
| `POST /sabbath-school/bookmarks` | — | Create bookmark |

### Admin Routes & Pages

| Route | Page | Description |
|-------|------|-------------|
| `GET /admin/sabbath-school` | Admin Quarterly Index | List quarters, import form |
| `POST /admin/sabbath-school/import` | — | Trigger quarter import |
| `POST /admin/sabbath-school/{quarterly}/sync` | — | Re-sync existing quarter |
| `PUT /admin/sabbath-school/{quarterly}/activate` | — | Set quarter as active |
| `GET /admin/sabbath-school/{quarterly}` | Admin Quarter Detail | Lesson list with status |

### Empty States
- **User (no quarters)**: "No Sabbath School lessons available yet." friendly message
- **Admin (no quarters)**: Same message + "Import Current Quarter" button
- **User (quarter with no lessons yet)**: "Lessons for this quarter are being prepared."

## 10. Attribution

- Subtle footer text on all quarterly content pages
- Text: "Content sourced from ssnet.org"
- Linked to ssnet.org
- Fine-print styling — not prominent, not a banner

## 11. Out of Scope (Deferred)

- PWA/offline caching (will follow devotional entry pattern when implemented)
- Structured discussion question responses (freeform observations only for now)
- AI-assisted HTML parsing (rule-based parser first, upgrade if needed)
- Inside Story content
- Per-day AI images (lesson-level only)
- Automated/scheduled quarter imports (admin-triggered only)
