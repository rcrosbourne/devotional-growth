<?php

declare(strict_types=1);

use App\Actions\PublishTheme;
use App\Enums\ContentStatus;
use App\Models\Theme;

it('publishes a draft theme', function (): void {
    $theme = Theme::factory()->draft()->create();
    $action = resolve(PublishTheme::class);

    $published = $action->handle($theme);

    expect($published->status)->toBe(ContentStatus::Published);
});

it('remains published when already published', function (): void {
    $theme = Theme::factory()->published()->create();
    $action = resolve(PublishTheme::class);

    $published = $action->handle($theme);

    expect($published->status)->toBe(ContentStatus::Published);
});
