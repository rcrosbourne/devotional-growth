<?php

declare(strict_types=1);

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;

it('casts status to the enum', function (): void {
    $theme = BibleStudyTheme::factory()->create(['status' => BibleStudyThemeStatus::Draft]);

    expect($theme->status)->toBe(BibleStudyThemeStatus::Draft);
});

it('scopes approved themes', function (): void {
    BibleStudyTheme::factory()->draft()->create();
    BibleStudyTheme::factory()->approved()->create();

    expect(BibleStudyTheme::query()->where('status', BibleStudyThemeStatus::Approved)->count())->toBe(1);
});

it('has a unique slug', function (): void {
    BibleStudyTheme::factory()->create(['slug' => 'wisdom']);

    expect(fn () => BibleStudyTheme::factory()->create(['slug' => 'wisdom']))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
