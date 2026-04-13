<?php

declare(strict_types=1);

use App\Actions\PublishDevotionalEntry;
use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use App\Models\Theme;

it('publishes a draft devotional entry', function (): void {
    $entry = DevotionalEntry::factory()->draft()->create();
    $action = resolve(PublishDevotionalEntry::class);

    $published = $action->handle($entry);

    expect($published->status)->toBe(ContentStatus::Published);
});

it('remains published when already published', function (): void {
    $entry = DevotionalEntry::factory()->published()->create();
    $action = resolve(PublishDevotionalEntry::class);

    $published = $action->handle($entry);

    expect($published->status)->toBe(ContentStatus::Published);
});

it('auto-publishes the theme when publishing an entry under a draft theme', function (): void {
    $theme = Theme::factory()->draft()->create();
    $entry = DevotionalEntry::factory()->draft()->for($theme)->create();

    $action = resolve(PublishDevotionalEntry::class);
    $action->handle($entry);

    expect($theme->refresh()->status)->toBe(ContentStatus::Published);
});

it('does not change theme status when theme is already published', function (): void {
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->draft()->for($theme)->create();

    $action = resolve(PublishDevotionalEntry::class);
    $action->handle($entry);

    expect($theme->refresh()->status)->toBe(ContentStatus::Published);
});
