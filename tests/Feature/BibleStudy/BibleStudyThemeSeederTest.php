<?php

declare(strict_types=1);

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use Database\Seeders\BibleStudyThemeSeeder;

it('seeds a single approved resilience theme with passages', function (): void {
    (new BibleStudyThemeSeeder)->run();

    $theme = BibleStudyTheme::query()->where('slug', 'resilience')->first();

    expect($theme)->not->toBeNull()
        ->and($theme->status)->toBe(BibleStudyThemeStatus::Approved)
        ->and($theme->passages()->count())->toBeGreaterThanOrEqual(1)
        ->and($theme->passages()->first()->insight)->not->toBeNull()
        ->and($theme->passages()->first()->historicalContext)->not->toBeNull();
});

it('is idempotent — running twice does not duplicate', function (): void {
    (new BibleStudyThemeSeeder)->run();
    (new BibleStudyThemeSeeder)->run();

    expect(BibleStudyTheme::query()->where('slug', 'resilience')->count())->toBe(1);
});
