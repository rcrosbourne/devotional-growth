<?php

declare(strict_types=1);

use App\Models\DevotionalCompletion;
use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

// Index

it('renders the themes index page for authenticated verified users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('themes/index'));
});

it('only shows published themes on index', function (): void {
    $user = User::factory()->create();
    Theme::factory()->published()->create();
    Theme::factory()->published()->create();
    Theme::factory()->draft()->create();

    $response = $this->actingAs($user)
        ->get(route('themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/index')
            ->has('themes', 2)
        );
});

it('includes published entry counts per theme', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    DevotionalEntry::factory()->published()->for($theme)->count(3)->create();
    DevotionalEntry::factory()->draft()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/index')
            ->has('themes', 1)
            ->where('themes.0.entries_count', 3)
        );
});

it('includes completed entry count for the authenticated user', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entries = DevotionalEntry::factory()->published()->for($theme)->count(3)->create();

    DevotionalCompletion::factory()->create([
        'user_id' => $user->id,
        'devotional_entry_id' => $entries[0]->id,
    ]);
    DevotionalCompletion::factory()->create([
        'user_id' => $user->id,
        'devotional_entry_id' => $entries[1]->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/index')
            ->where('themes.0.completed_entries_count', 2)
        );
});

it('does not count other users completions for the authenticated user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    DevotionalCompletion::factory()->create([
        'user_id' => $otherUser->id,
        'devotional_entry_id' => $entry->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/index')
            ->where('themes.0.completed_entries_count', 0)
        );
});

it('displays empty state when no published themes exist', function (): void {
    $user = User::factory()->create();
    Theme::factory()->draft()->create();

    $response = $this->actingAs($user)
        ->get(route('themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/index')
            ->has('themes', 0)
        );
});

it('includes cover image path from first published entry with a generated image', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 1]);
    $image = GeneratedImage::factory()->for($entry, 'devotionalEntry')->create();

    Storage::disk('public')->put($image->path, 'fake-image-content');

    $response = $this->actingAs($user)
        ->get(route('themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/index')
            ->where('themes.0.cover_image_path', $image->path)
        );
});

it('returns null cover image when no entries have generated images', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/index')
            ->where('themes.0.cover_image_path', null)
        );
});

it('redirects unauthenticated users to login from themes index', function (): void {
    $response = $this->get(route('themes.index'));

    $response->assertRedirectToRoute('login');
});

it('redirects unverified users from themes index', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('themes.index'));

    $response->assertRedirect(route('verification.notice'));
});

// Show

it('renders a published theme show page', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();

    $response = $this->actingAs($user)
        ->get(route('themes.show', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('themes/show'));
});

it('returns 404 for a draft theme', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->draft()->create();

    $response = $this->actingAs($user)
        ->get(route('themes.show', $theme));

    $response->assertNotFound();
});

it('only shows published entries in theme show', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    DevotionalEntry::factory()->published()->for($theme)->count(2)->create();
    DevotionalEntry::factory()->draft()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.show', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/show')
            ->has('entries', 2)
        );
});

it('shows entries ordered by display_order', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 2, 'title' => 'Second']);
    DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 1, 'title' => 'First']);

    $response = $this->actingAs($user)
        ->get(route('themes.show', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/show')
            ->where('entries.0.title', 'First')
            ->where('entries.1.title', 'Second')
        );
});

it('includes progress data in theme show', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entries = DevotionalEntry::factory()->published()->for($theme)->count(4)->create();

    DevotionalCompletion::factory()->create([
        'user_id' => $user->id,
        'devotional_entry_id' => $entries[0]->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('themes.show', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/show')
            ->where('progress.total', 4)
            ->where('progress.completed', 1)
            ->where('progress.percentage', 25)
        );
});

it('includes completion status per entry in theme show', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    DevotionalCompletion::factory()->create([
        'user_id' => $user->id,
        'devotional_entry_id' => $entry->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('themes.show', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/show')
            ->has('entries.0.completions', 1)
        );
});

it('includes entry image path when generated image file exists on disk', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();
    $image = GeneratedImage::factory()->for($entry, 'devotionalEntry')->create();

    Storage::disk('public')->put($image->path, 'fake-image-content');

    $response = $this->actingAs($user)
        ->get(route('themes.show', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/show')
            ->where('entries.0.image_path', $image->path)
        );
});

it('returns null entry image path when generated image file is missing', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();
    GeneratedImage::factory()->for($entry, 'devotionalEntry')->create();

    $response = $this->actingAs($user)
        ->get(route('themes.show', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('themes/show')
            ->where('entries.0.image_path', null)
        );
});

it('redirects unauthenticated users to login from theme show', function (): void {
    $theme = Theme::factory()->published()->create();

    $response = $this->get(route('themes.show', $theme));

    $response->assertRedirectToRoute('login');
});
