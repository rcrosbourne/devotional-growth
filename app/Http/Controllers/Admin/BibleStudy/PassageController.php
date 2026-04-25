<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\BibleStudy;

use App\Http\Requests\Admin\BibleStudy\StorePassageRequest;
use App\Http\Requests\Admin\BibleStudy\UpdatePassageRequest;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class PassageController
{
    public function store(StorePassageRequest $request, BibleStudyTheme $theme): RedirectResponse
    {
        $theme->passages()->create($request->validated());

        return back()->with('status', 'Passage added.');
    }

    public function update(UpdatePassageRequest $request, BibleStudyTheme $theme, BibleStudyThemePassage $passage): RedirectResponse
    {
        abort_unless($passage->bible_study_theme_id === $theme->id, 404);
        $passage->update($request->validated());

        return back()->with('status', 'Passage updated.');
    }

    public function destroy(BibleStudyTheme $theme, BibleStudyThemePassage $passage): RedirectResponse
    {
        abort_unless($passage->bible_study_theme_id === $theme->id, 404);
        $passage->delete();

        return back()->with('status', 'Passage deleted.');
    }

    public function reorder(Request $request, BibleStudyTheme $theme): RedirectResponse
    {
        /** @var array{ids: array<int, int>} $validated */
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = $validated['ids'];

        DB::transaction(function () use ($theme, $ids): void {
            foreach ($ids as $index => $id) {
                $theme->passages()->where('id', $id)->update(['position' => $index + 1]);
            }
        });

        return back()->with('status', 'Passages reordered.');
    }
}
