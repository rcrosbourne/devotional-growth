<?php

declare(strict_types=1);

use App\Actions\UpdateDevotionalEntry;
use App\Models\DevotionalEntry;
use App\Models\ScriptureReference;

it('updates devotional entry fields', function (): void {
    $entry = DevotionalEntry::factory()->create([
        'title' => 'Old Title',
        'body' => 'Old body.',
    ]);
    ScriptureReference::factory()->for($entry)->create();

    $action = resolve(UpdateDevotionalEntry::class);

    $updated = $action->handle($entry, [
        'title' => 'New Title',
        'body' => 'New body content.',
        'reflection_prompts' => 'Updated prompt.',
        'adventist_insights' => 'Updated insight.',
        'scripture_references' => ['Romans 12:1-2'],
    ]);

    expect($updated->title)->toBe('New Title')
        ->and($updated->body)->toBe('New body content.')
        ->and($updated->reflection_prompts)->toBe('Updated prompt.')
        ->and($updated->adventist_insights)->toBe('Updated insight.');
});

it('syncs scripture references replacing old ones', function (): void {
    $entry = DevotionalEntry::factory()->create();
    ScriptureReference::factory()->for($entry)->count(2)->create();

    $action = resolve(UpdateDevotionalEntry::class);

    $updated = $action->handle($entry, [
        'title' => $entry->title,
        'body' => $entry->body,
        'scripture_references' => ['Genesis 1:1'],
    ]);

    expect($updated->scriptureReferences)->toHaveCount(1)
        ->and($updated->scriptureReferences->first()->book)->toBe('Genesis');
});

it('sets optional fields to null when not provided', function (): void {
    $entry = DevotionalEntry::factory()->create([
        'reflection_prompts' => 'Some prompt',
        'adventist_insights' => 'Some insight',
    ]);
    ScriptureReference::factory()->for($entry)->create();

    $action = resolve(UpdateDevotionalEntry::class);

    $updated = $action->handle($entry, [
        'title' => $entry->title,
        'body' => $entry->body,
        'scripture_references' => ['John 1:1'],
    ]);

    expect($updated->reflection_prompts)->toBeNull()
        ->and($updated->adventist_insights)->toBeNull();
});
