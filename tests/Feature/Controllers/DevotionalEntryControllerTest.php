<?php

declare(strict_types=1);

use App\Models\Bookmark;
use App\Models\DevotionalCompletion;
use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Models\Observation;
use App\Models\ScriptureReference;
use App\Models\Theme;
use App\Models\User;
use App\Notifications\PartnerCompletedEntry;
use Illuminate\Support\Facades\Notification;

// Show

it('renders a published devotional entry show page', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('devotional-entries/show'));
});

it('returns 404 for a draft devotional entry', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->draft()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertNotFound();
});

it('returns 404 for an entry in a draft theme', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->draft()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertNotFound();
});

it('returns 404 when entry does not belong to the theme', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $otherTheme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($otherTheme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertNotFound();
});

it('includes scripture references in the entry show', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();
    ScriptureReference::factory()->for($entry)->count(2)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->has('entry.scripture_references', 2)
        );
});

it('includes generated image when available', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();
    GeneratedImage::factory()->for($entry)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->has('entry.generated_image')
        );
});

it('includes completion status for the authenticated user', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    DevotionalCompletion::factory()->create([
        'user_id' => $user->id,
        'devotional_entry_id' => $entry->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('isCompleted', true)
        );
});

it('shows not completed when user has not completed the entry', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('isCompleted', false)
        );
});

// Previous / Next Navigation

it('includes previous and next entry data for navigation', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $first = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 1]);
    $second = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 2]);
    $third = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 3]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $second]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('previousEntry.id', $first->id)
            ->where('previousEntry.title', $first->title)
            ->where('nextEntry.id', $third->id)
            ->where('nextEntry.title', $third->title)
        );
});

it('has no previous entry for the first entry', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $first = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 1]);
    $second = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 2]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $first]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('previousEntry', null)
            ->where('nextEntry.id', $second->id)
        );
});

it('has no next entry for the last entry', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $first = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 1]);
    $last = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 2]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $last]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('previousEntry.id', $first->id)
            ->where('nextEntry', null)
        );
});

it('excludes draft entries from previous/next navigation', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $first = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 1]);
    DevotionalEntry::factory()->draft()->for($theme)->create(['display_order' => 2]);
    $third = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 3]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $first]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('previousEntry', null)
            ->where('nextEntry.id', $third->id)
        );
});

// Solo vs Partner Mode

it('shows hasPartner as false for solo users', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('hasPartner', false)
        );
});

it('shows hasPartner as true for partnered users', function (): void {
    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('hasPartner', true)
        );
});

it('shows own observations for solo users', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();
    Observation::factory()->create([
        'user_id' => $user->id,
        'devotional_entry_id' => $entry->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->has('entry.observations', 1)
        );
});

it('shows both user and partner observations for partnered users', function (): void {
    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    Observation::factory()->create([
        'user_id' => $user->id,
        'devotional_entry_id' => $entry->id,
    ]);
    Observation::factory()->create([
        'user_id' => $partner->id,
        'devotional_entry_id' => $entry->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->has('entry.observations', 2)
        );
});

it('does not show other users observations for solo users', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    Observation::factory()->create([
        'user_id' => $otherUser->id,
        'devotional_entry_id' => $entry->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->has('entry.observations', 0)
        );
});

// Authentication

it('redirects unauthenticated users to login', function (): void {
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertRedirectToRoute('login');
});

it('redirects unverified users', function (): void {
    $user = User::factory()->unverified()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertRedirect(route('verification.notice'));
});

// Entry content

it('includes theme data in the entry show', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create(['name' => 'Faith']);
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('theme.name', 'Faith')
        );
});

it('includes entry body, reflection prompts, and adventist insights', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create([
        'title' => 'Test Entry',
        'body' => 'Test body content',
        'reflection_prompts' => 'What do you think?',
        'adventist_insights' => 'Sabbath perspective',
    ]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('entry.title', 'Test Entry')
            ->where('entry.body', 'Test body content')
            ->where('entry.reflection_prompts', 'What do you think?')
            ->where('entry.adventist_insights', 'Sabbath perspective')
        );
});

// Complete

it('marks a devotional entry as complete', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->post(route('themes.entries.complete', [$theme, $entry]));

    $response->assertRedirect();
    $this->assertDatabaseHas('devotional_completions', [
        'user_id' => $user->id,
        'devotional_entry_id' => $entry->id,
    ]);
});

it('returns 404 when completing an entry in a draft theme', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->draft()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->post(route('themes.entries.complete', [$theme, $entry]));

    $response->assertNotFound();
});

it('returns 404 when completing a draft entry', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->draft()->for($theme)->create();

    $response = $this->actingAs($user)
        ->post(route('themes.entries.complete', [$theme, $entry]));

    $response->assertNotFound();
});

it('returns 404 when completing an entry that does not belong to the theme', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $otherTheme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($otherTheme)->create();

    $response = $this->actingAs($user)
        ->post(route('themes.entries.complete', [$theme, $entry]));

    $response->assertNotFound();
});

it('redirects unauthenticated users when completing', function (): void {
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->post(route('themes.entries.complete', [$theme, $entry]));

    $response->assertRedirectToRoute('login');
});

it('sends a partner notification when completing an entry', function (): void {
    Notification::fake();

    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $this->actingAs($user)
        ->post(route('themes.entries.complete', [$theme, $entry]));

    Notification::assertSentTo($partner, PartnerCompletedEntry::class);
});

it('does not create duplicate completions on repeated requests', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $this->actingAs($user)
        ->post(route('themes.entries.complete', [$theme, $entry]));
    $this->actingAs($user)
        ->post(route('themes.entries.complete', [$theme, $entry]));

    expect(DevotionalCompletion::query()->count())->toBe(1);
});

// Bookmark & Position Data

it('includes bookmark status when entry is bookmarked', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();
    $bookmark = Bookmark::factory()->create([
        'user_id' => $user->id,
        'bookmarkable_type' => DevotionalEntry::class,
        'bookmarkable_id' => $entry->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('isBookmarked', true)
            ->where('bookmarkId', $bookmark->id)
        );
});

it('includes bookmark status when entry is not bookmarked', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    $entry = DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('isBookmarked', false)
            ->where('bookmarkId', null)
        );
});

it('includes entry position and total entries count', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();
    DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 1]);
    $second = DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 2]);
    DevotionalEntry::factory()->published()->for($theme)->create(['display_order' => 3]);

    $response = $this->actingAs($user)
        ->get(route('themes.entries.show', [$theme, $second]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('devotional-entries/show')
            ->where('entryPosition', 2)
            ->where('totalEntries', 3)
        );
});
