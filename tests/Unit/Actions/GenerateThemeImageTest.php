<?php

declare(strict_types=1);

use App\Actions\GenerateThemeImage;
use App\Models\Theme;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

beforeEach(function (): void {
    Storage::fake('public');
    Image::fake();
});

it('generates an image and stores the path on the theme', function (): void {
    $theme = Theme::factory()->create(['name' => 'Grace', 'description' => 'A study on grace']);

    $action = resolve(GenerateThemeImage::class);
    $result = $action->handle($theme);

    expect($result->image_path)->toStartWith('images/themes/')
        ->and($result->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($result->image_path);
});

it('returns theme without generating when image exists and replace is false', function (): void {
    $theme = Theme::factory()->create(['image_path' => 'images/themes/existing.png']);
    Storage::disk('public')->put('images/themes/existing.png', 'content');

    $action = resolve(GenerateThemeImage::class);
    $result = $action->handle($theme, false);

    expect($result->image_path)->toBe('images/themes/existing.png');
    Image::assertNothingGenerated();
});

it('replaces existing image when replace flag is true', function (): void {
    $theme = Theme::factory()->create(['image_path' => 'images/themes/old.png']);
    Storage::disk('public')->put('images/themes/old.png', 'old content');

    $action = resolve(GenerateThemeImage::class);
    $result = $action->handle($theme, true);

    expect($result->image_path)->not->toBe('images/themes/old.png');
    Storage::disk('public')->assertMissing('images/themes/old.png');
    Storage::disk('public')->assertExists($result->image_path);
});

it('includes theme name in the prompt', function (): void {
    $theme = Theme::factory()->create(['name' => 'Walking in Wisdom']);

    $action = resolve(GenerateThemeImage::class);
    $action->handle($theme);

    Image::assertGenerated(fn ($prompt): bool => str_contains((string) $prompt->prompt, 'Walking in Wisdom'));
});

it('uses theme name in prompt when description is null', function (): void {
    $theme = Theme::factory()->create(['name' => 'Mercy', 'description' => null]);

    $action = resolve(GenerateThemeImage::class);
    $action->handle($theme);

    Image::assertGenerated(fn ($prompt): bool => str_contains((string) $prompt->prompt, 'Mercy'));
});

it('strips extended attributes from generated images', function (): void {
    Process::fake();

    $theme = Theme::factory()->create();

    $action = resolve(GenerateThemeImage::class);
    $result = $action->handle($theme);

    Process::assertRan(fn ($process): bool => $process->command[0] === 'xattr'
        && $process->command[1] === '-c'
        && str_contains((string) $process->command[2], (string) $result->image_path));
});
