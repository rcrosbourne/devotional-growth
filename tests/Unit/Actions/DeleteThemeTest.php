<?php

declare(strict_types=1);

use App\Actions\DeleteTheme;
use App\Models\DevotionalEntry;
use App\Models\Theme;

it('deletes a theme', function (): void {
    $theme = Theme::factory()->create();
    $action = resolve(DeleteTheme::class);

    $action->handle($theme);

    expect(Theme::query()->find($theme->id))->toBeNull();
});

it('cascades deletion to associated entries', function (): void {
    $theme = Theme::factory()->create();
    DevotionalEntry::factory()->count(3)->create(['theme_id' => $theme->id]);
    $action = resolve(DeleteTheme::class);

    $action->handle($theme);

    expect(DevotionalEntry::query()->where('theme_id', $theme->id)->count())->toBe(0);
});
