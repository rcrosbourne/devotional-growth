<?php

declare(strict_types=1);

use App\Models\BibleStudyInsight;
use App\Models\BibleStudyThemePassage;

it('stores cross_references as an array of objects', function (): void {
    $insight = BibleStudyInsight::factory()->create([
        'cross_references' => [
            ['book' => 'Romans', 'chapter' => 8, 'verse_start' => 18, 'verse_end' => 30, 'note' => 'Endurance'],
        ],
    ]);

    expect($insight->cross_references)->toBeArray()
        ->and($insight->cross_references[0]['book'])->toBe('Romans');
});

it('belongs to a passage (one-to-one)', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    $insight = BibleStudyInsight::factory()->for($passage, 'passage')->create();

    expect($insight->passage->is($passage))->toBeTrue()
        ->and($passage->insight->is($insight))->toBeTrue();
});

it('cannot have two insights for the same passage', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    BibleStudyInsight::factory()->for($passage, 'passage')->create();

    expect(fn () => BibleStudyInsight::factory()->for($passage, 'passage')->create())
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
