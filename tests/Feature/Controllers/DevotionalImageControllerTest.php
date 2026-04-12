<?php

declare(strict_types=1);

use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

beforeEach(function (): void {
    Storage::fake('public');
    Image::fake();
});

// Store

it('generates an image for a devotional entry', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $response = $this->actingAs($user)
        ->post(route('entries.generate-image', $entry));

    $response->assertRedirect();

    expect(GeneratedImage::query()->count())->toBe(1);
});

it('stores the image in the correct storage path', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $this->actingAs($user)
        ->post(route('entries.generate-image', $entry));

    $image = GeneratedImage::query()->first();

    expect($image->path)->toStartWith('images/devotionals/');
});

it('replaces an existing image when replace flag is true', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();
    GeneratedImage::factory()->for($entry)->create([
        'path' => 'images/devotionals/old.png',
    ]);
    Storage::disk('public')->put('images/devotionals/old.png', 'old');

    $response = $this->actingAs($user)
        ->post(route('entries.generate-image', $entry), ['replace' => true]);

    $response->assertRedirect();

    expect(GeneratedImage::query()->count())->toBe(1);
    Storage::disk('public')->assertMissing('images/devotionals/old.png');
});

it('returns existing image without regenerating when replace is false', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();
    $existing = GeneratedImage::factory()->for($entry)->create();

    $response = $this->actingAs($user)
        ->post(route('entries.generate-image', $entry), ['replace' => false]);

    $response->assertRedirect();

    expect(GeneratedImage::query()->count())->toBe(1);
    expect($existing->refresh()->id)->toBe($existing->id);
});

it('redirects back with error when image generation fails', function (): void {
    Image::fake(fn () => throw new RuntimeException('Provider error'));

    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $response = $this->actingAs($user)
        ->post(route('entries.generate-image', $entry));

    $response->assertRedirect()
        ->assertSessionHas('error', 'Image generation is currently unavailable. Please try again later.');
});

// Authentication

it('redirects unauthenticated users to login', function (): void {
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $response = $this->post(route('entries.generate-image', $entry));

    $response->assertRedirectToRoute('login');
});

it('rejects unverified users', function (): void {
    $user = User::factory()->unverified()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $response = $this->actingAs($user)
        ->postJson(route('entries.generate-image', $entry));

    $response->assertForbidden();
});

it('returns 404 for a non-existent entry', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('entries.generate-image', 99999));

    $response->assertNotFound();
});
