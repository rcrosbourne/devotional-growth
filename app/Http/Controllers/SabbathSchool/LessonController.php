<?php

declare(strict_types=1);

namespace App\Http\Controllers\SabbathSchool;

use App\Models\Lesson;
use App\Models\LessonDayCompletion;
use App\Models\Quarterly;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LessonController
{
    public function show(Quarterly $quarterly, Lesson $lesson, #[CurrentUser] User $user): Response
    {
        $lesson->load(['days' => function (HasMany $query): void {
            $query->orderBy('day_position');
        }]);

        $previousLesson = Lesson::query()
            ->where('quarterly_id', $quarterly->id)
            ->where('lesson_number', $lesson->lesson_number - 1)
            ->first();

        $nextLesson = Lesson::query()
            ->where('quarterly_id', $quarterly->id)
            ->where('lesson_number', $lesson->lesson_number + 1)
            ->first();

        $completedDayIds = LessonDayCompletion::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_day_id', $lesson->days->pluck('id'))
            ->pluck('lesson_day_id')
            ->all();

        return Inertia::render('sabbath-school/lesson', [
            'quarterly' => $quarterly,
            'lesson' => $lesson,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson,
            'completedDayIds' => $completedDayIds,
        ]);
    }
}
