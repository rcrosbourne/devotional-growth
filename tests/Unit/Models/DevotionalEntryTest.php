<?php

declare(strict_types=1);

use App\Enums\ContentStatus;
use App\Models\Bookmark;
use App\Models\DevotionalCompletion;
use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Models\Observation;
use App\Models\Theme;

test('to array', function (): void {
    $entry = DevotionalEntry::factory()->create()->refresh();

    expect(array_keys($entry->toArray()))
        ->toBe([
            'id',
            'theme_id',
            'title',
            'body',
            'reflection_prompts',
            'adventist_insights',
            'display_order',
            'status',
            'created_at',
            'updated_at',
        ]);
});

test('theme returns belongs to relationship', function (): void {
    $theme = Theme::factory()->create();
    $entry = DevotionalEntry::factory()->for($theme)->create();

    expect($entry->theme)
        ->toBeInstanceOf(Theme::class)
        ->id->toBe($theme->id);
});

test('scope published filters to published entries', function (): void {
    $theme = Theme::factory()->create();
    DevotionalEntry::factory()->for($theme)->draft()->create();
    DevotionalEntry::factory()->for($theme)->published()->create();
    DevotionalEntry::factory()->for($theme)->published()->create();

    expect(DevotionalEntry::query()->published()->count())->toBe(2);
});

test('factory creates draft entry by default', function (): void {
    $entry = DevotionalEntry::factory()->create();

    expect($entry->status)->toBe(ContentStatus::Draft);
});

test('factory published state sets status to published', function (): void {
    $entry = DevotionalEntry::factory()->published()->create();

    expect($entry->status)->toBe(ContentStatus::Published);
});

test('completions returns has many relationship', function (): void {
    $entry = DevotionalEntry::factory()->create();
    DevotionalCompletion::factory()->for($entry, 'devotionalEntry')->count(2)->create();

    expect($entry->completions)->toHaveCount(2);
});

test('observations returns has many relationship', function (): void {
    $entry = DevotionalEntry::factory()->create();
    Observation::factory()->for($entry, 'devotionalEntry')->count(2)->create();

    expect($entry->observations)->toHaveCount(2);
});

test('generated image returns has one relationship', function (): void {
    $entry = DevotionalEntry::factory()->create();
    $image = GeneratedImage::factory()->for($entry, 'devotionalEntry')->create();

    expect($entry->generatedImage)
        ->toBeInstanceOf(GeneratedImage::class)
        ->id->toBe($image->id);
});

test('bookmarks returns morph many relationship', function (): void {
    $entry = DevotionalEntry::factory()->create();
    Bookmark::factory()->forDevotionalEntry($entry)->count(2)->create();

    expect($entry->bookmarks)->toHaveCount(2);
});

test('theme has many entries', function (): void {
    $theme = Theme::factory()->create();
    DevotionalEntry::factory()->for($theme)->count(3)->create();

    expect($theme->entries)->toHaveCount(3);
});
