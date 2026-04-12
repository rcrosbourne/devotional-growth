<?php

declare(strict_types=1);

use App\Actions\UnpublishTheme;
use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use App\Models\Theme;

it('unpublishes a published theme', function (): void {
    $theme = Theme::factory()->published()->create();

    $action = resolve(UnpublishTheme::class);
    $result = $action->handle($theme);

    expect($result->status)->toBe(ContentStatus::Draft);
});

it('unpublishes all entries when theme is unpublished', function (): void {
    $theme = Theme::factory()->published()->create();
    DevotionalEntry::factory()->published()->for($theme)->count(3)->create();

    $action = resolve(UnpublishTheme::class);
    $action->handle($theme);

    expect($theme->entries()->where('status', ContentStatus::Draft)->count())->toBe(3)
        ->and($theme->entries()->where('status', ContentStatus::Published)->count())->toBe(0);
});

it('handles unpublishing a theme that is already draft', function (): void {
    $theme = Theme::factory()->draft()->create();

    $action = resolve(UnpublishTheme::class);
    $result = $action->handle($theme);

    expect($result->status)->toBe(ContentStatus::Draft);
});
