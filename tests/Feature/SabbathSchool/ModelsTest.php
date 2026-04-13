<?php

declare(strict_types=1);

use App\Models\Lesson;
use App\Models\LessonDay;
use App\Models\LessonDayScriptureReference;
use App\Models\Quarterly;

it('creates a quarterly with all casts working', function (): void {
    $quarterly = Quarterly::factory()->active()->synced()->create();

    $fresh = Quarterly::query()->find($quarterly->id);

    expect($fresh->id)->toBeInt();
    expect($fresh->title)->toBeString();
    expect($fresh->quarter_code)->toBeString();
    expect($fresh->year)->toBeInt();
    expect($fresh->quarter_number)->toBeInt();
    expect($fresh->is_active)->toBeTrue();
    expect($fresh->source_url)->toBeString();
    expect($fresh->last_synced_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
    expect($fresh->created_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
});

it('creates a lesson with all casts working', function (): void {
    $lesson = Lesson::factory()->create();

    $fresh = Lesson::query()->find($lesson->id);

    expect($fresh->id)->toBeInt();
    expect($fresh->quarterly_id)->toBeInt();
    expect($fresh->lesson_number)->toBeInt();
    expect($fresh->title)->toBeString();
    expect($fresh->date_start)->toBeInstanceOf(Carbon\CarbonInterface::class);
    expect($fresh->date_end)->toBeInstanceOf(Carbon\CarbonInterface::class);
    expect($fresh->memory_text)->toBeString();
    expect($fresh->memory_text_reference)->toBeString();
    expect($fresh->has_parse_warnings)->toBeFalse();
    expect($fresh->created_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
});

it('creates a lesson day with all casts working', function (): void {
    $day = LessonDay::factory()->friday()->create();

    $fresh = LessonDay::query()->find($day->id);

    expect($fresh->id)->toBeInt();
    expect($fresh->lesson_id)->toBeInt();
    expect($fresh->day_position)->toBeInt();
    expect($fresh->day_name)->toBeString();
    expect($fresh->title)->toBeString();
    expect($fresh->body)->toBeString();
    expect($fresh->discussion_questions)->toBeArray();
    expect($fresh->has_parse_warning)->toBeFalse();
    expect($fresh->created_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
});

it('quarterly has lessons relationship', function (): void {
    $quarterly = Quarterly::factory()->create();
    Lesson::factory()->count(3)->create(['quarterly_id' => $quarterly->id]);

    expect($quarterly->lessons)->toHaveCount(3);
});

it('lesson has days relationship', function (): void {
    $lesson = Lesson::factory()->create();
    LessonDay::factory()->count(7)->sequence(
        ['day_position' => 0, 'day_name' => 'Sabbath'],
        ['day_position' => 1, 'day_name' => 'Sunday'],
        ['day_position' => 2, 'day_name' => 'Monday'],
        ['day_position' => 3, 'day_name' => 'Tuesday'],
        ['day_position' => 4, 'day_name' => 'Wednesday'],
        ['day_position' => 5, 'day_name' => 'Thursday'],
        ['day_position' => 6, 'day_name' => 'Friday'],
    )->create(['lesson_id' => $lesson->id]);

    expect($lesson->days)->toHaveCount(7);
});

it('lesson belongs to quarterly', function (): void {
    $lesson = Lesson::factory()->create();

    expect($lesson->quarterly)->toBeInstanceOf(Quarterly::class);
});

it('lesson day belongs to lesson', function (): void {
    $day = LessonDay::factory()->create();

    expect($day->lesson)->toBeInstanceOf(Lesson::class);
});

it('lesson day has scripture references relationship', function (): void {
    $day = LessonDay::factory()->create();
    LessonDayScriptureReference::factory()->count(3)->create(['lesson_day_id' => $day->id]);

    expect($day->scriptureReferences)->toHaveCount(3);
});

it('lesson day scripture reference belongs to lesson day', function (): void {
    $ref = LessonDayScriptureReference::factory()->create();

    expect($ref->lessonDay)->toBeInstanceOf(LessonDay::class);
});

it('creates a scripture reference with all casts working', function (): void {
    $ref = LessonDayScriptureReference::factory()->create([
        'book' => 'John',
        'chapter' => 3,
        'verse_start' => 16,
        'verse_end' => 17,
        'raw_reference' => 'John 3:16-17',
    ]);

    $fresh = LessonDayScriptureReference::query()->find($ref->id);

    expect($fresh->id)->toBeInt();
    expect($fresh->lesson_day_id)->toBeInt();
    expect($fresh->book)->toBe('John');
    expect($fresh->chapter)->toBe(3);
    expect($fresh->verse_start)->toBe(16);
    expect($fresh->verse_end)->toBe(17);
    expect($fresh->raw_reference)->toBe('John 3:16-17');
    expect($fresh->created_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
});

it('quarterly scope active works', function (): void {
    Quarterly::factory()->active()->create();
    Quarterly::factory()->create();

    expect(Quarterly::query()->active()->count())->toBe(1);
});

it('lesson factory with image state works', function (): void {
    $lesson = Lesson::factory()->withImage()->create();

    expect($lesson->image_path)->not->toBeNull();
    expect($lesson->image_prompt)->not->toBeNull();
});

it('lesson factory with parse warnings state works', function (): void {
    $lesson = Lesson::factory()->withParseWarnings()->create();

    expect($lesson->has_parse_warnings)->toBeTrue();
});

it('lesson day factory sabbath state works', function (): void {
    $day = LessonDay::factory()->sabbath()->create();

    expect($day->day_position)->toBe(0);
    expect($day->day_name)->toBe('Sabbath');
});

it('lesson day factory forDay state works', function (): void {
    $day = LessonDay::factory()->forDay(3)->create();

    expect($day->day_position)->toBe(3);
    expect($day->day_name)->toBe('Tuesday');
});

it('lesson day factory withParseWarning state works', function (): void {
    $day = LessonDay::factory()->withParseWarning()->create();

    expect($day->has_parse_warning)->toBeTrue();
});

it('lesson day discussion_questions cast returns null when not set', function (): void {
    $day = LessonDay::factory()->create(['discussion_questions' => null]);

    expect(LessonDay::query()->find($day->id)->discussion_questions)->toBeNull();
});
