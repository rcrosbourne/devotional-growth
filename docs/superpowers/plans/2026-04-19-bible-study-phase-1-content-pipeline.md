# Bible Study — Phase 1: Content Pipeline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the backend content pipeline for the Bible Study theme-driven feature — migrations, models/factories, the AI drafter agent, the queued draft job, publish action, and the admin review UI capable of editing and publishing a draft theme. No user-facing UI in this phase.

**Architecture:** Eight new tables under a `bible_study_*` prefix plus one column added to `notification_preferences`. An AI agent (`BibleStudyThemeDrafter`) following the existing `DevotionalContentGenerator` pattern drafts themes via structured output. A queued job wraps a `DraftBibleStudyTheme` action. Admin review UI is built as a nested-resource REST-ish set of endpoints under `admin/bible-study/*`, with two Inertia pages (queue index + theme review).

**Tech Stack:** Laravel 12, PHP 8.4, Pest 4, Larastan, Laravel AI, Inertia + React 19, Tailwind 4. Enforce 100% type coverage and 100% line coverage per `composer test:local`.

---

## Source Spec

`docs/superpowers/specs/2026-04-19-bible-study-themes-design.md` — read sections §1–§4.7 before starting.

## Conventions Reminders (read once)

- Strict types + final classes: every new PHP file starts with `declare(strict_types=1);` and classes are `final` unless a reason to allow extension.
- Model property annotations: match `app/Models/AiGenerationLog.php` — full `@property-read` block with nullable types.
- Migrations: `Schema::create(...)` inside a `return new class extends Migration`; `down()` uses `Schema::dropIfExists(...)`.
- Factory return type: `array<string, mixed>`; add `Factory<Model>` generic in `@extends Factory<>` doc.
- Controllers: `final readonly class`, use `#[CurrentUser]` attribute when the acting user is needed, return `Inertia::render('...')` or `RedirectResponse`/`JsonResponse`.
- Routes: reuse the existing `admin.` name prefix group in `routes/web.php` — all new admin routes go inside the `Route::middleware(['auth','admin'])->prefix('admin')->name('admin.')->group(...)` block.
- Tests: Pest, factories for setup, use `$this->actingAs($admin)`, assert `$response->assertInertia(fn ($page) => $page->component('...')`).
- Run `vendor/bin/pint --dirty --format agent` before every commit.
- Run the minimal test filter after each change: `php artisan test --compact --filter=<TestName>`.
- Do not edit files outside the declared `Files:` block of the current task.

---

## File Structure (Phase 1)

New files to create:

```
app/Enums/BibleStudyThemeStatus.php
app/Models/BibleStudyTheme.php
app/Models/BibleStudyThemePassage.php
app/Models/BibleStudyInsight.php
app/Models/BibleStudyHistoricalContext.php
app/Models/BibleStudyWordHighlight.php
app/Models/BibleStudyReflection.php
app/Models/BibleStudyThemeRequest.php
app/Models/BibleStudySession.php

database/factories/BibleStudyThemeFactory.php
database/factories/BibleStudyThemePassageFactory.php
database/factories/BibleStudyInsightFactory.php
database/factories/BibleStudyHistoricalContextFactory.php
database/factories/BibleStudyWordHighlightFactory.php
database/factories/BibleStudyReflectionFactory.php
database/factories/BibleStudyThemeRequestFactory.php
database/factories/BibleStudySessionFactory.php

database/migrations/<ts>_create_bible_study_themes_table.php
database/migrations/<ts>_create_bible_study_theme_passages_table.php
database/migrations/<ts>_create_bible_study_insights_table.php
database/migrations/<ts>_create_bible_study_historical_contexts_table.php
database/migrations/<ts>_create_bible_study_word_highlights_table.php
database/migrations/<ts>_create_bible_study_reflections_table.php
database/migrations/<ts>_create_bible_study_theme_requests_table.php
database/migrations/<ts>_create_bible_study_sessions_table.php
database/migrations/<ts>_add_bible_study_partner_share_to_notification_preferences_table.php

app/Ai/Agents/BibleStudyThemeDrafter.php
app/Jobs/DraftBibleStudyThemeJob.php
app/Actions/BibleStudy/DraftBibleStudyTheme.php
app/Actions/BibleStudy/PublishBibleStudyTheme.php

app/Http/Controllers/Admin/BibleStudy/ThemeController.php
app/Http/Controllers/Admin/BibleStudy/PassageController.php
app/Http/Controllers/Admin/BibleStudy/InsightController.php
app/Http/Controllers/Admin/BibleStudy/HistoricalContextController.php
app/Http/Controllers/Admin/BibleStudy/WordHighlightController.php

app/Http/Requests/Admin/BibleStudy/StoreDraftRequest.php
app/Http/Requests/Admin/BibleStudy/UpdateThemeRequest.php
app/Http/Requests/Admin/BibleStudy/StorePassageRequest.php
app/Http/Requests/Admin/BibleStudy/UpdatePassageRequest.php
app/Http/Requests/Admin/BibleStudy/UpdateInsightRequest.php
app/Http/Requests/Admin/BibleStudy/UpdateHistoricalContextRequest.php
app/Http/Requests/Admin/BibleStudy/StoreWordHighlightRequest.php

resources/js/pages/admin/bible-study/themes/index.tsx
resources/js/pages/admin/bible-study/themes/show.tsx

database/seeders/BibleStudyThemeSeeder.php

tests/Feature/BibleStudy/…        (per task)
tests/Unit/BibleStudy/…            (per task)
```

Files to modify:

```
routes/web.php                                    (append new admin routes)
app/Models/NotificationPreference.php             (cast new boolean)
app/Actions/SendPartnerNotification.php           (add new match arm — but NOT Phase 1; defer to Phase 3)
app/Models/User.php                               (no change — keep Phase-scoped)
```

**Scope boundary reminder:** Phase 1 adds the `bible_study_partner_share` preference column so later phases can rely on it, but does not add the notification class or wire it into `SendPartnerNotification`. Both come in Phase 3.

---

## Task List

Tasks are grouped by coherent unit (migration + model + factory + model-level test ship together). Each task ends with a commit. Follow TDD: write the failing test first, run to confirm failure, implement, run to confirm pass, commit.

- [ ] Task 1 — `BibleStudyThemeStatus` enum + `bible_study_themes` migration/model/factory
- [ ] Task 2 — `bible_study_theme_passages` migration/model/factory (+ parent relation)
- [ ] Task 3 — `bible_study_insights` migration/model/factory
- [ ] Task 4 — `bible_study_historical_contexts` migration/model/factory
- [ ] Task 5 — `bible_study_word_highlights` migration/model/factory
- [ ] Task 6 — `bible_study_reflections` migration/model/factory
- [ ] Task 7 — `bible_study_theme_requests` migration/model/factory
- [ ] Task 8 — `bible_study_sessions` migration/model/factory
- [ ] Task 9 — Add `bible_study_partner_share` preference column
- [ ] Task 10 — `BibleStudyThemeDrafter` AI agent
- [ ] Task 11 — `DraftBibleStudyTheme` action + `DraftBibleStudyThemeJob` queued job
- [ ] Task 12 — `PublishBibleStudyTheme` action
- [ ] Task 13 — Admin routes skeleton + `ThemeController@index` (queue)
- [ ] Task 14 — `ThemeController@show` (review payload)
- [ ] Task 15 — `ThemeController@storeDraft` (manual trigger) + `ThemeController@update` (edit meta) + `ThemeController@publish` + `ThemeController@destroy`
- [ ] Task 16 — `PassageController` store/update/destroy/reorder
- [ ] Task 17 — `InsightController@update`
- [ ] Task 18 — `HistoricalContextController@update`
- [ ] Task 19 — `WordHighlightController` store/destroy
- [ ] Task 20 — Admin Inertia page: themes/index (queue)
- [ ] Task 21 — Admin Inertia page: themes/show (review)
- [ ] Task 22 — `BibleStudyThemeSeeder` — one approved "Resilience" theme
- [ ] Task 23 — End-to-end smoke test: manual draft → edit → publish

---

## Task 1 — `BibleStudyThemeStatus` enum + `bible_study_themes`

**Files:**
- Create: `app/Enums/BibleStudyThemeStatus.php`
- Create: `database/migrations/<ts>_create_bible_study_themes_table.php` (timestamp: use `php artisan make:migration`)
- Create: `app/Models/BibleStudyTheme.php`
- Create: `database/factories/BibleStudyThemeFactory.php`
- Test: `tests/Unit/BibleStudy/BibleStudyThemeTest.php`

- [ ] **Step 1: Write the failing unit test**

Create `tests/Unit/BibleStudy/BibleStudyThemeTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;

it('casts status to the enum', function (): void {
    $theme = BibleStudyTheme::factory()->create(['status' => BibleStudyThemeStatus::Draft]);

    expect($theme->status)->toBe(BibleStudyThemeStatus::Draft);
});

it('scopes approved themes', function (): void {
    BibleStudyTheme::factory()->draft()->create();
    BibleStudyTheme::factory()->approved()->create();

    expect(BibleStudyTheme::query()->where('status', BibleStudyThemeStatus::Approved)->count())->toBe(1);
});

it('has a unique slug', function (): void {
    BibleStudyTheme::factory()->create(['slug' => 'wisdom']);

    expect(fn () => BibleStudyTheme::factory()->create(['slug' => 'wisdom']))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=BibleStudyThemeTest`
Expected: FAIL — "Class App\Models\BibleStudyTheme not found" or similar.

- [ ] **Step 3: Create the enum**

Create `app/Enums/BibleStudyThemeStatus.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum BibleStudyThemeStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Archived = 'archived';
}
```

- [ ] **Step 4: Generate migration and fill it in**

Run: `php artisan make:migration create_bible_study_themes_table --no-interaction`

Open the created file and replace body with:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_themes', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('short_description');
            $table->text('long_intro');
            $table->string('status', 16)->index();
            $table->unsignedInteger('requested_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_themes');
    }
};
```

- [ ] **Step 5: Create the model**

Create `app/Models/BibleStudyTheme.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BibleStudyThemeStatus;
use Carbon\CarbonInterface;
use Database\Factories\BibleStudyThemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $slug
 * @property string $title
 * @property string $short_description
 * @property string $long_intro
 * @property BibleStudyThemeStatus $status
 * @property int $requested_count
 * @property CarbonInterface|null $approved_at
 * @property int|null $approved_by_user_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyTheme extends Model
{
    /** @use HasFactory<BibleStudyThemeFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'status' => BibleStudyThemeStatus::class,
            'requested_count' => 'integer',
            'approved_at' => 'datetime',
            'approved_by_user_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 6: Create the factory**

Create `database/factories/BibleStudyThemeFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BibleStudyTheme>
 */
final class BibleStudyThemeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'title' => ucfirst($title),
            'short_description' => fake()->sentence(),
            'long_intro' => fake()->paragraphs(2, true),
            'status' => BibleStudyThemeStatus::Draft,
            'requested_count' => 0,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (): array => [
            'status' => BibleStudyThemeStatus::Draft,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (): array => [
            'status' => BibleStudyThemeStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function archived(): self
    {
        return $this->state(fn (): array => [
            'status' => BibleStudyThemeStatus::Archived,
        ]);
    }
}
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --compact --filter=BibleStudyThemeTest`
Expected: PASS (3 tests).

- [ ] **Step 8: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/BibleStudyThemeStatus.php app/Models/BibleStudyTheme.php database/factories/BibleStudyThemeFactory.php database/migrations/*_create_bible_study_themes_table.php tests/Unit/BibleStudy/BibleStudyThemeTest.php
git commit -m "feat(bible-study): add BibleStudyTheme model and migration"
```

---

## Task 2 — `bible_study_theme_passages`

**Files:**
- Create: `database/migrations/<ts>_create_bible_study_theme_passages_table.php`
- Create: `app/Models/BibleStudyThemePassage.php`
- Create: `database/factories/BibleStudyThemePassageFactory.php`
- Modify: `app/Models/BibleStudyTheme.php` (add `passages()` hasMany relation)
- Test: `tests/Unit/BibleStudy/BibleStudyThemePassageTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/BibleStudy/BibleStudyThemePassageTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;

it('belongs to a theme', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create();

    expect($passage->theme->is($theme))->toBeTrue();
});

it('a theme has many passages ordered by position', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 2]);
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 1]);

    $positions = $theme->passages()->orderBy('position')->pluck('position')->all();

    expect($positions)->toBe([1, 2]);
});

it('enforces uniqueness of passage range within a theme', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22,
    ]);

    expect(fn () => BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22,
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=BibleStudyThemePassageTest`
Expected: FAIL.

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_bible_study_theme_passages_table --no-interaction`

Replace body:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_theme_passages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_study_theme_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->boolean('is_guided_path')->default(false);
            $table->string('book');
            $table->unsignedInteger('chapter');
            $table->unsignedInteger('verse_start');
            $table->unsignedInteger('verse_end')->nullable();
            $table->text('passage_intro')->nullable();
            $table->timestamps();

            $table->unique(
                ['bible_study_theme_id', 'book', 'chapter', 'verse_start', 'verse_end'],
                'bsp_unique_range',
            );
            $table->index(['book', 'chapter', 'verse_start', 'verse_end'], 'bsp_reverse_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_theme_passages');
    }
};
```

- [ ] **Step 4: Create the model**

Create `app/Models/BibleStudyThemePassage.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyThemePassageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $bible_study_theme_id
 * @property int $position
 * @property bool $is_guided_path
 * @property string $book
 * @property int $chapter
 * @property int $verse_start
 * @property int|null $verse_end
 * @property string|null $passage_intro
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyThemePassage extends Model
{
    /** @use HasFactory<BibleStudyThemePassageFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<BibleStudyTheme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(BibleStudyTheme::class, 'bible_study_theme_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'bible_study_theme_id' => 'integer',
            'position' => 'integer',
            'is_guided_path' => 'boolean',
            'chapter' => 'integer',
            'verse_start' => 'integer',
            'verse_end' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 5: Create the factory**

Create `database/factories/BibleStudyThemePassageFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyThemePassage>
 */
final class BibleStudyThemePassageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->numberBetween(1, 20);

        return [
            'bible_study_theme_id' => BibleStudyTheme::factory(),
            'position' => fake()->unique()->numberBetween(1, 10000),
            'is_guided_path' => false,
            'book' => fake()->randomElement(['Job', 'Psalms', 'Proverbs', 'Matthew', 'Romans']),
            'chapter' => fake()->numberBetween(1, 50),
            'verse_start' => $start,
            'verse_end' => $start + fake()->numberBetween(0, 10),
            'passage_intro' => fake()->sentence(),
        ];
    }

    public function guided(): self
    {
        return $this->state(fn (): array => ['is_guided_path' => true]);
    }
}
```

- [ ] **Step 6: Add `passages()` relation to `BibleStudyTheme`**

In `app/Models/BibleStudyTheme.php`, add import and relation method after `approvedBy`:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

```php
/**
 * @return HasMany<BibleStudyThemePassage, $this>
 */
public function passages(): HasMany
{
    return $this->hasMany(BibleStudyThemePassage::class, 'bible_study_theme_id')->orderBy('position');
}
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --compact --filter=BibleStudyThemePassageTest`
Expected: PASS (3 tests).

- [ ] **Step 8: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/BibleStudyTheme.php app/Models/BibleStudyThemePassage.php database/factories/BibleStudyThemePassageFactory.php database/migrations/*_create_bible_study_theme_passages_table.php tests/Unit/BibleStudy/BibleStudyThemePassageTest.php
git commit -m "feat(bible-study): add BibleStudyThemePassage model with theme relation"
```

---

## Task 3 — `bible_study_insights`

**Files:**
- Create: `database/migrations/<ts>_create_bible_study_insights_table.php`
- Create: `app/Models/BibleStudyInsight.php`
- Create: `database/factories/BibleStudyInsightFactory.php`
- Modify: `app/Models/BibleStudyThemePassage.php` (add `insight()` hasOne relation)
- Test: `tests/Unit/BibleStudy/BibleStudyInsightTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/BibleStudy/BibleStudyInsightTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyInsight;
use App\Models\BibleStudyThemePassage;

it('stores cross_references as an array of objects', function (): void {
    $insight = BibleStudyInsight::factory()->create([
        'cross_references' => [
            ['book' => 'Romans', 'chapter' => 8, 'verse_start' => 18, 'verse_end' => 30, 'note' => 'Endurance'],
        ],
    ]);

    expect($insight->cross_references)->toBeArray()
        ->and($insight->cross_references[0]['book'])->toBe('Romans');
});

it('belongs to a passage (one-to-one)', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    $insight = BibleStudyInsight::factory()->for($passage, 'passage')->create();

    expect($insight->passage->is($passage))->toBeTrue()
        ->and($passage->insight->is($insight))->toBeTrue();
});

it('cannot have two insights for the same passage', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    BibleStudyInsight::factory()->for($passage, 'passage')->create();

    expect(fn () => BibleStudyInsight::factory()->for($passage, 'passage')->create())
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=BibleStudyInsightTest`
Expected: FAIL.

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_bible_study_insights_table --no-interaction`

Replace body:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_insights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_study_theme_passage_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('interpretation');
            $table->text('application');
            $table->json('cross_references');
            $table->text('literary_context');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_insights');
    }
};
```

- [ ] **Step 4: Create the model**

Create `app/Models/BibleStudyInsight.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyInsightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $bible_study_theme_passage_id
 * @property string $interpretation
 * @property string $application
 * @property array<int, array{book: string, chapter: int, verse_start: int, verse_end?: int, note?: string}> $cross_references
 * @property string $literary_context
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyInsight extends Model
{
    /** @use HasFactory<BibleStudyInsightFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<BibleStudyThemePassage, $this>
     */
    public function passage(): BelongsTo
    {
        return $this->belongsTo(BibleStudyThemePassage::class, 'bible_study_theme_passage_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'bible_study_theme_passage_id' => 'integer',
            'cross_references' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 5: Create factory**

Create `database/factories/BibleStudyInsightFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyInsight;
use App\Models\BibleStudyThemePassage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyInsight>
 */
final class BibleStudyInsightFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bible_study_theme_passage_id' => BibleStudyThemePassage::factory(),
            'interpretation' => fake()->paragraph(),
            'application' => fake()->paragraph(),
            'cross_references' => [
                ['book' => 'Romans', 'chapter' => 8, 'verse_start' => 18, 'verse_end' => 30, 'note' => fake()->sentence()],
            ],
            'literary_context' => fake()->paragraph(),
        ];
    }
}
```

- [ ] **Step 6: Add `insight()` relation to `BibleStudyThemePassage`**

In `app/Models/BibleStudyThemePassage.php`, add import and relation after `theme()`:

```php
use Illuminate\Database\Eloquent\Relations\HasOne;
```

```php
/**
 * @return HasOne<BibleStudyInsight, $this>
 */
public function insight(): HasOne
{
    return $this->hasOne(BibleStudyInsight::class, 'bible_study_theme_passage_id');
}
```

- [ ] **Step 7: Run test**

Run: `php artisan test --compact --filter=BibleStudyInsightTest`
Expected: PASS (3 tests).

- [ ] **Step 8: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/BibleStudyInsight.php app/Models/BibleStudyThemePassage.php database/factories/BibleStudyInsightFactory.php database/migrations/*_create_bible_study_insights_table.php tests/Unit/BibleStudy/BibleStudyInsightTest.php
git commit -m "feat(bible-study): add BibleStudyInsight model with passage relation"
```

---

## Task 4 — `bible_study_historical_contexts`

**Files:**
- Create: migration, `app/Models/BibleStudyHistoricalContext.php`, factory
- Modify: `app/Models/BibleStudyThemePassage.php` (add `historicalContext()` hasOne)
- Test: `tests/Unit/BibleStudy/BibleStudyHistoricalContextTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyThemePassage;

it('stores structured historical fields', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    $context = BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create([
        'author' => 'Matthew',
        'date_range' => 'ca. 70–90 AD',
    ]);

    expect($context->author)->toBe('Matthew')
        ->and($context->date_range)->toBe('ca. 70–90 AD')
        ->and($passage->historicalContext->is($context))->toBeTrue();
});

it('is unique per passage', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create();

    expect(fn () => BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create())
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
```

- [ ] **Step 2: Run test — FAIL**

Run: `php artisan test --compact --filter=BibleStudyHistoricalContextTest`

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_bible_study_historical_contexts_table --no-interaction`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_historical_contexts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_study_theme_passage_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('setting');
            $table->string('author');
            $table->string('date_range');
            $table->text('audience');
            $table->text('historical_events');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_historical_contexts');
    }
};
```

- [ ] **Step 4: Create model**

`app/Models/BibleStudyHistoricalContext.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyHistoricalContextFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $bible_study_theme_passage_id
 * @property string $setting
 * @property string $author
 * @property string $date_range
 * @property string $audience
 * @property string $historical_events
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyHistoricalContext extends Model
{
    /** @use HasFactory<BibleStudyHistoricalContextFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<BibleStudyThemePassage, $this>
     */
    public function passage(): BelongsTo
    {
        return $this->belongsTo(BibleStudyThemePassage::class, 'bible_study_theme_passage_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'bible_study_theme_passage_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 5: Factory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyThemePassage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyHistoricalContext>
 */
final class BibleStudyHistoricalContextFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bible_study_theme_passage_id' => BibleStudyThemePassage::factory(),
            'setting' => fake()->sentence(),
            'author' => fake()->name(),
            'date_range' => 'ca. '.fake()->numberBetween(100, 2000).' BC',
            'audience' => fake()->sentence(),
            'historical_events' => fake()->paragraph(),
        ];
    }
}
```

- [ ] **Step 6: Add `historicalContext()` relation to `BibleStudyThemePassage`**

```php
/**
 * @return HasOne<BibleStudyHistoricalContext, $this>
 */
public function historicalContext(): HasOne
{
    return $this->hasOne(BibleStudyHistoricalContext::class, 'bible_study_theme_passage_id');
}
```

- [ ] **Step 7: Test passes**

Run: `php artisan test --compact --filter=BibleStudyHistoricalContextTest`
Expected: PASS.

- [ ] **Step 8: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A app/Models/BibleStudyHistoricalContext.php app/Models/BibleStudyThemePassage.php database/factories/BibleStudyHistoricalContextFactory.php database/migrations/*_create_bible_study_historical_contexts_table.php tests/Unit/BibleStudy/BibleStudyHistoricalContextTest.php
git commit -m "feat(bible-study): add BibleStudyHistoricalContext model"
```

---

## Task 5 — `bible_study_word_highlights`

**Files:**
- Create: migration, model, factory
- Modify: `app/Models/BibleStudyThemePassage.php` (add `wordHighlights()` hasMany)
- Test: `tests/Unit/BibleStudy/BibleStudyWordHighlightTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\WordStudy;

it('links a passage to an existing word study', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    $wordStudy = WordStudy::factory()->create();

    $highlight = BibleStudyWordHighlight::factory()
        ->for($passage, 'passage')
        ->for($wordStudy, 'wordStudy')
        ->create(['verse_number' => 20, 'word_index_in_verse' => 3, 'display_word' => 'worship']);

    expect($highlight->passage->is($passage))->toBeTrue()
        ->and($highlight->wordStudy->is($wordStudy))->toBeTrue();
});

it('is unique on passage + verse + word index', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    BibleStudyWordHighlight::factory()->for($passage, 'passage')->create([
        'verse_number' => 20, 'word_index_in_verse' => 3,
    ]);

    expect(fn () => BibleStudyWordHighlight::factory()->for($passage, 'passage')->create([
        'verse_number' => 20, 'word_index_in_verse' => 3,
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
```

- [ ] **Step 2: Run — FAIL**

Run: `php artisan test --compact --filter=BibleStudyWordHighlightTest`

- [ ] **Step 3: Migration**

Run: `php artisan make:migration create_bible_study_word_highlights_table --no-interaction`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_word_highlights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_study_theme_passage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_study_id')->constrained('word_studies')->cascadeOnDelete();
            $table->unsignedInteger('verse_number');
            $table->unsignedInteger('word_index_in_verse');
            $table->string('display_word');
            $table->timestamps();

            $table->unique(
                ['bible_study_theme_passage_id', 'verse_number', 'word_index_in_verse'],
                'bswh_unique_position',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_word_highlights');
    }
};
```

- [ ] **Step 4: Model**

`app/Models/BibleStudyWordHighlight.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyWordHighlightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $bible_study_theme_passage_id
 * @property int $word_study_id
 * @property int $verse_number
 * @property int $word_index_in_verse
 * @property string $display_word
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyWordHighlight extends Model
{
    /** @use HasFactory<BibleStudyWordHighlightFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<BibleStudyThemePassage, $this>
     */
    public function passage(): BelongsTo
    {
        return $this->belongsTo(BibleStudyThemePassage::class, 'bible_study_theme_passage_id');
    }

    /**
     * @return BelongsTo<WordStudy, $this>
     */
    public function wordStudy(): BelongsTo
    {
        return $this->belongsTo(WordStudy::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'bible_study_theme_passage_id' => 'integer',
            'word_study_id' => 'integer',
            'verse_number' => 'integer',
            'word_index_in_verse' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 5: Factory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\WordStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyWordHighlight>
 */
final class BibleStudyWordHighlightFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bible_study_theme_passage_id' => BibleStudyThemePassage::factory(),
            'word_study_id' => WordStudy::factory(),
            'verse_number' => fake()->numberBetween(1, 40),
            'word_index_in_verse' => fake()->numberBetween(0, 20),
            'display_word' => fake()->word(),
        ];
    }
}
```

- [ ] **Step 6: Add `wordHighlights()` relation on `BibleStudyThemePassage`**

```php
/**
 * @return HasMany<BibleStudyWordHighlight, $this>
 */
public function wordHighlights(): HasMany
{
    return $this->hasMany(BibleStudyWordHighlight::class, 'bible_study_theme_passage_id')
        ->orderBy('verse_number')
        ->orderBy('word_index_in_verse');
}
```

Also import `Illuminate\Database\Eloquent\Relations\HasMany;` if not already present.

- [ ] **Step 7: Test passes**

Run: `php artisan test --compact --filter=BibleStudyWordHighlightTest`

- [ ] **Step 8: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A app/Models/BibleStudyWordHighlight.php app/Models/BibleStudyThemePassage.php database/factories/BibleStudyWordHighlightFactory.php database/migrations/*_create_bible_study_word_highlights_table.php tests/Unit/BibleStudy/BibleStudyWordHighlightTest.php
git commit -m "feat(bible-study): add BibleStudyWordHighlight model"
```

---

## Task 6 — `bible_study_reflections`

**Files:** migration, model, factory, test.

- [ ] **Step 1: Failing test** — `tests/Unit/BibleStudy/BibleStudyReflectionTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('distinguishes passage vs verse scope by verse_number null', function (): void {
    $user = User::factory()->create();
    $passageLevel = BibleStudyReflection::factory()->for($user)->create(['verse_number' => null]);
    $verseLevel = BibleStudyReflection::factory()->for($user)->create(['verse_number' => 3]);

    expect($passageLevel->verse_number)->toBeNull()
        ->and($verseLevel->verse_number)->toBe(3);
});

it('defaults share flag to false', function (): void {
    $reflection = BibleStudyReflection::factory()->create();

    expect($reflection->is_shared_with_partner)->toBeFalse();
});

it('optionally links to a theme', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    $reflection = BibleStudyReflection::factory()->for($theme, 'theme')->create();

    expect($reflection->theme->is($theme))->toBeTrue();
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Migration** — `php artisan make:migration create_bible_study_reflections_table --no-interaction`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_reflections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bible_study_theme_id')->nullable()->constrained()->nullOnDelete();
            $table->string('book');
            $table->unsignedInteger('chapter');
            $table->unsignedInteger('verse_start');
            $table->unsignedInteger('verse_end')->nullable();
            $table->unsignedInteger('verse_number')->nullable();
            $table->text('body');
            $table->boolean('is_shared_with_partner')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'book', 'chapter'], 'bsr_user_passage');
            $table->index(['book', 'chapter', 'verse_start'], 'bsr_partner_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_reflections');
    }
};
```

- [ ] **Step 4: Model** — `app/Models/BibleStudyReflection.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyReflectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $user_id
 * @property int|null $bible_study_theme_id
 * @property string $book
 * @property int $chapter
 * @property int $verse_start
 * @property int|null $verse_end
 * @property int|null $verse_number
 * @property string $body
 * @property bool $is_shared_with_partner
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyReflection extends Model
{
    /** @use HasFactory<BibleStudyReflectionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BibleStudyTheme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(BibleStudyTheme::class, 'bible_study_theme_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'bible_study_theme_id' => 'integer',
            'chapter' => 'integer',
            'verse_start' => 'integer',
            'verse_end' => 'integer',
            'verse_number' => 'integer',
            'is_shared_with_partner' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 5: Factory** — `database/factories/BibleStudyReflectionFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyReflection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyReflection>
 */
final class BibleStudyReflectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bible_study_theme_id' => null,
            'book' => 'Job',
            'chapter' => 1,
            'verse_start' => 13,
            'verse_end' => 22,
            'verse_number' => null,
            'body' => fake()->paragraph(),
            'is_shared_with_partner' => false,
        ];
    }

    public function shared(): self
    {
        return $this->state(fn (): array => ['is_shared_with_partner' => true]);
    }
}
```

- [ ] **Step 6: Test passes**

Run: `php artisan test --compact --filter=BibleStudyReflectionTest`

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A app/Models/BibleStudyReflection.php database/factories/BibleStudyReflectionFactory.php database/migrations/*_create_bible_study_reflections_table.php tests/Unit/BibleStudy/BibleStudyReflectionTest.php
git commit -m "feat(bible-study): add BibleStudyReflection model"
```

---

## Task 7 — `bible_study_theme_requests`

**Files:** migration, model, factory, test.

- [ ] **Step 1: Failing test** — `tests/Unit/BibleStudy/BibleStudyThemeRequestTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemeRequest;
use App\Models\User;

it('stores a normalized query', function (): void {
    $request = BibleStudyThemeRequest::factory()->create([
        'search_query' => 'Forgiveness ',
        'normalized_query' => 'forgiveness',
    ]);

    expect($request->normalized_query)->toBe('forgiveness');
});

it('optionally links to a generated draft theme', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    $request = BibleStudyThemeRequest::factory()
        ->for($user)
        ->for($theme, 'generatedTheme')
        ->create();

    expect($request->generatedTheme->is($theme))->toBeTrue();
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Migration** — `php artisan make:migration create_bible_study_theme_requests_table --no-interaction`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_theme_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('search_query');
            $table->string('normalized_query')->index();
            $table->foreignId('generated_bible_study_theme_id')->nullable()->constrained('bible_study_themes')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_theme_requests');
    }
};
```

- [ ] **Step 4: Model** — `app/Models/BibleStudyThemeRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyThemeRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $user_id
 * @property string $search_query
 * @property string $normalized_query
 * @property int|null $generated_bible_study_theme_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyThemeRequest extends Model
{
    /** @use HasFactory<BibleStudyThemeRequestFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BibleStudyTheme, $this>
     */
    public function generatedTheme(): BelongsTo
    {
        return $this->belongsTo(BibleStudyTheme::class, 'generated_bible_study_theme_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'generated_bible_study_theme_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 5: Factory** — `database/factories/BibleStudyThemeRequestFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyThemeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyThemeRequest>
 */
final class BibleStudyThemeRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $query = fake()->word();

        return [
            'user_id' => User::factory(),
            'search_query' => ucfirst($query),
            'normalized_query' => mb_strtolower(trim($query)),
            'generated_bible_study_theme_id' => null,
        ];
    }
}
```

- [ ] **Step 6: Test passes**

Run: `php artisan test --compact --filter=BibleStudyThemeRequestTest`

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A app/Models/BibleStudyThemeRequest.php database/factories/BibleStudyThemeRequestFactory.php database/migrations/*_create_bible_study_theme_requests_table.php tests/Unit/BibleStudy/BibleStudyThemeRequestTest.php
git commit -m "feat(bible-study): add BibleStudyThemeRequest model"
```

---

## Task 8 — `bible_study_sessions`

**Files:** migration, model, factory, test.

- [ ] **Step 1: Failing test** — `tests/Unit/BibleStudy/BibleStudySessionTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudySession;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('allows at most one session per user', function (): void {
    $user = User::factory()->create();
    BibleStudySession::factory()->for($user)->create();

    expect(fn () => BibleStudySession::factory()->for($user)->create())
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('is theme-linkable but ad-hoc is allowed (theme_id null)', function (): void {
    $session = BibleStudySession::factory()->create(['bible_study_theme_id' => null]);

    expect($session->bible_study_theme_id)->toBeNull();
});

it('optionally belongs to a theme', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    $session = BibleStudySession::factory()->for($theme, 'theme')->create();

    expect($session->theme->is($theme))->toBeTrue();
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Migration** — `php artisan make:migration create_bible_study_sessions_table --no-interaction`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('bible_study_theme_id')->nullable()->constrained()->nullOnDelete();
            $table->string('current_book');
            $table->unsignedInteger('current_chapter');
            $table->unsignedInteger('current_verse_start');
            $table->unsignedInteger('current_verse_end')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_accessed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_sessions');
    }
};
```

- [ ] **Step 4: Model** — `app/Models/BibleStudySession.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudySessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $user_id
 * @property int|null $bible_study_theme_id
 * @property string $current_book
 * @property int $current_chapter
 * @property int $current_verse_start
 * @property int|null $current_verse_end
 * @property CarbonInterface $started_at
 * @property CarbonInterface $last_accessed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudySession extends Model
{
    /** @use HasFactory<BibleStudySessionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BibleStudyTheme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(BibleStudyTheme::class, 'bible_study_theme_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'bible_study_theme_id' => 'integer',
            'current_chapter' => 'integer',
            'current_verse_start' => 'integer',
            'current_verse_end' => 'integer',
            'started_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 5: Factory** — `database/factories/BibleStudySessionFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudySession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudySession>
 */
final class BibleStudySessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bible_study_theme_id' => null,
            'current_book' => 'Job',
            'current_chapter' => 1,
            'current_verse_start' => 13,
            'current_verse_end' => 22,
            'started_at' => now(),
            'last_accessed_at' => now(),
        ];
    }
}
```

- [ ] **Step 6: Test passes**

Run: `php artisan test --compact --filter=BibleStudySessionTest`

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A app/Models/BibleStudySession.php database/factories/BibleStudySessionFactory.php database/migrations/*_create_bible_study_sessions_table.php tests/Unit/BibleStudy/BibleStudySessionTest.php
git commit -m "feat(bible-study): add BibleStudySession model"
```

---

## Task 9 — Add `bible_study_partner_share` preference column

Spec §4.7: reuse existing column-per-key pattern.

**Files:**
- Create: `database/migrations/<ts>_add_bible_study_partner_share_to_notification_preferences_table.php`
- Modify: `app/Models/NotificationPreference.php` (add cast)
- Test: `tests/Unit/NotificationPreferenceBibleStudyPartnerShareTest.php`

- [ ] **Step 1: Failing test**

Create `tests/Unit/NotificationPreferenceBibleStudyPartnerShareTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\NotificationPreference;
use App\Models\User;

it('defaults bible_study_partner_share to true', function (): void {
    $user = User::factory()->create();
    $pref = NotificationPreference::query()->create(['user_id' => $user->id]);

    expect($pref->bible_study_partner_share_notifications)->toBeTrue();
});

it('casts bible_study_partner_share column to boolean', function (): void {
    $user = User::factory()->create();
    $pref = NotificationPreference::query()->create([
        'user_id' => $user->id,
        'bible_study_partner_share_notifications' => false,
    ]);

    expect($pref->bible_study_partner_share_notifications)->toBeFalse();
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Migration** — `php artisan make:migration add_bible_study_partner_share_to_notification_preferences_table --no-interaction`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->boolean('bible_study_partner_share_notifications')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->dropColumn('bible_study_partner_share_notifications');
        });
    }
};
```

- [ ] **Step 4: Update `NotificationPreference` casts**

Open `app/Models/NotificationPreference.php` and add the new key to the `casts()` return array:

```php
'bible_study_partner_share_notifications' => 'boolean',
```

Place it alongside the other boolean casts.

- [ ] **Step 5: Test passes**

Run: `php artisan test --compact --filter=NotificationPreferenceBibleStudyPartnerShareTest`

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/NotificationPreference.php database/migrations/*_add_bible_study_partner_share_to_notification_preferences_table.php tests/Unit/NotificationPreferenceBibleStudyPartnerShareTest.php
git commit -m "feat(bible-study): add bible_study_partner_share notification preference"
```

---

## Task 10 — `BibleStudyThemeDrafter` AI agent

**Files:**
- Create: `app/Ai/Agents/BibleStudyThemeDrafter.php`
- Test: `tests/Unit/BibleStudy/BibleStudyThemeDrafterTest.php`

The agent declares structured output matching the shape in spec §4.4. Testing focuses on the schema shape (instructions are prose and reviewed by eye).

- [ ] **Step 1: Failing test**

Create `tests/Unit/BibleStudy/BibleStudyThemeDrafterTest.php`:

```php
<?php

declare(strict_types=1);

use App\Ai\Agents\BibleStudyThemeDrafter;
use Illuminate\Support\Facades\App;

it('declares the theme-draft structured output schema', function (): void {
    $agent = new BibleStudyThemeDrafter;
    $schema = $agent->schema(App::make(Illuminate\Contracts\JsonSchema\JsonSchema::class));

    expect($schema)->toHaveKeys(['slug', 'short_description', 'long_intro', 'passages']);
});

it('provides instructions', function (): void {
    $agent = new BibleStudyThemeDrafter;

    expect($agent->instructions())->toBeString()->not->toBe('');
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Create the agent**

Create `app/Ai/Agents/BibleStudyThemeDrafter.php`:

```php
<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[Timeout(180)]
final class BibleStudyThemeDrafter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        You are drafting a Bible study "theme" for a Christian devotional application.
        A theme is a topical study (e.g., "wisdom", "resilience", "forgiveness") with 8-15 associated scripture passages.

        For the given theme title, produce:
        - A URL-friendly slug.
        - A one-sentence short description.
        - A 2-3 paragraph long introduction that explains the biblical shape of the theme.
        - A list of 8-15 scripture passages across the Old and New Testament that substantively develop this theme.

        For each passage:
        - Book, chapter, verse_start, and (if multi-verse) verse_end.
        - A sequential position starting at 1.
        - Mark 5-7 passages as is_guided_path=true to form an ordered walkthrough.
        - A 1-2 sentence passage_intro explaining how this passage develops the theme.
        - Insights: interpretation (plain-sense meaning), application (practical framing), cross_references (2-5 related passages with short notes), literary_context (how this sits in the surrounding argument).
        - Historical context: setting, author, date_range, audience, historical_events.
        - Suggested word highlights: 2-5 notable Hebrew or Greek words in the passage with verse_number, display_word (as rendered in English), original_root_hint (the Hebrew or Greek word), and a short rationale.

        Draw only on mainstream biblical scholarship. Be faithful to the text. Write in a warm, accessible register suited to laypeople doing devotional study.
        INSTRUCTIONS;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        $crossReference = $schema->object([
            'book' => $schema->string()->required(),
            'chapter' => $schema->integer()->required(),
            'verse_start' => $schema->integer()->required(),
            'verse_end' => $schema->integer(),
            'note' => $schema->string()->required(),
        ]);

        $wordHighlight = $schema->object([
            'verse_number' => $schema->integer()->required(),
            'display_word' => $schema->string()->required(),
            'original_root_hint' => $schema->string()->required(),
            'rationale' => $schema->string()->required(),
        ]);

        $passage = $schema->object([
            'book' => $schema->string()->required(),
            'chapter' => $schema->integer()->required(),
            'verse_start' => $schema->integer()->required(),
            'verse_end' => $schema->integer(),
            'position' => $schema->integer()->required(),
            'is_guided_path' => $schema->boolean()->required(),
            'passage_intro' => $schema->string()->required(),
            'insights' => $schema->object([
                'interpretation' => $schema->string()->required(),
                'application' => $schema->string()->required(),
                'cross_references' => $schema->array()->items($crossReference)->required(),
                'literary_context' => $schema->string()->required(),
            ])->required(),
            'historical_context' => $schema->object([
                'setting' => $schema->string()->required(),
                'author' => $schema->string()->required(),
                'date_range' => $schema->string()->required(),
                'audience' => $schema->string()->required(),
                'historical_events' => $schema->string()->required(),
            ])->required(),
            'suggested_word_highlights' => $schema->array()->items($wordHighlight)->required(),
        ]);

        return [
            'slug' => $schema->string()->required(),
            'short_description' => $schema->string()->required(),
            'long_intro' => $schema->string()->required(),
            'passages' => $schema->array()->items($passage)->required(),
        ];
    }
}
```

- [ ] **Step 4: Test passes**

Run: `php artisan test --compact --filter=BibleStudyThemeDrafterTest`

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Ai/Agents/BibleStudyThemeDrafter.php tests/Unit/BibleStudy/BibleStudyThemeDrafterTest.php
git commit -m "feat(bible-study): add BibleStudyThemeDrafter AI agent"
```

---

## Task 11 — `DraftBibleStudyTheme` action + `DraftBibleStudyThemeJob`

**Files:**
- Create: `app/Actions/BibleStudy/DraftBibleStudyTheme.php`
- Create: `app/Jobs/DraftBibleStudyThemeJob.php`
- Test: `tests/Feature/BibleStudy/DraftBibleStudyThemeTest.php`

The action wraps agent invocation, persists a draft theme with children, and logs to `AiGenerationLog`. Suggested word highlights are stored on the passage as `raw_suggested_word_highlights` in the insight's cross-reference JSON? No — we need a place for them. Simplest: stash into the `AiGenerationLog.generated_content` so admin review UI can surface them. Passage rows do not materialize highlights yet — that happens on admin confirmation in Task 19.

- [ ] **Step 1: Failing feature test**

```php
<?php

declare(strict_types=1);

use App\Actions\BibleStudy\DraftBibleStudyTheme;
use App\Ai\Agents\BibleStudyThemeDrafter;
use App\Enums\AiGenerationStatus;
use App\Enums\BibleStudyThemeStatus;
use App\Models\AiGenerationLog;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\User;
use Laravel\Ai\Responses\StructuredAgentResponse;
use function Pest\Laravel\mock;

it('persists a draft theme with passages, insights, and historical context', function (): void {
    $admin = User::factory()->admin()->create();

    mock(BibleStudyThemeDrafter::class)
        ->shouldReceive('prompt')
        ->once()
        ->andReturn(new StructuredAgentResponse(fixtureDraftResponse()));

    $log = app(DraftBibleStudyTheme::class)->handle($admin, 'Resilience');

    expect($log->status)->toBe(AiGenerationStatus::Completed)
        ->and(BibleStudyTheme::query()->count())->toBe(1)
        ->and(BibleStudyTheme::query()->first()->status)->toBe(BibleStudyThemeStatus::Draft)
        ->and(BibleStudyInsight::query()->count())->toBe(1);
});

it('marks the log as failed on agent throw', function (): void {
    $admin = User::factory()->admin()->create();

    mock(BibleStudyThemeDrafter::class)
        ->shouldReceive('prompt')
        ->andThrow(new RuntimeException('AI unavailable'));

    $log = app(DraftBibleStudyTheme::class)->handle($admin, 'Resilience');

    expect($log->status)->toBe(AiGenerationStatus::Failed)
        ->and($log->error_message)->toBe('AI unavailable')
        ->and(BibleStudyTheme::query()->count())->toBe(0);
});

function fixtureDraftResponse(): array
{
    return [
        'slug' => 'resilience',
        'short_description' => 'Faith under pressure.',
        'long_intro' => "Resilience in scripture is not stoicism. It is faith that clings through loss.\n\nAcross the canon, God meets afflicted people in their waiting.",
        'passages' => [[
            'book' => 'Job',
            'chapter' => 1,
            'verse_start' => 13,
            'verse_end' => 22,
            'position' => 1,
            'is_guided_path' => true,
            'passage_intro' => 'Job responds to catastrophic loss with lament and worship.',
            'insights' => [
                'interpretation' => 'Job does not charge God with wrongdoing.',
                'application' => 'Lament and worship can coexist.',
                'cross_references' => [
                    ['book' => 'Lamentations', 'chapter' => 3, 'verse_start' => 19, 'verse_end' => 24, 'note' => 'Grief holds hope.'],
                ],
                'literary_context' => 'Prologue to the book of Job.',
            ],
            'historical_context' => [
                'setting' => 'Land of Uz.',
                'author' => 'Unknown',
                'date_range' => 'Pre-exilic',
                'audience' => 'Israelite wisdom audience.',
                'historical_events' => 'Job loses family and possessions.',
            ],
            'suggested_word_highlights' => [
                ['verse_number' => 20, 'display_word' => 'worship', 'original_root_hint' => 'שָׁחָה', 'rationale' => 'prostration before God.'],
            ],
        ]],
    ];
}
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Create the action**

Create `app/Actions/BibleStudy/DraftBibleStudyTheme.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Ai\Agents\BibleStudyThemeDrafter;
use App\Enums\AiGenerationStatus;
use App\Enums\BibleStudyThemeStatus;
use App\Models\AiGenerationLog;
use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

final readonly class DraftBibleStudyTheme
{
    public function __construct(private BibleStudyThemeDrafter $agent) {}

    public function handle(User $admin, string $themeTitle): AiGenerationLog
    {
        $log = AiGenerationLog::query()->create([
            'admin_id' => $admin->id,
            'prompt' => 'Draft Bible study theme: '.$themeTitle,
            'status' => AiGenerationStatus::Pending,
        ]);

        try {
            /** @var StructuredAgentResponse $response */
            $response = $this->agent->prompt($themeTitle);
            $content = $response->toArray();

            DB::transaction(function () use ($content, $themeTitle): void {
                $slug = $this->uniqueSlug($content['slug'] ?? Str::slug($themeTitle));

                $theme = BibleStudyTheme::query()->create([
                    'slug' => $slug,
                    'title' => ucfirst($themeTitle),
                    'short_description' => $content['short_description'],
                    'long_intro' => $content['long_intro'],
                    'status' => BibleStudyThemeStatus::Draft,
                    'requested_count' => 0,
                ]);

                foreach ($content['passages'] as $p) {
                    $passage = BibleStudyThemePassage::query()->create([
                        'bible_study_theme_id' => $theme->id,
                        'position' => $p['position'],
                        'is_guided_path' => $p['is_guided_path'] ?? false,
                        'book' => $p['book'],
                        'chapter' => $p['chapter'],
                        'verse_start' => $p['verse_start'],
                        'verse_end' => $p['verse_end'] ?? null,
                        'passage_intro' => $p['passage_intro'],
                    ]);

                    BibleStudyInsight::query()->create([
                        'bible_study_theme_passage_id' => $passage->id,
                        'interpretation' => $p['insights']['interpretation'],
                        'application' => $p['insights']['application'],
                        'cross_references' => $p['insights']['cross_references'],
                        'literary_context' => $p['insights']['literary_context'],
                    ]);

                    BibleStudyHistoricalContext::query()->create([
                        'bible_study_theme_passage_id' => $passage->id,
                        'setting' => $p['historical_context']['setting'],
                        'author' => $p['historical_context']['author'],
                        'date_range' => $p['historical_context']['date_range'],
                        'audience' => $p['historical_context']['audience'],
                        'historical_events' => $p['historical_context']['historical_events'],
                    ]);
                }
            });

            $log->update([
                'status' => AiGenerationStatus::Completed,
                'generated_content' => $content,
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => AiGenerationStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log->refresh();
    }

    private function uniqueSlug(string $proposed): string
    {
        $base = Str::slug($proposed);
        $slug = $base;
        $i = 1;

        while (BibleStudyTheme::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$i;
        }

        return $slug;
    }
}
```

- [ ] **Step 4: Create the job**

Create `app/Jobs/DraftBibleStudyThemeJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\BibleStudy\DraftBibleStudyTheme;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class DraftBibleStudyThemeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public User $admin,
        public string $themeTitle,
    ) {}

    public function handle(DraftBibleStudyTheme $action): void
    {
        $action->handle($this->admin, $this->themeTitle);
    }
}
```

- [ ] **Step 5: Test passes**

Run: `php artisan test --compact --filter=DraftBibleStudyThemeTest`

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/BibleStudy/DraftBibleStudyTheme.php app/Jobs/DraftBibleStudyThemeJob.php tests/Feature/BibleStudy/DraftBibleStudyThemeTest.php
git commit -m "feat(bible-study): add DraftBibleStudyTheme action and queued job"
```

---

## Task 12 — `PublishBibleStudyTheme` action

**Files:**
- Create: `app/Actions/BibleStudy/PublishBibleStudyTheme.php`
- Test: `tests/Feature/BibleStudy/PublishBibleStudyThemeTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\BibleStudy\PublishBibleStudyTheme;
use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('flips status to approved and stamps metadata', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    app(PublishBibleStudyTheme::class)->handle($admin, $theme);

    $theme->refresh();
    expect($theme->status)->toBe(BibleStudyThemeStatus::Approved)
        ->and($theme->approved_at)->not->toBeNull()
        ->and($theme->approved_by_user_id)->toBe($admin->id);
});

it('throws when the theme is not a draft', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    expect(fn () => app(PublishBibleStudyTheme::class)->handle($admin, $theme))
        ->toThrow(DomainException::class);
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Create action**

```php
<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use App\Models\User;
use DomainException;

final readonly class PublishBibleStudyTheme
{
    public function handle(User $admin, BibleStudyTheme $theme): BibleStudyTheme
    {
        if ($theme->status !== BibleStudyThemeStatus::Draft) {
            throw new DomainException('Only draft themes can be published.');
        }

        $theme->update([
            'status' => BibleStudyThemeStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => $admin->id,
        ]);

        return $theme->refresh();
    }
}
```

- [ ] **Step 4: Test passes**

Run: `php artisan test --compact --filter=PublishBibleStudyThemeTest`

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/BibleStudy/PublishBibleStudyTheme.php tests/Feature/BibleStudy/PublishBibleStudyThemeTest.php
git commit -m "feat(bible-study): add PublishBibleStudyTheme action"
```

---

## Task 13 — Admin routes + `ThemeController@index`

**Files:**
- Create: `app/Http/Controllers/Admin/BibleStudy/ThemeController.php` (index only for now)
- Modify: `routes/web.php` (add admin routes block)
- Test: `tests/Feature/Controllers/Admin/BibleStudy/ThemeControllerIndexTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\User;

it('renders the admin bible-study themes index', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.bible-study.themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/bible-study/themes/index'));
});

it('shows drafts ordered by requested_count desc then created_at asc', function (): void {
    $admin = User::factory()->admin()->create();
    $old = BibleStudyTheme::factory()->draft()->create(['requested_count' => 5, 'created_at' => now()->subDay()]);
    $recent = BibleStudyTheme::factory()->draft()->create(['requested_count' => 5, 'created_at' => now()]);
    $hot = BibleStudyTheme::factory()->draft()->create(['requested_count' => 10]);

    $response = $this->actingAs($admin)->get(route('admin.bible-study.themes.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('admin/bible-study/themes/index')
        ->where('themes.0.id', $hot->id)
        ->where('themes.1.id', $old->id)
        ->where('themes.2.id', $recent->id)
    );
});

it('denies non-admin access', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.bible-study.themes.index'));

    $response->assertForbidden();
});

it('redirects unauthenticated users', function (): void {
    $response = $this->get(route('admin.bible-study.themes.index'));

    $response->assertRedirectToRoute('login');
});
```

- [ ] **Step 2: Run — FAIL** (route does not exist)

- [ ] **Step 3: Create the controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ThemeController
{
    public function index(): Response
    {
        $themes = BibleStudyTheme::query()
            ->orderByDesc('requested_count')
            ->orderBy('created_at')
            ->get();

        return Inertia::render('admin/bible-study/themes/index', [
            'themes' => $themes->map(fn (BibleStudyTheme $theme): array => [
                'id' => $theme->id,
                'slug' => $theme->slug,
                'title' => $theme->title,
                'short_description' => $theme->short_description,
                'status' => $theme->status->value,
                'requested_count' => $theme->requested_count,
                'created_at' => $theme->created_at,
                'approved_at' => $theme->approved_at,
            ]),
            'statuses' => collect(BibleStudyThemeStatus::cases())->map(fn (BibleStudyThemeStatus $s): string => $s->value)->all(),
        ]);
    }
}
```

- [ ] **Step 4: Add routes**

In `routes/web.php`, add this import near the other admin controllers:

```php
use App\Http\Controllers\Admin\BibleStudy\ThemeController as AdminBibleStudyThemeController;
```

Inside the existing `Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void { ... })` block (after other admin routes), add:

```php
    // Admin Bible Study...
    Route::get('bible-study/themes', new AdminBibleStudyThemeController()->index(...))->name('bible-study.themes.index');
```

- [ ] **Step 5: Test passes**

Run: `php artisan test --compact --filter=ThemeControllerIndexTest`

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/BibleStudy/ThemeController.php routes/web.php tests/Feature/Controllers/Admin/BibleStudy/ThemeControllerIndexTest.php
git commit -m "feat(bible-study): add admin bible-study themes index"
```

---

## Task 14 — `ThemeController@show` (review payload)

**Files:**
- Modify: `app/Http/Controllers/Admin/BibleStudy/ThemeController.php` (add `show`)
- Modify: `routes/web.php` (add show route)
- Test: `tests/Feature/Controllers/Admin/BibleStudy/ThemeControllerShowTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\User;
use App\Models\WordStudy;

it('returns the full review payload for a draft theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create();
    BibleStudyInsight::factory()->for($passage, 'passage')->create();
    BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create();
    $wordStudy = WordStudy::factory()->create();
    BibleStudyWordHighlight::factory()->for($passage, 'passage')->for($wordStudy, 'wordStudy')->create();

    $response = $this->actingAs($admin)->get(route('admin.bible-study.themes.show', $theme));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('admin/bible-study/themes/show')
        ->where('theme.id', $theme->id)
        ->has('theme.passages.0.insight')
        ->has('theme.passages.0.historical_context')
        ->has('theme.passages.0.word_highlights.0')
    );
});

it('denies non-admin access', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.bible-study.themes.show', $theme));

    $response->assertForbidden();
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Extend controller**

Add to `ThemeController`:

```php
public function show(BibleStudyTheme $theme): Response
{
    $theme->load([
        'passages.insight',
        'passages.historicalContext',
        'passages.wordHighlights.wordStudy',
    ]);

    return Inertia::render('admin/bible-study/themes/show', [
        'theme' => [
            'id' => $theme->id,
            'slug' => $theme->slug,
            'title' => $theme->title,
            'short_description' => $theme->short_description,
            'long_intro' => $theme->long_intro,
            'status' => $theme->status->value,
            'requested_count' => $theme->requested_count,
            'approved_at' => $theme->approved_at,
            'passages' => $theme->passages->map(fn ($p): array => [
                'id' => $p->id,
                'position' => $p->position,
                'is_guided_path' => $p->is_guided_path,
                'book' => $p->book,
                'chapter' => $p->chapter,
                'verse_start' => $p->verse_start,
                'verse_end' => $p->verse_end,
                'passage_intro' => $p->passage_intro,
                'insight' => $p->insight ? [
                    'id' => $p->insight->id,
                    'interpretation' => $p->insight->interpretation,
                    'application' => $p->insight->application,
                    'cross_references' => $p->insight->cross_references,
                    'literary_context' => $p->insight->literary_context,
                ] : null,
                'historical_context' => $p->historicalContext ? [
                    'id' => $p->historicalContext->id,
                    'setting' => $p->historicalContext->setting,
                    'author' => $p->historicalContext->author,
                    'date_range' => $p->historicalContext->date_range,
                    'audience' => $p->historicalContext->audience,
                    'historical_events' => $p->historicalContext->historical_events,
                ] : null,
                'word_highlights' => $p->wordHighlights->map(fn ($wh): array => [
                    'id' => $wh->id,
                    'verse_number' => $wh->verse_number,
                    'word_index_in_verse' => $wh->word_index_in_verse,
                    'display_word' => $wh->display_word,
                    'word_study' => [
                        'id' => $wh->wordStudy->id,
                        'original_word' => $wh->wordStudy->original_word,
                        'transliteration' => $wh->wordStudy->transliteration,
                        'language' => $wh->wordStudy->language,
                        'definition' => $wh->wordStudy->definition,
                        'strongs_number' => $wh->wordStudy->strongs_number,
                    ],
                ])->all(),
            ])->all(),
        ],
    ]);
}
```

- [ ] **Step 4: Add route**

In `routes/web.php`, below the themes index route:

```php
    Route::get('bible-study/themes/{theme}', new AdminBibleStudyThemeController()->show(...))->name('bible-study.themes.show');
```

- [ ] **Step 5: Test passes**

Run: `php artisan test --compact --filter=ThemeControllerShowTest`

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/BibleStudy/ThemeController.php routes/web.php tests/Feature/Controllers/Admin/BibleStudy/ThemeControllerShowTest.php
git commit -m "feat(bible-study): add admin bible-study theme show with full review payload"
```

---

## Task 15 — `ThemeController` write endpoints (`storeDraft`, `update`, `publish`, `destroy`)

**Files:**
- Modify: `app/Http/Controllers/Admin/BibleStudy/ThemeController.php`
- Create: `app/Http/Requests/Admin/BibleStudy/StoreDraftRequest.php`
- Create: `app/Http/Requests/Admin/BibleStudy/UpdateThemeRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Controllers/Admin/BibleStudy/ThemeControllerWriteTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\BibleStudy\DraftBibleStudyTheme;
use App\Enums\BibleStudyThemeStatus;
use App\Models\AiGenerationLog;
use App\Models\BibleStudyTheme;
use App\Models\User;
use function Pest\Laravel\mock;

it('triggers a draft via the action and redirects to the new theme', function (): void {
    $admin = User::factory()->admin()->create();

    mock(DraftBibleStudyTheme::class)
        ->shouldReceive('handle')
        ->once()
        ->andReturnUsing(function (User $admin, string $title): AiGenerationLog {
            $theme = BibleStudyTheme::factory()->draft()->create(['slug' => 'resilience', 'title' => 'Resilience']);
            $log = AiGenerationLog::factory()->create(['admin_id' => $admin->id]);
            return $log;
        });

    $response = $this->actingAs($admin)->post(route('admin.bible-study.themes.store'), [
        'title' => 'Resilience',
    ]);

    $response->assertRedirect();
});

it('updates theme meta', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    $response = $this->actingAs($admin)->put(route('admin.bible-study.themes.update', $theme), [
        'title' => 'Wisdom (edited)',
        'short_description' => 'New',
        'long_intro' => 'New intro.',
    ]);

    $response->assertRedirect();
    expect($theme->fresh()->title)->toBe('Wisdom (edited)');
});

it('publishes a draft', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    $response = $this->actingAs($admin)->put(route('admin.bible-study.themes.publish', $theme));

    $response->assertRedirect();
    expect($theme->fresh()->status)->toBe(BibleStudyThemeStatus::Approved);
});

it('rejects publishing a non-draft', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    $response = $this->actingAs($admin)->put(route('admin.bible-study.themes.publish', $theme));

    $response->assertSessionHasErrors();
});

it('deletes a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.bible-study.themes.destroy', $theme));

    $response->assertRedirect();
    expect(BibleStudyTheme::query()->find($theme->id))->toBeNull();
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Form requests**

Create `app/Http/Requests/Admin/BibleStudy/StoreDraftRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDraftRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:120'],
        ];
    }
}
```

Create `app/Http/Requests/Admin/BibleStudy/UpdateThemeRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateThemeRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:120'],
            'short_description' => ['required', 'string', 'max:255'],
            'long_intro' => ['required', 'string'],
        ];
    }
}
```

- [ ] **Step 4: Extend controller**

Add imports to `ThemeController`:

```php
use App\Actions\BibleStudy\DraftBibleStudyTheme;
use App\Actions\BibleStudy\PublishBibleStudyTheme;
use App\Http\Requests\Admin\BibleStudy\StoreDraftRequest;
use App\Http\Requests\Admin\BibleStudy\UpdateThemeRequest;
use App\Models\User;
use DomainException;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
```

Add methods:

```php
public function store(StoreDraftRequest $request, #[CurrentUser] User $admin, DraftBibleStudyTheme $action): RedirectResponse
{
    $log = $action->handle($admin, $request->string('title')->value());

    return redirect()->route('admin.bible-study.themes.index')
        ->with('status', "Draft generation queued (log #{$log->id}).");
}

public function update(UpdateThemeRequest $request, BibleStudyTheme $theme): RedirectResponse
{
    $theme->update($request->validated());

    return back()->with('status', 'Theme updated.');
}

public function publish(BibleStudyTheme $theme, #[CurrentUser] User $admin, PublishBibleStudyTheme $action): RedirectResponse
{
    try {
        $action->handle($admin, $theme);
    } catch (DomainException $e) {
        throw ValidationException::withMessages(['status' => $e->getMessage()]);
    }

    return back()->with('status', 'Theme published.');
}

public function destroy(BibleStudyTheme $theme): RedirectResponse
{
    $theme->delete();

    return redirect()->route('admin.bible-study.themes.index')->with('status', 'Theme deleted.');
}
```

- [ ] **Step 5: Add routes**

In `routes/web.php` (inside the admin group, below `themes.show`):

```php
    Route::post('bible-study/themes', new AdminBibleStudyThemeController()->store(...))->name('bible-study.themes.store');
    Route::put('bible-study/themes/{theme}', new AdminBibleStudyThemeController()->update(...))->name('bible-study.themes.update');
    Route::put('bible-study/themes/{theme}/publish', new AdminBibleStudyThemeController()->publish(...))->name('bible-study.themes.publish');
    Route::delete('bible-study/themes/{theme}', new AdminBibleStudyThemeController()->destroy(...))->name('bible-study.themes.destroy');
```

- [ ] **Step 6: Test passes**

Run: `php artisan test --compact --filter=ThemeControllerWriteTest`

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/BibleStudy/ThemeController.php app/Http/Requests/Admin/BibleStudy/StoreDraftRequest.php app/Http/Requests/Admin/BibleStudy/UpdateThemeRequest.php routes/web.php tests/Feature/Controllers/Admin/BibleStudy/ThemeControllerWriteTest.php
git commit -m "feat(bible-study): admin can trigger drafts, edit meta, publish, and delete themes"
```

---

## Task 16 — `PassageController` (store/update/destroy/reorder)

**Files:**
- Create: `app/Http/Controllers/Admin/BibleStudy/PassageController.php`
- Create: `app/Http/Requests/Admin/BibleStudy/StorePassageRequest.php`
- Create: `app/Http/Requests/Admin/BibleStudy/UpdatePassageRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Controllers/Admin/BibleStudy/PassageControllerTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\User;

it('creates a passage on a draft theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    $response = $this->actingAs($admin)->post(
        route('admin.bible-study.themes.passages.store', $theme),
        [
            'position' => 1,
            'is_guided_path' => true,
            'book' => 'Job',
            'chapter' => 1,
            'verse_start' => 13,
            'verse_end' => 22,
            'passage_intro' => 'Job\'s losses.',
        ]
    );

    $response->assertRedirect();
    expect($theme->passages()->count())->toBe(1);
});

it('updates a passage', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create();

    $response = $this->actingAs($admin)->put(
        route('admin.bible-study.themes.passages.update', [$theme, $passage]),
        [
            'position' => 3,
            'is_guided_path' => false,
            'book' => $passage->book,
            'chapter' => $passage->chapter,
            'verse_start' => $passage->verse_start,
            'verse_end' => $passage->verse_end,
            'passage_intro' => 'Updated intro.',
        ]
    );

    $response->assertRedirect();
    expect($passage->fresh()->passage_intro)->toBe('Updated intro.');
});

it('deletes a passage and cascades children', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create();

    $this->actingAs($admin)->delete(
        route('admin.bible-study.themes.passages.destroy', [$theme, $passage])
    )->assertRedirect();

    expect(BibleStudyThemePassage::query()->find($passage->id))->toBeNull();
});

it('reorders passages', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();
    $p1 = BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 1]);
    $p2 = BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 2]);

    $this->actingAs($admin)->put(
        route('admin.bible-study.themes.passages.reorder', $theme),
        ['ids' => [$p2->id, $p1->id]]
    )->assertRedirect();

    expect($p2->fresh()->position)->toBe(1)
        ->and($p1->fresh()->position)->toBe(2);
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Form requests**

`StorePassageRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class StorePassageRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'position' => ['required', 'integer', 'min:1'],
            'is_guided_path' => ['required', 'boolean'],
            'book' => ['required', 'string', 'max:64'],
            'chapter' => ['required', 'integer', 'min:1'],
            'verse_start' => ['required', 'integer', 'min:1'],
            'verse_end' => ['nullable', 'integer', 'min:1', 'gte:verse_start'],
            'passage_intro' => ['nullable', 'string'],
        ];
    }
}
```

`UpdatePassageRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePassageRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'position' => ['required', 'integer', 'min:1'],
            'is_guided_path' => ['required', 'boolean'],
            'book' => ['required', 'string', 'max:64'],
            'chapter' => ['required', 'integer', 'min:1'],
            'verse_start' => ['required', 'integer', 'min:1'],
            'verse_end' => ['nullable', 'integer', 'min:1', 'gte:verse_start'],
            'passage_intro' => ['nullable', 'string'],
        ];
    }
}
```

- [ ] **Step 4: Controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Http\Requests\Admin\BibleStudy\StorePassageRequest;
use App\Http\Requests\Admin\BibleStudy\UpdatePassageRequest;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class PassageController
{
    public function store(StorePassageRequest $request, BibleStudyTheme $theme): RedirectResponse
    {
        $theme->passages()->create($request->validated());

        return back()->with('status', 'Passage added.');
    }

    public function update(UpdatePassageRequest $request, BibleStudyTheme $theme, BibleStudyThemePassage $passage): RedirectResponse
    {
        abort_unless($passage->bible_study_theme_id === $theme->id, 404);
        $passage->update($request->validated());

        return back()->with('status', 'Passage updated.');
    }

    public function destroy(BibleStudyTheme $theme, BibleStudyThemePassage $passage): RedirectResponse
    {
        abort_unless($passage->bible_study_theme_id === $theme->id, 404);
        $passage->delete();

        return back()->with('status', 'Passage deleted.');
    }

    public function reorder(Request $request, BibleStudyTheme $theme): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ])['ids'];

        DB::transaction(function () use ($theme, $ids): void {
            foreach ($ids as $index => $id) {
                $theme->passages()->where('id', $id)->update(['position' => $index + 1]);
            }
        });

        return back()->with('status', 'Passages reordered.');
    }
}
```

- [ ] **Step 5: Routes**

In `routes/web.php`, add import:

```php
use App\Http\Controllers\Admin\BibleStudy\PassageController as AdminBibleStudyPassageController;
```

Add routes inside admin group:

```php
    Route::post('bible-study/themes/{theme}/passages', new AdminBibleStudyPassageController()->store(...))->name('bible-study.themes.passages.store');
    Route::put('bible-study/themes/{theme}/passages/reorder', new AdminBibleStudyPassageController()->reorder(...))->name('bible-study.themes.passages.reorder');
    Route::put('bible-study/themes/{theme}/passages/{passage}', new AdminBibleStudyPassageController()->update(...))->name('bible-study.themes.passages.update');
    Route::delete('bible-study/themes/{theme}/passages/{passage}', new AdminBibleStudyPassageController()->destroy(...))->name('bible-study.themes.passages.destroy');
```

**Route order matters** — place `reorder` before `{passage}` to avoid collision.

- [ ] **Step 6: Test passes**

Run: `php artisan test --compact --filter=PassageControllerTest`

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(bible-study): admin passage CRUD + reorder"
```

---

## Task 17 — `InsightController@update`

**Files:**
- Create: `app/Http/Controllers/Admin/BibleStudy/InsightController.php`
- Create: `app/Http/Requests/Admin/BibleStudy/UpdateInsightRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Controllers/Admin/BibleStudy/InsightControllerTest.php`

Endpoint upserts the single insight row for a passage.

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\User;

it('creates the insight when missing', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create();

    $response = $this->actingAs($admin)->put(
        route('admin.bible-study.passages.insight.update', $passage),
        [
            'interpretation' => 'I',
            'application' => 'A',
            'cross_references' => [['book' => 'Romans', 'chapter' => 8, 'verse_start' => 18, 'note' => 'x']],
            'literary_context' => 'L',
        ]
    );

    $response->assertRedirect();
    expect(BibleStudyInsight::query()->where('bible_study_theme_passage_id', $passage->id)->exists())->toBeTrue();
});

it('updates an existing insight', function (): void {
    $admin = User::factory()->admin()->create();
    $passage = BibleStudyThemePassage::factory()->create();
    BibleStudyInsight::factory()->for($passage, 'passage')->create(['interpretation' => 'old']);

    $this->actingAs($admin)->put(
        route('admin.bible-study.passages.insight.update', $passage),
        [
            'interpretation' => 'new',
            'application' => 'a',
            'cross_references' => [],
            'literary_context' => 'l',
        ]
    )->assertRedirect();

    expect($passage->insight->fresh()->interpretation)->toBe('new');
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Form request**

`UpdateInsightRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateInsightRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'interpretation' => ['required', 'string'],
            'application' => ['required', 'string'],
            'cross_references' => ['array'],
            'cross_references.*.book' => ['required', 'string'],
            'cross_references.*.chapter' => ['required', 'integer', 'min:1'],
            'cross_references.*.verse_start' => ['required', 'integer', 'min:1'],
            'cross_references.*.verse_end' => ['nullable', 'integer', 'min:1'],
            'cross_references.*.note' => ['nullable', 'string'],
            'literary_context' => ['required', 'string'],
        ];
    }
}
```

- [ ] **Step 4: Controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Http\Requests\Admin\BibleStudy\UpdateInsightRequest;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyThemePassage;
use Illuminate\Http\RedirectResponse;

final readonly class InsightController
{
    public function update(UpdateInsightRequest $request, BibleStudyThemePassage $passage): RedirectResponse
    {
        BibleStudyInsight::query()->updateOrCreate(
            ['bible_study_theme_passage_id' => $passage->id],
            $request->validated(),
        );

        return back()->with('status', 'Insight saved.');
    }
}
```

- [ ] **Step 5: Route**

Add import + route:

```php
use App\Http\Controllers\Admin\BibleStudy\InsightController as AdminBibleStudyInsightController;
```

```php
    Route::put('bible-study/passages/{passage}/insight', new AdminBibleStudyInsightController()->update(...))->name('bible-study.passages.insight.update');
```

- [ ] **Step 6: Test passes, lint, commit**

```bash
php artisan test --compact --filter=InsightControllerTest
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(bible-study): admin insight upsert per passage"
```

---

## Task 18 — `HistoricalContextController@update`

Same shape as Task 17 but for the historical context row.

**Files:**
- Create: `app/Http/Controllers/Admin/BibleStudy/HistoricalContextController.php`
- Create: `app/Http/Requests/Admin/BibleStudy/UpdateHistoricalContextRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Controllers/Admin/BibleStudy/HistoricalContextControllerTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyThemePassage;
use App\Models\User;

it('upserts historical context for a passage', function (): void {
    $admin = User::factory()->admin()->create();
    $passage = BibleStudyThemePassage::factory()->create();

    $this->actingAs($admin)->put(
        route('admin.bible-study.passages.historical-context.update', $passage),
        [
            'setting' => 'Land of Uz',
            'author' => 'Unknown',
            'date_range' => 'Pre-exilic',
            'audience' => 'Israelite',
            'historical_events' => 'Loss of family.',
        ]
    )->assertRedirect();

    expect(BibleStudyHistoricalContext::query()->where('bible_study_theme_passage_id', $passage->id)->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Form request**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateHistoricalContextRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'setting' => ['required', 'string'],
            'author' => ['required', 'string'],
            'date_range' => ['required', 'string'],
            'audience' => ['required', 'string'],
            'historical_events' => ['required', 'string'],
        ];
    }
}
```

- [ ] **Step 4: Controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Http\Requests\Admin\BibleStudy\UpdateHistoricalContextRequest;
use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyThemePassage;
use Illuminate\Http\RedirectResponse;

final readonly class HistoricalContextController
{
    public function update(UpdateHistoricalContextRequest $request, BibleStudyThemePassage $passage): RedirectResponse
    {
        BibleStudyHistoricalContext::query()->updateOrCreate(
            ['bible_study_theme_passage_id' => $passage->id],
            $request->validated(),
        );

        return back()->with('status', 'Historical context saved.');
    }
}
```

- [ ] **Step 5: Route**

```php
use App\Http\Controllers\Admin\BibleStudy\HistoricalContextController as AdminBibleStudyHistoricalContextController;
```

```php
    Route::put('bible-study/passages/{passage}/historical-context', new AdminBibleStudyHistoricalContextController()->update(...))->name('bible-study.passages.historical-context.update');
```

- [ ] **Step 6: Test, lint, commit**

```bash
php artisan test --compact --filter=HistoricalContextControllerTest
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(bible-study): admin historical context upsert per passage"
```

---

## Task 19 — `WordHighlightController` (store/destroy)

**Files:**
- Create: `app/Http/Controllers/Admin/BibleStudy/WordHighlightController.php`
- Create: `app/Http/Requests/Admin/BibleStudy/StoreWordHighlightRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Controllers/Admin/BibleStudy/WordHighlightControllerTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\User;
use App\Models\WordStudy;

it('creates a word highlight against an existing word study', function (): void {
    $admin = User::factory()->admin()->create();
    $passage = BibleStudyThemePassage::factory()->create();
    $wordStudy = WordStudy::factory()->create();

    $this->actingAs($admin)->post(
        route('admin.bible-study.passages.word-highlights.store', $passage),
        [
            'word_study_id' => $wordStudy->id,
            'verse_number' => 20,
            'word_index_in_verse' => 3,
            'display_word' => 'worship',
        ]
    )->assertRedirect();

    expect(BibleStudyWordHighlight::query()->count())->toBe(1);
});

it('deletes a highlight', function (): void {
    $admin = User::factory()->admin()->create();
    $passage = BibleStudyThemePassage::factory()->create();
    $highlight = BibleStudyWordHighlight::factory()->for($passage, 'passage')->create();

    $this->actingAs($admin)->delete(
        route('admin.bible-study.passages.word-highlights.destroy', [$passage, $highlight])
    )->assertRedirect();

    expect(BibleStudyWordHighlight::query()->find($highlight->id))->toBeNull();
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Form request**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use App\Models\WordStudy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWordHighlightRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'word_study_id' => ['required', 'integer', Rule::exists(WordStudy::class, 'id')],
            'verse_number' => ['required', 'integer', 'min:1'],
            'word_index_in_verse' => ['required', 'integer', 'min:0'],
            'display_word' => ['required', 'string', 'max:64'],
        ];
    }
}
```

- [ ] **Step 4: Controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Http\Requests\Admin\BibleStudy\StoreWordHighlightRequest;
use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use Illuminate\Http\RedirectResponse;

final readonly class WordHighlightController
{
    public function store(StoreWordHighlightRequest $request, BibleStudyThemePassage $passage): RedirectResponse
    {
        $passage->wordHighlights()->create($request->validated());

        return back()->with('status', 'Highlight added.');
    }

    public function destroy(BibleStudyThemePassage $passage, BibleStudyWordHighlight $highlight): RedirectResponse
    {
        abort_unless($highlight->bible_study_theme_passage_id === $passage->id, 404);
        $highlight->delete();

        return back()->with('status', 'Highlight removed.');
    }
}
```

- [ ] **Step 5: Routes**

```php
use App\Http\Controllers\Admin\BibleStudy\WordHighlightController as AdminBibleStudyWordHighlightController;
```

```php
    Route::post('bible-study/passages/{passage}/word-highlights', new AdminBibleStudyWordHighlightController()->store(...))->name('bible-study.passages.word-highlights.store');
    Route::delete('bible-study/passages/{passage}/word-highlights/{highlight}', new AdminBibleStudyWordHighlightController()->destroy(...))->name('bible-study.passages.word-highlights.destroy');
```

- [ ] **Step 6: Test, lint, commit**

```bash
php artisan test --compact --filter=WordHighlightControllerTest
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(bible-study): admin word highlight create/delete"
```

---

## Task 20 — Admin Inertia page: themes/index (queue)

**Files:**
- Create: `resources/js/pages/admin/bible-study/themes/index.tsx`
- Test: none new (browser assertion is covered by Task 13; we only confirm the component file exists and renders)

**Design-system note:** reuse `resources/js/layouts/devotional-layout.tsx`, the shadcn `Button`/`Card`/`Input`/`Table` primitives from `@/components/ui/`, and any existing admin grid layout from `resources/js/pages/admin/themes/index.tsx`. Do not introduce new UI primitives.

- [ ] **Step 1: Read the reference admin index page**

Read `resources/js/pages/admin/themes/index.tsx` and `resources/js/pages/admin/ai-content/generate.tsx` to match layout/imports/spacing conventions.

- [ ] **Step 2: Create the page**

Create `resources/js/pages/admin/bible-study/themes/index.tsx`. Mirror the existing admin themes index layout. At a minimum:

```tsx
import DevotionalLayout from '@/layouts/devotional-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type ThemeRow = {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    status: 'draft' | 'approved' | 'archived';
    requested_count: number;
    created_at: string;
    approved_at: string | null;
};

interface Props {
    themes: ThemeRow[];
    statuses: string[];
}

export default function BibleStudyThemesIndex({ themes }: Props) {
    const { data, setData, post, processing } = useForm({ title: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('admin.bible-study.themes.store'));
    };

    return (
        <DevotionalLayout>
            <Head title="Bible Study — Themes" />

            <div className="mx-auto max-w-5xl px-4 py-8">
                <header className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Bible Study Themes</h1>
                        <p className="text-muted-foreground text-sm">Draft, review, and publish theme studies.</p>
                    </div>
                </header>

                <form onSubmit={submit} className="mb-8 flex gap-2">
                    <Input
                        placeholder="New theme title (e.g., Forgiveness)"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                    />
                    <Button type="submit" disabled={processing}>
                        Draft with AI
                    </Button>
                </form>

                <div className="divide-y rounded-lg border">
                    {themes.map((theme) => (
                        <Link
                            key={theme.id}
                            href={route('admin.bible-study.themes.show', theme.id)}
                            className="flex items-center justify-between px-4 py-3 hover:bg-muted"
                        >
                            <div>
                                <div className="font-medium">{theme.title}</div>
                                <div className="text-muted-foreground text-sm">{theme.short_description}</div>
                            </div>
                            <div className="text-xs text-muted-foreground">
                                <span className="mr-3">Status: {theme.status}</span>
                                <span>Requests: {theme.requested_count}</span>
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </DevotionalLayout>
    );
}
```

- [ ] **Step 3: Run existing index test**

Run: `php artisan test --compact --filter=ThemeControllerIndexTest`
Expected: still passing. If Inertia dev server complains about route types, run `bun run build` once.

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/admin/bible-study/themes/index.tsx
git commit -m "feat(bible-study): admin themes index page"
```

---

## Task 21 — Admin Inertia page: themes/show (review)

**Files:**
- Create: `resources/js/pages/admin/bible-study/themes/show.tsx`

**Design-system note:** same constraints as Task 20. The review page is dense — use existing shadcn `Tabs`, `Collapsible`, `Textarea`, `Button`, and `Table` primitives. If `frontend-design` is installed, invoke it before building this page.

- [ ] **Step 1: Invoke `frontend-design` skill if available**

If the repo has the `frontend-design` skill available, run it now to get layout guidance before writing code. Otherwise proceed.

- [ ] **Step 2: Create the page**

The page must let the admin: edit theme meta (title/short_description/long_intro), view/edit each passage's intro + position + guided-path flag, upsert each passage's insight + historical context, add/remove word highlights, reorder passages, delete passages, and publish the theme.

Given size, create one file with sections. Reference: mirror `resources/js/pages/admin/themes/{edit,show}.tsx` for section composition (read those first).

```tsx
import DevotionalLayout from '@/layouts/devotional-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Head, router, useForm } from '@inertiajs/react';

// Types

type CrossRef = { book: string; chapter: number; verse_start: number; verse_end?: number; note?: string };
type WordStudy = { id: number; original_word: string; transliteration: string; language: string; definition: string; strongs_number: string };
type WordHighlight = { id: number; verse_number: number; word_index_in_verse: number; display_word: string; word_study: WordStudy };
type Insight = { id: number; interpretation: string; application: string; cross_references: CrossRef[]; literary_context: string };
type HistoricalContext = { id: number; setting: string; author: string; date_range: string; audience: string; historical_events: string };
type Passage = {
    id: number;
    position: number;
    is_guided_path: boolean;
    book: string;
    chapter: number;
    verse_start: number;
    verse_end: number | null;
    passage_intro: string | null;
    insight: Insight | null;
    historical_context: HistoricalContext | null;
    word_highlights: WordHighlight[];
};
type Theme = {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    long_intro: string;
    status: 'draft' | 'approved' | 'archived';
    requested_count: number;
    approved_at: string | null;
    passages: Passage[];
};

interface Props {
    theme: Theme;
}

export default function BibleStudyThemeShow({ theme }: Props) {
    const metaForm = useForm({
        title: theme.title,
        short_description: theme.short_description,
        long_intro: theme.long_intro,
    });

    const saveMeta = (e: React.FormEvent) => {
        e.preventDefault();
        metaForm.put(route('admin.bible-study.themes.update', theme.id));
    };

    const publish = () => router.put(route('admin.bible-study.themes.publish', theme.id));
    const destroyTheme = () => {
        if (confirm('Delete this theme?')) router.delete(route('admin.bible-study.themes.destroy', theme.id));
    };

    return (
        <DevotionalLayout>
            <Head title={`Review — ${theme.title}`} />

            <div className="mx-auto max-w-4xl px-4 py-8">
                <header className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">{theme.title}</h1>
                        <div className="text-xs uppercase tracking-wide text-muted-foreground">Status: {theme.status}</div>
                    </div>
                    <div className="flex gap-2">
                        {theme.status === 'draft' && <Button onClick={publish}>Publish</Button>}
                        <Button variant="destructive" onClick={destroyTheme}>Delete</Button>
                    </div>
                </header>

                <form onSubmit={saveMeta} className="mb-10 space-y-4">
                    <Input value={metaForm.data.title} onChange={(e) => metaForm.setData('title', e.target.value)} placeholder="Title" />
                    <Input value={metaForm.data.short_description} onChange={(e) => metaForm.setData('short_description', e.target.value)} placeholder="Short description" />
                    <Textarea value={metaForm.data.long_intro} onChange={(e) => metaForm.setData('long_intro', e.target.value)} placeholder="Long intro" rows={8} />
                    <Button type="submit" disabled={metaForm.processing}>Save meta</Button>
                </form>

                <section className="space-y-8">
                    {theme.passages.map((passage) => (
                        <PassageBlock key={passage.id} themeId={theme.id} passage={passage} />
                    ))}
                </section>
            </div>
        </DevotionalLayout>
    );
}

function PassageBlock({ themeId, passage }: { themeId: number; passage: Passage }) {
    const passageForm = useForm({
        position: passage.position,
        is_guided_path: passage.is_guided_path,
        book: passage.book,
        chapter: passage.chapter,
        verse_start: passage.verse_start,
        verse_end: passage.verse_end ?? '',
        passage_intro: passage.passage_intro ?? '',
    });

    const save = (e: React.FormEvent) => {
        e.preventDefault();
        passageForm.put(route('admin.bible-study.themes.passages.update', [themeId, passage.id]));
    };

    const destroy = () => {
        if (confirm('Delete this passage?')) router.delete(route('admin.bible-study.themes.passages.destroy', [themeId, passage.id]));
    };

    return (
        <div className="rounded-lg border p-4">
            <div className="mb-3 flex items-center justify-between">
                <div className="font-medium">{passage.book} {passage.chapter}:{passage.verse_start}{passage.verse_end ? `–${passage.verse_end}` : ''}</div>
                <Button variant="ghost" onClick={destroy}>Remove</Button>
            </div>
            <form onSubmit={save} className="mb-4 grid grid-cols-2 gap-3">
                <Input type="number" value={passageForm.data.position} onChange={(e) => passageForm.setData('position', Number(e.target.value))} placeholder="Position" />
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={passageForm.data.is_guided_path} onChange={(e) => passageForm.setData('is_guided_path', e.target.checked)} />
                    Guided path
                </label>
                <Textarea value={passageForm.data.passage_intro} onChange={(e) => passageForm.setData('passage_intro', e.target.value)} placeholder="Passage intro" className="col-span-2" />
                <Button type="submit" className="col-span-2" disabled={passageForm.processing}>Save passage</Button>
            </form>

            <InsightEditor passage={passage} />
            <HistoricalEditor passage={passage} />
            <WordHighlightsEditor passage={passage} />
        </div>
    );
}

function InsightEditor({ passage }: { passage: Passage }) {
    const form = useForm({
        interpretation: passage.insight?.interpretation ?? '',
        application: passage.insight?.application ?? '',
        cross_references: passage.insight?.cross_references ?? [],
        literary_context: passage.insight?.literary_context ?? '',
    });

    const save = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(route('admin.bible-study.passages.insight.update', passage.id));
    };

    return (
        <form onSubmit={save} className="mb-4 space-y-2 border-t pt-4">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Insight</div>
            <Textarea value={form.data.interpretation} onChange={(e) => form.setData('interpretation', e.target.value)} placeholder="Interpretation" />
            <Textarea value={form.data.application} onChange={(e) => form.setData('application', e.target.value)} placeholder="Application" />
            <Textarea value={form.data.literary_context} onChange={(e) => form.setData('literary_context', e.target.value)} placeholder="Literary context" />
            <Button type="submit" disabled={form.processing}>Save insight</Button>
        </form>
    );
}

function HistoricalEditor({ passage }: { passage: Passage }) {
    const form = useForm({
        setting: passage.historical_context?.setting ?? '',
        author: passage.historical_context?.author ?? '',
        date_range: passage.historical_context?.date_range ?? '',
        audience: passage.historical_context?.audience ?? '',
        historical_events: passage.historical_context?.historical_events ?? '',
    });

    const save = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(route('admin.bible-study.passages.historical-context.update', passage.id));
    };

    return (
        <form onSubmit={save} className="mb-4 space-y-2 border-t pt-4">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Historical context</div>
            <Input value={form.data.setting} onChange={(e) => form.setData('setting', e.target.value)} placeholder="Setting" />
            <Input value={form.data.author} onChange={(e) => form.setData('author', e.target.value)} placeholder="Author" />
            <Input value={form.data.date_range} onChange={(e) => form.setData('date_range', e.target.value)} placeholder="Date range" />
            <Input value={form.data.audience} onChange={(e) => form.setData('audience', e.target.value)} placeholder="Audience" />
            <Textarea value={form.data.historical_events} onChange={(e) => form.setData('historical_events', e.target.value)} placeholder="Historical events" />
            <Button type="submit" disabled={form.processing}>Save context</Button>
        </form>
    );
}

function WordHighlightsEditor({ passage }: { passage: Passage }) {
    const addForm = useForm<{ word_study_id: number | ''; verse_number: number | ''; word_index_in_verse: number | ''; display_word: string }>({
        word_study_id: '',
        verse_number: '',
        word_index_in_verse: '',
        display_word: '',
    });

    const add = (e: React.FormEvent) => {
        e.preventDefault();
        addForm.post(route('admin.bible-study.passages.word-highlights.store', passage.id), {
            onSuccess: () => addForm.reset(),
        });
    };

    const remove = (id: number) => router.delete(route('admin.bible-study.passages.word-highlights.destroy', [passage.id, id]));

    return (
        <div className="mb-2 space-y-2 border-t pt-4">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Word highlights</div>
            <ul className="space-y-1 text-sm">
                {passage.word_highlights.map((h) => (
                    <li key={h.id} className="flex items-center justify-between">
                        <span>
                            v{h.verse_number} — <strong>{h.display_word}</strong> ({h.word_study.transliteration}, {h.word_study.strongs_number})
                        </span>
                        <button onClick={() => remove(h.id)} className="text-xs text-destructive">Remove</button>
                    </li>
                ))}
            </ul>
            <form onSubmit={add} className="grid grid-cols-5 gap-2">
                <Input type="number" placeholder="word_study_id" value={addForm.data.word_study_id} onChange={(e) => addForm.setData('word_study_id', e.target.value === '' ? '' : Number(e.target.value))} />
                <Input type="number" placeholder="verse" value={addForm.data.verse_number} onChange={(e) => addForm.setData('verse_number', e.target.value === '' ? '' : Number(e.target.value))} />
                <Input type="number" placeholder="word idx" value={addForm.data.word_index_in_verse} onChange={(e) => addForm.setData('word_index_in_verse', e.target.value === '' ? '' : Number(e.target.value))} />
                <Input placeholder="display word" value={addForm.data.display_word} onChange={(e) => addForm.setData('display_word', e.target.value)} />
                <Button type="submit" disabled={addForm.processing}>Add</Button>
            </form>
        </div>
    );
}
```

- [ ] **Step 3: Run existing show test**

Run: `php artisan test --compact --filter=ThemeControllerShowTest`
Expected: still passing.

- [ ] **Step 4: Lint and commit**

```bash
cd resources/js && npx prettier -w pages/admin/bible-study/themes/show.tsx && cd ../../
git add resources/js/pages/admin/bible-study/themes/show.tsx
git commit -m "feat(bible-study): admin theme review page"
```

---

## Task 22 — `BibleStudyThemeSeeder` (seed one approved "Resilience" theme)

**Files:**
- Create: `database/seeders/BibleStudyThemeSeeder.php`
- Test: `tests/Feature/BibleStudy/BibleStudyThemeSeederTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

declare(strict_types=1);

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use Database\Seeders\BibleStudyThemeSeeder;

it('seeds a single approved resilience theme with passages', function (): void {
    (new BibleStudyThemeSeeder)->run();

    $theme = BibleStudyTheme::query()->where('slug', 'resilience')->first();

    expect($theme)->not->toBeNull()
        ->and($theme->status)->toBe(BibleStudyThemeStatus::Approved)
        ->and($theme->passages()->count())->toBeGreaterThanOrEqual(1)
        ->and($theme->passages()->first()->insight)->not->toBeNull()
        ->and($theme->passages()->first()->historicalContext)->not->toBeNull();
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Seeder**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use Illuminate\Database\Seeder;

final class BibleStudyThemeSeeder extends Seeder
{
    public function run(): void
    {
        $theme = BibleStudyTheme::query()->updateOrCreate(
            ['slug' => 'resilience'],
            [
                'title' => 'Resilience',
                'short_description' => 'Faith under loss, waiting, and affliction.',
                'long_intro' => "Resilience in scripture is not stoic endurance. It is faith that clings through pain.\n\nAcross the canon, God meets afflicted people in their waiting—Job in ashes, Israel in exile, Paul in chains. The shape of biblical resilience is trust that keeps speaking, even through tears.",
                'status' => BibleStudyThemeStatus::Approved,
                'approved_at' => now(),
            ],
        );

        $passage = BibleStudyThemePassage::query()->updateOrCreate(
            ['bible_study_theme_id' => $theme->id, 'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22],
            [
                'position' => 1,
                'is_guided_path' => true,
                'passage_intro' => "Job loses his children and his livelihood in a single day. His response—grief expressed physically, trust expressed in worship—presents lament and faith as companions, not opposites.",
            ],
        );

        BibleStudyInsight::query()->updateOrCreate(
            ['bible_study_theme_passage_id' => $passage->id],
            [
                'interpretation' => "Job's tearing his robe and shaving his head are ritual acts of deep mourning. Falling to the ground in worship reframes the grief: he refuses to interpret his loss as evidence that God is unworthy.",
                'application' => "Space to grieve does not require suspending faith. Worship here is not dismissal of pain; it is the refusal to let pain have the final word.",
                'cross_references' => [
                    ['book' => 'Lamentations', 'chapter' => 3, 'verse_start' => 19, 'verse_end' => 24, 'note' => 'Grief holds hope.'],
                    ['book' => '1 Peter', 'chapter' => 1, 'verse_start' => 6, 'verse_end' => 7, 'note' => 'Trials refine faith.'],
                ],
                'literary_context' => "Part of the prose prologue framing the poetic dialogues that follow. Verse 22 is the narrator's verdict: Job did not sin.",
            ],
        );

        BibleStudyHistoricalContext::query()->updateOrCreate(
            ['bible_study_theme_passage_id' => $passage->id],
            [
                'setting' => 'The land of Uz, likely east of Canaan in a patriarchal-era setting.',
                'author' => 'Unknown',
                'date_range' => 'Uncertain; possibly pre-exilic',
                'audience' => 'Israelite wisdom readers wrestling with undeserved suffering.',
                'historical_events' => "The narrative reports a sequence of raids (Sabean, Chaldean) and a natural disaster that destroy Job's household in a day.",
            ],
        );
    }
}
```

- [ ] **Step 4: Test passes**

Run: `php artisan test --compact --filter=BibleStudyThemeSeederTest`

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/seeders/BibleStudyThemeSeeder.php tests/Feature/BibleStudy/BibleStudyThemeSeederTest.php
git commit -m "feat(bible-study): add Resilience seeder as end-to-end reference data"
```

---

## Task 23 — End-to-end smoke test: manual draft → edit → publish

**Files:**
- Create: `tests/Feature/BibleStudy/AdminEndToEndTest.php`

This is a smoke test to prove the pipeline holds together. No new production code.

- [ ] **Step 1: Write the test**

```php
<?php

declare(strict_types=1);

use App\Actions\BibleStudy\DraftBibleStudyTheme;
use App\Enums\BibleStudyThemeStatus;
use App\Models\AiGenerationLog;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\User;
use App\Models\WordStudy;
use function Pest\Laravel\mock;

it('walks from draft trigger to publish end-to-end', function (): void {
    $admin = User::factory()->admin()->create();

    mock(DraftBibleStudyTheme::class)
        ->shouldReceive('handle')
        ->once()
        ->andReturnUsing(function (User $admin): AiGenerationLog {
            $theme = BibleStudyTheme::factory()->draft()->create(['slug' => 'resilience', 'title' => 'Resilience']);
            BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 1]);
            return AiGenerationLog::factory()->create(['admin_id' => $admin->id]);
        });

    $this->actingAs($admin)->post(route('admin.bible-study.themes.store'), ['title' => 'Resilience'])->assertRedirect();

    $theme = BibleStudyTheme::query()->where('slug', 'resilience')->firstOrFail();
    $passage = $theme->passages()->first();

    $this->actingAs($admin)->put(route('admin.bible-study.themes.update', $theme), [
        'title' => 'Resilience',
        'short_description' => 'Faith under pressure.',
        'long_intro' => 'Long intro here.',
    ])->assertRedirect();

    $this->actingAs($admin)->put(route('admin.bible-study.passages.insight.update', $passage), [
        'interpretation' => 'i',
        'application' => 'a',
        'cross_references' => [],
        'literary_context' => 'l',
    ])->assertRedirect();

    $this->actingAs($admin)->put(route('admin.bible-study.passages.historical-context.update', $passage), [
        'setting' => 's',
        'author' => 'a',
        'date_range' => 'd',
        'audience' => 'au',
        'historical_events' => 'h',
    ])->assertRedirect();

    $wordStudy = WordStudy::factory()->create();
    $this->actingAs($admin)->post(route('admin.bible-study.passages.word-highlights.store', $passage), [
        'word_study_id' => $wordStudy->id,
        'verse_number' => 20,
        'word_index_in_verse' => 3,
        'display_word' => 'worship',
    ])->assertRedirect();

    $this->actingAs($admin)->put(route('admin.bible-study.themes.publish', $theme))->assertRedirect();

    $theme->refresh();
    expect($theme->status)->toBe(BibleStudyThemeStatus::Approved)
        ->and($theme->approved_by_user_id)->toBe($admin->id)
        ->and(BibleStudyWordHighlight::query()->count())->toBe(1);
});
```

- [ ] **Step 2: Run — expect PASS**

Run: `php artisan test --compact --filter=AdminEndToEndTest`

- [ ] **Step 3: Run full `composer test:local`**

Run: `composer test:local`
Expected: all tests pass, 100% line & type coverage, Pint clean, PHPStan clean.

If coverage gaps exist, add targeted unit tests for any untested branches (e.g., error paths in `DraftBibleStudyTheme`, `shouldPublish` validation path).

- [ ] **Step 4: Final commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/BibleStudy/AdminEndToEndTest.php
git commit -m "test(bible-study): end-to-end admin draft→edit→publish smoke test"
```

---

## Self-Review Checklist (for the executor)

Before declaring Phase 1 done:

- [ ] All 23 tasks committed.
- [ ] `composer test:local` green (100% line coverage, 100% type coverage, Pint, PHPStan).
- [ ] Running the seeder: `php artisan db:seed --class=BibleStudyThemeSeeder --no-interaction` produces the "Resilience" theme visible in the admin index (status: `approved`).
- [ ] Admin can visit `/admin/bible-study/themes`, click "Resilience", see full review payload.
- [ ] Admin can submit a new title and see a draft appear (AI-mocked in dev or real if `OPENAI_API_KEY` is set).
- [ ] No user-facing `/bible-study` routes were modified — user tab still shows the existing Reading Plans experience.

## Out of Scope for Phase 1 (deferred to Phase 2 / 3)

- User-facing Themes tab, theme detail page, reader view.
- Reflections UI and endpoints.
- Search / fuzzy / theme requests / request queue.
- `PartnerStartedBibleStudy` notification class, wiring into `SendPartnerNotification`, "your theme is ready" notifications.
- `ResolvePassageEnrichment` action.
- Any changes to `resources/js/pages/bible-study/index.tsx`.

---

## Spec Coverage Verification

Spec §4.2 tables:
- `bible_study_themes` → Task 1 ✓
- `bible_study_theme_passages` → Task 2 ✓
- `bible_study_word_highlights` → Task 5 ✓
- `bible_study_insights` → Task 3 ✓
- `bible_study_historical_contexts` → Task 4 ✓
- `bible_study_reflections` → Task 6 ✓
- `bible_study_theme_requests` → Task 7 ✓
- `bible_study_sessions` → Task 8 ✓
- `bible_study_partner_share` preference → Task 9 ✓

Spec §4.3 actions in Phase 1 scope:
- `DraftBibleStudyTheme` → Task 11 ✓
- `PublishBibleStudyTheme` → Task 12 ✓
- (Other actions — `StartOrResumeStudySession`, `SearchThemes`, `RequestTheme`, `SaveBibleStudyReflection`, `ShareBibleStudyWithPartner`, `ResolvePassageEnrichment` — are Phase 2/3; explicitly deferred above.)

Spec §4.4 agent → Task 10 ✓
Spec §4.5 admin routes → Tasks 13–19 ✓
Spec §4.6 admin pages → Tasks 20–21 ✓ (user pages deferred)
Spec §4.7 notification column → Task 9 ✓ (notification class and wiring deferred to Phase 3)
Spec §6 testing strategy — every new class gets a test in the task that creates it ✓
Spec §8 Phase 1 scope — matches exactly ✓
