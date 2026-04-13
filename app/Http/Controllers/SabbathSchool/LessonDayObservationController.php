<?php

declare(strict_types=1);

namespace App\Http\Controllers\SabbathSchool;

use App\Actions\SabbathSchool\CreateLessonDayObservation;
use App\Actions\SabbathSchool\DeleteLessonDayObservation;
use App\Actions\SabbathSchool\UpdateLessonDayObservation;
use App\Http\Requests\CreateLessonDayObservationRequest;
use App\Http\Requests\UpdateObservationRequest;
use App\Models\LessonDay;
use App\Models\LessonDayObservation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class LessonDayObservationController
{
    public function store(
        CreateLessonDayObservationRequest $request,
        LessonDay $lessonDay,
        #[CurrentUser] User $user,
        CreateLessonDayObservation $action,
    ): RedirectResponse {
        $action->handle($user, $lessonDay, $request->string('body')->value());

        return back();
    }

    public function update(
        UpdateObservationRequest $request,
        LessonDayObservation $lessonDayObservation,
        #[CurrentUser] User $user,
        UpdateLessonDayObservation $action,
    ): RedirectResponse {
        abort_unless($lessonDayObservation->user_id === $user->id, 403);

        $action->handle($lessonDayObservation, $request->string('body')->value());

        return back();
    }

    public function destroy(
        LessonDayObservation $lessonDayObservation,
        #[CurrentUser] User $user,
        DeleteLessonDayObservation $action,
    ): RedirectResponse {
        abort_unless($lessonDayObservation->user_id === $user->id, 403);

        $action->handle($lessonDayObservation);

        return back();
    }
}
