<?php

declare(strict_types=1);

namespace App\Http\Controllers\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use Illuminate\Database\Eloquent\Relations\Relation;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ThemeController
{
    public function index(): Response
    {
        $themes = BibleStudyTheme::query()
            ->where('status', BibleStudyThemeStatus::Approved)
            ->withCount('passages')
            ->orderBy('title')
            ->get();

        return Inertia::render('bible-study/themes/index', [
            'themes' => $themes->map(fn (BibleStudyTheme $t): array => [
                'id' => $t->id,
                'slug' => $t->slug,
                'title' => $t->title,
                'short_description' => $t->short_description,
                'passage_count' => $t->passages_count,
            ]),
        ]);
    }

    public function show(string $slug): Response
    {
        $theme = BibleStudyTheme::query()
            ->where('slug', $slug)
            ->where('status', BibleStudyThemeStatus::Approved)
            ->with(['passages' => fn (Relation $q) => $q->orderBy('position')])
            ->firstOrFail();

        return Inertia::render('bible-study/themes/show', [
            'theme' => [
                'id' => $theme->id,
                'slug' => $theme->slug,
                'title' => $theme->title,
                'short_description' => $theme->short_description,
                'long_intro' => $theme->long_intro,
                'passages' => $theme->passages->map(fn (BibleStudyThemePassage $p): array => [
                    'id' => $p->id,
                    'position' => $p->position,
                    'is_guided_path' => $p->is_guided_path,
                    'book' => $p->book,
                    'chapter' => $p->chapter,
                    'verse_start' => $p->verse_start,
                    'verse_end' => $p->verse_end,
                    'passage_intro' => $p->passage_intro,
                ])->all(),
            ],
        ]);
    }
}
