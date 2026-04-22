<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Http\Requests\Admin\BibleStudy\StoreWordHighlightRequest;
use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use Illuminate\Http\RedirectResponse;

final readonly class WordHighlightController
{
    public function store(StoreWordHighlightRequest $request, BibleStudyThemePassage $passage): RedirectResponse
    {
        $passage->wordHighlights()->create($request->validated());

        return back()->with('status', 'Highlight added.');
    }

    public function destroy(BibleStudyThemePassage $passage, BibleStudyWordHighlight $highlight): RedirectResponse
    {
        abort_unless($highlight->bible_study_theme_passage_id === $passage->id, 404);
        $highlight->delete();

        return back()->with('status', 'Highlight removed.');
    }
}
