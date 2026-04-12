<?php

declare(strict_types=1);

use App\Actions\GenerateDevotionalImage;
use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Models\ScriptureReference;
use App\Models\Theme;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

beforeEach(function (): void {
    Storage::fake('public');
    Image::fake();
});

it('generates an image and creates a generated image record', function (): void {
    $entry = DevotionalEntry::factory()
        ->for(Theme::factory())
        ->has(ScriptureReference::factory()->count(2))
        ->create(['title' => 'Walking in Faith', 'body' => 'Trust in the Lord with all your heart.']);

    $action = resolve(GenerateDevotionalImage::class);

    $result = $action->handle($entry);

    expect($result)->toBeInstanceOf(GeneratedImage::class)
        ->and($result->devotional_entry_id)->toBe($entry->id)
        ->and($result->path)->toStartWith('images/devotionals/')
        ->and($result->prompt)->toContain('Walking in Faith')
        ->and($result->prompt)->toContain('Trust in the Lord with all your heart.');
});

it('constructs a prompt containing the entry title and body', function (): void {
    $entry = DevotionalEntry::factory()
        ->for(Theme::factory())
        ->create(['title' => 'Grace and Mercy', 'body' => 'For by grace you have been saved.']);

    $action = resolve(GenerateDevotionalImage::class);

    $result = $action->handle($entry);

    expect($result->prompt)->toContain('Grace and Mercy')
        ->and($result->prompt)->toContain('For by grace you have been saved.');
});

it('includes scripture references in the prompt', function (): void {
    $entry = DevotionalEntry::factory()
        ->for(Theme::factory())
        ->create();
    ScriptureReference::factory()->for($entry)->create(['raw_reference' => 'John 3:16']);
    ScriptureReference::factory()->for($entry)->create(['raw_reference' => 'Romans 8:28']);

    $action = resolve(GenerateDevotionalImage::class);

    $result = $action->handle($entry);

    expect($result->prompt)->toContain('John 3:16')
        ->and($result->prompt)->toContain('Romans 8:28');
});

it('stores the image file on the public disk', function (): void {
    $entry = DevotionalEntry::factory()
        ->for(Theme::factory())
        ->create();

    $action = resolve(GenerateDevotionalImage::class);

    $result = $action->handle($entry);

    Storage::disk('public')->assertExists($result->path);
});

it('returns existing image when replace is false and image exists', function (): void {
    $entry = DevotionalEntry::factory()
        ->for(Theme::factory())
        ->create();
    $existingImage = GeneratedImage::factory()->for($entry)->create();

    $action = resolve(GenerateDevotionalImage::class);

    $result = $action->handle($entry, replace: false);

    expect($result->id)->toBe($existingImage->id);
    expect(GeneratedImage::query()->count())->toBe(1);
});

it('replaces existing image when replace flag is true', function (): void {
    $entry = DevotionalEntry::factory()
        ->for(Theme::factory())
        ->create();

    $existingImage = GeneratedImage::factory()->for($entry)->create([
        'path' => 'images/devotionals/old-image.png',
    ]);
    Storage::disk('public')->put('images/devotionals/old-image.png', 'old content');

    $action = resolve(GenerateDevotionalImage::class);

    $result = $action->handle($entry, replace: true);

    expect($result->id)->not->toBe($existingImage->id)
        ->and(GeneratedImage::query()->count())->toBe(1);
    Storage::disk('public')->assertMissing('images/devotionals/old-image.png');
});

it('calls the image ai provider', function (): void {
    $entry = DevotionalEntry::factory()
        ->for(Theme::factory())
        ->create(['title' => 'Forgiveness', 'body' => 'Forgive as the Lord forgave you.']);

    $action = resolve(GenerateDevotionalImage::class);

    $action->handle($entry);

    Image::assertGenerated(fn ($prompt): bool => str_contains((string) $prompt->prompt, 'Forgiveness'));
});

it('truncates long body text in the prompt', function (): void {
    $longBody = str_repeat('A word of faith. ', 100);
    $entry = DevotionalEntry::factory()
        ->for(Theme::factory())
        ->create(['body' => $longBody]);

    $action = resolve(GenerateDevotionalImage::class);

    $result = $action->handle($entry);

    expect(mb_strlen($result->prompt))->toBeLessThan(mb_strlen($longBody));
});

it('strips extended attributes from generated images on macOS', function (): void {
    Process::fake();

    $entry = DevotionalEntry::factory()
        ->for(Theme::factory())
        ->create();

    $action = resolve(GenerateDevotionalImage::class);

    $result = $action->handle($entry);

    Process::assertRan(fn ($process): bool => $process->command[0] === 'xattr'
        && $process->command[1] === '-c'
        && str_contains((string) $process->command[2], $result->path));
});

it('persists the generated image in the database', function (): void {
    $entry = DevotionalEntry::factory()
        ->for(Theme::factory())
        ->create();

    $action = resolve(GenerateDevotionalImage::class);

    $action->handle($entry);

    expect(GeneratedImage::query()->count())->toBe(1)
        ->and(GeneratedImage::query()->first()->devotional_entry_id)->toBe($entry->id);
});
