<?php

declare(strict_types=1);

use App\Actions\BibleStudy\DraftBibleStudyTheme;
use App\Ai\Agents\BibleStudyThemeDrafter;
use App\Enums\AiGenerationStatus;
use App\Jobs\DraftBibleStudyThemeJob;
use App\Models\AiGenerationLog;
use App\Models\BibleStudyTheme;
use App\Models\User;
use App\Notifications\BibleStudyDraftReady;
use Illuminate\Support\Facades\Notification;

it('runs the action and notifies the admin on success', function (): void {
    Notification::fake();
    BibleStudyThemeDrafter::fake([jobFixtureDraftResponse()]);

    $admin = User::factory()->admin()->create();
    $job = new DraftBibleStudyThemeJob($admin, 'Resilience');

    $job->handle(resolve(DraftBibleStudyTheme::class));

    expect(AiGenerationLog::query()->first()->status)->toBe(AiGenerationStatus::Completed)
        ->and(BibleStudyTheme::query()->count())->toBe(1);

    Notification::assertSentTo(
        $admin,
        BibleStudyDraftReady::class,
        fn (BibleStudyDraftReady $n): bool => $n->theme instanceof BibleStudyTheme && $n->themeTitle === 'Resilience',
    );
});

it('notifies the admin with a failed state when the agent throws', function (): void {
    Notification::fake();
    BibleStudyThemeDrafter::fake([new RuntimeException('AI unavailable')]);

    $admin = User::factory()->admin()->create();
    $job = new DraftBibleStudyThemeJob($admin, 'Resilience');

    $job->handle(resolve(DraftBibleStudyTheme::class));

    expect(AiGenerationLog::query()->first()->status)->toBe(AiGenerationStatus::Failed)
        ->and(BibleStudyTheme::query()->count())->toBe(0);

    Notification::assertSentTo(
        $admin,
        BibleStudyDraftReady::class,
        fn (BibleStudyDraftReady $n): bool => ! $n->theme instanceof BibleStudyTheme,
    );
});

/**
 * @return array<string, mixed>
 */
function jobFixtureDraftResponse(): array
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
