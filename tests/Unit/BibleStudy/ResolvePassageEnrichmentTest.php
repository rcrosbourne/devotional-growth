<?php

declare(strict_types=1);

use App\Actions\BibleStudy\ResolvePassageEnrichment;
use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\WordStudy;

it('returns the matching passage when it lives in an approved theme', function (): void {
    $theme = BibleStudyTheme::factory()->approved()->create(['slug' => 'resilience']);
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22,
    ]);
    BibleStudyInsight::factory()->for($passage, 'passage')->create();
    BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create();
    $word = WordStudy::factory()->create();
    BibleStudyWordHighlight::factory()->for($passage, 'passage')->for($word, 'wordStudy')->create();

    $resolved = resolve(ResolvePassageEnrichment::class)->handle('Job', 1, 13, 22);

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($passage->id)
        ->and($resolved->insight)->not->toBeNull()
        ->and($resolved->historicalContext)->not->toBeNull()
        ->and($resolved->wordHighlights)->toHaveCount(1);
});

it('returns null when the only matching passage belongs to a draft theme', function (): void {
    $theme = BibleStudyTheme::factory()->draft()->create();
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22,
    ]);

    $resolved = resolve(ResolvePassageEnrichment::class)->handle('Job', 1, 13, 22);

    expect($resolved)->toBeNull();
});

it('treats NULL verse_end as a distinct match value', function (): void {
    $theme = BibleStudyTheme::factory()->approved()->create();
    $singleVerse = BibleStudyThemePassage::factory()->for($theme, 'theme')->create([
        'book' => 'John', 'chapter' => 3, 'verse_start' => 16, 'verse_end' => null,
    ]);

    expect(resolve(ResolvePassageEnrichment::class)->handle('John', 3, 16, null)?->id)->toBe($singleVerse->id)
        ->and(resolve(ResolvePassageEnrichment::class)->handle('John', 3, 16, 17))->toBeNull();
});

it('returns null when no theme passage matches', function (): void {
    BibleStudyTheme::factory()->approved()->create();

    $resolved = resolve(ResolvePassageEnrichment::class)->handle('Genesis', 1, 1, 5);

    expect($resolved)->toBeNull();
});
