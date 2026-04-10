<?php

declare(strict_types=1);

use App\Actions\UpdateTheme;
use App\Models\DevotionalEntry;
use App\Models\Theme;

it('updates a theme name and description', function (): void {
    $theme = Theme::factory()->create(['name' => 'Old Name', 'description' => 'Old description']);
    $action = resolve(UpdateTheme::class);

    $updated = $action->handle($theme, 'New Name', 'New description');

    expect($updated->name)->toBe('New Name')
        ->and($updated->description)->toBe('New description');
});

it('preserves associated entries when updating', function (): void {
    $theme = Theme::factory()->create();
    DevotionalEntry::factory()->count(3)->create(['theme_id' => $theme->id]);
    $action = resolve(UpdateTheme::class);

    $action->handle($theme, 'Updated Name', 'Updated description');

    expect($theme->entries()->count())->toBe(3);
});

it('sets description to null when not provided', function (): void {
    $theme = Theme::factory()->create(['description' => 'Has description']);
    $action = resolve(UpdateTheme::class);

    $updated = $action->handle($theme, 'Same Name');

    expect($updated->description)->toBeNull();
});
