<?php

declare(strict_types=1);

use App\Actions\SabbathSchool\ImportQuarter;
use App\Models\Lesson;
use App\Models\LessonDay;
use App\Models\LessonDayScriptureReference;
use App\Models\Quarterly;
use App\Services\ScriptureReferenceParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $this->fixtureHtml = file_get_contents(base_path('tests/fixtures/ssnet_lesson_03.html'));
});

it('imports a quarter and creates quarterly record', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26b');

    expect($result['quarterly'])->toBeInstanceOf(Quarterly::class);
    expect($result['quarterly']->quarter_code)->toBe('26b');
    expect($result['quarterly']->year)->toBe(2026);
    expect($result['quarterly']->quarter_number)->toBe(2);
    expect($result['quarterly']->is_active)->toBeTrue();
    expect($result['quarterly']->last_synced_at)->not->toBeNull();
});

it('imports all 13 lessons', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26b');

    expect($result['lessons_imported'])->toBe(13);
    expect($result['lessons_failed'])->toBe(0);
    expect(Lesson::query()->count())->toBe(13);
});

it('creates 7 days per lesson', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $action->handle('26b');

    expect(LessonDay::query()->count())->toBe(91); // 13 lessons x 7 days
});

it('stores scripture references for lesson days during import', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $action->handle('26b');

    // The fixture has scripture references in multiple days
    expect(LessonDayScriptureReference::query()->count())->toBeGreaterThan(0);

    // Check that a specific reference was stored
    $ref = LessonDayScriptureReference::query()->where('book', '1 John')->first();
    expect($ref)->not->toBeNull();
    expect($ref->chapter)->toBe(2);
    expect($ref->verse_start)->toBe(15);
    expect($ref->verse_end)->toBe(17);
});

it('clears old scripture references on re-import', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $action->handle('26b');

    $countFirst = LessonDayScriptureReference::query()->count();

    $action->handle('26b');

    // Should be the same count (old deleted, new created)
    expect(LessonDayScriptureReference::query()->count())->toBe($countFirst);
});

it('handles partial availability gracefully', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/less01.html' => Http::response($this->fixtureHtml, 200),
        'ssnet.org/lessons/26b/less02.html' => Http::response($this->fixtureHtml, 200),
        'ssnet.org/lessons/26b/less03.html' => Http::response($this->fixtureHtml, 200),
        'ssnet.org/lessons/26b/*' => Http::response('Not found', 404),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26b');

    expect($result['lessons_imported'])->toBe(3);
    expect($result['lessons_failed'])->toBe(10);
    expect($result['errors'])->toHaveCount(10);
});

it('upserts without duplicating on re-import', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $action->handle('26b');
    $action->handle('26b');

    expect(Quarterly::query()->count())->toBe(1);
    expect(Lesson::query()->count())->toBe(13);
    expect(LessonDay::query()->count())->toBe(91);
});

it('sets the imported quarter as active and deactivates others', function (): void {
    $oldQuarter = Quarterly::factory()->active()->create(['quarter_code' => '26a']);

    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $action->handle('26b');

    expect($oldQuarter->fresh()->is_active)->toBeFalse();
    expect(Quarterly::query()->where('quarter_code', '26b')->first()->is_active)->toBeTrue();
});

it('resolves the current quarter code based on date', function (): void {
    $action = resolve(ImportQuarter::class);
    $code = $action->resolveCurrentQuarterCode();

    // Current date is April 2026, which is Q2
    expect($code)->toBe('26b');
});

it('skips lessons with tiny responses', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/less01.html' => Http::response($this->fixtureHtml, 200),
        'ssnet.org/lessons/26b/*' => Http::response('<html><body>stub</body></html>', 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26b');

    expect($result['lessons_imported'])->toBe(1);
    expect($result['lessons_failed'])->toBe(12);
});

it('extracts the quarter title from the page', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26b');

    expect($result['quarterly']->title)->toBe('Growing in a Relationship With God');
});

it('falls back to title tag when em tag is not found', function (): void {
    $htmlNoEm = '<html><head><title>My Quarter - Sabbath School Lesson 01, 1st Qtr 2026</title></head><body>'.mb_substr($this->fixtureHtml, mb_strpos($this->fixtureHtml, '<nav'));

    Http::fake([
        'ssnet.org/lessons/26a/less01.html' => Http::response($htmlNoEm, 200),
        'ssnet.org/lessons/26a/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26a');

    expect($result['quarterly']->title)->toBe('My Quarter');
});

it('falls back to default title when page fetch fails', function (): void {
    Http::fake([
        'ssnet.org/lessons/26a/less01.html' => Http::response('error', 500),
        'ssnet.org/lessons/26a/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26a');

    expect($result['quarterly']->title)->toBe('Quarter 26a');
});

it('handles connection failures gracefully during lesson fetch', function (): void {
    Http::fake([
        'ssnet.org/lessons/26a/less01.html' => Http::response($this->fixtureHtml, 200),
        'ssnet.org/lessons/26a/*' => Http::response('Server Error', 500),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26a');

    expect($result['lessons_imported'])->toBe(1);
    expect($result['lessons_failed'])->toBe(12);
});

it('handles parse exceptions gracefully', function (): void {
    // Create HTML that will trigger an exception in the parser
    $badHtml = str_repeat('<p>content</p>', 100); // No proper structure but > 2000 chars

    Http::fake([
        'ssnet.org/lessons/26a/less01.html' => Http::response($this->fixtureHtml, 200),
        'ssnet.org/lessons/26a/less02.html' => Http::response($badHtml, 200),
        'ssnet.org/lessons/26a/*' => Http::response('Not found', 404),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26a');

    // less01 should succeed, less02 may succeed or fail depending on parse
    // The remaining 11 should fail (404)
    expect($result['lessons_imported'])->toBeGreaterThanOrEqual(1);
});

it('defaults to current quarter when null is passed', function (): void {
    Http::fake([
        'ssnet.org/lessons/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle();

    // Current date is April 2026 = Q2 = 26b
    expect($result['quarterly']->quarter_code)->toBe('26b');
});

it('stores the source url on the quarterly', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26b');

    expect($result['quarterly']->source_url)->toBe('https://ssnet.org/lessons/26b/');
});

it('handles invalid quarter code letter gracefully', function (): void {
    Http::fake([
        'ssnet.org/lessons/26z/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26z');

    // Invalid letter should default to quarter_number 1
    expect($result['quarterly']->quarter_number)->toBe(1);
});

it('uses fallback title when title page returns error', function (): void {
    Http::fake([
        'ssnet.org/lessons/26d/less01.html' => Http::response('', 500),
        'ssnet.org/lessons/26d/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26d');

    expect($result['quarterly']->title)->toBe('Quarter 26d');
});

it('handles server errors during lesson fetch', function (): void {
    Http::fake([
        'ssnet.org/lessons/26c/less01.html' => Http::response($this->fixtureHtml, 200),
        'ssnet.org/lessons/26c/*' => Http::response('', 503),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26c');

    expect($result['lessons_imported'])->toBe(1);
    expect($result['lessons_failed'])->toBe(12);
});

it('parses date strings with just a day number using reference date', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26b');

    // The fixture has "April 11-17", so date_end should parse "17" using "April" from date_start
    $lesson = Lesson::query()->where('quarterly_id', $result['quarterly']->id)->first();
    expect($lesson->date_end->format('Y-m-d'))->toBe('2026-04-17');
});

it('handles connection exception in title fetch via Http::fake throwing', function (): void {
    Http::fake(function (): void {
        throw new Illuminate\Http\Client\ConnectionException('DNS resolution failed');
    });

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26c');

    // All should fail since every request throws
    expect($result['quarterly']->title)->toBe('Quarter 26c');
    expect($result['lessons_imported'])->toBe(0);
    expect($result['lessons_failed'])->toBe(13);
});

it('handles missing date range with empty week_dates', function (): void {
    // Create HTML with empty week_dates which will cause parseDateString to receive empty strings
    $padding = str_repeat('<p>Padding content for size requirement.</p>', 50);
    $syntheticHtml = '<html><head><title>Test</title></head><body>';
    $syntheticHtml .= '<p class="flush-left"><span class="lesson_number">Lesson 1</span> <span class="week_dates"></span></p>';
    $syntheticHtml .= '<h2>Test Lesson</h2>';
    $syntheticHtml .= '<p class="memory_text">Memory Text: "Test memory" (John 3:16).</p>';
    $syntheticHtml .= '<p id="sab">Sabbath</p><p>Content</p>';
    $syntheticHtml .= '<p id="sun" class="day">Sunday</p><p>Content</p>';
    $syntheticHtml .= '<p id="mon" class="day">Monday</p><p>Content</p>';
    $syntheticHtml .= '<p id="tue" class="day">Tuesday</p><p>Content</p>';
    $syntheticHtml .= '<p id="wed" class="day">Wednesday</p><p>Content</p>';
    $syntheticHtml .= '<p id="thu" class="day">Thursday</p><p>Content</p>';
    $syntheticHtml .= '<p id="fri" class="day">Friday</p><p>Content</p>';
    $syntheticHtml .= '<div id="inside_story"></div>'.$padding.'</body></html>';

    Http::fake([
        'ssnet.org/lessons/26c/*' => Http::response($syntheticHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26c');

    // Should import successfully with fallback dates
    expect($result['lessons_imported'])->toBe(13);
    $lesson = Lesson::query()->where('quarterly_id', $result['quarterly']->id)->first();
    expect($lesson->date_start)->not->toBeNull();
});

it('catches throwable when parser returns unparseable date', function (): void {
    // Create HTML with proper structure but invalid date that triggers Carbon exception
    $padding = str_repeat('<p>Padding content for size requirement.</p>', 50);
    $syntheticHtml = '<html><head><title>Test - Sabbath School Lesson 01</title></head><body>';
    $syntheticHtml .= '<p class="flush-left"><span class="lesson_number">Lesson 1</span> <span class="week_dates">InvalidDate</span></p>';
    $syntheticHtml .= '<h2>Test Lesson</h2>';
    $syntheticHtml .= '<p class="memory_text">Memory Text: "Test memory" (John 3:16).</p>';
    $syntheticHtml .= '<p id="sab">Sabbath</p><p>Sabbath content here for the lesson</p>';
    $syntheticHtml .= '<p id="sun" class="day">Sunday</p><h4>Sunday Title</h4><p>Sunday content</p>';
    $syntheticHtml .= '<p id="mon" class="day">Monday</p><h4>Monday Title</h4><p>Monday content</p>';
    $syntheticHtml .= '<p id="tue" class="day">Tuesday</p><h4>Tuesday Title</h4><p>Tuesday content</p>';
    $syntheticHtml .= '<p id="wed" class="day">Wednesday</p><h4>Wednesday Title</h4><p>Wednesday content</p>';
    $syntheticHtml .= '<p id="thu" class="day">Thursday</p><h4>Thursday Title</h4><p>Thursday content</p>';
    $syntheticHtml .= '<p id="fri" class="day">Friday</p><p>Friday content</p>';
    $syntheticHtml .= '<div id="inside_story"></div>'.$padding.'</body></html>';

    Http::fake([
        'ssnet.org/lessons/26c/*' => Http::response($syntheticHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $result = $action->handle('26c');

    // Should import successfully (date falls back to current date)
    expect($result['lessons_imported'])->toBe(13);
});

it('logs unparseable scripture references without failing', function (): void {
    Log::spy();

    $lessonDay = LessonDay::factory()->create();
    $parser = new ScriptureReferenceParser();

    $action = resolve(ImportQuarter::class);
    $method = new ReflectionMethod($action, 'upsertScriptureReferences');
    $method->invoke($action, $lessonDay, ['not a reference', 'John 3:16'], $parser);

    expect(LessonDayScriptureReference::query()->where('lesson_day_id', $lessonDay->id)->count())->toBe(1);
    expect(LessonDayScriptureReference::query()->where('lesson_day_id', $lessonDay->id)->first()->book)->toBe('John');

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context): bool => $message === 'ImportQuarter: Could not parse scripture reference'
            && $context['reference'] === 'not a reference')
        ->once();
});
