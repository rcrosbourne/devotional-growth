<?php

declare(strict_types=1);

use App\Actions\DeleteBookmark;
use App\Models\Bookmark;
use App\Models\DevotionalEntry;
use App\Models\User;

it('deletes a bookmark', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->forDevotionalEntry($entry)->create();
    $action = resolve(DeleteBookmark::class);

    $action->handle($bookmark);

    expect(Bookmark::query()->count())->toBe(0);
});

it('only deletes the specified bookmark', function (): void {
    $user = User::factory()->create();
    $entry1 = DevotionalEntry::factory()->create();
    $entry2 = DevotionalEntry::factory()->create();
    $bookmarkToDelete = Bookmark::factory()->for($user)->forDevotionalEntry($entry1)->create();
    Bookmark::factory()->for($user)->forDevotionalEntry($entry2)->create();
    $action = resolve(DeleteBookmark::class);

    $action->handle($bookmarkToDelete);

    expect(Bookmark::query()->count())->toBe(1);
});
