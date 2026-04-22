<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Http\Requests\Admin\BibleStudy\UpdateHistoricalContextRequest;
use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyThemePassage;
use Illuminate\Http\RedirectResponse;

final readonly class HistoricalContextController
{
    public function update(UpdateHistoricalContextRequest $request, BibleStudyThemePassage $passage): RedirectResponse
    {
        BibleStudyHistoricalContext::query()->updateOrCreate(
            ['bible_study_theme_passage_id' => $passage->id],
            $request->validated(),
        );

        return back()->with('status', 'Historical context saved.');
    }
}
