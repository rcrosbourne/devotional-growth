<?php

declare(strict_types=1);

use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Models\Lesson;
use App\Models\Theme;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

beforeEach(function (): void {
    Storage::fake('public');
    Image::fake();
});

it('rejects unknown types', function (): void {
    $this->artisan('images:regenerate', ['--type' => 'invalid'])
        ->expectsOutputToContain('Invalid --type "invalid"')
        ->assertFailed();
});

it('reports counts without regenerating on dry-run', function (): void {
    $theme = Theme::factory()->create(['image_path' => 'images/themes/t.png']);
    $entry = DevotionalEntry::factory()->for($theme)->create();
    GeneratedImage::factory()->for($entry)->create();
    Lesson::factory()->withImage()->create();

    $this->artisan('images:regenerate', ['--type' => 'all', '--dry-run' => true])
        ->expectsOutputToContain('Devotionals with images: 1')
        ->expectsOutputToContain('Themes with images: 1')
        ->expectsOutputToContain('Lessons with images: 1')
        ->expectsOutputToContain('Would regenerate 3 image(s).')
        ->assertSuccessful();

    Image::assertNothingGenerated();
});

it('regenerates devotional images when type is devotionals', function (): void {
    $theme = Theme::factory()->create();
    $entry = DevotionalEntry::factory()->for($theme)->create();
    GeneratedImage::factory()->for($entry)->create(['path' => 'images/devotionals/old.png']);
    Storage::disk('public')->put('images/devotionals/old.png', 'old');

    $this->artisan('images:regenerate', ['--type' => 'devotionals'])
        ->expectsOutputToContain('Processed 1 image(s).')
        ->assertSuccessful();

    Image::assertGenerated(fn (): bool => true);
    Storage::disk('public')->assertMissing('images/devotionals/old.png');
});

it('regenerates theme images when type is themes', function (): void {
    Theme::factory()->create(['image_path' => 'images/themes/old.png']);
    Storage::disk('public')->put('images/themes/old.png', 'old');

    $this->artisan('images:regenerate', ['--type' => 'themes'])
        ->expectsOutputToContain('Themes with images: 1')
        ->assertSuccessful();

    Image::assertGenerated(fn (): bool => true);
});

it('regenerates lesson images when type is lessons', function (): void {
    Lesson::factory()->withImage()->create();

    $this->artisan('images:regenerate', ['--type' => 'lessons'])
        ->expectsOutputToContain('Lessons with images: 1')
        ->assertSuccessful();

    Image::assertGenerated(fn (): bool => true);
});

it('honors the --limit flag for devotionals', function (): void {
    $theme = Theme::factory()->create();
    DevotionalEntry::factory()
        ->for($theme)
        ->count(3)
        ->has(GeneratedImage::factory(), 'generatedImage')
        ->create();

    $this->artisan('images:regenerate', ['--type' => 'devotionals', '--limit' => 2])
        ->expectsOutputToContain('Devotionals with images: 2')
        ->assertSuccessful();
});

it('honors the --limit flag for themes', function (): void {
    Theme::factory()->count(3)->create(['image_path' => 'images/themes/x.png']);

    $this->artisan('images:regenerate', ['--type' => 'themes', '--limit' => 1])
        ->expectsOutputToContain('Themes with images: 1')
        ->assertSuccessful();
});

it('honors the --limit flag for lessons', function (): void {
    Lesson::factory()->count(3)->withImage()->create();

    $this->artisan('images:regenerate', ['--type' => 'lessons', '--limit' => 2])
        ->expectsOutputToContain('Lessons with images: 2')
        ->assertSuccessful();
});

it('continues after a per-theme failure', function (): void {
    Theme::factory()->create(['image_path' => 'images/themes/x.png']);

    Image::fake(fn () => throw new RuntimeException('Theme boom'));

    $this->artisan('images:regenerate', ['--type' => 'themes'])
        ->expectsOutputToContain('failed: Theme boom')
        ->assertSuccessful();
});

it('continues after a per-record failure', function (): void {
    $theme = Theme::factory()->create();
    $entry = DevotionalEntry::factory()->for($theme)->create();
    GeneratedImage::factory()->for($entry)->create();

    Image::fake(fn () => throw new RuntimeException('API down'));

    $this->artisan('images:regenerate', ['--type' => 'devotionals'])
        ->expectsOutputToContain('failed: API down')
        ->assertSuccessful();
});
