<?php

declare(strict_types=1);

use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\User;
use App\Models\WordStudy;

it('returns the full review payload for a draft theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();
    $passage = BibleStudyThemePassage::factory()->for($theme, 'theme')->create();
    BibleStudyInsight::factory()->for($passage, 'passage')->create();
    BibleStudyHistoricalContext::factory()->for($passage, 'passage')->create();
    $wordStudy = WordStudy::factory()->create();
    BibleStudyWordHighlight::factory()->for($passage, 'passage')->for($wordStudy, 'wordStudy')->create();

    $response = $this->actingAs($admin)->get(route('admin.bible-study.themes.show', $theme));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('admin/bible-study/themes/show')
        ->where('theme.id', $theme->id)
        ->has('theme.passages.0.insight')
        ->has('theme.passages.0.historical_context')
        ->has('theme.passages.0.word_highlights.0')
    );
});

it('denies non-admin access', function (): void {
    $theme = BibleStudyTheme::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.bible-study.themes.show', $theme));

    $response->assertForbidden();
});
