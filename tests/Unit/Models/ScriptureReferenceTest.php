<?php

declare(strict_types=1);

use App\Models\Bookmark;
use App\Models\DevotionalEntry;
use App\Models\ScriptureReference;

test('to array', function (): void {
    $reference = ScriptureReference::factory()->create()->refresh();

    expect(array_keys($reference->toArray()))
        ->toBe([
            'id',
            'devotional_entry_id',
            'book',
            'chapter',
            'verse_start',
            'verse_end',
            'raw_reference',
            'created_at',
            'updated_at',
        ]);
});

test('devotional entry returns belongs to relationship', function (): void {
    $entry = DevotionalEntry::factory()->create();
    $reference = ScriptureReference::factory()->for($entry, 'devotionalEntry')->create();

    expect($reference->devotionalEntry)
        ->toBeInstanceOf(DevotionalEntry::class)
        ->id->toBe($entry->id);
});

test('devotional entry has many scripture references', function (): void {
    $entry = DevotionalEntry::factory()->create();
    ScriptureReference::factory()->for($entry, 'devotionalEntry')->count(3)->create();

    expect($entry->scriptureReferences)->toHaveCount(3);
});

test('bookmarks returns morph many relationship', function (): void {
    $reference = ScriptureReference::factory()->create();
    Bookmark::factory()->forScriptureReference($reference)->count(2)->create();

    expect($reference->bookmarks)->toHaveCount(2);
});

test('verse end is nullable', function (): void {
    $reference = ScriptureReference::factory()->create();

    expect($reference->verse_end)->toBeNull();
});

test('factory with verse range sets verse end and raw reference', function (): void {
    $reference = ScriptureReference::factory()->withVerseRange()->create();

    expect($reference->verse_end)->toBeGreaterThan($reference->verse_start)
        ->and($reference->raw_reference)->toContain('-');
});
