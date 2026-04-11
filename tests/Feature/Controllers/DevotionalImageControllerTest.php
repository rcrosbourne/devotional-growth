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
        ->postJson(route('entries.generate-image', $entry));

    $response->assertOk()
        ->assertJsonStructure([
            'image' => ['id', 'path', 'url'],
        ]);

    expect(GeneratedImage::query()->count())->toBe(1);
});

it('returns the image path and url in the response', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $response = $this->actingAs($user)
        ->postJson(route('entries.generate-image', $entry));

    $data = $response->json('image');

    expect($data['path'])->toStartWith('images/devotionals/')
        ->and($data['url'])->toContain('storage/images/devotionals/');
});

it('replaces an existing image when replace flag is true', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();
    GeneratedImage::factory()->for($entry)->create([
        'path' => 'images/devotionals/old.png',
    ]);
    Storage::disk('public')->put('images/devotionals/old.png', 'old');

    $response = $this->actingAs($user)
        ->postJson(route('entries.generate-image', $entry), ['replace' => true]);

    $response->assertOk();

    expect(GeneratedImage::query()->count())->toBe(1);
    Storage::disk('public')->assertMissing('images/devotionals/old.png');
});

it('returns existing image without regenerating when replace is false', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();
    $existing = GeneratedImage::factory()->for($entry)->create();

    $response = $this->actingAs($user)
        ->postJson(route('entries.generate-image', $entry), ['replace' => false]);

    $response->assertOk();

    expect($response->json('image.id'))->toBe($existing->id);
});

it('returns 503 when image generation fails', function (): void {
    Image::fake(fn () => throw new RuntimeException('Provider error'));

    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $response = $this->actingAs($user)
        ->postJson(route('entries.generate-image', $entry));

    $response->assertStatus(503)
        ->assertJson([
            'message' => 'Image generation is currently unavailable. Please try again later.',
        ]);
});

// Authentication

it('redirects unauthenticated users to login', function (): void {
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $response = $this->postJson(route('entries.generate-image', $entry));

    $response->assertUnauthorized();
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
        ->postJson(route('entries.generate-image', 99999));

    $response->assertNotFound();
});
