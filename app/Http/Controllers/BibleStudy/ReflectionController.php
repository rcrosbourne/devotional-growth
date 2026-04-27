<?php

declare(strict_types=1);

namespace App\Http\Controllers\BibleStudy;

use App\Actions\BibleStudy\SaveBibleStudyReflection;
use App\Http\Requests\BibleStudy\StoreReflectionRequest;
use App\Http\Requests\BibleStudy\UpdateReflectionRequest;
use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class ReflectionController
{
    public function store(StoreReflectionRequest $request, #[CurrentUser] User $user, SaveBibleStudyReflection $action): RedirectResponse
    {
        $themeId = (int) $request->integer('theme_id');
        $theme = $themeId > 0 ? BibleStudyTheme::query()->find($themeId) : null;

        $action->handle(
            user: $user,
            theme: $theme,
            book: $request->string('book')->value(),
            chapter: (int) $request->integer('chapter'),
            verseStart: (int) $request->integer('verse_start'),
            verseEnd: $request->filled('verse_end') ? (int) $request->integer('verse_end') : null,
            verseNumber: $request->filled('verse_number') ? (int) $request->integer('verse_number') : null,
            body: $request->string('body')->value(),
            shareWithPartner: $request->boolean('is_shared_with_partner'),
        );

        return back()->with('status', 'Reflection saved.');
    }

    public function update(UpdateReflectionRequest $request, #[CurrentUser] User $user, BibleStudyReflection $reflection): RedirectResponse
    {
        abort_unless($reflection->user_id === $user->id, 403);

        $reflection->update($request->validated());

        return back()->with('status', 'Reflection updated.');
    }

    public function destroy(#[CurrentUser] User $user, BibleStudyReflection $reflection): RedirectResponse
    {
        abort_unless($reflection->user_id === $user->id, 403);

        $reflection->delete();

        return back()->with('status', 'Reflection deleted.');
    }
}
