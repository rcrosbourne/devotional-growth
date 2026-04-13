<?php

declare(strict_types=1);

namespace App\Http\Controllers\SabbathSchool;

use App\Actions\SabbathSchool\CompleteLessonDay;
use App\Actions\SabbathSchool\UncompleteLessonDay;
use App\Models\LessonDay;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class LessonDayCompletionController
{
    public function store(LessonDay $lessonDay, #[CurrentUser] User $user, CompleteLessonDay $action): RedirectResponse
    {
        $action->handle($user, $lessonDay);

        return back();
    }

    public function destroy(LessonDay $lessonDay, #[CurrentUser] User $user, UncompleteLessonDay $action): RedirectResponse
    {
        $action->handle($user, $lessonDay);

        return back();
    }
}
