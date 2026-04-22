<?php

declare(strict_types=1);

use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\User;

it('creates the insight when missing', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create();

    $response = $this->actingAs($admin)->put(
        route('admin.bible-study.passages.insight.update', $passage),
        [
            'interpretation' => 'I',
            'application' => 'A',
            'cross_references' => [['book' => 'Romans', 'chapter' => 8, 'verse_start' => 18, 'note' => 'x']],
            'literary_context' => 'L',
        ]
    );

    $response->assertRedirect();

    expect(BibleStudyInsight::query()->where('bible_study_theme_passage_id', $passage->id)->exists())->toBeTrue();
});

it('updates an existing insight', function (): void {
    $admin = User::factory()->admin()->create();
    $passage = BibleStudyThemePassage::factory()->create();
    BibleStudyInsight::factory()->for($passage, 'passage')->create(['interpretation' => 'old']);

    $this->actingAs($admin)->put(
        route('admin.bible-study.passages.insight.update', $passage),
        [
            'interpretation' => 'new',
            'application' => 'a',
            'cross_references' => [],
            'literary_context' => 'l',
        ]
    )->assertRedirect();

    expect($passage->insight->fresh()->interpretation)->toBe('new');
});
