<?php

declare(strict_types=1);

use App\Models\Bookmark;
use App\Models\DevotionalEntry;
use App\Models\ScriptureReference;
use App\Models\User;
use App\Models\WordStudy;

// Index

it('renders the bookmarks index page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('bookmarks.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('bookmarks/index'));
});

it('groups bookmarks by type', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->create();
    $reference = ScriptureReference::factory()->create();
    $wordStudy = WordStudy::factory()->create();

    Bookmark::factory()->for($user)->forDevotionalEntry($entry)->create();
    Bookmark::factory()->for($user)->forScriptureReference($reference)->create();
    Bookmark::factory()->for($user)->forWordStudy($wordStudy)->create();

    $response = $this->actingAs($user)
        ->get(route('bookmarks.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bookmarks/index')
            ->has('devotionalEntries', 1)
            ->has('scriptureReferences', 1)
            ->has('wordStudies', 1)
        );
});

it('only shows bookmarks belonging to the authenticated user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $entry = DevotionalEntry::factory()->create();

    Bookmark::factory()->for($otherUser)->forDevotionalEntry($entry)->create();

    $response = $this->actingAs($user)
        ->get(route('bookmarks.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bookmarks/index')
            ->has('devotionalEntries', 0)
            ->has('scriptureReferences', 0)
            ->has('wordStudies', 0)
        );
});

it('loads the bookmarkable relationship', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->create(['title' => 'Walking in Faith']);
    Bookmark::factory()->for($user)->forDevotionalEntry($entry)->create();

    $response = $this->actingAs($user)
        ->get(route('bookmarks.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bookmarks/index')
            ->has('devotionalEntries', 1)
            ->where('devotionalEntries.0.bookmarkable.title', 'Walking in Faith')
        );
});

// Store

it('creates a bookmark for a devotional entry', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'bookmarkable_type' => DevotionalEntry::class,
            'bookmarkable_id' => $entry->id,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $user->id,
        'bookmarkable_type' => DevotionalEntry::class,
        'bookmarkable_id' => $entry->id,
    ]);
});

it('creates a bookmark for a scripture reference', function (): void {
    $user = User::factory()->create();
    $reference = ScriptureReference::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'bookmarkable_type' => ScriptureReference::class,
            'bookmarkable_id' => $reference->id,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $user->id,
        'bookmarkable_type' => ScriptureReference::class,
        'bookmarkable_id' => $reference->id,
    ]);
});

it('creates a bookmark for a word study', function (): void {
    $user = User::factory()->create();
    $wordStudy = WordStudy::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'bookmarkable_type' => WordStudy::class,
            'bookmarkable_id' => $wordStudy->id,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $user->id,
        'bookmarkable_type' => WordStudy::class,
        'bookmarkable_id' => $wordStudy->id,
    ]);
});

it('rejects an invalid bookmarkable type', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'bookmarkable_type' => User::class,
            'bookmarkable_id' => 1,
        ]);

    $response->assertSessionHasErrors('bookmarkable_type');
});

it('rejects missing bookmarkable type', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'bookmarkable_id' => 1,
        ]);

    $response->assertSessionHasErrors('bookmarkable_type');
});

it('rejects missing bookmarkable id', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'bookmarkable_type' => DevotionalEntry::class,
        ]);

    $response->assertSessionHasErrors('bookmarkable_id');
});

// Destroy

it('deletes a bookmark belonging to the user', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->forDevotionalEntry($entry)->create();

    $response = $this->actingAs($user)
        ->delete(route('bookmarks.destroy', $bookmark));

    $response->assertRedirect();
    $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
});

it('forbids deleting another user bookmark', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $entry = DevotionalEntry::factory()->create();
    $bookmark = Bookmark::factory()->for($otherUser)->forDevotionalEntry($entry)->create();

    $response = $this->actingAs($user)
        ->delete(route('bookmarks.destroy', $bookmark));

    $response->assertForbidden();
    $this->assertDatabaseHas('bookmarks', ['id' => $bookmark->id]);
});

// Authentication

it('redirects unauthenticated users to login for index', function (): void {
    $response = $this->get(route('bookmarks.index'));

    $response->assertRedirectToRoute('login');
});

it('redirects unauthenticated users to login for store', function (): void {
    $response = $this->post(route('bookmarks.store'));

    $response->assertRedirectToRoute('login');
});

it('redirects unauthenticated users to login for destroy', function (): void {
    $bookmark = Bookmark::factory()->create();

    $response = $this->delete(route('bookmarks.destroy', $bookmark));

    $response->assertRedirectToRoute('login');
});

it('redirects unverified users for index', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('bookmarks.index'));

    $response->assertRedirect(route('verification.notice'));
});
