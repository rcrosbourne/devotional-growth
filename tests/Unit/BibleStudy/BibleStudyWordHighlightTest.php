<?php

declare(strict_types=1);

use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\WordStudy;

it('links a passage to an existing word study', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    $wordStudy = WordStudy::factory()->create();

    $highlight = BibleStudyWordHighlight::factory()
        ->for($passage, 'passage')
        ->for($wordStudy, 'wordStudy')
        ->create(['verse_number' => 20, 'word_index_in_verse' => 3, 'display_word' => 'worship']);

    expect($highlight->passage->is($passage))->toBeTrue()
        ->and($highlight->wordStudy->is($wordStudy))->toBeTrue();
});

it('loads word highlights from the passage side in position order', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    $highlight = BibleStudyWordHighlight::factory()
        ->for($passage, 'passage')
        ->create(['verse_number' => 5, 'word_index_in_verse' => 2]);

    expect($passage->wordHighlights->first()->is($highlight))->toBeTrue();
});

it('is unique on passage + verse + word index', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    BibleStudyWordHighlight::factory()->for($passage, 'passage')->create([
        'verse_number' => 20, 'word_index_in_verse' => 3,
    ]);

    expect(fn () => BibleStudyWordHighlight::factory()->for($passage, 'passage')->create([
        'verse_number' => 20, 'word_index_in_verse' => 3,
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
