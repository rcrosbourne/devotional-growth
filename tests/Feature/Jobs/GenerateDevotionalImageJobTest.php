<?php

declare(strict_types=1);

use App\Actions\GenerateDevotionalImage;
use App\Jobs\GenerateDevotionalImageJob;
use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Models\Theme;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

beforeEach(function (): void {
    Storage::fake('public');
    Image::fake();
});

it('generates an image for the devotional entry', function (): void {
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    new GenerateDevotionalImageJob($entry)->handle(resolve(GenerateDevotionalImage::class));

    expect(GeneratedImage::query()->count())->toBe(1);
    expect($entry->refresh()->generatedImage)->not->toBeNull();
});

it('replaces an existing image when replace is true', function (): void {
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();
    GeneratedImage::factory()->for($entry)->create([
        'path' => 'images/devotionals/old.png',
    ]);
    Storage::disk('public')->put('images/devotionals/old.png', 'old');

    new GenerateDevotionalImageJob($entry, replace: true)->handle(resolve(GenerateDevotionalImage::class));

    expect(GeneratedImage::query()->count())->toBe(1);
    Storage::disk('public')->assertMissing('images/devotionals/old.png');
});
