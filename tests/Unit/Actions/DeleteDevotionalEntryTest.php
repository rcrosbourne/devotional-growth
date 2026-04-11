<?php

declare(strict_types=1);

use App\Actions\DeleteDevotionalEntry;
use App\Models\DevotionalEntry;
use App\Models\ScriptureReference;

it('deletes a devotional entry', function (): void {
    $entry = DevotionalEntry::factory()->create();
    $action = resolve(DeleteDevotionalEntry::class);

    $action->handle($entry);

    expect(DevotionalEntry::query()->find($entry->id))->toBeNull();
});

it('cascades deletion to scripture references', function (): void {
    $entry = DevotionalEntry::factory()->create();
    ScriptureReference::factory()->for($entry)->count(2)->create();

    $action = resolve(DeleteDevotionalEntry::class);

    $action->handle($entry);

    expect(ScriptureReference::query()->where('devotional_entry_id', $entry->id)->count())->toBe(0);
});
