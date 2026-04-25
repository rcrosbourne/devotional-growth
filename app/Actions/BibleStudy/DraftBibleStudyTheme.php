<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Ai\Agents\BibleStudyThemeDrafter;
use App\Enums\AiGenerationStatus;
use App\Enums\BibleStudyThemeStatus;
use App\Models\AiGenerationLog;
use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

final readonly class DraftBibleStudyTheme
{
    public function __construct(private BibleStudyThemeDrafter $agent) {}

    public function handle(User $admin, string $themeTitle): AiGenerationLog
    {
        $log = AiGenerationLog::query()->create([
            'admin_id' => $admin->id,
            'prompt' => 'Draft Bible study theme: '.$themeTitle,
            'status' => AiGenerationStatus::Pending,
        ]);

        try {
            /** @var StructuredAgentResponse $response */
            $response = $this->agent->prompt($themeTitle);

            /**
             * @var array{
             *   slug: string,
             *   short_description: string,
             *   long_intro: string,
             *   passages: array<int, array{
             *     book: string,
             *     chapter: int,
             *     verse_start: int,
             *     verse_end?: int,
             *     position: int,
             *     is_guided_path: bool,
             *     passage_intro: string,
             *     insights: array{
             *       interpretation: string,
             *       application: string,
             *       cross_references: array<int, array<string, mixed>>,
             *       literary_context: string,
             *     },
             *     historical_context: array{
             *       setting: string,
             *       author: string,
             *       date_range: string,
             *       audience: string,
             *       historical_events: string,
             *     },
             *     suggested_word_highlights: array<int, array<string, mixed>>,
             *   }>
             * } $content
             */
            $content = $response->toArray();

            DB::transaction(function () use ($content, $themeTitle): void {
                $slug = $this->uniqueSlug($content['slug']);

                $theme = BibleStudyTheme::query()->create([
                    'slug' => $slug,
                    'title' => ucfirst($themeTitle),
                    'short_description' => $content['short_description'],
                    'long_intro' => $content['long_intro'],
                    'status' => BibleStudyThemeStatus::Draft,
                    'requested_count' => 0,
                ]);

                foreach ($content['passages'] as $p) {
                    $passage = BibleStudyThemePassage::query()->create([
                        'bible_study_theme_id' => $theme->id,
                        'position' => $p['position'],
                        'is_guided_path' => $p['is_guided_path'],
                        'book' => $p['book'],
                        'chapter' => $p['chapter'],
                        'verse_start' => $p['verse_start'],
                        'verse_end' => $p['verse_end'] ?? null,
                        'passage_intro' => $p['passage_intro'],
                    ]);

                    BibleStudyInsight::query()->create([
                        'bible_study_theme_passage_id' => $passage->id,
                        'interpretation' => $p['insights']['interpretation'],
                        'application' => $p['insights']['application'],
                        'cross_references' => $p['insights']['cross_references'],
                        'literary_context' => $p['insights']['literary_context'],
                    ]);

                    BibleStudyHistoricalContext::query()->create([
                        'bible_study_theme_passage_id' => $passage->id,
                        'setting' => $p['historical_context']['setting'],
                        'author' => $p['historical_context']['author'],
                        'date_range' => $p['historical_context']['date_range'],
                        'audience' => $p['historical_context']['audience'],
                        'historical_events' => $p['historical_context']['historical_events'],
                    ]);
                }
            });

            $log->update([
                'status' => AiGenerationStatus::Completed,
                'generated_content' => $content,
            ]);
        } catch (Throwable $throwable) {
            $log->update([
                'status' => AiGenerationStatus::Failed,
                'error_message' => $throwable->getMessage(),
            ]);
        }

        return $log->refresh();
    }

    private function uniqueSlug(string $proposed): string
    {
        $base = Str::slug($proposed);
        $slug = $base;
        $i = 1;

        while (BibleStudyTheme::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$i;
        }

        return $slug;
    }
}
