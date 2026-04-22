<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ThemeController
{
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
