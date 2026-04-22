<?php

declare(strict_types=1);

use App\Ai\Agents\BibleStudyThemeDrafter;
use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyWordHighlight;
use App\Models\User;
use App\Models\WordStudy;

it('walks from draft trigger to publish end-to-end', function (): void {
    $admin = User::factory()->admin()->create();

    BibleStudyThemeDrafter::fake([
        [
            'slug' => 'resilience',
            'short_description' => 'Faith under pressure.',
            'long_intro' => "Resilience is faith that clings through loss.\n\nAcross scripture, God meets the afflicted in their waiting.",
            'passages' => [[
                'book' => 'Job',
                'chapter' => 1,
                'verse_start' => 13,
                'verse_end' => 22,
                'position' => 1,
                'is_guided_path' => true,
                'passage_intro' => 'Job responds to loss with lament and worship.',
                'insights' => [
                    'interpretation' => 'Job does not charge God with wrongdoing.',
                    'application' => 'Lament and worship can coexist.',
                    'cross_references' => [
                        ['book' => 'Lamentations', 'chapter' => 3, 'verse_start' => 19, 'verse_end' => 24, 'note' => 'Grief holds hope.'],
                    ],
                    'literary_context' => 'Prologue to the book of Job.',
                ],
                'historical_context' => [
                    'setting' => 'Land of Uz.',
                    'author' => 'Unknown',
                    'date_range' => 'Pre-exilic',
                    'audience' => 'Israelite wisdom audience.',
                    'historical_events' => 'Job loses family and possessions.',
                ],
                'suggested_word_highlights' => [
                    ['verse_number' => 20, 'display_word' => 'worship', 'original_root_hint' => 'שָׁחָה', 'rationale' => 'prostration before God.'],
                ],
            ]],
        ],
    ]);

    // 1. Admin triggers a draft via the store endpoint
    $this->actingAs($admin)
        ->post(route('admin.bible-study.themes.store'), ['title' => 'Resilience'])
        ->assertRedirect();

    $theme = BibleStudyTheme::query()->where('slug', 'resilience')->firstOrFail();
    $passage = $theme->passages()->firstOrFail();

    // 2. Admin edits theme meta
    $this->actingAs($admin)->put(route('admin.bible-study.themes.update', $theme), [
        'title' => 'Resilience',
        'short_description' => 'Faith under pressure.',
        'long_intro' => 'Long intro here.',
    ])->assertRedirect();

    // 3. Admin confirms a word highlight against an existing word study
    $wordStudy = WordStudy::factory()->create();
    $this->actingAs($admin)->post(route('admin.bible-study.passages.word-highlights.store', $passage), [
        'word_study_id' => $wordStudy->id,
        'verse_number' => 20,
        'word_index_in_verse' => 3,
        'display_word' => 'worship',
    ])->assertRedirect();

    // 4. Admin publishes
    $this->actingAs($admin)->put(route('admin.bible-study.themes.publish', $theme))->assertRedirect();

    $theme->refresh();
    expect($theme->status)->toBe(BibleStudyThemeStatus::Approved)
        ->and($theme->approved_by_user_id)->toBe($admin->id)
        ->and($theme->approved_at)->not->toBeNull()
        ->and(BibleStudyWordHighlight::query()->count())->toBe(1);
});
