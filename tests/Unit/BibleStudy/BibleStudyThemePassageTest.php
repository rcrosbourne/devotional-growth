<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;

it('belongs to a theme', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create();

    expect($passage->theme->is($theme))->toBeTrue();
});

it('a theme has many passages ordered by position', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 2]);
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 1]);

    $positions = $theme->passages()->orderBy('position')->pluck('position')->all();

    expect($positions)->toBe([1, 2]);
});

it('enforces uniqueness of passage range within a theme', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22,
    ]);

    expect(fn () => BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22,
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
