<?php

declare(strict_types=1);

use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

// Index

it('renders the admin themes index page', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/themes/index'));
});

it('shows all themes regardless of status', function (): void {
    $admin = User::factory()->admin()->create();
    Theme::factory()->draft()->create(['created_by' => $admin->id]);
    Theme::factory()->published()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/themes/index')
            ->has('themes', 2)
        );
});

it('includes cover image path when generated image exists on disk', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    $entry = DevotionalEntry::factory()->for($theme)->create();
    $image = GeneratedImage::factory()->for($entry, 'devotionalEntry')->create();

    Storage::disk('public')->put($image->path, 'fake-image-content');

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/themes/index')
            ->where('themes.0.cover_image_path', $image->path)
        );
});

it('denies non-admin access to themes index', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('admin.themes.index'));

    $response->assertForbidden();
});

it('redirects unauthenticated users to login', function (): void {
    $response = $this->get(route('admin.themes.index'));

    $response->assertRedirectToRoute('login');
});

// Create

it('renders the create theme form', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/themes/create'));
});

// Store

it('creates a new theme', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.create')
        ->post(route('admin.themes.store'), [
            'name' => 'Faith',
            'description' => 'A theme about faith',
        ]);

    $response->assertRedirectToRoute('admin.themes.index');

    $theme = Theme::query()->where('name', 'Faith')->first();

    expect($theme)->not->toBeNull()
        ->and($theme->description)->toBe('A theme about faith')
        ->and($theme->status)->toBe(ContentStatus::Draft)
        ->and($theme->created_by)->toBe($admin->id);
});

it('creates a theme without description', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.create')
        ->post(route('admin.themes.store'), [
            'name' => 'Forgiveness',
        ]);

    $response->assertRedirectToRoute('admin.themes.index');

    $theme = Theme::query()->where('name', 'Forgiveness')->first();

    expect($theme)->not->toBeNull()
        ->and($theme->description)->toBeNull();
});

it('requires a name to create a theme', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.create')
        ->post(route('admin.themes.store'), [
            'description' => 'A description',
        ]);

    $response->assertRedirectToRoute('admin.themes.create')
        ->assertSessionHasErrors('name');
});

it('requires a unique name to create a theme', function (): void {
    $admin = User::factory()->admin()->create();
    Theme::factory()->create(['name' => 'Faith']);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.create')
        ->post(route('admin.themes.store'), [
            'name' => 'Faith',
        ]);

    $response->assertRedirectToRoute('admin.themes.create')
        ->assertSessionHasErrors('name');
});

it('denies non-admin from creating a theme', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('admin.themes.store'), [
            'name' => 'Faith',
        ]);

    $response->assertForbidden();
});

// Edit

it('renders the edit theme form', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.edit', $theme));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/themes/edit')
            ->has('theme')
        );
});

// Update

it('updates a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id, 'name' => 'Old Name']);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.edit', $theme)
        ->put(route('admin.themes.update', $theme), [
            'name' => 'New Name',
            'description' => 'Updated description',
        ]);

    $response->assertRedirectToRoute('admin.themes.index');

    $theme->refresh();

    expect($theme->name)->toBe('New Name')
        ->and($theme->description)->toBe('Updated description');
});

it('preserves entries when updating a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    DevotionalEntry::factory()->count(3)->create(['theme_id' => $theme->id]);

    $this->actingAs($admin)
        ->put(route('admin.themes.update', $theme), [
            'name' => 'Updated Name',
        ]);

    expect($theme->entries()->count())->toBe(3);
});

it('requires a unique name to update a theme', function (): void {
    $admin = User::factory()->admin()->create();
    Theme::factory()->create(['name' => 'Existing']);
    $theme = Theme::factory()->create(['created_by' => $admin->id, 'name' => 'Original']);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.themes.edit', $theme)
        ->put(route('admin.themes.update', $theme), [
            'name' => 'Existing',
        ]);

    $response->assertRedirectToRoute('admin.themes.edit', $theme)
        ->assertSessionHasErrors('name');
});

it('allows keeping the same name when updating a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id, 'name' => 'Faith']);

    $response = $this->actingAs($admin)
        ->put(route('admin.themes.update', $theme), [
            'name' => 'Faith',
            'description' => 'Updated',
        ]);

    $response->assertRedirectToRoute('admin.themes.index');
});

it('denies non-admin from updating a theme', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();

    $response = $this->actingAs($user)
        ->put(route('admin.themes.update', $theme), [
            'name' => 'Hacked',
        ]);

    $response->assertForbidden();
});

// Destroy

it('deletes a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->delete(route('admin.themes.destroy', $theme));

    $response->assertRedirectToRoute('admin.themes.index');

    expect(Theme::query()->find($theme->id))->toBeNull();
});

it('cascades deletion to entries', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    DevotionalEntry::factory()->count(2)->create(['theme_id' => $theme->id]);

    $this->actingAs($admin)
        ->delete(route('admin.themes.destroy', $theme));

    expect(DevotionalEntry::query()->where('theme_id', $theme->id)->count())->toBe(0);
});

it('denies non-admin from deleting a theme', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('admin.themes.destroy', $theme));

    $response->assertForbidden();
});

// Publish

it('publishes a draft theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->draft()->create(['created_by' => $admin->id]);
    DevotionalEntry::factory()->published()->for($theme)->create();

    $response = $this->actingAs($admin)
        ->put(route('admin.themes.publish', $theme));

    $response->assertRedirectToRoute('admin.themes.index');

    $theme->refresh();

    expect($theme->status)->toBe(ContentStatus::Published);
});

it('rejects publishing a theme without published entries', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->draft()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->put(route('admin.themes.publish', $theme));

    $response->assertSessionHasErrors('theme');

    expect($theme->refresh()->status)->toBe(ContentStatus::Draft);
});

it('denies non-admin from publishing a theme', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->draft()->create();

    $response = $this->actingAs($user)
        ->put(route('admin.themes.publish', $theme));

    $response->assertForbidden();
});

// Unpublish

it('unpublishes a published theme and its entries', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->published()->create(['created_by' => $admin->id]);
    $entries = DevotionalEntry::factory()->published()->for($theme)->count(2)->create();

    $response = $this->actingAs($admin)
        ->put(route('admin.themes.unpublish', $theme));

    $response->assertRedirectToRoute('admin.themes.index');

    expect($theme->refresh()->status)->toBe(ContentStatus::Draft);

    foreach ($entries as $entry) {
        expect($entry->refresh()->status)->toBe(ContentStatus::Draft);
    }
});

it('denies non-admin from unpublishing a theme', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->published()->create();

    $response = $this->actingAs($user)
        ->put(route('admin.themes.unpublish', $theme));

    $response->assertForbidden();
});

// Generate Image

it('generates an image for a theme', function (): void {
    Storage::fake('public');
    Laravel\Ai\Image::fake();

    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->post(route('admin.themes.generate-image', $theme));

    $response->assertRedirect();

    expect($theme->refresh()->image_path)->not->toBeNull();
});

it('prefers theme own image over entry image in cover', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create([
        'created_by' => $admin->id,
        'image_path' => 'images/themes/theme-cover.png',
    ]);
    $entry = DevotionalEntry::factory()->for($theme)->create();
    $image = GeneratedImage::factory()->for($entry, 'devotionalEntry')->create();

    Storage::disk('public')->put('images/themes/theme-cover.png', 'theme-image');
    Storage::disk('public')->put($image->path, 'entry-image');

    $response = $this->actingAs($admin)
        ->get(route('admin.themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('themes.0.cover_image_path', 'images/themes/theme-cover.png')
        );
});

it('handles image generation failure gracefully', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    // Simulate AI service failure by not configuring AI, and using a bad config
    config()->set('ai.default', 'nonexistent-provider');

    $response = $this->actingAs($admin)
        ->post(route('admin.themes.generate-image', $theme));

    $response->assertRedirect()
        ->assertSessionHas('error');
});

it('denies non-admin from generating theme image', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('admin.themes.generate-image', $theme));

    $response->assertForbidden();
});
