<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ThemeController
{
    public function show(BibleStudyTheme $theme): Response
    {
        $theme->load([
            'passages.insight',
            'passages.historicalContext',
            'passages.wordHighlights.wordStudy',
        ]);

        return Inertia::render('admin/bible-study/themes/show', [
            'theme' => [
                'id' => $theme->id,
                'slug' => $theme->slug,
                'title' => $theme->title,
                'short_description' => $theme->short_description,
                'long_intro' => $theme->long_intro,
                'status' => $theme->status->value,
                'requested_count' => $theme->requested_count,
                'approved_at' => $theme->approved_at,
                'passages' => $theme->passages->map(fn (BibleStudyThemePassage $p): array => [
                    'id' => $p->id,
                    'position' => $p->position,
                    'is_guided_path' => $p->is_guided_path,
                    'book' => $p->book,
                    'chapter' => $p->chapter,
                    'verse_start' => $p->verse_start,
                    'verse_end' => $p->verse_end,
                    'passage_intro' => $p->passage_intro,
                    'insight' => $p->insight === null ? null : [
                        'id' => $p->insight->id,
                        'interpretation' => $p->insight->interpretation,
                        'application' => $p->insight->application,
                        'cross_references' => $p->insight->cross_references,
                        'literary_context' => $p->insight->literary_context,
                    ],
                    'historical_context' => $p->historicalContext === null ? null : [
                        'id' => $p->historicalContext->id,
                        'setting' => $p->historicalContext->setting,
                        'author' => $p->historicalContext->author,
                        'date_range' => $p->historicalContext->date_range,
                        'audience' => $p->historicalContext->audience,
                        'historical_events' => $p->historicalContext->historical_events,
                    ],
                    'word_highlights' => $p->wordHighlights->map(fn (BibleStudyWordHighlight $wh): array => [
                        'id' => $wh->id,
                        'verse_number' => $wh->verse_number,
                        'word_index_in_verse' => $wh->word_index_in_verse,
                        'display_word' => $wh->display_word,
                        'word_study' => $wh->wordStudy === null ? null : [
                            'id' => $wh->wordStudy->id,
                            'original_word' => $wh->wordStudy->original_word,
                            'transliteration' => $wh->wordStudy->transliteration,
                            'language' => $wh->wordStudy->language,
                            'definition' => $wh->wordStudy->definition,
                            'strongs_number' => $wh->wordStudy->strongs_number,
                        ],
                    ])->all(),
                ])->all(),
            ],
        ]);
    }

    public function index(): Response
    {
        $themes = BibleStudyTheme::query()
            ->orderByDesc('requested_count')->oldest()
            ->get();

        return Inertia::render('admin/bible-study/themes/index', [
            'themes' => $themes->map(fn (BibleStudyTheme $theme): array => [
                'id' => $theme->id,
                'slug' => $theme->slug,
                'title' => $theme->title,
                'short_description' => $theme->short_description,
                'status' => $theme->status->value,
                'requested_count' => $theme->requested_count,
                'created_at' => $theme->created_at,
                'approved_at' => $theme->approved_at,
            ]),
            'statuses' => collect(BibleStudyThemeStatus::cases())->map(fn (BibleStudyThemeStatus $s): string => $s->value)->all(),
        ]);
    }
}
