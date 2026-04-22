<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Http\Requests\Admin\BibleStudy\UpdateInsightRequest;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyThemePassage;
use Illuminate\Http\RedirectResponse;

final readonly class InsightController
{
    public function update(UpdateInsightRequest $request, BibleStudyThemePassage $passage): RedirectResponse
    {
        BibleStudyInsight::query()->updateOrCreate(
            ['bible_study_theme_passage_id' => $passage->id],
            $request->validated(),
        );

        return back()->with('status', 'Insight saved.');
    }
}
