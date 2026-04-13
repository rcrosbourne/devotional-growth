<?php

declare(strict_types=1);

use App\Actions\UnpublishDevotionalEntry;
use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use App\Models\Theme;

it('unpublishes a published entry', function (): void {
    $entry = DevotionalEntry::factory()->published()->create();

    $action = resolve(UnpublishDevotionalEntry::class);
    $result = $action->handle($entry);

    expect($result->status)->toBe(ContentStatus::Draft);
});

it('unpublishes the theme when the last published entry is unpublished', function (): void {
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $action = resolve(UnpublishDevotionalEntry::class);
    $action->handle($entry);

    expect($theme->refresh()->status)->toBe(ContentStatus::Draft);
});

it('does not unpublish the theme when other published entries remain', function (): void {
    $theme = Theme::factory()->published()->create();
    $entry1 = DevotionalEntry::factory()->published()->for($theme)->create();
    DevotionalEntry::factory()->published()->for($theme)->create();

    $action = resolve(UnpublishDevotionalEntry::class);
    $action->handle($entry1);

    expect($entry1->refresh()->status)->toBe(ContentStatus::Draft)
        ->and($theme->refresh()->status)->toBe(ContentStatus::Published);
});

it('handles unpublishing an already draft entry', function (): void {
    $entry = DevotionalEntry::factory()->draft()->create();

    $action = resolve(UnpublishDevotionalEntry::class);
    $result = $action->handle($entry);

    expect($result->status)->toBe(ContentStatus::Draft);
});
