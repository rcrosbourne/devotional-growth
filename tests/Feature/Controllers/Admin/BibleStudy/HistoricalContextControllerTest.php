<?php

declare(strict_types=1);

use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyThemePassage;
use App\Models\User;

it('upserts historical context for a passage', function (): void {
    $admin = User::factory()->admin()->create();
    $passage = BibleStudyThemePassage::factory()->create();

    $this->actingAs($admin)->put(
        route('admin.bible-study.passages.historical-context.update', $passage),
        [
            'setting' => 'Land of Uz',
            'author' => 'Unknown',
            'date_range' => 'Pre-exilic',
            'audience' => 'Israelite',
            'historical_events' => 'Loss of family.',
        ]
    )->assertRedirect();

    expect(BibleStudyHistoricalContext::query()->where('bible_study_theme_passage_id', $passage->id)->exists())->toBeTrue();
});

it('updates an existing historical context', function (): void {
    $admin = User::factory()->admin()->create();
    $passage = BibleStudyThemePassage::factory()->create();
    BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create(['author' => 'old']);

    $this->actingAs($admin)->put(
        route('admin.bible-study.passages.historical-context.update', $passage),
        [
            'setting' => 's',
            'author' => 'new',
            'date_range' => 'd',
            'audience' => 'a',
            'historical_events' => 'h',
        ]
    )->assertRedirect();

    expect($passage->historicalContext->fresh()->author)->toBe('new');
});
