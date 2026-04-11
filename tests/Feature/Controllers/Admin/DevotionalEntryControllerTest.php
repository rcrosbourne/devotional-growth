<?php

declare(strict_types=1);

use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use App\Models\ScriptureReference;
use App\Models\Theme;
use App\Models\User;

// Index

it('renders the admin entries index page for a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.entries.index', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/devotional-entries/index'));
});

it('shows all entries regardless of status', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    DevotionalEntry::factory()->draft()->for($theme)->create();
    DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.entries.index', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/devotional-entries/index')
            ->has('entries', 2)
        );
});

it('orders entries by display_order', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    DevotionalEntry::factory()->for($theme)->create(['display_order' => 2, 'title' => 'Third']);
    DevotionalEntry::factory()->for($theme)->create(['display_order' => 0, 'title' => 'First']);
    DevotionalEntry::factory()->for($theme)->create(['display_order' => 1, 'title' => 'Second']);

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.entries.index', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('entries', 3)
            ->where('entries.0.title', 'First')
            ->where('entries.1.title', 'Second')
            ->where('entries.2.title', 'Third')
        );
});

it('denies non-admin access to entries index', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('admin.themes.entries.index', $theme));

    $response->assertForbidden();
});

it('redirects unauthenticated users to login from entries index', function (): void {
    $theme = Theme::factory()->create();

    $response = $this->get(route('admin.themes.entries.index', $theme));

    $response->assertRedirectToRoute('login');
});

// Create

it('renders the create entry form', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.entries.create', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/devotional-entries/create'));
});

// Store

it('creates a new devotional entry', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.entries.create', $theme)
        ->post(route('admin.themes.entries.store', $theme), [
            'title' => 'Walking in Faith',
            'body' => 'A devotional about walking in faith.',
            'reflection_prompts' => 'What does faith mean to you?',
            'adventist_insights' => 'Adventist perspective on faith.',
            'scripture_references' => ['John 3:16', 'Romans 8:28-39'],
        ]);

    $response->assertRedirectToRoute('admin.themes.entries.index', $theme);

    $entry = DevotionalEntry::query()->where('title', 'Walking in Faith')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->body)->toBe('A devotional about walking in faith.')
        ->and($entry->status)->toBe(ContentStatus::Draft)
        ->and($entry->theme_id)->toBe($theme->id)
        ->and($entry->scriptureReferences)->toHaveCount(2);
});

it('creates an entry without optional fields', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.entries.create', $theme)
        ->post(route('admin.themes.entries.store', $theme), [
            'title' => 'Simple Entry',
            'body' => 'A simple devotional.',
            'scripture_references' => ['Psalm 23:1-6'],
        ]);

    $response->assertRedirectToRoute('admin.themes.entries.index', $theme);

    $entry = DevotionalEntry::query()->where('title', 'Simple Entry')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->reflection_prompts)->toBeNull()
        ->and($entry->adventist_insights)->toBeNull();
});

it('requires a title to create an entry', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.entries.create', $theme)
        ->post(route('admin.themes.entries.store', $theme), [
            'body' => 'Body text.',
            'scripture_references' => ['John 3:16'],
        ]);

    $response->assertRedirectToRoute('admin.themes.entries.create', $theme)
        ->assertSessionHasErrors('title');
});

it('requires a body to create an entry', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.entries.create', $theme)
        ->post(route('admin.themes.entries.store', $theme), [
            'title' => 'A Title',
            'scripture_references' => ['John 3:16'],
        ]);

    $response->assertRedirectToRoute('admin.themes.entries.create', $theme)
        ->assertSessionHasErrors('body');
});

it('requires at least one scripture reference to create an entry', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.entries.create', $theme)
        ->post(route('admin.themes.entries.store', $theme), [
            'title' => 'A Title',
            'body' => 'Body text.',
            'scripture_references' => [],
        ]);

    $response->assertRedirectToRoute('admin.themes.entries.create', $theme)
        ->assertSessionHasErrors('scripture_references');
});

it('validates scripture reference format', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.entries.create', $theme)
        ->post(route('admin.themes.entries.store', $theme), [
            'title' => 'A Title',
            'body' => 'Body text.',
            'scripture_references' => ['not a reference'],
        ]);

    $response->assertRedirectToRoute('admin.themes.entries.create', $theme)
        ->assertSessionHasErrors('scripture_references.0');
});

it('denies non-admin from creating an entry', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('admin.themes.entries.store', $theme), [
            'title' => 'A Title',
            'body' => 'Body text.',
            'scripture_references' => ['John 3:16'],
        ]);

    $response->assertForbidden();
});

// Edit

it('renders the edit entry form', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    $entry = DevotionalEntry::factory()->for($theme)->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.entries.edit', [$theme, $entry]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/devotional-entries/edit')
            ->has('entry')
            ->has('theme')
        );
});

// Update

it('updates a devotional entry', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    $entry = DevotionalEntry::factory()->for($theme)->create(['title' => 'Old Title']);
    ScriptureReference::factory()->for($entry)->create();

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.entries.edit', [$theme, $entry])
        ->put(route('admin.themes.entries.update', [$theme, $entry]), [
            'title' => 'New Title',
            'body' => 'Updated body.',
            'scripture_references' => ['Genesis 1:1'],
        ]);

    $response->assertRedirectToRoute('admin.themes.entries.index', $theme);

    $entry->refresh();

    expect($entry->title)->toBe('New Title')
        ->and($entry->body)->toBe('Updated body.')
        ->and($entry->scriptureReferences)->toHaveCount(1)
        ->and($entry->scriptureReferences->first()->book)->toBe('Genesis');
});

it('validates required fields on update', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    $entry = DevotionalEntry::factory()->for($theme)->create();

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.entries.edit', [$theme, $entry])
        ->put(route('admin.themes.entries.update', [$theme, $entry]), [
            'scripture_references' => ['John 3:16'],
        ]);

    $response->assertRedirectToRoute('admin.themes.entries.edit', [$theme, $entry])
        ->assertSessionHasErrors(['title', 'body']);
});

it('denies non-admin from updating an entry', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();
    $entry = DevotionalEntry::factory()->for($theme)->create();

    $response = $this->actingAs($user)
        ->put(route('admin.themes.entries.update', [$theme, $entry]), [
            'title' => 'Hacked',
            'body' => 'Hacked body.',
            'scripture_references' => ['John 3:16'],
        ]);

    $response->assertForbidden();
});

// Destroy

it('deletes a devotional entry', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    $entry = DevotionalEntry::factory()->for($theme)->create();

    $response = $this->actingAs($admin)
        ->delete(route('admin.themes.entries.destroy', [$theme, $entry]));

    $response->assertRedirectToRoute('admin.themes.entries.index', $theme);

    expect(DevotionalEntry::query()->find($entry->id))->toBeNull();
});

it('cascades deletion to scripture references', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    $entry = DevotionalEntry::factory()->for($theme)->create();
    ScriptureReference::factory()->for($entry)->count(2)->create();

    $this->actingAs($admin)
        ->delete(route('admin.themes.entries.destroy', [$theme, $entry]));

    expect(ScriptureReference::query()->where('devotional_entry_id', $entry->id)->count())->toBe(0);
});

it('denies non-admin from deleting an entry', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();
    $entry = DevotionalEntry::factory()->for($theme)->create();

    $response = $this->actingAs($user)
        ->delete(route('admin.themes.entries.destroy', [$theme, $entry]));

    $response->assertForbidden();
});

// Publish

it('publishes a draft entry', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    $entry = DevotionalEntry::factory()->draft()->for($theme)->create();

    $response = $this->actingAs($admin)
        ->put(route('admin.themes.entries.publish', [$theme, $entry]));

    $response->assertRedirectToRoute('admin.themes.entries.index', $theme);

    $entry->refresh();

    expect($entry->status)->toBe(ContentStatus::Published);
});

it('denies non-admin from publishing an entry', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();
    $entry = DevotionalEntry::factory()->draft()->for($theme)->create();

    $response = $this->actingAs($user)
        ->put(route('admin.themes.entries.publish', [$theme, $entry]));

    $response->assertForbidden();
});

// Reorder

it('reorders entries within a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    $entry1 = DevotionalEntry::factory()->for($theme)->create(['display_order' => 0]);
    $entry2 = DevotionalEntry::factory()->for($theme)->create(['display_order' => 1]);
    $entry3 = DevotionalEntry::factory()->for($theme)->create(['display_order' => 2]);

    $response = $this->actingAs($admin)
        ->put(route('admin.themes.entries.reorder', $theme), [
            'ordered_ids' => [$entry3->id, $entry1->id, $entry2->id],
        ]);

    $response->assertRedirectToRoute('admin.themes.entries.index', $theme);

    expect($entry3->refresh()->display_order)->toBe(0)
        ->and($entry1->refresh()->display_order)->toBe(1)
        ->and($entry2->refresh()->display_order)->toBe(2);
});

it('validates ordered_ids on reorder', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.entries.index', $theme)
        ->put(route('admin.themes.entries.reorder', $theme), [
            'ordered_ids' => [],
        ]);

    $response->assertRedirectToRoute('admin.themes.entries.index', $theme)
        ->assertSessionHasErrors('ordered_ids');
});

it('denies non-admin from reordering entries', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();

    $response = $this->actingAs($user)
        ->put(route('admin.themes.entries.reorder', $theme), [
            'ordered_ids' => [1, 2, 3],
        ]);

    $response->assertForbidden();
});
