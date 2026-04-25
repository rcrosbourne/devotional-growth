# Bible Study — Phase 2: User Reading Experience Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the user-facing reading experience on top of the Phase 1 content pipeline — Themes landing tab, theme detail page, two-pane reader view (with translation switcher, word-highlight tap-to-define, AI insights, historical context), passage and verse-level reflections with optional partner sharing, ad-hoc Book/Chapter/Verse study with enrichment promotion, and exact-match search.

**Architecture:** Extend the existing `/bible-study` page with a Themes tab that becomes the default landing. New user-facing controllers under `App\Http\Controllers\BibleStudy\*` for themes, passages, reflections, and search. Reader view fetches scripture verse-by-verse via a new `FetchStructuredPassage` action, then renders highlights and reflections in two panes (desktop) or stacked sections (mobile). Reflections reuse the `bible_study_reflections` table from Phase 1; partner-shared reflections are loaded the same way the existing devotional `Observation` model surfaces partner data. Ad-hoc passages call `ResolvePassageEnrichment` to detect when an ad-hoc reference matches an approved theme passage and unlock the full enriched view automatically. Search is exact-match on theme title/slug only — fuzzy matching, theme requests, and the partner-notification flow are deferred to Phase 3.

**Tech Stack:** Laravel 12 + PHP 8.5, Pest 4, Inertia + React 19, Tailwind v4, shadcn/ui primitives. Enforces 100% line + type coverage via `composer test:local`.

---

## Source Spec

`docs/superpowers/specs/2026-04-19-bible-study-themes-design.md` — read sections §3 (UX), §4.3–§4.6 (architecture), §8 (phasing). Phase 1 is merged: all migrations, models, factories, AI agent, queued draft pipeline, admin review UI, and the Resilience seeder are in place.

## Conventions Reminders (read once)

- Strict types + `final` (or `final readonly`) on every new PHP class. `declare(strict_types=1);` at the top.
- Models follow Phase 1's house style (`@property-read` for every column, every column cast, no `$guarded`).
- Controllers are `final readonly`, return `Inertia::render(...)` for pages or `RedirectResponse`/`JsonResponse`/`Response` for actions.
- Routes inside the existing `Route::middleware(['auth', 'verified'])->group(...)` block in `routes/web.php` (alongside the existing `bible-study/reading-plan/*` and `bible-study/word-study/*` routes). Use first-class callable syntax like the Phase 1 admin routes.
- Tests are Pest 4 with factories. Cover happy + failure paths, hit `composer test:local` before each commit.
- Run `vendor/bin/pint --dirty --format agent` before committing.
- Frontend work uses shadcn/ui components from `@/components/ui/`, the `DevotionalLayout` wrapper, and existing color tokens (`text-on-surface`, `text-on-surface-variant`, `bg-moss`, `text-moss-foreground`, `bg-surface-container-low`, `border-border`, `bg-primary`, `text-primary-foreground`). **If a `frontend-design` skill is available, invoke it before writing each new page.**
- Prefer Wayfinder route imports (`@/routes/...`) over the global `route()` helper — that's the project convention used everywhere else.

---

## File Structure (Phase 2)

New PHP files:

```
app/Actions/BibleStudy/
    FetchStructuredPassage.php
    ResolvePassageEnrichment.php
    SaveBibleStudyReflection.php
    SearchThemes.php
    StartOrResumeStudySession.php

app/Http/Controllers/BibleStudy/
    ThemeController.php
    PassageController.php
    ReflectionController.php
    SearchController.php

app/Http/Requests/BibleStudy/
    StoreReflectionRequest.php
    UpdateReflectionRequest.php
    PassageQueryRequest.php
    SearchQueryRequest.php
```

New TSX files:

```
resources/js/pages/bible-study/
    themes/
        index.tsx       (themes landing tab content + theme list)
        show.tsx        (theme detail with guided path + library)
    passage.tsx          (the reader view)

resources/js/components/bible-study/
    scripture-reader.tsx
    word-study-sheet.tsx
    insights-panel.tsx
    historical-context-card.tsx
    reflection-composer.tsx
    reflection-list.tsx
    share-with-partner-toggle.tsx
    recent-passages.tsx
    themes-tab.tsx
    passage-search-bar.tsx
```

Files modified:

```
app/Http/Controllers/ReadingPlanController.php   (extend index() to include themes prop)
resources/js/pages/bible-study/index.tsx         (add Themes tab as default)
routes/web.php                                   (add user-facing bible-study routes)
```

---

## Task List

- [ ] Task 1 — `FetchStructuredPassage` action (verse-keyed scripture fetch)
- [ ] Task 2 — `ResolvePassageEnrichment` action (exact-match passage→approved-theme lookup)
- [ ] Task 3 — `SearchThemes` action (exact-match)
- [ ] Task 4 — `StartOrResumeStudySession` action
- [ ] Task 5 — `SaveBibleStudyReflection` action
- [ ] Task 6 — User-facing `BibleStudy\ThemeController` (`index` + `show`)
- [ ] Task 7 — User-facing `BibleStudy\PassageController` (reader view payload)
- [ ] Task 8 — `BibleStudy\ReflectionController` (`store` / `update` / `destroy`)
- [ ] Task 9 — `BibleStudy\SearchController` (JSON search endpoint)
- [ ] Task 10 — Wire user routes + extend `ReadingPlanController@index` to add `themes` prop
- [ ] Task 11 — `ThemesTab` + landing tab integration in `bible-study/index.tsx`
- [ ] Task 12 — `bible-study/themes/show.tsx` (theme detail page)
- [ ] Task 13 — `bible-study/passage.tsx` skeleton (header, layout, translation switcher)
- [ ] Task 14 — `ScriptureReader` component (verse rendering + highlight spans)
- [ ] Task 15 — `WordStudySheet` (tap-to-define bottom sheet)
- [ ] Task 16 — `InsightsPanel` + `HistoricalContextCard`
- [ ] Task 17 — `ReflectionComposer` + `ReflectionList`
- [ ] Task 18 — `ShareWithPartnerToggle` + partner-shared reflection load
- [ ] Task 19 — `PassageSearchBar` + `RecentPassages` strip
- [ ] Task 20 — Pest 4 browser test for the reader flow

---

## Task 1 — `FetchStructuredPassage` action

Returns scripture text keyed by verse number so the reader can highlight per-verse word indices and attach verse-level reflections. Uses bible-api.com's structured response for unauthenticated versions; falls back to the existing `FetchScripturePassage` for API.Bible-only versions and returns a single-key map.

**Files:**
- Create: `app/Actions/BibleStudy/FetchStructuredPassage.php`
- Test: `tests/Unit/BibleStudy/FetchStructuredPassageTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\BibleStudy\FetchStructuredPassage;
use Illuminate\Support\Facades\Http;

it('returns a verse-keyed array for KJV using bible-api.com', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'reference' => 'Job 1:13-15',
            'verses' => [
                ['book_id' => 'JOB', 'book_name' => 'Job', 'chapter' => 1, 'verse' => 13, 'text' => "And there was a day...\n"],
                ['book_id' => 'JOB', 'book_name' => 'Job', 'chapter' => 1, 'verse' => 14, 'text' => "And there came a messenger...\n"],
                ['book_id' => 'JOB', 'book_name' => 'Job', 'chapter' => 1, 'verse' => 15, 'text' => "And the Sabeans fell upon them...\n"],
            ],
            'translation_id' => 'kjv',
            'translation_name' => 'King James Version',
        ]),
    ]);

    $result = resolve(FetchStructuredPassage::class)->handle('Job', 1, 13, 15, 'KJV');

    expect($result['structured'])->toBeTrue()
        ->and($result['verses'])->toHaveKey(13)
        ->and($result['verses'][13])->toContain('And there was a day')
        ->and($result['verses'][14])->toContain('messenger')
        ->and($result['verses'][15])->toContain('Sabeans');
});

it('falls back to a single-key map for API.Bible-only versions', function (): void {
    $fallback = Mockery::mock(App\Actions\FetchScripturePassage::class);
    $fallback->shouldReceive('handle')
        ->once()
        ->with('Genesis', 1, 1, 3, 'NIV')
        ->andReturn('In the beginning God created the heavens and the earth...');
    app()->instance(App\Actions\FetchScripturePassage::class, $fallback);

    $result = resolve(FetchStructuredPassage::class)->handle('Genesis', 1, 1, 3, 'NIV');

    expect($result['structured'])->toBeFalse()
        ->and($result['verses'])->toHaveKey(1)
        ->and($result['verses'][1])->toContain('In the beginning');
});

it('returns a single-key error placeholder when bible-api.com fails', function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response('', 500),
    ]);

    $result = resolve(FetchStructuredPassage::class)->handle('Job', 1, 13, 22, 'KJV');

    expect($result['structured'])->toBeFalse()
        ->and($result['verses'])->toHaveKey(13);
});
```

- [ ] **Step 2: Run test — FAIL**

Run: `php artisan test --compact --filter=FetchStructuredPassageTest`

- [ ] **Step 3: Implement the action**

```php
<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Actions\FetchScripturePassage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

final readonly class FetchStructuredPassage
{
    /**
     * @var array<int, string> Versions exposed via bible-api.com (returns structured JSON).
     */
    private const array BIBLE_API_VERSIONS = ['KJV', 'ASV', 'WEB', 'BBE', 'DARBY'];

    public function __construct(private FetchScripturePassage $fallback) {}

    /**
     * @return array{verses: array<int, string>, structured: bool}
     */
    public function handle(string $book, int $chapter, int $verseStart, ?int $verseEnd, string $bibleVersion = 'KJV'): array
    {
        if (in_array($bibleVersion, self::BIBLE_API_VERSIONS, true)) {
            $structured = $this->fetchStructured($book, $chapter, $verseStart, $verseEnd, $bibleVersion);
            if ($structured !== null) {
                return ['verses' => $structured, 'structured' => true];
            }
        }

        $text = $this->fallback->handle($book, $chapter, $verseStart, $verseEnd, $bibleVersion);

        return [
            'verses' => [$verseStart => $text],
            'structured' => false,
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function fetchStructured(string $book, int $chapter, int $verseStart, ?int $verseEnd, string $bibleVersion): ?array
    {
        $reference = sprintf('%s %d:%d', $book, $chapter, $verseStart);
        if ($verseEnd !== null && $verseEnd !== $verseStart) {
            $reference .= '-'.$verseEnd;
        }

        $url = sprintf(
            'https://bible-api.com/%s?translation=%s',
            rawurlencode($reference),
            mb_strtolower($bibleVersion),
        );

        try {
            $response = Http::timeout(10)->get($url);
        } catch (ConnectionException) {
            return null;
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        /** @var array{verses?: array<int, array{verse: int, text: string}>} $body */
        $body = $response->json();
        $verses = $body['verses'] ?? [];

        if ($verses === []) {
            return null;
        }

        $structured = [];
        foreach ($verses as $verse) {
            $structured[(int) $verse['verse']] = mb_trim($verse['text']);
        }

        return $structured;
    }
}
```

- [ ] **Step 4: Run test — PASS**

Run: `php artisan test --compact --filter=FetchStructuredPassageTest`

- [ ] **Step 5: Lint, full verification, commit**

```bash
vendor/bin/pint --dirty --format agent
composer test:local
git add app/Actions/BibleStudy/FetchStructuredPassage.php tests/Unit/BibleStudy/FetchStructuredPassageTest.php
git commit -m "feat(bible-study): add FetchStructuredPassage action for verse-keyed reading view"
```

---

## Task 2 — `ResolvePassageEnrichment` action

Given a Book/Chapter/Verse range, returns the matching `bible_study_theme_passages` row (with insight + historical context + word highlights eager-loaded) when an exact match exists inside an approved theme. Per spec §4.3 v1 semantics: exact match on `(book, chapter, verse_start, verse_end)` with NULL-equals-NULL.

**Files:**
- Create: `app/Actions/BibleStudy/ResolvePassageEnrichment.php`
- Test: `tests/Unit/BibleStudy/ResolvePassageEnrichmentTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\BibleStudy\ResolvePassageEnrichment;
use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\WordStudy;

it('returns the matching passage when it lives in an approved theme', function (): void {
    $theme = BibleStudyTheme::factory()->approved()->create(['slug' => 'resilience']);
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22,
    ]);
    BibleStudyInsight::factory()->for($passage, 'passage')->create();
    BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create();
    $word = WordStudy::factory()->create();
    BibleStudyWordHighlight::factory()->for($passage, 'passage')->for($word, 'wordStudy')->create();

    $resolved = resolve(ResolvePassageEnrichment::class)->handle('Job', 1, 13, 22);

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($passage->id)
        ->and($resolved->insight)->not->toBeNull()
        ->and($resolved->historicalContext)->not->toBeNull()
        ->and($resolved->wordHighlights)->toHaveCount(1);
});

it('returns null when the only matching passage belongs to a draft theme', function (): void {
    $theme = BibleStudyTheme::factory()->draft()->create();
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22,
    ]);

    $resolved = resolve(ResolvePassageEnrichment::class)->handle('Job', 1, 13, 22);

    expect($resolved)->toBeNull();
});

it('treats NULL verse_end as a distinct match value', function (): void {
    $theme = BibleStudyTheme::factory()->approved()->create();
    $singleVerse = BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'John', 'chapter' => 3, 'verse_start' => 16, 'verse_end' => null,
    ]);

    expect(resolve(ResolvePassageEnrichment::class)->handle('John', 3, 16, null)?->id)->toBe($singleVerse->id)
        ->and(resolve(ResolvePassageEnrichment::class)->handle('John', 3, 16, 17))->toBeNull();
});

it('returns null when no theme passage matches', function (): void {
    BibleStudyTheme::factory()->approved()->create();

    $resolved = resolve(ResolvePassageEnrichment::class)->handle('Genesis', 1, 1, 5);

    expect($resolved)->toBeNull();
});
```

- [ ] **Step 2: Run test — FAIL**

Run: `php artisan test --compact --filter=ResolvePassageEnrichmentTest`

- [ ] **Step 3: Implement the action**

```php
<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyThemePassage;

final readonly class ResolvePassageEnrichment
{
    public function handle(string $book, int $chapter, int $verseStart, ?int $verseEnd): ?BibleStudyThemePassage
    {
        $query = BibleStudyThemePassage::query()
            ->whereHas('theme', fn ($q) => $q->where('status', BibleStudyThemeStatus::Approved))
            ->where('book', $book)
            ->where('chapter', $chapter)
            ->where('verse_start', $verseStart);

        if ($verseEnd === null) {
            $query->whereNull('verse_end');
        } else {
            $query->where('verse_end', $verseEnd);
        }

        return $query->with(['theme', 'insight', 'historicalContext', 'wordHighlights.wordStudy'])->first();
    }
}
```

- [ ] **Step 4: Run test — PASS**

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint --dirty --format agent
composer test:local
git add app/Actions/BibleStudy/ResolvePassageEnrichment.php tests/Unit/BibleStudy/ResolvePassageEnrichmentTest.php
git commit -m "feat(bible-study): add ResolvePassageEnrichment for ad-hoc passage promotion"
```

---

## Task 3 — `SearchThemes` action (exact match)

Phase 2 search: only approved themes, exact (case-insensitive) match on slug or title. Phase 3 will replace this with fuzzy ranking.

**Files:**
- Create: `app/Actions/BibleStudy/SearchThemes.php`
- Test: `tests/Unit/BibleStudy/SearchThemesTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\BibleStudy\SearchThemes;
use App\Models\BibleStudyTheme;

it('returns approved themes with case-insensitive title match', function (): void {
    $resilience = BibleStudyTheme::factory()->approved()->create([
        'slug' => 'resilience', 'title' => 'Resilience',
    ]);
    BibleStudyTheme::factory()->approved()->create(['slug' => 'wisdom', 'title' => 'Wisdom']);

    $results = resolve(SearchThemes::class)->handle('resilience');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($resilience->id);
});

it('matches by slug exactly', function (): void {
    $forgiveness = BibleStudyTheme::factory()->approved()->create([
        'slug' => 'forgiveness', 'title' => 'Forgiveness',
    ]);

    $results = resolve(SearchThemes::class)->handle('FORGIVENESS');

    expect($results->first()->id)->toBe($forgiveness->id);
});

it('excludes draft themes', function (): void {
    BibleStudyTheme::factory()->draft()->create(['slug' => 'patience', 'title' => 'Patience']);

    expect(resolve(SearchThemes::class)->handle('patience'))->toBeEmpty();
});

it('returns an empty collection on a non-match', function (): void {
    BibleStudyTheme::factory()->approved()->create(['slug' => 'wisdom']);

    expect(resolve(SearchThemes::class)->handle('xyzz'))->toBeEmpty();
});
```

- [ ] **Step 2: Run test — FAIL**

- [ ] **Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use Illuminate\Support\Collection;

final readonly class SearchThemes
{
    /**
     * @return Collection<int, BibleStudyTheme>
     */
    public function handle(string $query): Collection
    {
        $normalized = mb_strtolower(mb_trim($query));

        if ($normalized === '') {
            return new Collection;
        }

        return BibleStudyTheme::query()
            ->where('status', BibleStudyThemeStatus::Approved)
            ->where(function ($q) use ($normalized): void {
                $q->whereRaw('LOWER(slug) = ?', [$normalized])
                    ->orWhereRaw('LOWER(title) = ?', [$normalized]);
            })
            ->get();
    }
}
```

- [ ] **Step 4: PASS, lint, commit**

```bash
vendor/bin/pint --dirty --format agent
composer test:local
git add app/Actions/BibleStudy/SearchThemes.php tests/Unit/BibleStudy/SearchThemesTest.php
git commit -m "feat(bible-study): add SearchThemes (exact match) action"
```

---

## Task 4 — `StartOrResumeStudySession` action

Upserts the single `bible_study_sessions` row per user when they open a passage. Used by the partner-share button (Phase 3) and the "Recent passages" strip on the landing.

**Files:**
- Create: `app/Actions/BibleStudy/StartOrResumeStudySession.php`
- Test: `tests/Unit/BibleStudy/StartOrResumeStudySessionTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\BibleStudy\StartOrResumeStudySession;
use App\Models\BibleStudySession;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('creates a session for a user with no existing session', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    resolve(StartOrResumeStudySession::class)->handle($user, $theme, 'Job', 1, 13, 22);

    $session = BibleStudySession::query()->where('user_id', $user->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->bible_study_theme_id)->toBe($theme->id)
        ->and($session->current_book)->toBe('Job')
        ->and($session->current_chapter)->toBe(1)
        ->and($session->current_verse_start)->toBe(13)
        ->and($session->current_verse_end)->toBe(22);
});

it('updates the session in place when one already exists', function (): void {
    $user = User::factory()->create();
    BibleStudySession::factory()->for($user)->create([
        'current_book' => 'Genesis', 'current_chapter' => 1, 'current_verse_start' => 1, 'current_verse_end' => 5,
    ]);

    resolve(StartOrResumeStudySession::class)->handle($user, null, 'John', 3, 16, null);

    $count = BibleStudySession::query()->where('user_id', $user->id)->count();
    $session = BibleStudySession::query()->where('user_id', $user->id)->first();

    expect($count)->toBe(1)
        ->and($session->bible_study_theme_id)->toBeNull()
        ->and($session->current_book)->toBe('John')
        ->and($session->current_verse_end)->toBeNull();
});
```

- [ ] **Step 2: Run test — FAIL**

- [ ] **Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Models\BibleStudySession;
use App\Models\BibleStudyTheme;
use App\Models\User;

final readonly class StartOrResumeStudySession
{
    public function handle(User $user, ?BibleStudyTheme $theme, string $book, int $chapter, int $verseStart, ?int $verseEnd): BibleStudySession
    {
        $now = now();

        return BibleStudySession::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'bible_study_theme_id' => $theme?->id,
                'current_book' => $book,
                'current_chapter' => $chapter,
                'current_verse_start' => $verseStart,
                'current_verse_end' => $verseEnd,
                'started_at' => $now,
                'last_accessed_at' => $now,
            ],
        );
    }
}
```

- [ ] **Step 4: PASS, lint, commit**

```bash
vendor/bin/pint --dirty --format agent
composer test:local
git add app/Actions/BibleStudy/StartOrResumeStudySession.php tests/Unit/BibleStudy/StartOrResumeStudySessionTest.php
git commit -m "feat(bible-study): add StartOrResumeStudySession upsert action"
```

---

## Task 5 — `SaveBibleStudyReflection` action

Idempotent upsert for the user's reflection on a passage scope. Verse-level annotations create distinct rows (verse_number != null); passage-level reflection has at most one per user/passage (verse_number = null).

**Files:**
- Create: `app/Actions/BibleStudy/SaveBibleStudyReflection.php`
- Test: `tests/Unit/BibleStudy/SaveBibleStudyReflectionTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\BibleStudy\SaveBibleStudyReflection;
use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('creates a passage-level reflection when none exists', function (): void {
    $user = User::factory()->create();

    $reflection = resolve(SaveBibleStudyReflection::class)->handle(
        user: $user,
        theme: null,
        book: 'Job',
        chapter: 1,
        verseStart: 13,
        verseEnd: 22,
        verseNumber: null,
        body: 'Worship before understanding.',
        shareWithPartner: false,
    );

    expect($reflection->user_id)->toBe($user->id)
        ->and($reflection->verse_number)->toBeNull()
        ->and($reflection->body)->toBe('Worship before understanding.')
        ->and($reflection->is_shared_with_partner)->toBeFalse();
});

it('updates the existing passage-level reflection in place', function (): void {
    $user = User::factory()->create();
    $existing = BibleStudyReflection::factory()->for($user)->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22,
        'verse_number' => null, 'body' => 'old', 'is_shared_with_partner' => false,
    ]);

    $result = resolve(SaveBibleStudyReflection::class)->handle(
        user: $user,
        theme: null,
        book: 'Job', chapter: 1, verseStart: 13, verseEnd: 22, verseNumber: null,
        body: 'new', shareWithPartner: true,
    );

    expect($result->id)->toBe($existing->id)
        ->and($result->fresh()->body)->toBe('new')
        ->and($result->fresh()->is_shared_with_partner)->toBeTrue()
        ->and(BibleStudyReflection::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('creates a separate verse-level annotation alongside the passage-level reflection', function (): void {
    $user = User::factory()->create();
    BibleStudyReflection::factory()->for($user)->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22, 'verse_number' => null,
    ]);

    resolve(SaveBibleStudyReflection::class)->handle(
        user: $user,
        theme: null,
        book: 'Job', chapter: 1, verseStart: 13, verseEnd: 22, verseNumber: 20,
        body: 'shaved his head — ritual mourning.',
        shareWithPartner: false,
    );

    $rows = BibleStudyReflection::query()->where('user_id', $user->id)->get();

    expect($rows)->toHaveCount(2)
        ->and($rows->whereNull('verse_number')->count())->toBe(1)
        ->and($rows->whereNotNull('verse_number')->count())->toBe(1);
});

it('records the theme id when supplied', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    $reflection = resolve(SaveBibleStudyReflection::class)->handle(
        user: $user,
        theme: $theme,
        book: 'Job', chapter: 1, verseStart: 13, verseEnd: 22, verseNumber: null,
        body: 'b', shareWithPartner: false,
    );

    expect($reflection->bible_study_theme_id)->toBe($theme->id);
});
```

- [ ] **Step 2: Run test — FAIL**

- [ ] **Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\User;

final readonly class SaveBibleStudyReflection
{
    public function handle(
        User $user,
        ?BibleStudyTheme $theme,
        string $book,
        int $chapter,
        int $verseStart,
        ?int $verseEnd,
        ?int $verseNumber,
        string $body,
        bool $shareWithPartner,
    ): BibleStudyReflection {
        $key = [
            'user_id' => $user->id,
            'book' => $book,
            'chapter' => $chapter,
            'verse_start' => $verseStart,
            'verse_end' => $verseEnd,
            'verse_number' => $verseNumber,
        ];

        $values = [
            'bible_study_theme_id' => $theme?->id,
            'body' => $body,
            'is_shared_with_partner' => $shareWithPartner,
        ];

        return BibleStudyReflection::query()->updateOrCreate($key, $values);
    }
}
```

- [ ] **Step 4: PASS, lint, commit**

```bash
vendor/bin/pint --dirty --format agent
composer test:local
git add app/Actions/BibleStudy/SaveBibleStudyReflection.php tests/Unit/BibleStudy/SaveBibleStudyReflectionTest.php
git commit -m "feat(bible-study): add SaveBibleStudyReflection upsert action"
```

---

## Task 6 — User-facing `BibleStudy\ThemeController` (`index` + `show`)

Two read-only endpoints: list approved themes for the Themes tab + render a single theme detail with passages grouped into Guided Path and All Passages. Reflection counts per passage are loaded as a quick scalar so the theme detail can show "3 reflections" badges without N+1 queries.

**Files:**
- Create: `app/Http/Controllers/BibleStudy/ThemeController.php`
- Create: `tests/Feature/Controllers/BibleStudy/ThemeControllerTest.php`
- Create: placeholder `resources/js/pages/bible-study/themes/index.tsx` (filled in Task 11)
- Create: placeholder `resources/js/pages/bible-study/themes/show.tsx` (filled in Task 12)

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\User;

it('lists only approved themes on the index endpoint', function (): void {
    $user = User::factory()->create();
    $approved = BibleStudyTheme::factory()->approved()->create(['title' => 'Resilience']);
    BibleStudyTheme::factory()->draft()->create(['title' => 'Patience']);

    $response = $this->actingAs($user)->get(route('bible-study.themes.index'));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('bible-study/themes/index')
        ->has('themes', 1)
        ->where('themes.0.id', $approved->id)
    );
});

it('renders a theme detail with passages and reflection counts', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create(['slug' => 'resilience']);
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 1, 'is_guided_path' => true]);
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 2, 'is_guided_path' => false]);

    $response = $this->actingAs($user)->get(route('bible-study.themes.show', $theme->slug));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('bible-study/themes/show')
        ->where('theme.slug', 'resilience')
        ->has('theme.passages', 2)
        ->where('theme.passages.0.is_guided_path', true)
    );
});

it('404s when looking up a draft theme by slug', function (): void {
    $user = User::factory()->create();
    $draft = BibleStudyTheme::factory()->draft()->create(['slug' => 'forgiveness']);

    $this->actingAs($user)->get(route('bible-study.themes.show', $draft->slug))->assertNotFound();
});

it('redirects unauthenticated users from the index', function (): void {
    $this->get(route('bible-study.themes.index'))->assertRedirectToRoute('login');
});
```

- [ ] **Step 2: FAIL** — routes don't exist yet (added in Task 10).

- [ ] **Step 3: Create the controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ThemeController
{
    public function index(): Response
    {
        $themes = BibleStudyTheme::query()
            ->where('status', BibleStudyThemeStatus::Approved)
            ->withCount('passages')
            ->orderBy('title')
            ->get();

        return Inertia::render('bible-study/themes/index', [
            'themes' => $themes->map(fn (BibleStudyTheme $t): array => [
                'id' => $t->id,
                'slug' => $t->slug,
                'title' => $t->title,
                'short_description' => $t->short_description,
                'passage_count' => $t->passages_count,
            ]),
        ]);
    }

    public function show(string $slug): Response
    {
        $theme = BibleStudyTheme::query()
            ->where('slug', $slug)
            ->where('status', BibleStudyThemeStatus::Approved)
            ->with(['passages' => fn ($q) => $q->orderBy('position')])
            ->firstOrFail();

        return Inertia::render('bible-study/themes/show', [
            'theme' => [
                'id' => $theme->id,
                'slug' => $theme->slug,
                'title' => $theme->title,
                'short_description' => $theme->short_description,
                'long_intro' => $theme->long_intro,
                'passages' => $theme->passages->map(fn ($p): array => [
                    'id' => $p->id,
                    'position' => $p->position,
                    'is_guided_path' => $p->is_guided_path,
                    'book' => $p->book,
                    'chapter' => $p->chapter,
                    'verse_start' => $p->verse_start,
                    'verse_end' => $p->verse_end,
                    'passage_intro' => $p->passage_intro,
                ])->all(),
            ],
        ]);
    }
}
```

- [ ] **Step 4: Create placeholder Inertia pages so route renders**

`resources/js/pages/bible-study/themes/index.tsx`:

```tsx
import DevotionalLayout from '@/layouts/devotional-layout';
import { Head } from '@inertiajs/react';

interface Props {
    themes: Array<{ id: number; slug: string; title: string }>;
}

export default function Index({ themes }: Props) {
    return (
        <DevotionalLayout>
            <Head title="Bible Study Themes" />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <h1 className="text-2xl font-semibold">Themes</h1>
                <p className="mt-1 text-sm text-on-surface-variant">
                    {themes.length} themes available
                </p>
            </div>
        </DevotionalLayout>
    );
}
```

`resources/js/pages/bible-study/themes/show.tsx`:

```tsx
import DevotionalLayout from '@/layouts/devotional-layout';
import { Head } from '@inertiajs/react';

interface Props {
    theme: { slug: string; title: string };
}

export default function Show({ theme }: Props) {
    return (
        <DevotionalLayout>
            <Head title={`Theme — ${theme.title}`} />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <h1 className="text-2xl font-semibold">{theme.title}</h1>
            </div>
        </DevotionalLayout>
    );
}
```

(Tasks 11 and 12 replace these placeholders.)

- [ ] **Step 5: Routes — see Task 10.** This step depends on Task 10 wiring routes; you'll commit Tasks 6–9 + 10 together. Skip the test run until then.

- [ ] **Step 6: Commit (held until after Task 10 — see Task 10's commit step).**

---

## Task 7 — User-facing `BibleStudy\PassageController`

The reader view payload. Accepts `theme` (slug, optional), `book`, `chapter`, `verse_start`, `verse_end`, `translation` query params. Resolves the passage's enrichment if applicable, fetches structured scripture, loads user's own + partner-shared reflections, and starts/resumes the study session.

**Files:**
- Create: `app/Http/Controllers/BibleStudy/PassageController.php`
- Create: `app/Http/Requests/BibleStudy/PassageQueryRequest.php`
- Create: `tests/Feature/Controllers/BibleStudy/PassageControllerTest.php`
- Create: placeholder `resources/js/pages/bible-study/passage.tsx` (filled in Task 13)

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'verses' => [
                ['verse' => 13, 'text' => 'And there was a day...'],
                ['verse' => 14, 'text' => 'And there came a messenger...'],
            ],
        ]),
    ]);
});

it('renders the reader view for a theme passage with full enrichment', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create(['slug' => 'resilience']);
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]);
    BibleStudyInsight::factory()->for($passage, 'passage')->create();
    BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create();

    $response = $this->actingAs($user)->get(route('bible-study.passage.show', [
        'theme' => 'resilience', 'book' => 'Job', 'chapter' => 1,
        'verse_start' => 13, 'verse_end' => 14, 'translation' => 'KJV',
    ]));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('bible-study/passage')
        ->where('passage.book', 'Job')
        ->where('passage.is_enriched', true)
        ->has('passage.verses', 2)
        ->has('passage.insight')
        ->has('passage.historical_context')
        ->where('passage.translation', 'KJV')
    );
});

it('renders an ad-hoc passage with enrichment promotion when the reference matches an approved theme passage', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create(['slug' => 'resilience']);
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]);

    $response = $this->actingAs($user)->get(route('bible-study.passage.show', [
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->where('passage.is_enriched', true)
        ->where('passage.theme_slug', 'resilience')
    );
});

it('renders an ad-hoc passage without enrichment when no theme matches', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('bible-study.passage.show', [
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->where('passage.is_enriched', false)
        ->where('passage.insight', null)
        ->where('passage.historical_context', null)
        ->where('passage.theme_slug', null)
    );
});

it('loads the user\'s own and the partner\'s shared reflections', function (): void {
    $user = User::factory()->create();
    $partner = User::factory()->create();
    $user->update(['partner_id' => $partner->id]);
    $partner->update(['partner_id' => $user->id]);

    BibleStudyReflection::factory()->for($user)->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
        'verse_number' => null, 'body' => 'mine', 'is_shared_with_partner' => true,
    ]);
    BibleStudyReflection::factory()->for($partner)->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
        'verse_number' => null, 'body' => 'partner-shared', 'is_shared_with_partner' => true,
    ]);
    BibleStudyReflection::factory()->for($partner)->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
        'verse_number' => null, 'body' => 'partner-private', 'is_shared_with_partner' => false,
    ]);

    $response = $this->actingAs($user)->get(route('bible-study.passage.show', [
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]));

    $response->assertInertia(fn ($page) => $page
        ->has('passage.reflections', 2)
        ->where('passage.has_partner', true)
    );
});

it('redirects unauthenticated users', function (): void {
    $this->get(route('bible-study.passage.show', [
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 14,
    ]))->assertRedirectToRoute('login');
});
```

- [ ] **Step 2: FAIL — routes/controller don't exist.**

- [ ] **Step 3: Form request**

`app/Http/Requests/BibleStudy/PassageQueryRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class PassageQueryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'theme' => ['nullable', 'string', 'max:128'],
            'book' => ['required', 'string', 'max:64'],
            'chapter' => ['required', 'integer', 'min:1'],
            'verse_start' => ['required', 'integer', 'min:1'],
            'verse_end' => ['nullable', 'integer', 'min:1', 'gte:verse_start'],
            'translation' => ['nullable', 'string', 'in:KJV,NKJV,NIV,NLT,ASV,WEB,BBE,DARBY,HEBREW'],
        ];
    }
}
```

- [ ] **Step 4: Controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\BibleStudy;

use App\Actions\BibleStudy\FetchStructuredPassage;
use App\Actions\BibleStudy\ResolvePassageEnrichment;
use App\Actions\BibleStudy\StartOrResumeStudySession;
use App\Enums\BibleStudyThemeStatus;
use App\Http\Requests\BibleStudy\PassageQueryRequest;
use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PassageController
{
    public function show(
        PassageQueryRequest $request,
        #[CurrentUser] User $user,
        FetchStructuredPassage $fetcher,
        ResolvePassageEnrichment $resolver,
        StartOrResumeStudySession $sessionAction,
    ): Response {
        $book = $request->string('book')->value();
        $chapter = (int) $request->integer('chapter');
        $verseStart = (int) $request->integer('verse_start');
        $verseEnd = $request->filled('verse_end') ? (int) $request->integer('verse_end') : null;
        $translation = $request->string('translation', 'KJV')->upper()->value();

        $theme = $this->resolveTheme($request->string('theme', '')->value());
        $themePassage = $theme === null
            ? $resolver->handle($book, $chapter, $verseStart, $verseEnd)
            : $this->themePassage($theme, $book, $chapter, $verseStart, $verseEnd);

        $passageTheme = $themePassage?->theme ?? $theme;

        $sessionAction->handle($user, $passageTheme, $book, $chapter, $verseStart, $verseEnd);

        $scripture = $fetcher->handle($book, $chapter, $verseStart, $verseEnd, $translation);

        $reflectionUserIds = $user->hasPartner() ? [$user->id, $user->partner_id] : [$user->id];

        $reflections = BibleStudyReflection::query()
            ->whereIn('user_id', $reflectionUserIds)
            ->where('book', $book)
            ->where('chapter', $chapter)
            ->where('verse_start', $verseStart)
            ->when(
                $verseEnd === null,
                fn ($q) => $q->whereNull('verse_end'),
                fn ($q) => $q->where('verse_end', $verseEnd),
            )
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere('is_shared_with_partner', true);
            })
            ->with('user:id,name')
            ->oldest()
            ->get();

        return Inertia::render('bible-study/passage', [
            'passage' => [
                'theme_slug' => $passageTheme?->slug,
                'theme_title' => $passageTheme?->title,
                'theme_id' => $passageTheme?->id,
                'book' => $book,
                'chapter' => $chapter,
                'verse_start' => $verseStart,
                'verse_end' => $verseEnd,
                'translation' => $translation,
                'verses' => $scripture['verses'],
                'structured' => $scripture['structured'],
                'is_enriched' => $themePassage !== null,
                'theme_passage_id' => $themePassage?->id,
                'passage_intro' => $themePassage?->passage_intro,
                'insight' => $this->insightPayload($themePassage),
                'historical_context' => $this->historicalContextPayload($themePassage),
                'word_highlights' => $this->wordHighlightsPayload($themePassage),
                'reflections' => $reflections->map(fn (BibleStudyReflection $r): array => [
                    'id' => $r->id,
                    'user_id' => $r->user_id,
                    'user_name' => $r->user?->name,
                    'is_own' => $r->user_id === $user->id,
                    'verse_number' => $r->verse_number,
                    'body' => $r->body,
                    'is_shared_with_partner' => $r->is_shared_with_partner,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ])->all(),
                'has_partner' => $user->hasPartner(),
            ],
        ]);
    }

    private function resolveTheme(string $slug): ?BibleStudyTheme
    {
        if ($slug === '') {
            return null;
        }

        return BibleStudyTheme::query()
            ->where('slug', $slug)
            ->where('status', BibleStudyThemeStatus::Approved)
            ->first();
    }

    private function themePassage(BibleStudyTheme $theme, string $book, int $chapter, int $verseStart, ?int $verseEnd): ?BibleStudyThemePassage
    {
        return BibleStudyThemePassage::query()
            ->where('bible_study_theme_id', $theme->id)
            ->where('book', $book)
            ->where('chapter', $chapter)
            ->where('verse_start', $verseStart)
            ->when(
                $verseEnd === null,
                fn ($q) => $q->whereNull('verse_end'),
                fn ($q) => $q->where('verse_end', $verseEnd),
            )
            ->with(['theme', 'insight', 'historicalContext', 'wordHighlights.wordStudy'])
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function insightPayload(?BibleStudyThemePassage $passage): ?array
    {
        if ($passage?->insight === null) {
            return null;
        }

        return [
            'interpretation' => $passage->insight->interpretation,
            'application' => $passage->insight->application,
            'cross_references' => $passage->insight->cross_references,
            'literary_context' => $passage->insight->literary_context,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function historicalContextPayload(?BibleStudyThemePassage $passage): ?array
    {
        if ($passage?->historicalContext === null) {
            return null;
        }

        return [
            'setting' => $passage->historicalContext->setting,
            'author' => $passage->historicalContext->author,
            'date_range' => $passage->historicalContext->date_range,
            'audience' => $passage->historicalContext->audience,
            'historical_events' => $passage->historicalContext->historical_events,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function wordHighlightsPayload(?BibleStudyThemePassage $passage): array
    {
        if ($passage === null) {
            return [];
        }

        return $passage->wordHighlights->map(fn (BibleStudyWordHighlight $wh): array => [
            'id' => $wh->id,
            'verse_number' => $wh->verse_number,
            'word_index_in_verse' => $wh->word_index_in_verse,
            'display_word' => $wh->display_word,
            'word_study' => $wh->wordStudy === null ? null : [
                'id' => $wh->wordStudy->id,
                'original_word' => $wh->wordStudy->original_word,
                'transliteration' => $wh->wordStudy->transliteration,
                'language' => $wh->wordStudy->language,
                'definition' => $wh->wordStudy->definition,
                'strongs_number' => $wh->wordStudy->strongs_number,
            ],
        ])->all();
    }
}
```

- [ ] **Step 5: Placeholder Inertia page**

`resources/js/pages/bible-study/passage.tsx`:

```tsx
import DevotionalLayout from '@/layouts/devotional-layout';
import { Head } from '@inertiajs/react';

interface Props {
    passage: { book: string; chapter: number; verse_start: number; verse_end: number | null };
}

export default function Passage({ passage }: Props) {
    const ref = `${passage.book} ${passage.chapter}:${passage.verse_start}${passage.verse_end ? `–${passage.verse_end}` : ''}`;
    return (
        <DevotionalLayout>
            <Head title={`Reading — ${ref}`} />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <h1 className="text-2xl font-semibold">{ref}</h1>
            </div>
        </DevotionalLayout>
    );
}
```

(Task 13 replaces this placeholder.)

- [ ] **Step 6: Commit held until Task 10.**

---

## Task 8 — `BibleStudy\ReflectionController`

Three endpoints: store, update, destroy. All return back/redirect responses (Inertia-friendly). Authorization: a user can only mutate their own reflections.

**Files:**
- Create: `app/Http/Controllers/BibleStudy/ReflectionController.php`
- Create: `app/Http/Requests/BibleStudy/StoreReflectionRequest.php`
- Create: `app/Http/Requests/BibleStudy/UpdateReflectionRequest.php`
- Create: `tests/Feature/Controllers/BibleStudy/ReflectionControllerTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('stores a passage-level reflection', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    $response = $this->actingAs($user)->post(route('bible-study.reflections.store'), [
        'theme_id' => $theme->id,
        'book' => 'Job',
        'chapter' => 1,
        'verse_start' => 13,
        'verse_end' => 22,
        'verse_number' => null,
        'body' => 'Worship before understanding.',
        'is_shared_with_partner' => false,
    ]);

    $response->assertRedirect();

    expect(BibleStudyReflection::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('updates the user\'s own reflection', function (): void {
    $user = User::factory()->create();
    $reflection = BibleStudyReflection::factory()->for($user)->create(['body' => 'old']);

    $this->actingAs($user)->put(route('bible-study.reflections.update', $reflection), [
        'body' => 'new',
        'is_shared_with_partner' => true,
    ])->assertRedirect();

    expect($reflection->fresh()->body)->toBe('new')
        ->and($reflection->fresh()->is_shared_with_partner)->toBeTrue();
});

it('forbids updating someone else\'s reflection', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $reflection = BibleStudyReflection::factory()->for($other)->create();

    $this->actingAs($user)->put(route('bible-study.reflections.update', $reflection), [
        'body' => 'hijack', 'is_shared_with_partner' => false,
    ])->assertForbidden();
});

it('destroys the user\'s own reflection', function (): void {
    $user = User::factory()->create();
    $reflection = BibleStudyReflection::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('bible-study.reflections.destroy', $reflection))->assertRedirect();

    expect(BibleStudyReflection::query()->find($reflection->id))->toBeNull();
});

it('forbids destroying someone else\'s reflection', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $reflection = BibleStudyReflection::factory()->for($other)->create();

    $this->actingAs($user)->delete(route('bible-study.reflections.destroy', $reflection))->assertForbidden();
});
```

- [ ] **Step 2: FAIL**

- [ ] **Step 3: Form requests**

`app/Http/Requests/BibleStudy/StoreReflectionRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\BibleStudy;

use App\Models\BibleStudyTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreReflectionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'theme_id' => ['nullable', 'integer', Rule::exists(BibleStudyTheme::class, 'id')],
            'book' => ['required', 'string', 'max:64'],
            'chapter' => ['required', 'integer', 'min:1'],
            'verse_start' => ['required', 'integer', 'min:1'],
            'verse_end' => ['nullable', 'integer', 'min:1', 'gte:verse_start'],
            'verse_number' => ['nullable', 'integer', 'min:1'],
            'body' => ['required', 'string', 'min:1'],
            'is_shared_with_partner' => ['required', 'boolean'],
        ];
    }
}
```

`app/Http/Requests/BibleStudy/UpdateReflectionRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateReflectionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1'],
            'is_shared_with_partner' => ['required', 'boolean'],
        ];
    }
}
```

- [ ] **Step 4: Controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\BibleStudy;

use App\Actions\BibleStudy\SaveBibleStudyReflection;
use App\Http\Requests\BibleStudy\StoreReflectionRequest;
use App\Http\Requests\BibleStudy\UpdateReflectionRequest;
use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class ReflectionController
{
    public function store(StoreReflectionRequest $request, #[CurrentUser] User $user, SaveBibleStudyReflection $action): RedirectResponse
    {
        $themeId = $request->integer('theme_id');
        $theme = $themeId > 0 ? BibleStudyTheme::query()->find($themeId) : null;

        $action->handle(
            user: $user,
            theme: $theme,
            book: $request->string('book')->value(),
            chapter: (int) $request->integer('chapter'),
            verseStart: (int) $request->integer('verse_start'),
            verseEnd: $request->filled('verse_end') ? (int) $request->integer('verse_end') : null,
            verseNumber: $request->filled('verse_number') ? (int) $request->integer('verse_number') : null,
            body: $request->string('body')->value(),
            shareWithPartner: $request->boolean('is_shared_with_partner'),
        );

        return back()->with('status', 'Reflection saved.');
    }

    public function update(UpdateReflectionRequest $request, #[CurrentUser] User $user, BibleStudyReflection $reflection): RedirectResponse
    {
        abort_unless($reflection->user_id === $user->id, 403);

        $reflection->update($request->validated());

        return back()->with('status', 'Reflection updated.');
    }

    public function destroy(#[CurrentUser] User $user, BibleStudyReflection $reflection): RedirectResponse
    {
        abort_unless($reflection->user_id === $user->id, 403);

        $reflection->delete();

        return back()->with('status', 'Reflection deleted.');
    }
}
```

- [ ] **Step 5: Commit held until Task 10.**

---

## Task 9 — `BibleStudy\SearchController` (JSON)

Lightweight JSON endpoint used by the search bar on the Themes tab. Returns `[{id, slug, title, short_description}]` for exact matches and an empty array on miss-match.

**Files:**
- Create: `app/Http/Controllers/BibleStudy/SearchController.php`
- Create: `app/Http/Requests/BibleStudy/SearchQueryRequest.php`
- Create: `tests/Feature/Controllers/BibleStudy/SearchControllerTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\User;

it('returns matching approved themes as JSON', function (): void {
    $user = User::factory()->create();
    BibleStudyTheme::factory()->approved()->create(['slug' => 'wisdom', 'title' => 'Wisdom']);

    $response = $this->actingAs($user)->getJson(route('bible-study.search', ['q' => 'wisdom']));

    $response->assertOk()->assertJsonCount(1, 'themes');
});

it('returns an empty list on a miss-match', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('bible-study.search', ['q' => 'forgiveness']));

    $response->assertOk()->assertJsonCount(0, 'themes');
});

it('rejects empty queries', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('bible-study.search'))->assertUnprocessable();
});
```

- [ ] **Step 2: FAIL**

- [ ] **Step 3: Form request**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class SearchQueryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:128'],
        ];
    }
}
```

- [ ] **Step 4: Controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\BibleStudy;

use App\Actions\BibleStudy\SearchThemes;
use App\Http\Requests\BibleStudy\SearchQueryRequest;
use App\Models\BibleStudyTheme;
use Illuminate\Http\JsonResponse;

final readonly class SearchController
{
    public function show(SearchQueryRequest $request, SearchThemes $search): JsonResponse
    {
        $themes = $search->handle($request->string('q')->value());

        return response()->json([
            'themes' => $themes->map(fn (BibleStudyTheme $t): array => [
                'id' => $t->id,
                'slug' => $t->slug,
                'title' => $t->title,
                'short_description' => $t->short_description,
            ])->all(),
        ]);
    }
}
```

- [ ] **Step 5: Commit held until Task 10.**

---

## Task 10 — Wire user routes + extend `ReadingPlanController@index`

Adds the new user-facing routes to `routes/web.php` and includes `themes` (approved themes for the Themes tab) and `recentPassages` (last 5 distinct passage refs the user has visited via `bible_study_sessions`) on the existing `/bible-study` index payload.

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/ReadingPlanController.php`
- Modify: `tests/Feature/Controllers/ReadingPlanControllerTest.php` (or wherever the existing test lives — check first)

- [ ] **Step 1: Read existing routes**

Read `routes/web.php`. Locate the existing `bible-study/*` block (under the auth+verified middleware group, near `Route::get('bible-study', ReadingPlanController::index())->name('bible-study.index');`).

- [ ] **Step 2: Add imports + routes**

Add imports at the top of `routes/web.php`:

```php
use App\Http\Controllers\BibleStudy\PassageController as BibleStudyPassageController;
use App\Http\Controllers\BibleStudy\ReflectionController as BibleStudyReflectionController;
use App\Http\Controllers\BibleStudy\SearchController as BibleStudySearchController;
use App\Http\Controllers\BibleStudy\ThemeController as BibleStudyThemeController;
```

Inside the existing `Route::middleware(['auth', 'verified'])->group(...)`, after the Word Study routes:

```php
    // Bible Study Themes (user-facing)...
    Route::get('bible-study/themes', new BibleStudyThemeController()->index(...))->name('bible-study.themes.index');
    Route::get('bible-study/themes/{slug}', new BibleStudyThemeController()->show(...))->name('bible-study.themes.show');
    Route::get('bible-study/passage', new BibleStudyPassageController()->show(...))->name('bible-study.passage.show');
    Route::get('bible-study/search', new BibleStudySearchController()->show(...))->name('bible-study.search');
    Route::post('bible-study/reflections', new BibleStudyReflectionController()->store(...))->name('bible-study.reflections.store');
    Route::put('bible-study/reflections/{reflection}', new BibleStudyReflectionController()->update(...))->name('bible-study.reflections.update');
    Route::delete('bible-study/reflections/{reflection}', new BibleStudyReflectionController()->destroy(...))->name('bible-study.reflections.destroy');
```

- [ ] **Step 3: Extend `ReadingPlanController::index`**

Read `app/Http/Controllers/ReadingPlanController.php` and locate the `index` method. Modify it to also include `themes` (approved themes summary, same shape as `BibleStudy\ThemeController@index` returns) and `recentPassages` (last 5 distinct passage refs the user has open sessions on). Keep all existing props.

The added prop logic — paste verbatim into the `index` method's render array (alongside the existing `'plans'`, `'activePlanIds'`, `'progressByPlan'`):

```php
'themes' => \App\Models\BibleStudyTheme::query()
    ->where('status', \App\Enums\BibleStudyThemeStatus::Approved)
    ->withCount('passages')
    ->orderBy('title')
    ->get()
    ->map(fn (\App\Models\BibleStudyTheme $t): array => [
        'id' => $t->id,
        'slug' => $t->slug,
        'title' => $t->title,
        'short_description' => $t->short_description,
        'passage_count' => $t->passages_count,
    ])->all(),
'recentPassages' => \App\Models\BibleStudySession::query()
    ->where('user_id', $user->id)
    ->orderByDesc('last_accessed_at')
    ->limit(5)
    ->get()
    ->map(fn (\App\Models\BibleStudySession $s): array => [
        'theme_id' => $s->bible_study_theme_id,
        'book' => $s->current_book,
        'chapter' => $s->current_chapter,
        'verse_start' => $s->current_verse_start,
        'verse_end' => $s->current_verse_end,
        'last_accessed_at' => $s->last_accessed_at,
    ])->all(),
```

(Use a properly typed `User $user` from `#[CurrentUser]` if the existing controller doesn't already have it; otherwise reuse whatever the controller passes through.)

- [ ] **Step 4: Run all the held tests from Tasks 6–9**

```bash
php artisan test --compact --filter='ThemeControllerTest|PassageControllerTest|ReflectionControllerTest|SearchControllerTest'
```

Expected: all pass.

- [ ] **Step 5: Run `composer test:local`**

Expected: all green, 100% coverage. The existing ReadingPlan controller test (if any) should still pass — if it asserts the exact prop set with `where(...)` you may need to widen it to `has('themes')` etc.

- [ ] **Step 6: Commit Tasks 6–10 together**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/BibleStudy app/Http/Controllers/ReadingPlanController.php app/Http/Requests/BibleStudy resources/js/pages/bible-study routes/web.php tests/Feature/Controllers/BibleStudy
git commit -m "feat(bible-study): add user-facing themes, passage, reflection, and search controllers"
```

---

## Task 11 — `ThemesTab` + landing tab integration

The existing `bible-study/index.tsx` currently renders Verse-of-the-Day + Word Study + Reading Plans. We add a Themes tab as the default and route the existing content underneath their own tabs ("Reading Plans", "Word Studies"). Keep the existing sections functional.

**Files:**
- Create: `resources/js/components/bible-study/themes-tab.tsx`
- Modify: `resources/js/pages/bible-study/index.tsx`
- Test: visual via the browser test (Task 20)

- [ ] **Step 1: Audit existing `bible-study/index.tsx`**

Read the file. Note the prop shape and which sections render the existing reading-plan and word-study UI. Plan the tab split.

- [ ] **Step 2: Create the Themes tab component**

`resources/js/components/bible-study/themes-tab.tsx`:

```tsx
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { show as showTheme } from '@/routes/bible-study/themes';
import { show as showPassage } from '@/routes/bible-study/passage';
import { Link, router } from '@inertiajs/react';
import { ArrowRight, Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface Theme {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    passage_count: number;
}

interface RecentPassage {
    theme_id: number | null;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    last_accessed_at: string;
}

interface Props {
    themes: Theme[];
    recentPassages: RecentPassage[];
}

interface SearchResult {
    id: number;
    slug: string;
    title: string;
    short_description: string;
}

export function ThemesTab({ themes, recentPassages }: Props) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[] | null>(null);
    const [loading, setLoading] = useState(false);

    async function handleSearch(e: FormEvent) {
        e.preventDefault();
        if (!query.trim()) {
            setResults(null);
            return;
        }
        setLoading(true);
        try {
            const r = await fetch(`/bible-study/search?q=${encodeURIComponent(query.trim())}`, {
                headers: { Accept: 'application/json' },
            });
            const data = (await r.json()) as { themes: SearchResult[] };
            setResults(data.themes);
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="space-y-8">
            {/* Search */}
            <form onSubmit={handleSearch} className="flex gap-2">
                <div className="relative flex-1">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-on-surface-variant" />
                    <Input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search themes (e.g., wisdom, resilience)"
                        className="pl-10"
                    />
                </div>
                <Button type="submit" disabled={loading || !query.trim()}>
                    Search
                </Button>
            </form>

            {results !== null && results.length === 0 && (
                <div className="rounded-lg border border-dashed border-border p-6 text-center text-sm text-on-surface-variant">
                    No themes match "{query}". Try a different word — fuzzy
                    suggestions are coming in a later update.
                </div>
            )}

            {results !== null && results.length > 0 && (
                <section>
                    <h3 className="mb-3 text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Search results
                    </h3>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {results.map((theme) => (
                            <Link
                                key={theme.id}
                                href={showTheme.url(theme.slug)}
                                className="group rounded-lg border border-border bg-surface-container-low p-4 transition-colors hover:border-moss/40"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="font-medium">{theme.title}</div>
                                    <ArrowRight className="size-4 text-on-surface-variant transition-transform group-hover:translate-x-1" />
                                </div>
                                <p className="mt-1 text-sm text-on-surface-variant">
                                    {theme.short_description}
                                </p>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            {/* Recent passages */}
            {recentPassages.length > 0 && (
                <section>
                    <h3 className="mb-3 text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Recent passages
                    </h3>
                    <div className="flex flex-wrap gap-2">
                        {recentPassages.map((passage, i) => {
                            const ref = `${passage.book} ${passage.chapter}:${passage.verse_start}${passage.verse_end ? `–${passage.verse_end}` : ''}`;
                            const url = showPassage.url({
                                query: {
                                    book: passage.book,
                                    chapter: String(passage.chapter),
                                    verse_start: String(passage.verse_start),
                                    ...(passage.verse_end !== null
                                        ? { verse_end: String(passage.verse_end) }
                                        : {}),
                                },
                            });
                            return (
                                <Link
                                    // Recent-passage list is derived from the
                                    // bible_study_sessions ordered query and
                                    // never reorders within a single render.
                                    // eslint-disable-next-line @eslint-react/no-array-index-key
                                    key={i}
                                    href={url}
                                    className="rounded-full border border-border bg-surface-container-low px-3 py-1 text-xs text-on-surface transition-colors hover:border-moss/40"
                                >
                                    {ref}
                                </Link>
                            );
                        })}
                    </div>
                </section>
            )}

            {/* All approved themes */}
            <section>
                <h3 className="mb-3 text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                    All themes
                </h3>
                {themes.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-border p-6 text-center text-sm text-on-surface-variant">
                        No themes yet. Check back soon.
                    </div>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {themes.map((theme) => (
                            <Link
                                key={theme.id}
                                href={showTheme.url(theme.slug)}
                                className="group rounded-lg border border-border bg-surface-container-low p-4 transition-colors hover:border-moss/40"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="font-medium">{theme.title}</div>
                                    <ArrowRight className="size-4 text-on-surface-variant transition-transform group-hover:translate-x-1" />
                                </div>
                                <p className="mt-1 text-sm text-on-surface-variant line-clamp-2">
                                    {theme.short_description}
                                </p>
                                <div className="mt-2 text-xs text-on-surface-variant/80">
                                    {theme.passage_count} passages
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </section>
        </div>
    );
}
```

- [ ] **Step 3: Update `bible-study/index.tsx`**

Read the file. The project has shadcn `Switch` but **does not** have shadcn `Tabs`. Use a state-driven button row for the tabs.

The Props type now includes `themes` and `recentPassages` from Task 10. Wrap the existing reading-plan and word-study content inside the conditional render for their respective tabs:

```tsx
import { ThemesTab } from '@/components/bible-study/themes-tab';
import { cn } from '@/lib/utils';
import { useState } from 'react';
// ...preserve the existing imports

type TabKey = 'themes' | 'plans' | 'words';

interface ThemeRow {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    passage_count: number;
}

interface RecentPassageRow {
    theme_id: number | null;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    last_accessed_at: string;
}

interface Props {
    themes: ThemeRow[];
    recentPassages: RecentPassageRow[];
    // …existing props (plans, activePlanIds, progressByPlan, etc.)
}

const TABS: ReadonlyArray<{ key: TabKey; label: string }> = [
    { key: 'themes', label: 'Themes' },
    { key: 'plans', label: 'Reading Plans' },
    { key: 'words', label: 'Word Studies' },
];

export default function BibleStudyIndex({ themes, recentPassages, /* ...existing */ }: Props) {
    const [tab, setTab] = useState<TabKey>('themes');

    return (
        <DevotionalLayout>
            <Head title="Bible Study" />
            <div className="mx-auto max-w-5xl px-4 py-8 md:px-8">
                <header className="mb-6">
                    <h1 className="font-serif text-4xl font-medium tracking-tight text-on-surface">
                        Bible Study
                    </h1>
                </header>

                <div role="tablist" className="mb-8 flex gap-2 border-b border-border">
                    {TABS.map((t) => (
                        <button
                            key={t.key}
                            type="button"
                            role="tab"
                            aria-selected={tab === t.key}
                            onClick={() => setTab(t.key)}
                            className={cn(
                                'border-b-2 px-3 pb-2 pt-1 text-sm transition-colors',
                                tab === t.key
                                    ? 'border-moss font-medium text-on-surface'
                                    : 'border-transparent text-on-surface-variant hover:text-on-surface',
                            )}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>

                {tab === 'themes' && (
                    <ThemesTab themes={themes} recentPassages={recentPassages} />
                )}
                {tab === 'plans' && (
                    <div>{/* keep the existing reading-plan content here */}</div>
                )}
                {tab === 'words' && (
                    <div>{/* keep the existing word-study + verse-of-the-day content here */}</div>
                )}
            </div>
        </DevotionalLayout>
    );
}
```

The existing Reading Plans + Word Studies content already in `bible-study/index.tsx` should be lifted into the `tab === 'plans'` and `tab === 'words'` branches verbatim — preserve all existing imports, hooks, and behavior. The migration is purely structural.

- [ ] **Step 4: Verify**

Run `composer test:local`. The existing ReadingPlanControllerTest may need a tiny update if it asserts the exact set of props.

- [ ] **Step 5: Lint and commit**

```bash
cd resources/js && npx prettier --write components/bible-study/themes-tab.tsx pages/bible-study/index.tsx && cd ../../
git add resources/js/components/bible-study/themes-tab.tsx resources/js/pages/bible-study/index.tsx
git commit -m "feat(bible-study): add Themes tab to user-facing bible-study landing"
```

---

## Task 12 — `bible-study/themes/show.tsx`

Theme detail page: title + long intro + Guided Path subsection + All Passages library.

**Files:**
- Modify: `resources/js/pages/bible-study/themes/show.tsx` (replace placeholder)

- [ ] **Step 1: Implement the page**

Replace the contents of `resources/js/pages/bible-study/themes/show.tsx`:

```tsx
import DevotionalLayout from '@/layouts/devotional-layout';
import { show as showPassage } from '@/routes/bible-study/passage';
import { index as themesIndex } from '@/routes/bible-study/themes';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, BookOpen } from 'lucide-react';

interface Passage {
    id: number;
    position: number;
    is_guided_path: boolean;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    passage_intro: string | null;
}

interface Theme {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    long_intro: string;
    passages: Passage[];
}

interface Props {
    theme: Theme;
}

export default function Show({ theme }: Props) {
    const guided = theme.passages.filter((p) => p.is_guided_path);
    const all = theme.passages;

    function passageUrl(p: Passage): string {
        return showPassage.url({
            query: {
                theme: theme.slug,
                book: p.book,
                chapter: String(p.chapter),
                verse_start: String(p.verse_start),
                ...(p.verse_end !== null ? { verse_end: String(p.verse_end) } : {}),
            },
        });
    }

    function passageRef(p: Passage): string {
        return `${p.book} ${p.chapter}:${p.verse_start}${p.verse_end ? `–${p.verse_end}` : ''}`;
    }

    return (
        <DevotionalLayout>
            <Head title={`Theme — ${theme.title}`} />
            <div className="mx-auto max-w-4xl px-4 py-8 md:px-8">
                <Link
                    href={themesIndex.url()}
                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    Back to Themes
                </Link>

                <div className="mt-4">
                    <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        Bible Study Theme
                    </p>
                    <h1 className="mt-2 font-serif text-4xl font-medium tracking-tight text-on-surface md:text-5xl">
                        {theme.title}
                    </h1>
                    <p className="mt-3 text-base text-on-surface-variant">
                        {theme.short_description}
                    </p>
                    <div className="mt-6 space-y-4 text-on-surface/90 md:text-lg">
                        {theme.long_intro
                            .split(/\n+/)
                            .filter(Boolean)
                            .map((para) => (
                                // Paragraphs are derived from a frozen long_intro
                                // string and never reorder.
                                // eslint-disable-next-line @eslint-react/no-array-index-key
                                <p key={para.slice(0, 32)}>{para}</p>
                            ))}
                    </div>
                </div>

                {guided.length > 0 && (
                    <section className="mt-12">
                        <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            Guided Path
                        </h2>
                        <ol className="mt-4 space-y-3">
                            {guided.map((p, i) => (
                                <li key={p.id} className="flex gap-3">
                                    <span className="mt-1 inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-moss text-xs text-moss-foreground">
                                        {i + 1}
                                    </span>
                                    <Link
                                        href={passageUrl(p)}
                                        className="group flex-1 rounded-lg border border-border bg-surface-container-low p-4 transition-colors hover:border-moss/40"
                                    >
                                        <div className="font-medium">
                                            {passageRef(p)}
                                        </div>
                                        {p.passage_intro && (
                                            <p className="mt-1 text-sm text-on-surface-variant">
                                                {p.passage_intro}
                                            </p>
                                        )}
                                    </Link>
                                </li>
                            ))}
                        </ol>
                    </section>
                )}

                <section className="mt-12">
                    <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                        All Passages
                    </h2>
                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                        {all.map((p) => (
                            <Link
                                key={p.id}
                                href={passageUrl(p)}
                                className="group rounded-lg border border-border bg-surface-container-low p-4 transition-colors hover:border-moss/40"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="font-medium">{passageRef(p)}</div>
                                    <BookOpen className="size-4 text-on-surface-variant transition-transform group-hover:translate-x-1" />
                                </div>
                                {p.passage_intro && (
                                    <p className="mt-1 text-sm text-on-surface-variant line-clamp-2">
                                        {p.passage_intro}
                                    </p>
                                )}
                            </Link>
                        ))}
                    </div>
                </section>
            </div>
        </DevotionalLayout>
    );
}
```

- [ ] **Step 2: Verify**

Run `composer test:local`. Manual smoke (after `bun run build`): visit `/bible-study/themes/resilience`.

- [ ] **Step 3: Commit**

```bash
cd resources/js && npx prettier --write pages/bible-study/themes/show.tsx && cd ../../
git add resources/js/pages/bible-study/themes/show.tsx
git commit -m "feat(bible-study): theme detail page with guided path + library"
```

---

## Task 13 — `bible-study/passage.tsx` skeleton

The reader view's structural shell: header (back link, theme badge, translation switcher), two-pane layout on desktop, stacked on mobile. Tasks 14–18 fill in the content.

**Files:**
- Modify: `resources/js/pages/bible-study/passage.tsx` (replace placeholder)

- [ ] **Step 1: Implement the skeleton**

```tsx
import DevotionalLayout from '@/layouts/devotional-layout';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    BIBLE_VERSIONS,
    type BibleVersionKey,
    setPreferredVersion,
} from '@/lib/bible-versions';
import { show as showTheme } from '@/routes/bible-study/themes';
import { show as showPassage } from '@/routes/bible-study/passage';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { ScriptureReader } from '@/components/bible-study/scripture-reader';
import { InsightsPanel } from '@/components/bible-study/insights-panel';
import { HistoricalContextCard } from '@/components/bible-study/historical-context-card';
import { ReflectionList } from '@/components/bible-study/reflection-list';
import { ReflectionComposer } from '@/components/bible-study/reflection-composer';

interface CrossRef {
    book: string;
    chapter: number;
    verse_start: number;
    verse_end?: number | null;
    note?: string;
}

interface WordStudy {
    id: number;
    original_word: string;
    transliteration: string;
    language: string;
    definition: string;
    strongs_number: string;
}

interface WordHighlight {
    id: number;
    verse_number: number;
    word_index_in_verse: number;
    display_word: string;
    word_study: WordStudy | null;
}

interface Insight {
    interpretation: string;
    application: string;
    cross_references: CrossRef[];
    literary_context: string;
}

interface HistoricalContext {
    setting: string;
    author: string;
    date_range: string;
    audience: string;
    historical_events: string;
}

export interface Reflection {
    id: number;
    user_id: number;
    user_name: string | null;
    is_own: boolean;
    verse_number: number | null;
    body: string;
    is_shared_with_partner: boolean;
    created_at: string;
    updated_at: string;
}

interface PassagePayload {
    theme_slug: string | null;
    theme_title: string | null;
    theme_id: number | null;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    translation: string;
    verses: Record<number, string>;
    structured: boolean;
    is_enriched: boolean;
    theme_passage_id: number | null;
    passage_intro: string | null;
    insight: Insight | null;
    historical_context: HistoricalContext | null;
    word_highlights: WordHighlight[];
    reflections: Reflection[];
    has_partner: boolean;
}

interface Props {
    passage: PassagePayload;
}

export default function Passage({ passage }: Props) {
    const ref = `${passage.book} ${passage.chapter}:${passage.verse_start}${passage.verse_end ? `–${passage.verse_end}` : ''}`;

    function changeTranslation(translation: BibleVersionKey): void {
        setPreferredVersion(translation);
        router.get(showPassage.url(), {
            ...(passage.theme_slug ? { theme: passage.theme_slug } : {}),
            book: passage.book,
            chapter: passage.chapter,
            verse_start: passage.verse_start,
            ...(passage.verse_end !== null ? { verse_end: passage.verse_end } : {}),
            translation,
        }, { preserveScroll: true });
    }

    const backHref = passage.theme_slug
        ? showTheme.url(passage.theme_slug)
        : '/bible-study';

    return (
        <DevotionalLayout>
            <Head title={`Reading — ${ref}`} />
            <div className="mx-auto max-w-7xl px-4 py-8 md:px-8">
                <Link
                    href={backHref}
                    className="inline-flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
                >
                    <ArrowLeft className="size-4" />
                    {passage.theme_title ? `Back to ${passage.theme_title}` : 'Back to Bible Study'}
                </Link>

                <div className="mt-4 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        {passage.theme_title && (
                            <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                                {passage.theme_title}
                            </p>
                        )}
                        <h1 className="mt-2 font-serif text-3xl font-medium tracking-tight text-on-surface md:text-4xl">
                            {ref}
                        </h1>
                    </div>
                    <Select
                        value={passage.translation}
                        onValueChange={(v) => changeTranslation(v as BibleVersionKey)}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {BIBLE_VERSIONS.map((v) => (
                                <SelectItem key={v.value} value={v.value}>
                                    {v.value}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {passage.passage_intro && (
                    <p className="mt-6 text-base text-on-surface-variant md:text-lg">
                        {passage.passage_intro}
                    </p>
                )}

                <div className="mt-8 grid gap-8 lg:grid-cols-[1.4fr_1fr]">
                    {/* Scripture column */}
                    <div className="space-y-6">
                        <ScriptureReader
                            verses={passage.verses}
                            structured={passage.structured}
                            wordHighlights={passage.word_highlights}
                            reflections={passage.reflections}
                            partnerEnabled={passage.has_partner}
                            passageRef={{
                                themeId: passage.theme_id,
                                book: passage.book,
                                chapter: passage.chapter,
                                verseStart: passage.verse_start,
                                verseEnd: passage.verse_end,
                            }}
                        />
                        <ReflectionComposer
                            scope="passage"
                            partnerEnabled={passage.has_partner}
                            passageRef={{
                                themeId: passage.theme_id,
                                book: passage.book,
                                chapter: passage.chapter,
                                verseStart: passage.verse_start,
                                verseEnd: passage.verse_end,
                                verseNumber: null,
                            }}
                            existing={
                                passage.reflections.find(
                                    (r) => r.is_own && r.verse_number === null,
                                ) ?? null
                            }
                        />
                        <ReflectionList
                            reflections={passage.reflections.filter(
                                (r) => r.verse_number === null,
                            )}
                        />
                    </div>

                    {/* Insights column (desktop only — stacked on mobile via grid) */}
                    {passage.is_enriched && (
                        <aside className="space-y-6">
                            {passage.insight && <InsightsPanel insight={passage.insight} />}
                            {passage.historical_context && (
                                <HistoricalContextCard context={passage.historical_context} />
                            )}
                        </aside>
                    )}
                </div>
            </div>
        </DevotionalLayout>
    );
}
```

- [ ] **Step 2: Run `composer test:local`** — TS will fail because the imported components don't exist yet. That's expected; Tasks 14–18 add them. **Skip the commit until Task 18.**

---

## Task 14 — `ScriptureReader` component

Renders the structured verses with highlight spans and exposes a verse-tap interaction that opens the verse-level reflection composer (Task 17 wires the composer; for now the reader exposes a `onVerseClick` callback).

**Files:**
- Create: `resources/js/components/bible-study/scripture-reader.tsx`

- [ ] **Step 1: Implement**

```tsx
import { cn } from '@/lib/utils';
import { useState } from 'react';
import { WordStudySheet } from '@/components/bible-study/word-study-sheet';
import { ReflectionComposer } from '@/components/bible-study/reflection-composer';
import type { Reflection } from '@/pages/bible-study/passage';

interface WordStudy {
    id: number;
    original_word: string;
    transliteration: string;
    language: string;
    definition: string;
    strongs_number: string;
}

interface WordHighlight {
    id: number;
    verse_number: number;
    word_index_in_verse: number;
    display_word: string;
    word_study: WordStudy | null;
}

interface PassageRef {
    themeId: number | null;
    book: string;
    chapter: number;
    verseStart: number;
    verseEnd: number | null;
}

interface Props {
    verses: Record<number, string>;
    structured: boolean;
    wordHighlights: WordHighlight[];
    reflections: Reflection[];
    partnerEnabled: boolean;
    passageRef: PassageRef;
}

export function ScriptureReader({
    verses,
    structured,
    wordHighlights,
    reflections,
    partnerEnabled,
    passageRef,
}: Props) {
    const [activeWord, setActiveWord] = useState<WordStudy | null>(null);
    const [activeVerse, setActiveVerse] = useState<number | null>(null);

    const verseNumbers = Object.keys(verses)
        .map((n) => Number(n))
        .sort((a, b) => a - b);

    return (
        <div className="space-y-3 font-serif text-lg leading-relaxed text-on-surface md:text-xl">
            {verseNumbers.map((vn) => {
                const text = verses[vn];
                const tokens = text.split(/(\s+)/);
                const verseHighlights = wordHighlights.filter(
                    (h) => h.verse_number === vn,
                );

                return (
                    <div key={vn} className="group relative">
                        {structured && (
                            <button
                                type="button"
                                onClick={() => setActiveVerse(vn)}
                                className="float-left mr-2 mt-1.5 inline-block text-xs font-bold text-moss/70 transition-colors hover:text-moss"
                                aria-label={`Add note on verse ${vn}`}
                            >
                                {vn}
                            </button>
                        )}
                        <p className="inline">
                            {tokens.map((tok, idx) => {
                                if (/^\s+$/.test(tok)) {
                                    return tok;
                                }
                                const wordIdx = tokensToWordIndex(tokens, idx);
                                const highlight = verseHighlights.find(
                                    (h) => h.word_index_in_verse === wordIdx,
                                );
                                if (highlight && highlight.word_study) {
                                    const ws = highlight.word_study;
                                    return (
                                        <button
                                            // Token positions inside an
                                            // immutable verse string never
                                            // reorder.
                                            // eslint-disable-next-line @eslint-react/no-array-index-key
                                            key={idx}
                                            type="button"
                                            onClick={() => setActiveWord(ws)}
                                            className={cn(
                                                'inline border-b border-dashed border-moss/60 bg-moss/10 px-0.5 transition-colors',
                                                'hover:bg-moss/20',
                                            )}
                                        >
                                            {tok}
                                        </button>
                                    );
                                }
                                return (
                                    <span
                                        // Token positions inside an immutable
                                        // verse string never reorder.
                                        // eslint-disable-next-line @eslint-react/no-array-index-key
                                        key={idx}
                                    >
                                        {tok}
                                    </span>
                                );
                            })}
                        </p>

                        {activeVerse === vn && (
                            <div className="mt-3 rounded-lg border border-border bg-surface-container-low p-4">
                                <ReflectionComposer
                                    scope="verse"
                                    partnerEnabled={partnerEnabled}
                                    passageRef={{
                                        themeId: passageRef.themeId,
                                        book: passageRef.book,
                                        chapter: passageRef.chapter,
                                        verseStart: passageRef.verseStart,
                                        verseEnd: passageRef.verseEnd,
                                        verseNumber: vn,
                                    }}
                                    existing={
                                        reflections.find(
                                            (r) => r.is_own && r.verse_number === vn,
                                        ) ?? null
                                    }
                                    onClose={() => setActiveVerse(null)}
                                />
                                <VerseAnnotations
                                    reflections={reflections.filter(
                                        (r) => r.verse_number === vn,
                                    )}
                                />
                            </div>
                        )}
                    </div>
                );
            })}

            <WordStudySheet
                wordStudy={activeWord}
                onClose={() => setActiveWord(null)}
            />
        </div>
    );
}

function VerseAnnotations({ reflections }: { reflections: Reflection[] }) {
    if (reflections.length === 0) {
        return null;
    }
    return (
        <ul className="mt-3 space-y-2 border-t border-border pt-3 text-sm">
            {reflections.map((r) => (
                <li
                    key={r.id}
                    className="rounded-md bg-surface-container-lowest p-2 text-on-surface-variant"
                >
                    <div className="text-xs uppercase tracking-wide text-on-surface-variant/70">
                        {r.is_own ? 'You' : (r.user_name ?? 'Partner')}
                    </div>
                    <p className="mt-1 text-on-surface">{r.body}</p>
                </li>
            ))}
        </ul>
    );
}

/**
 * Translate a `split(/(\s+)/)` index back into a 0-based word index
 * (excluding whitespace tokens).
 */
function tokensToWordIndex(tokens: string[], index: number): number {
    let count = 0;
    for (let i = 0; i < index; i++) {
        if (!/^\s+$/.test(tokens[i])) {
            count++;
        }
    }
    return count;
}
```

- [ ] **Step 2: Skip commit; move to Task 15.**

---

## Task 15 — `WordStudySheet` component

Bottom sheet that displays the tapped word's Hebrew/Greek root, transliteration, definition, and Strong's number. Uses shadcn `Sheet` if present, else a fixed-position panel.

**Files:**
- Create: `resources/js/components/bible-study/word-study-sheet.tsx`

- [ ] **Step 1: Implement**

```tsx
import { Button } from '@/components/ui/button';
import { X } from 'lucide-react';

interface WordStudy {
    id: number;
    original_word: string;
    transliteration: string;
    language: string;
    definition: string;
    strongs_number: string;
}

interface Props {
    wordStudy: WordStudy | null;
    onClose: () => void;
}

export function WordStudySheet({ wordStudy, onClose }: Props) {
    if (wordStudy === null) {
        return null;
    }

    return (
        <>
            <button
                type="button"
                aria-label="Close word study"
                onClick={onClose}
                className="fixed inset-0 z-40 bg-black/40"
            />
            <div className="fixed inset-x-0 bottom-0 z-50 rounded-t-2xl border-t border-border bg-surface-container-highest p-6 shadow-ambient-lg md:right-8 md:bottom-8 md:left-auto md:w-96 md:rounded-2xl">
                <div className="mb-4 flex items-start justify-between">
                    <div>
                        <p className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                            {wordStudy.language}
                        </p>
                        <p className="mt-1 font-serif text-3xl text-on-surface">
                            {wordStudy.original_word}
                        </p>
                        <p className="mt-1 text-sm italic text-on-surface-variant">
                            {wordStudy.transliteration} ·{' '}
                            <span className="font-mono text-xs">
                                {wordStudy.strongs_number}
                            </span>
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={onClose}
                        aria-label="Close"
                    >
                        <X className="size-4" />
                    </Button>
                </div>
                <p className="text-sm leading-relaxed text-on-surface">
                    {wordStudy.definition}
                </p>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Skip commit; move to Task 16.**

---

## Task 16 — `InsightsPanel` + `HistoricalContextCard`

Right-rail components shown on enriched passages.

**Files:**
- Create: `resources/js/components/bible-study/insights-panel.tsx`
- Create: `resources/js/components/bible-study/historical-context-card.tsx`

- [ ] **Step 1: `insights-panel.tsx`**

```tsx
interface CrossRef {
    book: string;
    chapter: number;
    verse_start: number;
    verse_end?: number | null;
    note?: string;
}

interface Insight {
    interpretation: string;
    application: string;
    cross_references: CrossRef[];
    literary_context: string;
}

export function InsightsPanel({ insight }: { insight: Insight }) {
    return (
        <section className="rounded-2xl border border-border bg-surface-container-low p-5">
            <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                Insights
            </h2>

            <div className="mt-4 space-y-4 text-sm leading-relaxed text-on-surface/90">
                <Block title="Interpretation" body={insight.interpretation} />
                <Block title="Application" body={insight.application} />
                <Block title="Literary Context" body={insight.literary_context} />

                {insight.cross_references.length > 0 && (
                    <div>
                        <h3 className="mb-1 text-xs font-bold uppercase text-on-surface-variant">
                            Cross-references
                        </h3>
                        <ul className="space-y-1">
                            {insight.cross_references.map((ref) => (
                                <li
                                    key={`${ref.book}-${ref.chapter}-${ref.verse_start}`}
                                    className="text-on-surface"
                                >
                                    <span className="font-medium">
                                        {ref.book} {ref.chapter}:{ref.verse_start}
                                        {ref.verse_end ? `–${ref.verse_end}` : ''}
                                    </span>
                                    {ref.note && (
                                        <span className="ml-2 text-on-surface-variant">
                                            — {ref.note}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </section>
    );
}

function Block({ title, body }: { title: string; body: string }) {
    return (
        <div>
            <h3 className="mb-1 text-xs font-bold uppercase text-on-surface-variant">
                {title}
            </h3>
            <p className="text-on-surface">{body}</p>
        </div>
    );
}
```

- [ ] **Step 2: `historical-context-card.tsx`**

```tsx
interface HistoricalContext {
    setting: string;
    author: string;
    date_range: string;
    audience: string;
    historical_events: string;
}

export function HistoricalContextCard({ context }: { context: HistoricalContext }) {
    return (
        <section className="rounded-2xl border border-border bg-surface-container-low p-5">
            <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                Historical Context
            </h2>
            <dl className="mt-4 space-y-3 text-sm">
                <Field label="Setting" value={context.setting} />
                <Field label="Author" value={context.author} />
                <Field label="Date" value={context.date_range} />
                <Field label="Audience" value={context.audience} />
                <Field label="Events" value={context.historical_events} />
            </dl>
        </section>
    );
}

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs font-bold uppercase text-on-surface-variant/70">
                {label}
            </dt>
            <dd className="mt-0.5 text-on-surface">{value}</dd>
        </div>
    );
}
```

- [ ] **Step 3: Skip commit; move to Task 17.**

---

## Task 17 — `ReflectionComposer` + `ReflectionList`

Composer posts to `bible-study.reflections.store` (or `update` if `existing` is supplied) and includes a "share with partner" toggle. List renders existing reflections (passage-level only — verse-level annotations live next to verses).

**Files:**
- Create: `resources/js/components/bible-study/reflection-composer.tsx`
- Create: `resources/js/components/bible-study/reflection-list.tsx`

- [ ] **Step 1: `reflection-composer.tsx`**

```tsx
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { store as storeReflection, update as updateReflection } from '@/routes/bible-study/reflections';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import type { Reflection } from '@/pages/bible-study/passage';

interface PassageRef {
    themeId: number | null;
    book: string;
    chapter: number;
    verseStart: number;
    verseEnd: number | null;
    verseNumber: number | null;
}

interface Props {
    scope: 'passage' | 'verse';
    partnerEnabled: boolean;
    passageRef: PassageRef;
    existing: Reflection | null;
    onClose?: () => void;
}

export function ReflectionComposer({
    scope,
    partnerEnabled,
    passageRef,
    existing,
    onClose,
}: Props) {
    const form = useForm({
        body: existing?.body ?? '',
        is_shared_with_partner: existing?.is_shared_with_partner ?? false,
    });

    function submit(e: FormEvent): void {
        e.preventDefault();
        if (existing !== null) {
            form.put(updateReflection.url(existing.id), {
                preserveScroll: true,
                onSuccess: () => onClose?.(),
            });
            return;
        }
        // POST to store with the full passage ref.
        form.transform((data) => ({
            ...data,
            theme_id: passageRef.themeId,
            book: passageRef.book,
            chapter: passageRef.chapter,
            verse_start: passageRef.verseStart,
            verse_end: passageRef.verseEnd,
            verse_number: passageRef.verseNumber,
        }))
            .post(storeReflection.url(), {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset('body');
                    onClose?.();
                },
            });
    }

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-border bg-surface-container-lowest p-4"
        >
            <Textarea
                value={form.data.body}
                onChange={(e) => form.setData('body', e.target.value)}
                placeholder={
                    scope === 'passage'
                        ? 'Reflect on this passage...'
                        : 'Add a verse-level note...'
                }
                rows={3}
                className="resize-none border-border bg-surface-container-low"
            />
            <div className="mt-3 flex items-center justify-between gap-3">
                <label className="flex items-center gap-2 text-xs text-on-surface-variant">
                    <Switch
                        checked={form.data.is_shared_with_partner}
                        onCheckedChange={(v) =>
                            form.setData('is_shared_with_partner', v)
                        }
                        disabled={!partnerEnabled}
                    />
                    Share with partner
                    {!partnerEnabled && (
                        <span className="text-xs text-on-surface-variant/60">
                            (link a partner first)
                        </span>
                    )}
                </label>
                <div className="flex gap-2">
                    {onClose && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                    )}
                    <Button
                        type="submit"
                        size="sm"
                        disabled={form.processing || !form.data.body.trim()}
                    >
                        {existing ? 'Save changes' : 'Save reflection'}
                    </Button>
                </div>
            </div>
        </form>
    );
}
```

- [ ] **Step 2: `reflection-list.tsx`**

```tsx
import { Button } from '@/components/ui/button';
import { destroy as destroyReflection } from '@/routes/bible-study/reflections';
import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import type { Reflection } from '@/pages/bible-study/passage';

interface Props {
    reflections: Reflection[];
}

export function ReflectionList({ reflections }: Props) {
    if (reflections.length === 0) {
        return null;
    }

    function destroy(id: number): void {
        if (!confirm('Delete this reflection?')) {
            return;
        }
        router.delete(destroyReflection.url(id), { preserveScroll: true });
    }

    return (
        <ul className="space-y-3">
            {reflections.map((r) => (
                <li
                    key={r.id}
                    className="rounded-xl border border-border bg-surface-container-low p-4"
                >
                    <div className="mb-2 flex items-center justify-between">
                        <div className="text-xs font-medium uppercase tracking-wide text-on-surface-variant">
                            {r.is_own ? 'You' : (r.user_name ?? 'Partner')}
                            {r.is_shared_with_partner && (
                                <span className="ml-2 rounded-full bg-moss/15 px-2 py-0.5 text-[10px] text-moss">
                                    shared
                                </span>
                            )}
                        </div>
                        {r.is_own && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => destroy(r.id)}
                                aria-label="Delete reflection"
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        )}
                    </div>
                    <p className="text-sm leading-relaxed text-on-surface">
                        {r.body}
                    </p>
                </li>
            ))}
        </ul>
    );
}
```

- [ ] **Step 3: Skip commit; move to Task 18.**

---

## Task 18 — Final reader-view wiring + commit Tasks 13–18

By this point, all files referenced by `passage.tsx` exist. Build, run the suite, and commit Tasks 13 through 18 in one block.

- [ ] **Step 1: Verify TypeScript compiles**

```bash
bun run build 2>&1 | tail -5
```

Expected: success.

- [ ] **Step 2: Run `composer test:local`**

Expected: all green.

- [ ] **Step 3: Commit**

```bash
cd resources/js && npx prettier --write components/bible-study pages/bible-study/passage.tsx && cd ../../
git add resources/js/components/bible-study resources/js/pages/bible-study/passage.tsx
git commit -m "feat(bible-study): add user reader view (scripture, highlights, insights, reflections)"
```

---

## Task 19 — `PassageSearchBar` + `RecentPassages` for ad-hoc study

Adds a Book/Chapter/Verse picker that lets the user open any passage ad-hoc directly into the reader view. Lives next to the search bar on the Themes tab.

**Files:**
- Create: `resources/js/components/bible-study/passage-search-bar.tsx`
- Modify: `resources/js/components/bible-study/themes-tab.tsx` (mount the picker)

- [ ] **Step 1: Implement the picker**

```tsx
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { show as showPassage } from '@/routes/bible-study/passage';
import { router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

const BOOKS = [
    'Genesis','Exodus','Leviticus','Numbers','Deuteronomy','Joshua','Judges','Ruth',
    '1 Samuel','2 Samuel','1 Kings','2 Kings','1 Chronicles','2 Chronicles','Ezra','Nehemiah',
    'Esther','Job','Psalm','Proverbs','Ecclesiastes','Song of Solomon','Isaiah','Jeremiah',
    'Lamentations','Ezekiel','Daniel','Hosea','Joel','Amos','Obadiah','Jonah','Micah','Nahum',
    'Habakkuk','Zephaniah','Haggai','Zechariah','Malachi','Matthew','Mark','Luke','John',
    'Acts','Romans','1 Corinthians','2 Corinthians','Galatians','Ephesians','Philippians',
    'Colossians','1 Thessalonians','2 Thessalonians','1 Timothy','2 Timothy','Titus','Philemon',
    'Hebrews','James','1 Peter','2 Peter','1 John','2 John','3 John','Jude','Revelation',
];

export function PassageSearchBar() {
    const [book, setBook] = useState('Job');
    const [chapter, setChapter] = useState('1');
    const [verseStart, setVerseStart] = useState('1');
    const [verseEnd, setVerseEnd] = useState('');

    function submit(e: FormEvent): void {
        e.preventDefault();
        router.get(
            showPassage.url(),
            {
                book,
                chapter,
                verse_start: verseStart,
                ...(verseEnd ? { verse_end: verseEnd } : {}),
            },
            { preserveScroll: false },
        );
    }

    return (
        <form onSubmit={submit} className="grid gap-2 md:grid-cols-[2fr_1fr_1fr_1fr_auto]">
            <select
                value={book}
                onChange={(e) => setBook(e.target.value)}
                className="rounded-md border border-border bg-surface-container-low px-3 py-2 text-sm"
            >
                {BOOKS.map((b) => (
                    <option key={b} value={b}>
                        {b}
                    </option>
                ))}
            </select>
            <Input
                inputMode="numeric"
                placeholder="Chapter"
                value={chapter}
                onChange={(e) => setChapter(e.target.value)}
            />
            <Input
                inputMode="numeric"
                placeholder="Verse"
                value={verseStart}
                onChange={(e) => setVerseStart(e.target.value)}
            />
            <Input
                inputMode="numeric"
                placeholder="To (optional)"
                value={verseEnd}
                onChange={(e) => setVerseEnd(e.target.value)}
            />
            <Button type="submit">Open</Button>
        </form>
    );
}
```

- [ ] **Step 2: Mount in `ThemesTab`**

In `resources/js/components/bible-study/themes-tab.tsx`, import and add the `PassageSearchBar` above the search section:

```tsx
import { PassageSearchBar } from '@/components/bible-study/passage-search-bar';
// ...

return (
    <div className="space-y-8">
        <section>
            <h3 className="mb-3 text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                Open any passage
            </h3>
            <PassageSearchBar />
        </section>
        {/* existing search form, recent passages, themes grid */}
    </div>
);
```

- [ ] **Step 3: Commit**

```bash
cd resources/js && npx prettier --write components/bible-study/passage-search-bar.tsx components/bible-study/themes-tab.tsx && cd ../../
git add resources/js/components/bible-study/passage-search-bar.tsx resources/js/components/bible-study/themes-tab.tsx
git commit -m "feat(bible-study): add ad-hoc Book/Chapter/Verse picker to landing"
```

---

## Task 20 — Pest 4 browser test for the reader flow

End-to-end visual test: a user opens a theme, taps a passage in the guided path, lands on the reader view with the seeded Resilience theme's content, sees the historical context card, types a reflection, saves it, and confirms it appears in the list.

**Files:**
- Create: `tests/Browser/BibleStudy/ReaderFlowTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyReflection;
use App\Models\User;
use Database\Seeders\BibleStudyThemeSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'verses' => [
                ['verse' => 13, 'text' => 'And there was a day when his sons and his daughters were eating...'],
                ['verse' => 22, 'text' => 'In all this Job sinned not, nor charged God foolishly.'],
            ],
        ]),
    ]);
});

it('user can open the Resilience theme, read Job 1:13–22, and save a reflection', function (): void {
    (new BibleStudyThemeSeeder)->run();
    $user = User::factory()->create();

    $page = visit('/bible-study')->loginAs($user);

    $page->click('Themes')
        ->click('Resilience')
        ->assertSee('Job 1:13')
        ->click('Job 1:13')
        ->assertSee('Insights')
        ->assertSee('Historical Context')
        ->fill('textarea', 'Worship before understanding.')
        ->click('Save reflection')
        ->assertSee('Worship before understanding.');

    expect(BibleStudyReflection::query()->where('user_id', $user->id)->count())->toBe(1);
});
```

- [ ] **Step 2: Run the test**

```bash
php artisan test --compact --filter=ReaderFlowTest
```

Expected: PASS. If the test infra needs a `bun run build` first, run it once.

- [ ] **Step 3: Run `composer test:local`**

Expected: all green.

- [ ] **Step 4: Commit**

```bash
git add tests/Browser/BibleStudy/ReaderFlowTest.php
git commit -m "test(bible-study): browser test for theme → passage → reflection flow"
```

---

## Self-Review Checklist (for the executor)

- [ ] All 20 tasks committed.
- [ ] `composer test:local` green (100% line coverage, 100% type coverage, Pint, PHPStan, Rector, ESLint, Prettier, TypeScript).
- [ ] Visit `/bible-study` → Themes tab is the default; "Open any passage" picker, search bar, recent passages strip, and approved themes grid all render.
- [ ] Navigate to `/bible-study/themes/resilience` → see the Resilience theme detail with Guided Path + All Passages.
- [ ] Click a passage → reader view loads with two-pane layout on desktop, stacked on mobile, translation switcher works, word highlights are tappable and open the WordStudySheet, AI insights + historical context render, passage-level reflection composer saves with the share-with-partner toggle.
- [ ] Tap a verse number → verse-level annotation composer opens; saving creates a separate row.
- [ ] Test ad-hoc: open `/bible-study/passage?book=Job&chapter=1&verse_start=13&verse_end=22` (no `theme=`) → enrichment appears (because the seeded Resilience theme exactly covers Job 1:13–22).
- [ ] Test ad-hoc miss: `/bible-study/passage?book=Genesis&chapter=1&verse_start=1&verse_end=5` → scripture only, no insight/historical-context card.
- [ ] Search "wisdom" with no Wisdom theme → "no themes match" empty state. Phase 3 will add fuzzy suggestions.

## Out of Scope for Phase 2 (deferred to Phase 3)

- `PartnerStartedBibleStudy` notification class and `ShareWithPartner` button that fires it. The `is_shared_with_partner` flag on reflections is honored, but pushing a "I started studying X" notification to the partner is Phase 3.
- Fuzzy theme search ranking (currently exact-match only).
- `bible_study_theme_requests` insertion on miss-match + auto-enqueued draft jobs.
- "Your theme is ready" notification when an admin publishes a previously-requested theme.

---

## Spec Coverage Verification

Spec §3 user-facing experience:
- §3.1 Entry points (Themes tab as default) → Tasks 10, 11 ✓
- §3.2 Browsing & search (exact match) → Tasks 6, 9, 11 ✓
- §3.3 Theme detail (intro + guided path + library) → Tasks 6, 12 ✓
- §3.4 Reader view (two-pane, translations, highlights, insights, historical) → Tasks 7, 13–16 ✓
- §3.5 Reflections (passage + verse, partner-share toggle) → Tasks 5, 8, 14, 17 ✓
- §3.6 Partner sync — only the share-toggle UI; the notification dispatch is deferred to Phase 3 per spec §8 ✓

Spec §4 architecture:
- §4.3 Actions: `StartOrResumeStudySession` (Task 4), `SaveBibleStudyReflection` (Task 5), `ResolvePassageEnrichment` (Task 2), `SearchThemes` (Task 3) ✓
- §4.5 Routes: `GET /bible-study` extended (Task 10), `GET /bible-study/themes/{slug}` (Task 6), `GET /bible-study/passage` (Task 7), `POST/PUT/DELETE /bible-study/reflections` (Task 8), `GET /bible-study/search` (Task 9) ✓
- §4.6 Frontend pages + components → Tasks 11–19 ✓

Spec §8 Phase 2 scope: matches exactly. Phase 3 items explicitly out of scope.
