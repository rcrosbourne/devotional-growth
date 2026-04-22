<?php

declare(strict_types=1);

use App\Actions\BibleStudy\DraftBibleStudyTheme;
use App\Ai\Agents\BibleStudyThemeDrafter;
use App\Enums\AiGenerationStatus;
use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('persists a draft theme with passages, insights, and historical context', function (): void {
    BibleStudyThemeDrafter::fake([fixtureDraftResponse()]);

    $admin = User::factory()->admin()->create();

    $log = resolve(DraftBibleStudyTheme::class)->handle($admin, 'Resilience');

    expect($log->status)->toBe(AiGenerationStatus::Completed)
        ->and(BibleStudyTheme::query()->count())->toBe(1)
        ->and(BibleStudyTheme::query()->first()->status)->toBe(BibleStudyThemeStatus::Draft)
        ->and(BibleStudyInsight::query()->count())->toBe(1);
});

it('marks the log as failed on agent throw', function (): void {
    BibleStudyThemeDrafter::fake(function (): never {
        throw new RuntimeException('AI unavailable');
    });

    $admin = User::factory()->admin()->create();

    $log = resolve(DraftBibleStudyTheme::class)->handle($admin, 'Resilience');

    expect($log->status)->toBe(AiGenerationStatus::Failed)
        ->and($log->error_message)->toBe('AI unavailable')
        ->and(BibleStudyTheme::query()->count())->toBe(0);
});

it('appends a numeric suffix when the slug already exists', function (): void {
    BibleStudyThemeDrafter::fake([fixtureDraftResponse()]);

    $admin = User::factory()->admin()->create();

    BibleStudyTheme::factory()->create(['slug' => 'resilience']);

    $log = resolve(DraftBibleStudyTheme::class)->handle($admin, 'Resilience');

    expect($log->status)->toBe(AiGenerationStatus::Completed)
        ->and(BibleStudyTheme::query()->count())->toBe(2)
        ->and(BibleStudyTheme::query()->latest('id')->first()->slug)->toBe('resilience-2');
});

/**
 * @return array<string, mixed>
 */
function fixtureDraftResponse(): array
{
    return [
        'slug' => 'resilience',
        'short_description' => 'Faith under pressure.',
        'long_intro' => "Resilience in scripture is not stoicism. It is faith that clings through loss.\n\nAcross the canon, God meets afflicted people in their waiting.",
        'passages' => [[
            'book' => 'Job',
            'chapter' => 1,
            'verse_start' => 13,
            'verse_end' => 22,
            'position' => 1,
            'is_guided_path' => true,
            'passage_intro' => 'Job responds to catastrophic loss with lament and worship.',
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
    ];
}
