<?php

declare(strict_types=1);

use App\Actions\BibleStudy\SearchThemes;
use App\Models\BibleStudyTheme;

it('returns approved themes with case-insensitive title match', function (): void {
    $resilience = BibleStudyTheme::factory()->approved()->create([
        'slug' => 'resilience', 'title' => 'Resilience',
    ]);
    BibleStudyTheme::factory()->approved()->create(['slug' => 'wisdom', 'title' => 'Wisdom']);

    $results = resolve(SearchThemes::class)->handle('resilience');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($resilience->id);
});

it('matches by slug exactly', function (): void {
    $forgiveness = BibleStudyTheme::factory()->approved()->create([
        'slug' => 'forgiveness', 'title' => 'Forgiveness',
    ]);

    $results = resolve(SearchThemes::class)->handle('FORGIVENESS');

    expect($results->first()->id)->toBe($forgiveness->id);
});

it('excludes draft themes', function (): void {
    BibleStudyTheme::factory()->draft()->create(['slug' => 'patience', 'title' => 'Patience']);

    expect(resolve(SearchThemes::class)->handle('patience'))->toBeEmpty();
});

it('returns an empty collection on a non-match', function (): void {
    BibleStudyTheme::factory()->approved()->create(['slug' => 'wisdom']);

    expect(resolve(SearchThemes::class)->handle('xyzz'))->toBeEmpty();
});

it('returns an empty collection for an empty query', function (): void {
    BibleStudyTheme::factory()->approved()->create(['slug' => 'wisdom']);

    expect(resolve(SearchThemes::class)->handle(''))->toBeEmpty();
});

it('returns an empty collection for a whitespace-only query', function (): void {
    BibleStudyTheme::factory()->approved()->create(['slug' => 'wisdom']);

    expect(resolve(SearchThemes::class)->handle('   '))->toBeEmpty();
});
