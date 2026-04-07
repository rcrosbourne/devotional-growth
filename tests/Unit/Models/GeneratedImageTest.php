<?php

declare(strict_types=1);

use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;

test('to array', function (): void {
    $image = GeneratedImage::factory()->create()->refresh();

    expect(array_keys($image->toArray()))
        ->toBe([
            'id',
            'devotional_entry_id',
            'path',
            'prompt',
            'created_at',
            'updated_at',
        ]);
});

test('devotional entry returns belongs to relationship', function (): void {
    $entry = DevotionalEntry::factory()->create();
    $image = GeneratedImage::factory()->for($entry, 'devotionalEntry')->create();

    expect($image->devotionalEntry)
        ->toBeInstanceOf(DevotionalEntry::class)
        ->id->toBe($entry->id);
});
