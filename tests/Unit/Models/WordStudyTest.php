<?php

declare(strict_types=1);

use App\Models\WordStudy;
use App\Models\WordStudyPassage;

test('word study to array', function (): void {
    $study = WordStudy::factory()->create()->refresh();

    expect(array_keys($study->toArray()))
        ->toBe([
            'id',
            'original_word',
            'transliteration',
            'language',
            'definition',
            'strongs_number',
            'created_at',
            'updated_at',
        ]);
});

test('word study has many passages', function (): void {
    $study = WordStudy::factory()->create();
    WordStudyPassage::factory()->for($study, 'wordStudy')->count(3)->create();

    expect($study->passages)->toHaveCount(3);
});

test('word study factory greek state sets language to greek', function (): void {
    $study = WordStudy::factory()->greek()->create();

    expect($study->language)->toBe('greek');
});

test('word study factory hebrew state sets language and strongs prefix', function (): void {
    $study = WordStudy::factory()->hebrew()->create();

    expect($study->language)->toBe('hebrew')
        ->and($study->strongs_number)->toStartWith('H');
});

test('word study passage to array', function (): void {
    $passage = WordStudyPassage::factory()->create()->refresh();

    expect(array_keys($passage->toArray()))
        ->toBe([
            'id',
            'word_study_id',
            'book',
            'chapter',
            'verse',
            'english_word',
            'created_at',
            'updated_at',
        ]);
});

test('word study passage belongs to word study', function (): void {
    $study = WordStudy::factory()->create();
    $passage = WordStudyPassage::factory()->for($study, 'wordStudy')->create();

    expect($passage->wordStudy)
        ->toBeInstanceOf(WordStudy::class)
        ->id->toBe($study->id);
});
