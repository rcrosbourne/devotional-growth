<?php

declare(strict_types=1);

use App\Models\DevotionalEntry;
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

    expect(DevotionalEntry::published()->count())->toBe(2);
});

test('factory creates draft entry by default', function (): void {
    $entry = DevotionalEntry::factory()->create();

    expect($entry->status)->toBe('draft');
});

test('factory published state sets status to published', function (): void {
    $entry = DevotionalEntry::factory()->published()->create();

    expect($entry->status)->toBe('published');
});

test('theme has many entries', function (): void {
    $theme = Theme::factory()->create();
    DevotionalEntry::factory()->for($theme)->count(3)->create();

    expect($theme->entries)->toHaveCount(3);
});
