<?php

declare(strict_types=1);

use App\Actions\PublishDevotionalEntry;
use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;

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
