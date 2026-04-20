<?php

declare(strict_types=1);

use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyThemePassage;

it('stores structured historical fields', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    $context = BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create([
        'author' => 'Matthew',
        'date_range' => 'ca. 70–90 AD',
    ]);

    expect($context->author)->toBe('Matthew')
        ->and($context->date_range)->toBe('ca. 70–90 AD')
        ->and($passage->historicalContext->is($context))->toBeTrue();
});

it('is unique per passage', function (): void {
    $passage = BibleStudyThemePassage::factory()->create();
    BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create();

    expect(fn () => BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create())
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
