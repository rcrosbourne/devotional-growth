# Bible Study — Theme-Driven Study with Partner Sync

**Status:** Design
**Date:** 2026-04-19
**Author:** rcrosbourne (with Claude)

## 1. Summary

A solo-first, theme-driven scripture exploration tool inside the existing `/bible-study` section, with an optional partner-sync overlay.

Users browse a library of approved themes (Wisdom, Resilience, Poverty, etc.) or search. Picking a theme reveals an intro plus a library of associated passages with an optional curated "guided path" subset. A reader view presents the scripture text with admin-curated Hebrew/Greek word highlights, interpretation/application/cross-reference/literary-context AI insights in a persistent side panel (desktop) or stacked sections (mobile), and a structured historical-context card. Users reflect at passage level and verse level; reflections are private by default with a per-reflection "share with partner" toggle.

Users can also pick any Book/Chapter/Verse ad-hoc. If that passage lives inside an approved theme, the full enriched view unlocks; otherwise only scripture + existing word highlights are shown.

All study content is produced through an AI-draft → admin-review → publish pipeline. Unknown searches suggest fuzzy-matched approved themes and silently enqueue a new AI draft for admin review.

## 2. Goals & Non-Goals

### Goals

- Ship a theme-driven study experience that preserves the admin-reviewed quality bar already set by the devotional and Sabbath School content.
- Reuse the existing partner linkage, notification system, observation pattern, scripture fetching, scripture caching, and word-study data without modifying them.
- Allow ad-hoc passage study that gracefully degrades when the passage is outside curated content.
- Provide admins a workflow that scales: AI drafts the bulk, admin reviews and refines.

### Non-Goals

- No live co-reading (real-time cursor/highlight/translation sync). Partner sync is presence-on-entry only.
- No per-verse AI insights — insights are generated and stored per-passage.
- No user-submitted themes, community boards, or public sharing beyond the one-to-one partner link.
- No new push-notification channel — in-app database notifications only, matching the existing convention.
- No map or image assets in historical context — structured text only.
- No full-text search across scripture text. Search covers theme titles/descriptions and passage references.

## 3. User-Facing Experience

### 3.1 Entry points

The existing `/bible-study` page gains a new **Themes** tab, which becomes the default landing. Existing **Reading Plans** and **Word Studies** tabs remain. Search and an ad-hoc Book/Chapter/Verse input sit in a unified search row at the top of the Themes tab.

### 3.2 Browsing & search

- Themes grid shows title, short description, passage count, and a "Guided path" badge when applicable.
- Exact-match search jumps directly to a theme.
- Miss-match search returns fuzzy-matched approved themes above the full grid, an info banner ("'forgiveness' isn't available yet — we've added it to the queue"), and silently records a `bible_study_theme_requests` row. If no draft exists yet for that query, the request also enqueues a drafting job.
- A "Recent passages" strip shows the user's last ~5 visited passage references across both theme and ad-hoc studies.

### 3.3 Theme detail

- Title, long intro, reflection/share metadata.
- Two sub-sections:
    - **Guided Path** — ordered subset (if the admin marked any passages as guided-path). Shown as a numbered list with per-passage short intro.
    - **All Passages** — full library, unordered, with per-passage short intro and a user's reflection count.

### 3.4 Reader view (two-pane desktop, stacked mobile)

Header: passage reference, theme badge (or "Ad-hoc" label), translation switcher, "Share with partner" icon (disabled if no partner linked).

**Scripture column (left / top):**

- Scripture rendered in the chosen translation via the existing `FetchScripturePassage`/`ScriptureCache` pipeline.
- Admin-curated word highlights rendered as subtly-styled spans; tapping opens a detail sheet (Strong's number, transliteration, definition, language) from the existing `WordStudy` row.
- Verses are tappable targets for verse-level annotations.
- Inline annotation chips appear beneath their verse when present (author's own + partner's shared annotations).
- A sticky "Add reflection" button at the bottom of the column opens a passage-level composer.

**Insights column (right / stacked-collapsible on mobile):**

- Sections: Interpretation, Application, Cross-references (each with a tap-to-jump link), Literary Context.
- Separate card: Historical Context — Setting, Author, Date Range, Audience, Historical Events.
- If the passage is ad-hoc and not in an approved theme, this column is hidden on desktop and the collapsible sections are absent on mobile.

### 3.5 Reflections

- **Passage-level:** one primary journal entry per user per passage. Composer is a multi-line textarea with a "Share with partner" toggle (default off).
- **Verse-level:** tap a verse → short annotation composer with the same share toggle.
- Both stored in `bible_study_reflections`, distinguished by whether `verse_number` is null.
- Partner's shared reflections render inline on the passage with a subtle "Partner" chip (mirrors how devotional `Observation` entries already render).

### 3.6 Partner sync

- When a user opens a passage, a `bible_study_sessions` row is upserted (one active session per user).
- The "Share with partner" button in the reader header fires `App\Notifications\PartnerStartedBibleStudy` via the database channel, gated by a new `bible_study_partner_share` notification preference.
- Notification payload includes theme (nullable for ad-hoc), book, chapter, and verse range.
- Partner tapping the notification routes directly into the reader view on the same passage. No further live sync.

## 4. Architecture

### 4.1 Reused building blocks

| Concern | Reused from |
| --- | --- |
| Scripture fetching / translations | `App\Actions\FetchScripturePassage`, `ScriptureCache` |
| Word definitions (Hebrew/Greek, Strong's) | `WordStudy`, `WordStudyPassage` |
| AI structured-output pattern | `App\Ai\Agents\DevotionalContentGenerator` and `AiGenerationLog` |
| Partner linking | `users.partner_id`, `PartnerController`, `LinkPartner` |
| Partner notifications | `App\Actions\SendPartnerNotification`, database notifications, `NotificationPreference` |
| Shared observations rendering | pattern from `DevotionalEntryController`'s partner-aware view data |
| Admin UI shell | `app/Http/Controllers/Admin/` + its existing pages |

### 4.2 New database tables

- `bible_study_themes` — `id`, `slug` (unique), `title`, `short_description`, `long_intro` (text), `status` (`draft`|`approved`|`archived`), `requested_count` (int, default 0), `approved_at` (nullable), `approved_by_user_id` (nullable FK), timestamps. Index on `status`, `slug`.
- `bible_study_theme_passages` — `id`, `theme_id` (FK cascade), `position` (int), `is_guided_path` (bool), `book` (string), `chapter` (int), `verse_start` (int), `verse_end` (int, nullable), `passage_intro` (text, nullable), timestamps. Unique on `(theme_id, book, chapter, verse_start, verse_end)`. Index on `(book, chapter, verse_start, verse_end)` for reverse lookups.
- `bible_study_word_highlights` — `id`, `theme_passage_id` (FK cascade), `word_study_id` (FK to `word_studies`), `verse_number` (int), `word_index_in_verse` (int), `display_word` (string), timestamps. Unique on `(theme_passage_id, verse_number, word_index_in_verse)`.
- `bible_study_insights` — `id`, `theme_passage_id` (FK cascade, unique), `interpretation` (text), `application` (text), `cross_references` (JSON array of `{book, chapter, verse_start, verse_end, note}`), `literary_context` (text), timestamps.
- `bible_study_historical_contexts` — `id`, `theme_passage_id` (FK cascade, unique), `setting` (text), `author` (string), `date_range` (string), `audience` (text), `historical_events` (text), timestamps.
- `bible_study_reflections` — `id`, `user_id` (FK), `theme_id` (nullable FK — set when reflected in a theme context, null for ad-hoc), `book`, `chapter`, `verse_start`, `verse_end` (nullable), `verse_number` (nullable — null = passage-level journal, non-null = verse annotation), `body` (text), `is_shared_with_partner` (bool, default false), timestamps. Index on `(user_id, book, chapter)`. Partial index (or composite) on `(book, chapter, verse_start)` for partner-shared lookups.
- `bible_study_theme_requests` — `id`, `user_id` (FK), `search_query` (string), `normalized_query` (string, for dedup), `generated_theme_id` (nullable FK), timestamps. Index on `normalized_query` and `generated_theme_id`.
- `bible_study_sessions` — `id`, `user_id` (FK unique), `theme_id` (nullable FK), `current_book`, `current_chapter`, `current_verse_start`, `current_verse_end` (nullable), `started_at`, `last_accessed_at`, timestamps.

No changes to existing tables.

### 4.3 New actions (under `app/Actions/BibleStudy/`)

- `StartOrResumeStudySession` — upserts `bible_study_sessions` when user opens a passage.
- `ShareBibleStudyWithPartner` — fires `PartnerStartedBibleStudy` notification, gated by preference.
- `SaveBibleStudyReflection` — creates/updates a passage or verse reflection.
- `ResolvePassageEnrichment` — given book/chapter/verse range, returns the matching `bible_study_theme_passages` row (with insights, historical context, highlights) if one exists inside an approved theme, else null. Powers the ad-hoc → enriched promotion. **Match semantics (v1):** exact match only on `(book, chapter, verse_start, verse_end)` where a NULL ad-hoc `verse_end` matches a NULL stored `verse_end`. Partial/overlap matching is out of scope for v1.
- `SearchThemes` — exact + fuzzy matching. Fuzzy uses simple trigram / Levenshtein on title + description; implementation detail of plan step.
- `RequestTheme` — idempotent on `(user_id, normalized_query)`; on first insert for a query, enqueues `DraftBibleStudyTheme` if no draft exists.
- `DraftBibleStudyTheme` — queued job wrapping the new AI agent.
- `PublishBibleStudyTheme` — admin action; flips `status` to `approved`, stamps `approved_at`/`approved_by_user_id`, and emits best-effort in-app notifications to users who requested the theme (best-effort — no retry storm).

### 4.4 New AI agent

`App\Ai\Agents\BibleStudyThemeDrafter` — follows the `DevotionalContentGenerator` structured-output pattern.

**Input:** theme title (e.g., "forgiveness").

**Structured output:**

```json
{
  "slug": "forgiveness",
  "short_description": "…",
  "long_intro": "…",
  "passages": [
    {
      "book": "Matthew",
      "chapter": 18,
      "verse_start": 21,
      "verse_end": 35,
      "position": 1,
      "is_guided_path": true,
      "passage_intro": "…",
      "insights": {
        "interpretation": "…",
        "application": "…",
        "cross_references": [
          {"book": "Ephesians", "chapter": 4, "verse_start": 32, "note": "…"}
        ],
        "literary_context": "…"
      },
      "historical_context": {
        "setting": "…",
        "author": "Matthew",
        "date_range": "ca. 70–90 AD",
        "audience": "…",
        "historical_events": "…"
      },
      "suggested_word_highlights": [
        {"verse_number": 22, "display_word": "seventy-seven", "original_root_hint": "ἑβδομηκοντάκις ἑπτά", "rationale": "…"}
      ]
    }
  ]
}
```

All output persists as a `draft` theme. Word highlight suggestions are *candidates*; admin matches them to `WordStudy` rows (or creates new ones) during review.

Logged to `AiGenerationLog` with agent name, input, output, and error state.

### 4.5 Controllers and routes

New routes under the existing `web.php` `bible-study` prefix:

- `GET /bible-study` — existing page; extended with Themes tab. Themes tab is the default.
- `GET /bible-study/themes/{theme:slug}` — theme detail.
- `GET /bible-study/passage` — reader view. Accepts `?theme=&book=&chapter=&verse_start=&verse_end=&translation=`. Ad-hoc requests omit `theme`.
- `POST /bible-study/reflections` — create/update.
- `POST /bible-study/session/share` — fire partner notification.
- `POST /bible-study/theme-requests` — for miss-match search.
- `GET /bible-study/search` — JSON endpoint for live theme search (exact + fuzzy).

Admin routes under `admin/bible-study/`:

- `GET admin/bible-study/themes` — review queue ordered by `requested_count DESC, created_at ASC`.
- `GET admin/bible-study/themes/{theme}` — full review screen with sub-resources.
- `POST admin/bible-study/themes/{theme}/publish`.
- Admin CRUD for passages, insights, historical contexts, word highlights under the same prefix.

### 4.6 Frontend

**Design-system constraint:** all new UI must stay faithful to the existing design system (shadcn/ui components under `resources/js/components/ui/`, existing Tailwind v4 tokens, dark-mode parity with existing pages). Before any frontend implementation, invoke the `frontend-design` skill and audit existing `bible-study/*`, `devotional/*`, and settings pages for patterns to reuse (card shells, tab strips, collapsible sections, composer styling). Do not invent new component primitives when existing ones fit.

- New React pages under `resources/js/pages/bible-study/`:
    - `themes/index.tsx` — landing (new Themes tab).
    - `themes/show.tsx` — theme detail.
    - `passage.tsx` — reader view (reused for theme + ad-hoc entry).
- New components under `resources/js/components/bible-study/`:
    - `scripture-reader.tsx` — renders scripture with highlight spans; handles tap-for-definition.
    - `word-study-sheet.tsx` — bottom-sheet / side-panel for tapped-word detail.
    - `insights-panel.tsx` — persistent side panel on desktop, collapsible sections on mobile.
    - `historical-context-card.tsx`.
    - `reflection-composer.tsx` + `reflection-list.tsx` — handles both passage and verse scopes, and partner-shared rendering.
    - `share-with-partner-button.tsx`.
- Wayfinder routes generated for all new endpoints.

### 4.7 Notification changes

- Add `App\Notifications\PartnerStartedBibleStudy` (database channel).
- Add a new preference key `bible_study_partner_share` to the existing `NotificationPreference` mechanism, following the same shape the existing `completion` / `observation` / `new_theme` keys use (the implementation plan will inspect the current model to determine whether to add a column, a row-type, or a JSON flag — whichever matches how the existing keys are stored). Default: enabled.

## 5. Data Flow — Key Paths

**Reader view load (theme-based):** browser request → `PassageController@show` → reads `bible_study_theme_passages` + `insights` + `historical_context` + `word_highlights` (with `word_study`) + user's + partner's reflections + scripture text (via `FetchScripturePassage`) → rendered via Inertia.

**Reader view load (ad-hoc):** same, but `PassageController@show` calls `ResolvePassageEnrichment`. If hit, returns full enriched view; if miss, returns scripture + any `WordStudyPassage` rows that cover the verse range (auto-highlights), no insights, no historical context.

**Miss-match search:** `SearchThemes` returns fuzzy matches → controller renders landing with banner and fuzzy suggestions → client-side posts to `POST /bible-study/theme-requests` → `RequestTheme` inserts (idempotent on `normalized_query`) → on first insert for that query, dispatches `DraftBibleStudyTheme` job.

**Draft generation:** job runs `BibleStudyThemeDrafter`, persists `draft` theme with all children, sets `bible_study_theme_requests.generated_theme_id`, logs to `AiGenerationLog`.

**Admin publish:** `PublishBibleStudyTheme` flips status, stamps approval fields, iterates `bible_study_theme_requests.where(generated_theme_id=…)` and notifies requesters best-effort.

## 6. Testing Strategy

All tests use Pest. Follow the `composer test:local` rule after changes.

**Feature tests (`tests/Feature/BibleStudy/`):**

- `ThemesLandingTest` — Themes tab renders, exact search hits, fuzzy search returns suggestions and records a `theme_request`, empty-state banner rendering.
- `PassageViewTest` — theme-based passage renders full enrichment; ad-hoc inside an approved theme unlocks enrichment; ad-hoc outside an approved theme shows only scripture + auto-highlights.
- `ReflectionTest` — passage-level create/update, verse-level create/update, private vs. shared visibility (partner sees shared, does not see private), idempotency on re-save.
- `PartnerShareTest` — sharing fires notification only when partner is linked and preference is enabled; tapping notification routes to correct passage.
- `AdminReviewTest` — admin can edit draft theme, reorder passages, toggle `is_guided_path`, confirm highlights (creating `bible_study_word_highlights` from `word_studies`), publish flips status and notifies requesters.
- `RequestThemeTest` — miss-match search enqueues draft on first request, dedups on subsequent requests for same normalized query.

**Unit tests (`tests/Unit/BibleStudy/`):**

- `ResolvePassageEnrichmentTest` — correct matching semantics across overlapping verse ranges.
- `SearchThemesTest` — fuzzy ranking, stopword handling, case-insensitivity.
- `BibleStudyThemeDrafterTest` — mock AI response, assert persisted shape.

**Browser tests (`tests/Browser/BibleStudy/`, Pest 4):**

- `ReaderViewTest` — tap highlighted word → detail sheet; switch translation → scripture re-renders; expand insights section (mobile) / insights panel always visible (desktop); add passage reflection with partner share.
- `PartnerJumpTest` — user A shares, user B's notification surfaces, tapping jumps to same passage.

## 7. Open Risks and Mitigations

- **AI theological accuracy** — mitigated by the admin-approval gate (all published content is reviewed). Drafts never reach end users.
- **Word-highlight match quality** — the drafter suggests highlights but admin must match them to `WordStudy` rows. Avoids the model hallucinating Hebrew roots. Acceptable: some drafts will need the admin to also create new `WordStudy` entries; reuse the existing word-study admin tooling.
- **Scripture text licensing** — already handled by existing API.Bible + bible-api.com routing; this feature adds no new translations.
- **Request queue flooding from search misses** — `bible_study_theme_requests` is idempotent on `(user_id, normalized_query)`; drafts are only enqueued on the first-ever request for a given `normalized_query`.
- **Fuzzy search false negatives** — acceptable for v1; can iterate on the ranking strategy post-launch.
- **Reflections leaking to partner after unlink** — partner-visibility query must check current partner linkage at read time, not store a partner_id on the reflection.

## 8. Suggested Phasing

This spec is large enough that a single implementation plan would be unwieldy. Suggested phased plans, each its own plan → implementation cycle:

- **Phase 1 — Content pipeline.** Migrations for all tables, models + factories, `BibleStudyThemeDrafter` agent, `DraftBibleStudyTheme` queued job, admin review UI (draft queue, edit, publish). No user-facing UI yet. Seed one approved theme by hand to prove end-to-end.
- **Phase 2 — User reading experience.** Themes landing + theme detail + reader view (two-pane desktop, stacked mobile). Translation switcher. Word-highlight tap-to-define. Reflections (passage + verse) with share-with-partner toggle. Ad-hoc Book/Chapter/Verse study including `ResolvePassageEnrichment` promotion. Search in this phase is exact-match only against approved themes; miss-match shows a plain "no results" state. Frontend work in this phase must use the `frontend-design` skill and match existing design-system primitives.
- **Phase 3 — Partner + search enrichments.** `PartnerStartedBibleStudy` notification and preference. Fuzzy search, miss-match empty state, `bible_study_theme_requests`, auto-enqueued drafts, "your theme is ready" notification on publish.

Each phase is independently shippable; earlier phases have value without later ones.

## 9. Success Criteria

- Admin can take a theme title and reach a published, reviewed theme with 5+ passages using the draft pipeline in under 30 minutes of review time.
- User can go from the Themes tab to reading a passage with insights in ≤ 2 taps.
- Ad-hoc passage study works for any canonical Book/Chapter/Verse reference with zero additional admin work when the passage is outside a theme.
- Partner-share notification reliably routes to the intended passage.
- All new paths covered by Pest feature tests; reader view covered by at least one Pest 4 browser test.
