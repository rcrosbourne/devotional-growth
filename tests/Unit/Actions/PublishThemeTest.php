<?php

declare(strict_types=1);

use App\Actions\PublishTheme;
use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use App\Models\Theme;
use Illuminate\Validation\ValidationException;

it('publishes a draft theme with at least one published entry', function (): void {
    $theme = Theme::factory()->draft()->create();
    DevotionalEntry::factory()->published()->for($theme)->create();

    $action = resolve(PublishTheme::class);
    $published = $action->handle($theme);

    expect($published->status)->toBe(ContentStatus::Published);
});

it('remains published when already published', function (): void {
    $theme = Theme::factory()->published()->create();
    DevotionalEntry::factory()->published()->for($theme)->create();

    $action = resolve(PublishTheme::class);
    $published = $action->handle($theme);

    expect($published->status)->toBe(ContentStatus::Published);
});

it('throws validation exception when publishing theme with no published entries', function (): void {
    $theme = Theme::factory()->draft()->create();
    DevotionalEntry::factory()->draft()->for($theme)->create();

    $action = resolve(PublishTheme::class);
    $action->handle($theme);
})->throws(ValidationException::class);

it('throws validation exception when publishing theme with no entries at all', function (): void {
    $theme = Theme::factory()->draft()->create();

    $action = resolve(PublishTheme::class);
    $action->handle($theme);
})->throws(ValidationException::class);
