<?php

declare(strict_types=1);

use App\Models\Bookmark;
use App\Models\DevotionalEntry;
use App\Models\User;

test('to array', function (): void {
    $bookmark = Bookmark::factory()->create()->refresh();

    expect(array_keys($bookmark->toArray()))
        ->toBe([
            'id',
            'user_id',
            'bookmarkable_type',
            'bookmarkable_id',
            'created_at',
            'updated_at',
        ]);
});

test('user returns belongs to relationship', function (): void {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    expect($bookmark->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

test('bookmarkable returns morph to relationship', function (): void {
    $entry = DevotionalEntry::factory()->create();
    $bookmark = Bookmark::factory()->forDevotionalEntry($entry)->create();

    expect($bookmark->bookmarkable)
        ->toBeInstanceOf(DevotionalEntry::class)
        ->id->toBe($entry->id);
});

test('factory defaults to devotional entry bookmarkable', function (): void {
    $bookmark = Bookmark::factory()->create();

    expect($bookmark->bookmarkable_type)->toBe(DevotionalEntry::class);
});
