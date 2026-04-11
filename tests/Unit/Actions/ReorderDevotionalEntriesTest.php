<?php

declare(strict_types=1);

use App\Actions\ReorderDevotionalEntries;
use App\Models\DevotionalEntry;
use App\Models\Theme;

it('reorders entries by given id order', function (): void {
    $theme = Theme::factory()->create();
    $entry1 = DevotionalEntry::factory()->for($theme)->create(['display_order' => 0]);
    $entry2 = DevotionalEntry::factory()->for($theme)->create(['display_order' => 1]);
    $entry3 = DevotionalEntry::factory()->for($theme)->create(['display_order' => 2]);

    $action = resolve(ReorderDevotionalEntries::class);

    $action->handle($theme, [$entry3->id, $entry1->id, $entry2->id]);

    expect($entry3->refresh()->display_order)->toBe(0)
        ->and($entry1->refresh()->display_order)->toBe(1)
        ->and($entry2->refresh()->display_order)->toBe(2);
});

it('does not affect entries from other themes', function (): void {
    $theme = Theme::factory()->create();
    $otherTheme = Theme::factory()->create();
    $entry = DevotionalEntry::factory()->for($theme)->create(['display_order' => 0]);
    $otherEntry = DevotionalEntry::factory()->for($otherTheme)->create(['display_order' => 5]);

    $action = resolve(ReorderDevotionalEntries::class);

    $action->handle($theme, [$entry->id]);

    expect($otherEntry->refresh()->display_order)->toBe(5);
});
