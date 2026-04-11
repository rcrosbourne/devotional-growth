<?php

declare(strict_types=1);

use App\Models\DevotionalCompletion;
use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Models\Observation;
use App\Models\ScriptureReference;
use App\Models\Theme;
use App\Models\User;

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

it('includes previous and next entry IDs for navigation', function (): void {
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
            ->where('previousEntryId', $first->id)
            ->where('nextEntryId', $third->id)
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
            ->where('previousEntryId', null)
            ->where('nextEntryId', $second->id)
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
            ->where('previousEntryId', $first->id)
            ->where('nextEntryId', null)
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
            ->where('previousEntryId', null)
            ->where('nextEntryId', $third->id)
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
