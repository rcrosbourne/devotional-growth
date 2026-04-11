<?php

declare(strict_types=1);

use App\Actions\CreateBookmark;
use App\Models\Bookmark;
use App\Models\DevotionalEntry;
use App\Models\ScriptureReference;
use App\Models\User;
use App\Models\WordStudy;

it('creates a bookmark for a devotional entry', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->create();
    $action = resolve(CreateBookmark::class);

    $bookmark = $action->handle($user, DevotionalEntry::class, $entry->id);

    expect($bookmark)
        ->toBeInstanceOf(Bookmark::class)
        ->user_id->toBe($user->id)
        ->bookmarkable_type->toBe(DevotionalEntry::class)
        ->bookmarkable_id->toBe($entry->id);
});

it('creates a bookmark for a scripture reference', function (): void {
    $user = User::factory()->create();
    $reference = ScriptureReference::factory()->create();
    $action = resolve(CreateBookmark::class);

    $bookmark = $action->handle($user, ScriptureReference::class, $reference->id);

    expect($bookmark)
        ->toBeInstanceOf(Bookmark::class)
        ->bookmarkable_type->toBe(ScriptureReference::class)
        ->bookmarkable_id->toBe($reference->id);
});

it('creates a bookmark for a word study', function (): void {
    $user = User::factory()->create();
    $wordStudy = WordStudy::factory()->create();
    $action = resolve(CreateBookmark::class);

    $bookmark = $action->handle($user, WordStudy::class, $wordStudy->id);

    expect($bookmark)
        ->toBeInstanceOf(Bookmark::class)
        ->bookmarkable_type->toBe(WordStudy::class)
        ->bookmarkable_id->toBe($wordStudy->id);
});

it('does not create a duplicate bookmark for the same user and entity', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->create();
    $action = resolve(CreateBookmark::class);

    $first = $action->handle($user, DevotionalEntry::class, $entry->id);
    $second = $action->handle($user, DevotionalEntry::class, $entry->id);

    expect($first->id)->toBe($second->id);
    expect(Bookmark::query()->count())->toBe(1);
});

it('allows different users to bookmark the same entity', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $entry = DevotionalEntry::factory()->create();
    $action = resolve(CreateBookmark::class);

    $action->handle($user1, DevotionalEntry::class, $entry->id);
    $action->handle($user2, DevotionalEntry::class, $entry->id);

    expect(Bookmark::query()->count())->toBe(2);
});

it('throws an exception for an invalid bookmarkable type', function (): void {
    $user = User::factory()->create();
    $action = resolve(CreateBookmark::class);

    $action->handle($user, User::class, 1);
})->throws(InvalidArgumentException::class);

it('throws an exception when the bookmarkable entity does not exist', function (): void {
    $user = User::factory()->create();
    $action = resolve(CreateBookmark::class);

    $action->handle($user, DevotionalEntry::class, 99999);
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);
